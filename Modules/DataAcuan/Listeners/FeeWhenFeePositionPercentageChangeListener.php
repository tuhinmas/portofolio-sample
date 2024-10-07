<?php

namespace Modules\DataAcuan\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\DataAcuan\Events\FeeWhenFeePositionPercentageChangeEvent;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\SalesOrderV2\Entities\FeeSharing;

class FeeWhenFeePositionPercentageChangeListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeWhenFeePositionPercentageChangeEvent $event)
    {

        /**
         * -------------------------------------------------------------
         * NO LONGER USE
         * -----------------------------
         */

        /**
         * reset fee reguler percentage and fee shared in fee sharing
         * if fee percentage was change or fee from
         * follow up changes
         */
        $fee_position = FeePosition::get();
        if ($event->last_fee_position != $event->fee_position->fee || $event->last_fee_follow_up != $event->fee_position->follow_up) {
            $fee_position->each(function ($fee) {

                /**
                 * is_checked to false no matter what kind type it si
                 */
                $is_checked_reset = DB::table('fee_sharings')
                    ->whereYear("created_at", Carbon::now())
                    ->update([
                        "is_checked" => 0,
                    ]);

                /**
                 * fee reguler reset
                 */
                $fee_sharing_reset = DB::table('fee_sharings')
                    ->whereYear("created_at", Carbon::now())
                    ->where("position_id", $fee->position_id)
                    ->where("handover_status", "!=", "1")
                    ->update([
                        "fee_percentage" => $fee->fee,
                        "fee_shared" => 0,
                        "fee_target_percentage" => $fee->fee,
                        "fee_target_shared" => 0,
                    ]);

                /**
                 * fee target update percentage according
                 * new fee percentage
                 */
                $fee_target_sharing_reset = DB::table('fee_target_sharings')
                    ->whereYear("created_at", Carbon::now())
                    ->where("position_id", $fee->position_id)
                    ->update([
                        "fee_percentage" => $fee->fee,
                        "fee_nominal" => 0,
                        "is_checked" => 0,
                    ]);

            });
        }

        /**
         * fee sharing for sales counter update if there
         * changes on it was found
         */

        if ($event->last_sales_counter_fee_percentage != $event->fee_position->fee_sc_on_order) {

            /**
             * is_checked to false no matter what kind type it is
             */
            $is_checked_reset = DB::table('fee_sharings')
                ->whereYear("created_at", Carbon::now())
                ->update([
                    "is_checked" => 0,
                ]);

            $fee_position->each(function ($fee) {
                $fee_sharing_reset = FeeSharing::query()
                    ->whereYear("created_at", Carbon::now())
                    ->where("fee_status", "sales counter")
                    ->update([
                        "fee_percentage" => $fee->fee_sc_on_order,
                        "fee_shared" => 0,
                        "fee_target_percentage" => $fee->fee_sc_on_order,
                        "fee_target_shared" => 0,
                    ]);
            });
        }

        /**
         * delete log marketing fee counter
         */
        $log_marketing_fee_counter = LogMarketingFeeCounter::query()
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) {
                                return $QQQ
                                    ->whereYear("created_at", Carbon::now());
                            });
                    })
                    ->orWhere(function ($QQQ) {
                        return $QQQ
                            ->where("type", "2")
                            ->whereYear("date", Carbon::now());
                    });
            })
            ->delete();

        /**
         * reset fee marketing this year to 0
         */
        $reset_marketing_fee = MarketingFee::query()
            ->where("year", Carbon::now()->format("Y"))
            ->update([
                "fee_reguler_total" => 0,
                "fee_reguler_settle" => 0,
                "fee_target_total" => 0,
                "fee_target_settle" => 0,
            ]);

        return $event->fee_position;
    }
}
