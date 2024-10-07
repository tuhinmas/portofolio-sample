<?php

namespace Modules\DataAcuan\Events;

use Illuminate\Queue\SerializesModels;
use Modules\DataAcuan\Entities\PointProduct;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class PointInCreatedPointProductEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(PointProduct $point_product)
    {
        $this->point_product = $point_product;
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
