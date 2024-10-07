<?php

namespace Modules\Personel\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Personel\Entities\Personel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class PersoneJoinDateEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($previous_join_date, Personel $personel)
    {
        $this->previous_join_date = $previous_join_date;
        $this->personel = $personel;
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
