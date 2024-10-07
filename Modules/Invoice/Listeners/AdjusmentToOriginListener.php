<?php

namespace Modules\Invoice\Listeners;

use App\Traits\SalesOrderOriginTrait;
use Modules\Invoice\Entities\LogAdjustmentStockToOrigin;
use Modules\Invoice\Events\AdjusmentToOriginEvent;

class AdjusmentToOriginListener
{
    use SalesOrderOriginTrait;

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
    public function handle(AdjusmentToOriginEvent $event)
    {
        if ($event->adjustment_stock->is_first_stock) {

            /**
             * create fisrt stock as origin
             */
            $origin = $this->generateSalesOrderOriginFromFirstStock($event->adjustment_stock);

            /* save adjustment to log, it will use in distributor stovk check */
            $log = LogAdjustmentStockToOrigin::firstOrCreate([
                "adjustment_stock_id" => $event->adjustment_stock->id,
                "sales_order_origin_id" => $origin->id,
            ]);
        }

        return "adjustment stock created";
    }
}
