<?php

namespace Modules\SalesOrder\Events;

use Illuminate\Queue\SerializesModels;
use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class SalesOrderReturn
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sales_order;
    
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
