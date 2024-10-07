<?php

namespace Modules\SalesOrder\Console;

use Illuminate\Console\Command;
use App\Traits\DistributorStock;
use Spatie\Activitylog\Contracts\Activity;
use Modules\Personel\Entities\MarketingFee;
use Modules\Invoice\Events\FeeMarketingEvent;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\Invoice\Events\FeeTargetMarketingEvent;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrderV2\Events\FeeMarketingIndirectSaleEvent;
use Modules\SalesOrderV2\Events\FeeTargetMarketingEvent as FeeTargetMarketingIndirectSaleEvent;

class FeeRegulerSharingOriginGeneratorCommand extends Command
{
    use FeeMarketingTrait;
    use DistributorStock;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fee:fee_sharing_origin_generator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create fee sharing origin generator if sales order have no fee sharing generated';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogWorkerSalesFee $log_worker_sales_fee,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        SalesOrderOrigin $sales_order_origin,
        MarketingFee $marketing_fee,
        SalesOrderV2 $sales_order,
    ) {
        parent::__construct();
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->log_worker_sales_fee = $log_worker_sales_fee;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->sales_order_detail = $sales_order_detail;
        $this->sales_order_origin = $sales_order_origin;
        $this->marketing_fee = $marketing_fee;
        $this->sales_order = $sales_order;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * generate fee sharing currenmt quarter
         */
        $quarter_start = now()->startOfQuarter()->startOfDay();
        $quarter_end = now()->endOfQuarter()->endOfDay();

        $sales_orders = $this->sales_order->query()
            ->with([
                "invoice" => function ($QQQ) {
                    return $QQQ->with([
                        "salesOrder" => function ($QQQ) {
                            return $QQQ->with([
                                "invoice",
                            ]);
                        },
                    ]);
                },
                "dealer",
                "subDealer",
                "statusFee",
                "salesOrderDetail",
                "sales_order_detail",
                "lastReceivingGoodIndirect",
            ])
            ->where("status", ["confirmed", "pending"])
            ->whereHas("salesOrderDetail")
            ->where(function ($QQQ) use ($quarter_start, $quarter_end) {
                return $QQQ
                    ->where(function ($QQQ) use ($quarter_start, $quarter_end) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($quarter_start, $quarter_end) {
                                return $QQQ
                                    ->where("created_at", ">=", $quarter_start)
                                    ->where("created_at", "<=", $quarter_end);
                            });
                    })
                    ->orWhere(function ($QQQ) use ($quarter_start, $quarter_end) {
                        return $QQQ
                            ->where("type", "2")
                            ->where("created_at", ">=", $quarter_start)
                            ->where("created_at", "<=", $quarter_end);
                    });
            })
            ->get();

        try {

            $nomor = 1;
            $sales_orders->each(function ($sales_order) use(&$nomor){

                dump($nomor.". ".$sales_order->id.": ".$sales_order->type);
                $nomor++;

                /**
                 * recalculate marketing fee per product
                 */
                $this->feeMarketingPerProductCalculator($sales_order->first());

                /*
                |----------------------------------
                | FEE MARKETING COUNTER
                |---------------------------
                 */

                if ($sales_order->type == "2") {

                    /**
                     * fee reguler marketing
                     */
                    $fee_reguler_marketing = FeeMarketingIndirectSaleEvent::dispatch($sales_order);

                    /* fee target marketing event */
                    $fee_target_marketing = FeeTargetMarketingIndirectSaleEvent::dispatch($sales_order);
                } else {

                    /*
                    |-------------------------------------------------
                    | Fee Marketing
                    |-----------------------------------------
                     */

                    /* fee marketing */
                    $fee_marketing = FeeMarketingEvent::dispatch($sales_order->invoice);

                    /* fee target marketing */
                    $fee_target_marketing = FeeTargetMarketingEvent::dispatch($sales_order->invoice);
                }
            });

            /**
             * get fee sharing current quarter
             * to get all marketing which
             * affected by the change in
             * contract
             */
            $sales_orders_current_quarter = $sales_orders
                ->map(function ($sales_order) {
                    $sales_order->year_order = confirmation_time($sales_order)->format("Y");
                    $sales_order->quarter_order = confirmation_time($sales_order)->quarter;
                    return $sales_order;
                })
                ->filter(function ($sales_order) use ($quarter_end, $quarter_start) {
                    return $sales_order->year_order == $quarter_start->format("Y") && $sales_order->quarter_order == $quarter_end->quarter;
                });

            /**
             * recalculate all marketing fee in current quarter
             * which affected by the change in contract
             */
            $fee_sharings = $this->fee_sharing_origin->query()
                ->whereIn("sales_order_id", $sales_orders_current_quarter->pluck("id")->toArray())
                ->get()
                ->pluck("personel_id")
                ->reject(fn($personel_id) => !$personel_id)
                ->unique()
                ->each(function ($personel_id) use ($quarter_start, $quarter_end) {
                    $marketing_fee_total = $this->feeMarketingRegulerTotal($personel_id, $quarter_start->format("Y"), $quarter_end->quarter);
                    $marketing_fee_active = $this->feeMarketingRegulerActive($personel_id, $quarter_start->format("Y"), $quarter_end->quarter);
                    $marketing_fee_target_total = $this->feeMarketingTargetTotal($personel_id, $quarter_start->format("Y"), $quarter_end->quarter);
                    $marketing_fee_target_active = $this->feeMarketingTargetActive($personel_id, $quarter_start->format("Y"), $quarter_end->quarter);

                    for ($i = 1; $i < 5; $i++) {
                        $this->marketing_fee->firstOrCreate([
                            "personel_id" => $personel_id,
                            "year" => $quarter_start->format("Y"),
                            "quarter" => $quarter_end->quarter,
                        ], [
                            "fee_reguler_total" => 0,
                            "fee_reguler_settle" => 0,
                            "fee_target_total" => 0,
                            "fee_target_settle" => 0,
                        ]);
                    }

                    $marketing_fee = $this->marketing_fee->query()
                        ->where("personel_id", $personel_id)
                        ->where("year", $quarter_start->format("Y"))
                        ->where("quarter", $quarter_end->quarter)
                        ->first();

                    $old_fee = [
                        "personel_id" => $personel_id,
                        "year" => $quarter_start->format("Y"),
                        "quarter" => $quarter_end->quarter,
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

        } catch (\Throwable$th) {
            dump($th);
        }
    }
}
