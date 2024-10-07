<?php

namespace Modules\Personel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Personel\Entities\Personel;

class PersonelUpdateFromHistoryPersonelStatusEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $personel;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Personel $personel, $position, $entity, $request, $previous_status = null)
    {
        $this->personel = $personel;
        $this->position = $position;
        $this->entity = $entity;
        $this->request = $request;
        $this->previous_status = $previous_status;
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
