<?php

namespace Modules\Invoice\Listeners;

use App\Traits\ChildrenList;
use App\Traits\DistributorStock;
use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Invoice\Events\FeeMarketingEvent;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class MarketingFeeCounterListener
{
    use FeeMarketingTrait;
    use DistributorStock;
    use ResponseHandler;
    use ChildrenList;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        LogWorkerSalesFee $log_worker_sales_fee,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        MarketingFee $marketing_fee,
        FeePosition $fee_position,
        SalesOrder $sales_order,
        Personel $personel,
    ) {
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->log_marketing_fee_counter = $log_marketing_fee_counter;
        $this->log_worker_sales_fee = $log_worker_sales_fee;
        $this->sales_order_detail = $sales_order_detail;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->marketing_fee = $marketing_fee;
        $this->fee_position = $fee_position;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeMarketingEvent $event)
    {
        $year = $event->invoice->created_at->format("Y");
        $quarter = $event->invoice->created_at->quarter;

        /**
         * get fee sharing data
         */
        $fee_sharing_origin = $this->fee_sharing_origin
            ->whereNotNull("personel_id")
            ->where("is_checked", true)
            ->where("sales_order_id", $event->invoice->sales_order_id)
            ->whereDoesntHave("logMarketingFeeCounter", function ($QQQ) {
                return $QQQ->where("type", "reguler");
            })
            ->get()

        /* update marketing fee */
            ->groupBy("sales_order_id")
            ->each(function ($origin_per_order, $sales_order_id) use ($year, $quarter) {

                /* fee reguler */
                $marketing_fee_total = $this->feeMarketingRegulerTotal($origin_per_order[0]->personel_id, $year, $quarter, $event->invoice->salesOrder);
                $marketing_fee_active = $this->feeMarketingRegulerActive($origin_per_order[0]->personel_id, $year, $quarter, $event->invoice->salesOrder);

                for ($i = 1; $i < 5; $i++) {
                    $this->marketing_fee->firstOrCreate([
                        "personel_id" => $origin_per_order[0]->personel_id,
                        "year" => $year,
                        "quarter" => $i,
                    ], [
                        "fee_reguler_total" => 0,
                        "fee_reguler_settle" => 0,
                        "fee_target_total" => 0,
                        "fee_target_settle" => 0,
                    ]);
                }

                $marketing_fee = $this->marketing_fee->query()
                    ->where("personel_id", $origin_per_order[0]->personel_id)
                    ->where("year", $year)
                    ->where("quarter", $quarter)
                    ->first();

                $marketing_fee->fee_reguler_total += $marketing_fee_total;
                $marketing_fee->fee_reguler_settle += $marketing_fee_active;
                $marketing_fee->save();
            });

        return "fee done to distributed";

        /*
        |-==========================================-
        | PENDING
        |-================================-
         */

        if ($fee_sharing_origin->count() > 0) {
            $log = $this->log_marketing_fee_counter->updateOrCreate([
                "sales_order_id" => $event->invoice->sales_order_id,
                "type" => "reguler",
            ]);

            $fee_sharing_origin
                ->groupBy("sales_order_detail_id")
                ->each(function ($origin_per_detail, $sales_order_cetail_id) {

                    /**
                 * check sales order handover status
                 * to fixing fee value fee marketing
                 * as purchaser, prchaser = 0
                 * on handover to make it
                 * easy to calculate
                 */
                    $is_handover = collect($origin_per_detail)
                        ->filter(function ($fee_order) {
                            if ($fee_order->handover_status == "1") {
                                return $fee_order;
                            }
                        })
                        ->first();

                    $marketing_fee_reguler = $origin_per_detail;
                    if ($is_handover) {
                        $marketing_fee_reguler = $origin_per_detail->filter(function ($fee) use ($is_handover) {
                            return $fee->fee_status != "purchaser";
                        });
                    }

                    $marketing_fee_reguler->each(function ($fee) {
                        for ($i = 1; $i < 5; $i++) {
                            $this->marketing_fee->firstOrCreate([
                                "personel_id" => $fee["personel_id"],
                                "year" => Carbon::parse($fee["confirmed_at"])->format("Y"),
                                "quarter" => $i,
                            ], [
                                "fee_reguler_total" => 0,
                                "fee_reguler_settle" => 0,
                            ]);
                        }

                        $fee_per_marketing = $this->marketing_fee->query()
                            ->where("personel_id", $fee->personel_id)
                            ->where("year", Carbon::parse($fee->confirmed_at)->format("Y"))
                            ->where("quarter", $fee->quarter)
                            ->first();

                        $this->marketing_fee->updateOrCreate([
                            "personel_id" => $fee->personel_id,
                            "year" => Carbon::parse($fee->confirmed_at)->format("Y"),
                            "quarter" => Carbon::parse($fee["confirmed_at"])->quarter,
                        ], [
                            "fee_reguler_total" => $fee_per_marketing ? $fee_per_marketing->fee_reguler_total + $fee->fee_shared : $fee->fee_shared,
                        ]);
                    });
                });
        }

        return "fee done to distributed";
    }
}
