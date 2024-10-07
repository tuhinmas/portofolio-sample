<?php

namespace Modules\KiosDealer\Events;

use Illuminate\Queue\SerializesModels;
use Modules\KiosDealer\Entities\Dealer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Modules\KiosDealer\Entities\DealerTemp;

class DealerNotifWaitingApprovalEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(DealerTemp $dealer_temp)
    {
        $this->dealer_temp = $dealer_temp;
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
