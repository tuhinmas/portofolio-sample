<?php

namespace Modules\Invoice\Listeners;

use Carbon\Carbon;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Modules\Personel\Entities\MarketingFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\Invoice\Events\PaymentOnSettleEvent;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\Personel\Entities\LogMarketingFeeCounter;

class UpdateMarketingFeeBaseFeeSharingListener
{
    use FeeMarketingTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        // FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        MarketingFee $marketing_fee,
        SalesOrder $sales_order,
        Personel $personel,
    ) {
        $this->log_marketing_fee_counter = $log_marketing_fee_counter;
        // $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->sales_order_detail = $sales_order_detail;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->marketing_fee = $marketing_fee;
        $this->sales_order = $sales_order;
        $this->personel = $personel;

    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PaymentOnSettleEvent $event)
    {

        if ($event->invoice->salesOrder->personel_id) {
            $personel = $this->personel->findOrFail($event->invoice->salesOrder->personel_id);

            /* marketing_fee_list */
            $marketing_fee_list = collect();

            $fee_sharing_origins = $this->fee_sharing_origin->query()
                ->where("sales_order_id", $event->invoice->sales_order_id)
                ->get()
                ->map(function ($origin) {
                    return $origin->personel_id;
                })
                ->reject(fn($Personel_id) => !$Personel_id)
                ->unique()
                ->each(function ($personel_id) use ($event, &$marketing_fee_list) {

                    /* fee reguler */
                    $marketing_fee_active = $this->feeMarketingRegulerActive($personel_id, $event->invoice->created_at->format("Y"), $event->invoice->created_at->quarter, $event->invoice->salesOrder);

                    for ($i = 1; $i < 5; $i++) {
                        $this->marketing_fee->firstOrCreate([
                            "personel_id" => $personel_id,
                            "year" => $event->invoice->created_at->format("Y"),
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
                        ->where("year", $event->invoice->created_at->format("Y"))
                        ->where("quarter", $event->invoice->created_at->quarter)
                        ->first();

                    $old_fee = [
                        "personel_id" => $personel_id,
                        "year" => $event->invoice->created_at->format("Y"),
                        "quarter" => $event->invoice->created_at->quarter,
                        "fee_reguler_total" => $marketing_fee->fee_reguler_total,
                        "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
                    ];

                    $marketing_fee->fee_reguler_settle += $marketing_fee_active;
                    $marketing_fee->save();

                    $marketing_fee_list->push([
                        "personel_id" => $personel_id,
                        "year" => $event->invoice->created_at->format("Y"),
                        "quarter" => $event->invoice->created_at->quarter,
                        "fee_reguler_total" => $marketing_fee->fee_reguler_total,
                        "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
                    ]);

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

            return $marketing_fee_list;
        }

        return "ok";

        /**
         * PENDING
         */
        $maximum_settle_days = maximum_settle_days(now()->format("Y"));

        /**
         * marketing fee reguler, update fee reguler settle
         * if setle under 60 days
         */
        if ($event->invoice->created_at->diffInDays($event->payment->payment_date, false) <= $maximum_settle_days) {

            /**
             * get fee sharing data
             */
            $fee_sharing_origin = $this->fee_sharing_origin
                ->whereNotNull("personel_id")
                ->where("is_checked", true)
                ->where("sales_order_id", $event->invoice->sales_order_id)
                ->whereDoesntHave("logMarketingFeeCounter", function ($QQQ) {
                    return $QQQ->where("is_settle", "1");
                })
                ->get();

            if ($fee_sharing_origin->count() > 0) {

                $fee_sharing_origin = $fee_sharing_origin
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
                                    "personel_id" => $fee->personel_id,
                                    "year" => Carbon::parse($fee->confirmed_at)->format("Y"),
                                    "quarter" => $i,
                                ], [
                                    "fee_reguler_total" => 0,
                                    "fee_reguler_settle" => 0,
                                ]);
                            }

                            $fee_per_marketing = $this->marketing_fee->query()
                                ->where("personel_id", $fee->personel_id)
                                ->where("year", Carbon::parse($fee->confirmed_at)->format("Y"))
                                ->where("quarter", Carbon::parse($fee->confirmed_at)->quarter)
                                ->first();

                            $fee_per_marketing->fee_reguler_settle = $fee_per_marketing->fee_reguler_settle + $fee->fee_shared;
                            $fee_per_marketing->save();
                        });

                    });

                $this->log_marketing_fee_counter
                    ->where("sales_order_id", $event->invoice->sales_order_id)
                    ->update([
                        "is_settle" => "1",
                    ]);

                return "fee settle counted";
            }
        }

        return "out of payment days, fee not counted";

        /* update log marketing fee counter */

        /**
         * fee target update on settle
         * payment
         */
        // $year = $event->invoice->created_at->format("Y");
        // $quarter = $event->invoice->created_at->quarter;
        // $log_marketing_fee = LogMarketingFeeCounter::query()
        //     ->where("sales_order_id", $event->invoice->sales_order_id)
        //     ->where("type", "target")
        //     ->delete();

        // return $log_marketing_fee;
    }
}
