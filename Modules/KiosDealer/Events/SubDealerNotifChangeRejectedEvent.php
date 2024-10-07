<?php

namespace Modules\KiosDealer\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\SubDealerTemp;

class SubDealerNotifChangeRejectedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SubDealerTemp $sub_dealer_temp)
    {
        $this->sub_dealer_temp = $sub_dealer_temp;
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
