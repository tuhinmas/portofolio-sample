<?php

namespace Modules\DataAcuan\Events;

use Illuminate\Queue\SerializesModels;
use Modules\DataAcuan\Entities\FeePosition;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class FeeWhenFeePositionPercentageChangeEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(FeePosition $fee_position, $last_fee_position = null, $last_fee_follow_up = null, $last_sales_counter_fee_percentage = null)
    {
        $this->fee_position = $fee_position;
        $this->last_fee_position = $last_fee_position;
        $this->last_fee_follow_up = $last_fee_follow_up;
        $this->last_sales_counter_fee_percentage = $last_sales_counter_fee_percentage;
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
