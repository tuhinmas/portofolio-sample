<?php

namespace Modules\Invoice\Listeners;

use Modules\Invoice\Events\PaymentOnSettleEvent;
use Modules\Personel\Entities\LogMarketingFeeCounter;

class UpdateLogMarketingFee
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
    public function handle(PaymentOnSettleEvent $event)
    {
        // $deleted_sales_order_from_log = LogMarketingFeeCounter::query()
        //     ->where("sales_order_id", $event->invoice->salesOrderOnly->id)
        //     ->delete();

        // return $deleted_sales_order_from_log;
    }
}
