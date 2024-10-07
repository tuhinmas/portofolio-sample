<?php

namespace Modules\ReceivingGood\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;

class DeliveryStatusCheckInReceivingGoodEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ReceivingGoodDetail $receiving_good_detail)
    {
        $this->receiving_good_detail = $receiving_good_detail;
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
