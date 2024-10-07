<?php

namespace Modules\DataAcuan\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;

class MarketingAreaOnChangeEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(MarketingAreaDistrict $marketing_area_district)
    {
        $this->marketing_area_district = $marketing_area_district;
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
