<?php

namespace Modules\SalesOrder\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class DeletedProductEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sales_order_detail;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SalesOrderDetail $sales_order_detail)
    {
        $this->sales_order_detail = $sales_order_detail;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
