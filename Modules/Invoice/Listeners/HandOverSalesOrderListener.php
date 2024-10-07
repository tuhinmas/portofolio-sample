<?php

namespace Modules\Invoice\Listeners;

use App\Traits\HandOverOrderTrait;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\Invoice\Events\HandOverSalesOrderEvent;
use Modules\SalesOrder\Entities\LogStatusFeeOrder;
use Modules\SalesOrder\Entities\SalesOrder;

class HandOverSalesOrderListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(SalesOrder $sales_order, StatusFee $status_fee, LogStatusFeeOrder $log_status_fee_order)
    {
        $this->log_status_fee_order = $log_status_fee_order;
        $this->sales_order = $sales_order;
        $this->status_fee = $status_fee;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(HandOverSalesOrderEvent $event)
    {

        $this->log_status_fee_order->updateOrCreate([
            "sales_order_id" => $event->invoice->sales_order_id,
        ]);
        
        return "log created";
    }
}
