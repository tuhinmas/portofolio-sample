<?php

namespace Modules\DataAcuan\Events;

use Illuminate\Queue\SerializesModels;
use Modules\DataAcuan\Entities\PointProduct;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Modules\DataAcuan\Entities\GradingBlock;

class GradingBlockDeleteDealerEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(GradingBlock $grading_block)
    {
        $this->grading = $grading_block;
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
