<?php

namespace Modules\DataAcuan\Events;

use Modules\DataAcuan\Entities\Fee;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ForecastChangeAreaMarketingEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct($personel_id_change = [])
    {
        $this->personel_id_change = $personel_id_change;
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
