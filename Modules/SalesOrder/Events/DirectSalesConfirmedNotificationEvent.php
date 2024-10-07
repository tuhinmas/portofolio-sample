<?php

namespace Modules\SalesOrder\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Illuminate\Broadcasting\InteractsWithSockets;
use Modules\SalesOrder\Entities\SalesOrder;

class DirectSalesConfirmedNotificationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SalesOrder $sales_order)
    {
        $this->sales_order = $sales_order;
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
