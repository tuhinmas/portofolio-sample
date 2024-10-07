<?php

namespace Modules\SalesOrder\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Illuminate\Broadcasting\InteractsWithSockets;
use Modules\Authentication\Entities\User;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;

class DirectSalesAbandonedNotificationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
