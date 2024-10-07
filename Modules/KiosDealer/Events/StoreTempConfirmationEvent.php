<?php

namespace Modules\KiosDealer\Events;

use Illuminate\Queue\SerializesModels;
use Modules\KiosDealer\Entities\StoreTemp;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class StoreTempConfirmationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(StoreTemp $store_temp)
    {
        $this->store_temp = $store_temp;
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
