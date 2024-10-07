<?php

namespace Modules\SalesOrder\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\SalesOrderV2\Entities\FeeSharing;
use Modules\SalesOrder\Events\SalesOrderReturn;

class SalesOrderReturnListener
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
    public function handle(SalesOrderReturn $event)
    {
        /**
         * set fee reguler for this sales order to 0
         */
        // $fee_sharing_updated = FeeSharing::query()
        //     ->where("sales_order_id", $event->sales_order->id)
        //     ->update([
        //         "fee_shared" => 0,
        //         "fee_target_shared" => 0,
        //     ]);

        /**
         * set fee target for this sales order
         * to 0
         */
        // $fee_target_sharing_updated = DB::table('fee_target_sharings')
        //     ->where("sales_order_id", $event->sales_order->id)
        //     ->update([
        //         "quantity_unit" => 0,
        //         "fee_nominal" => 0,
        //     ]);

        /**
         * delete log marketing fee count
         * to recount fee for marketing
         * after return
         */
        // $log_marketing_fee_counter_deleted = DB::table('log_marketing_fee_counter')
        //     ->where("sales_order_id", $event->sales_order->id)
        //     ->delete();
            
        return 0;
    }
}
