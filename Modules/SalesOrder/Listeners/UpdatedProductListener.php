<?php

namespace Modules\SalesOrder\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\MarketingFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\SalesOrderV2\Entities\FeeSharing;
use Modules\SalesOrder\Events\UpdatedProductEvent;
use Modules\SalesOrderV2\Entities\FeeTargetSharing;

class UpdatedProductListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(UpdatedProductEvent $event)
    {

        /**
         * marketing fee reduction
         */
        $fee_reguler_total_reduction = collect();

        /**
         * marketing fee reguler, update fee reguler settle
         * if setle under 60 days
         */
        SalesOrder::findOrFail($event->sales_order_detail->sales_order_id);
        $settle_under_sixty_days = false;
        if ($event->sales_order_detail->sales_order->status == "confirmed" && $event->sales_order_detail->sales_order->invoice) {

            /* if invoice was settle */
            if ($event->sales_order_detail->sales_order->invoice->payment_status == "settle") {

                /* payment date setup default */
                $payment_date = now();
                if ($event->sales_order_detail->sales_order->invoice->last_payment != "-") {
                    $payment_date = $event->sales_order_detail->sales_order->invoice->last_payment;
                }

                /* payment date check */
                $payment_date_maximum = PaymentMethod::orderBy("days", "desc")->first();
                
                if ($event->sales_order_detail->sales_order->invoice->created_at->diffInDays($payment_date) <= ($payment_date_maximum ? $payment_date_maximum->days : 60)) {
                    $settle_under_sixty_days = true;
                }
            }
        } else if ($event->sales_order_detail->sales_order->status == "confirmed" && $event->sales_order_detail->sales_order->type == "2") {
            $settle_under_sixty_days = true;
        }

        $fee_sharing = DB::table('fee_sharings')
            ->where("sales_order_id", $event->sales_order_detail->sales_order_id)
            ->get();

        if ($fee_sharing->count() > 0) {
            $fee_purchser_on_follow_up = $fee_sharing->where("handover_status", "1")->first();

            $fee_sharing = $fee_sharing
                ->map(function ($fee) use ($fee_purchser_on_follow_up) {
                    if ($fee_purchser_on_follow_up) {
                        if ($fee->fee_status == "purchaser") {
                            $fee->fee_shared = 0;
                        }

                    }
                    return $fee;
                })
                ->filter(function ($fee) {
                    return $fee->personel_id != null;
                })
                ->values();

            $fee_sharing_grouped = $fee_sharing->groupBy("personel_id");

            $marketing_fee_reduction = $fee_sharing_grouped->map(function ($fee, $personel_id) use ($settle_under_sixty_days) {
                $detail = [
                    "personel_id" => $personel_id,
                    "sales_order_id" => $fee[0]->sales_order_id,
                    "year" => Carbon::parse($fee[0]->created_at)->format("Y"),
                    "quarter" => Carbon::parse($fee[0]->created_at)->quarter,
                    "fee_reguler_settle" => $settle_under_sixty_days ? collect($fee)->sum("fee_shared") : null,
                    "fee_reguler_total" => collect($fee)->sum("fee_shared"),
                ];
                return $detail;
            });

            /**
             * marketing fee reduction
             */
            $marketing_fee_check = collect();
            $marketing_fee_reduction->each(function ($fee_reduction, $personel_id) use ($event, &$marketing_fee_check, $settle_under_sixty_days) {
                $year = $fee_reduction["year"];
                $quarter = $fee_reduction["quarter"];
                $marketing_fee = MarketingFee::query()
                    ->where("personel_id", $personel_id)
                    ->where("year", $year)
                    ->where("quarter", $quarter)
                    ->first();

                $current_fee_reguler_settle = 0;
                if ($marketing_fee) {
                    $current_fee_reguler_settle = $marketing_fee->fee_reguler_settle - $fee_reduction["fee_reguler_settle"];
                    $current_fee_reguler_total = $marketing_fee->fee_reguler_total - $fee_reduction["fee_reguler_total"];
                }

                if ($settle_under_sixty_days) {
                    $fee = MarketingFee::query()
                        ->where("personel_id", $personel_id)
                        ->where("year", $year)
                        ->where("quarter", $quarter)
                        ->update([
                            "fee_reguler_settle" => $current_fee_reguler_settle,
                            "fee_reguler_total" => $current_fee_reguler_total,
                        ]);
                } else {
                    $fee = MarketingFee::query()
                        ->where("personel_id", $personel_id)
                        ->where("year", $year)
                        ->where("quarter", $quarter)
                        ->update([
                            "fee_reguler_total" => $current_fee_reguler_total,
                        ]);
                }

                if ($fee > 0) {
                    $marketing_fee_check->push($personel_id);
                }
            });

            /**
             * recount fee sharing
             */
            FeeSharing::query()
                ->where("sales_order_id", $event->sales_order_detail->sales_order_id)
                ->update([
                    "is_checked" => 0,
                    "fee_shared" => 0,
                ]);

            /**
             * sales order detail update fee reguler
             * with delete log fee for
             * sales order detail
             */
            $log_worker_fee = DB::table('log_worker_sales_fees')
                ->where("sales_order_id", $event->sales_order_detail->sales_order_id)
                ->delete();
        }

        /**
         * update fee target sharing
         */
        $fee_target_sharing = FeeTargetSharing::query()
            ->where("sales_order_detail_id", $event->sales_order_detail->id)
            ->update([
                "quantity_unit" => $event->sales_order_detail->quantity,
            ]);

        /**
         * recount marketing fee, delete
         * marketing fee counter
         */
        $log_marketing_fee_counter = DB::table('log_marketing_fee_counter')
            ->where("sales_order_id", $event->sales_order_detail->sales_order_id)
            ->delete();

        return $event->sales_order_detail;
    }
}
