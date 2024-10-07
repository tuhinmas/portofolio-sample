<?php

namespace Modules\KiosDealer\Events;

use Illuminate\Queue\SerializesModels;
use Modules\KiosDealer\Entities\Dealer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class DealerNotifAcceptedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Dealer $dealer_temp)
    {
        $this->dealer = $dealer_temp;
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
