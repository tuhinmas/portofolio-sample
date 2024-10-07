<?php

namespace Modules\DistributionChannel\Events;

use Illuminate\Queue\SerializesModels;
use Modules\DataAcuan\Entities\PointProduct;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class CanceledDispatchOrderEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $dispatchOrder;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($dispatchOrder)
    {
        $this->dispatchOrder = $dispatchOrder;
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
