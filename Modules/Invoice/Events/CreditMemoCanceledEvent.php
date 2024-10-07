<?php

namespace Modules\Invoice\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Authentication\Entities\User;
use Modules\Contest\Traits\ContestPointTrait;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Illuminate\Broadcasting\InteractsWithSockets;

class CreditMemoCanceledEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use ContestPointTrait;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SalesOrderV2 $sales_orderv2, User $user)
    {
        $this->sales_orderv2 = $sales_orderv2;
        $this->user = $user;
        $this->active_contract_contest = $this->activeContractStoreByDate($sales_orderv2->store_id, confirmation_time($sales_orderv2)->format("Y-m-d"));
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
