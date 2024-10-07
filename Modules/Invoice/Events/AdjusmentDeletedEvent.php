<?php

namespace Modules\Invoice\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Invoice\Entities\AdjustmentStock;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class AdjusmentDeletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(AdjustmentStock $adjustment_stock)
    {
        $this->adjustment_stock = $adjustment_stock;
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
