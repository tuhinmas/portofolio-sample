<?php

namespace Modules\Invoice\Listeners;

use Modules\Invoice\Entities\AdjustmentStock;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Modules\Invoice\Entities\LogAdjustmentStockToOrigin;
use Modules\Invoice\Events\SalesOrderOriginDirectGeneratorEvent;

class AdjustmentCheckAfterGenerateOriginListener
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
    public function handle(SalesOrderOriginDirectGeneratorEvent $event)
    {
        /**
         * no need to check origin accorinf first stock
         * first stock origin was created after
         * it's added
         */
        if ($event->invoice->salesOrderOnly->salesOrderDetail->count() > 0) {
            foreach ($event->invoice->salesOrderOnly->salesOrderDetail as $detail) {

                /* adjustment check does not added to origin */
                $adjustment_check = AdjustmentStock::query()
                    ->where("dealer_id", $event->invoice->salesOrderOnly->store_id)
                    ->where("product_id", $detail->product_id)
                    ->whereDoesnthave("logAdjsutmentStockToOrigin")
                    ->orderBy("opname_date")
                    ->first();

                if ($adjustment_check) {
                    $origin = SalesOrderOrigin::query()
                        ->where("store_id", $event->invoice->salesOrderOnly->store_id)
                        ->where("product_id", $detail->product_id)
                        ->where("confirmed_at", ">=", $adjustment_check->opname_date)
                        ->orderBy("confirmed_at")
                        ->first();

                    /* add adjusment to nearest origin if exist */
                    if ($origin) {

                        if ($adjustment_check->is_first_stock) {
                            $origin->first_stock = $adjustment_check->real_stock;
                        } else {
                            $origin->stock_opname = $adjustment_check->real_stock;
                        }

                        $origin->stock_ready = $origin->stock_ready + $adjustment_check->real_stock;
                        $origin->save();

                        /* save adjustment to log, it will use in distributor stock check */
                        $log = LogAdjustmentStockToOrigin::firstOrCreate([
                            "adjustment_stock_id" => $adjustment_check->id,
                            "sales_order_origin_id" => $origin->id,
                        ]);
                    }
                }

            }
            return $event->invoice->salesOrderOnly;
        }
    }
}
