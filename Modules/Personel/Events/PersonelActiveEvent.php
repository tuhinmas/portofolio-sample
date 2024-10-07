<?php

namespace Modules\Personel\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Personel\Entities\Personel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class PersonelActiveEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Personel $personel, $isNew)
    {
        $this->personel = $personel;
        $this->is_new = $isNew;
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
