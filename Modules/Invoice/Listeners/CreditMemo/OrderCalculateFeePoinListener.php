<?php

namespace Modules\Invoice\Listeners\CreditMemo;

use App\Traits\ChildrenList;
use App\Traits\MarketingFeeTrait;
use Illuminate\Http\Request;
use Modules\Contest\Traits\ContestPointTrait;
use Modules\DataAcuan\Entities\PointProduct;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\Personel\Traits\PointMarketingTrait;
use Modules\PointMarketing\Entities\MarketingPointAdjustment;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\LogWorkerPointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketingActive;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrder\Entities\LogWorkerSalesPoint;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Spatie\Activitylog\Contracts\Activity;

class OrderCalculateFeePoinListener
{
    // use ContestPointCalculationTrait;
    use PointMarketingTrait;
    use FeeMarketingTrait;
    use MarketingFeeTrait;
    use ContestPointTrait;
    use ChildrenList;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        protected LogWorkerPointMarketingActive $log_worker_point_marketing_active,
        protected MarketingPointAdjustment $marketing_point_adjustment,
        protected LogWorkerPointMarketing $log_worker_point_marketing,
        protected FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        protected LogMarketingFeeCounter $log_marketing_fee_counter,
        protected LogWorkerSalesPoint $log_worker_sales_point,
        protected LogWorkerSalesFee $log_worker_sales_fee,
        protected FeeSharingSoOrigin $fee_sharing_origin,
        protected SalesOrderOrigin $sales_order_origin,
        protected SalesOrderDetail $sales_order_detail,
        protected PointMarketing $point_marketing,
        protected MarketingFee $marketing_fee,
        protected PointProduct $point_product,
        protected SalesOrder $sales_order,
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        /*
        |-----------------------------------------------------
        | FEE POINT PER PRODUCT
        |-------------------------------------
         */
        $year_of_return = confirmation_time($event->sales_orderv2)->format("Y");
        $quarter_of_return = confirmation_time($event->sales_orderv2)->quarter;

        $this->sales_order->query()
            ->with([
                "sales_order_detail",
            ])
            ->where("store_id", $event->sales_orderv2->store_id)
            ->quartalOrder($year_of_return, $quarter_of_return)
            ->get()
            ->each(function ($order) use ($event) {
                /* update marketing fee in sales order detail */
                $this->feeMarketingPerProductCalculator($order);

                /* update marketing point in sales order detail */
                $this->marketingPointPerProductCalculator($order);

                /* contest point origin update after return, all point will set to 0 */
                $this->contestPointOriginGenerator($event->active_contract_contest, $order, $order->sales_order_detail);

            });

        /*
        |-----------------------------------------------------
        | FEE MARKETING
        |-------------------------------------
         */
        $marketing_supervisor = $this->parentPersonel($event->sales_orderv2->personel_id);
        collect($marketing_supervisor)->each(function ($personel_id) use ($year_of_return, $quarter_of_return) {

            /* fee reguler */
            $marketing_fee_total = $this->feeMarketingRegulerTotal($personel_id, $year_of_return, $quarter_of_return);
            $marketing_fee_active = $this->feeMarketingRegulerActive($personel_id, $year_of_return, $quarter_of_return);

            /* fe target */
            $marketing_fee_target_total = $this->feeMarketingTargetTotal($personel_id, $year_of_return, $quarter_of_return);
            $marketing_fee_target_active = $this->feeMarketingTargetActive($personel_id, $year_of_return, $quarter_of_return);

            for ($i = 1; $i < 5; $i++) {
                $this->marketing_fee->firstOrCreate([
                    "personel_id" => $personel_id,
                    "year" => $year_of_return,
                    "quarter" => $i,
                ], [
                    "fee_reguler_total" => 0,
                    "fee_reguler_settle" => 0,
                    "fee_target_total" => 0,
                    "fee_target_settle" => 0,
                ]);
            }

            $marketing_fee = $this->marketing_fee->query()
                ->where("personel_id", $personel_id)
                ->where("year", $year_of_return)
                ->where("quarter", $quarter_of_return)
                ->first();

            $old_fee = [
                "personel_id" => $personel_id,
                "year" => $year_of_return,
                "quarter" => $quarter_of_return,
                "fee_reguler_total" => $marketing_fee->fee_reguler_total,
                "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
                "fee_target_total" => $marketing_fee->fee_target_total,
                "fee_target_settle" => $marketing_fee->fee_target_settle,
            ];

            $marketing_fee->fee_reguler_total = $marketing_fee_total;
            $marketing_fee->fee_reguler_settle = $marketing_fee_active;
            $marketing_fee->fee_target_total = $marketing_fee_target_total;
            $marketing_fee->fee_target_settle = $marketing_fee_target_active;
            $marketing_fee->save();

            $test = activity()
                ->causedBy(auth()->id())
                ->performedOn($marketing_fee)
                ->withProperties([
                    "old" => $old_fee,
                    "attributes" => $marketing_fee,
                ])
                ->tap(function (Activity $activity) {
                    $activity->log_name = 'sync';
                })
                ->log('marketing point syncronize');
        });

        /*
        |-----------------------------------------------------
        | POINT MARKETING
        |-------------------------------------
         */
        $point_total = $this->recalcultePointMarketingTotal($event->sales_orderv2->personel_id, confirmation_time($event->sales_orderv2)->format("Y"));

        /* calculate point marketing active */
        $point_marketing_active = $this->recalcultePointMarketingActive($event->sales_orderv2->personel_id, confirmation_time($event->sales_orderv2)->format("Y"));

        /*
        |-----------------------------------------------------
        | POINT CONTEST
        |-------------------------------------
         */
        $request = new Request;
        $request->merge([
            "update_contest_point_origin" => true,
        ]);
        $this->recalculateContestParticipantPoint($request, $event->active_contract_contest);

    }
}
