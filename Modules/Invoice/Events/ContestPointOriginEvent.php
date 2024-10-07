<?php

namespace Modules\Invoice\Events;

use Modules\Invoice\Entities\Invoice;
use Illuminate\Queue\SerializesModels;
use Modules\Contest\Traits\ContestPointTrait;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Contest\Entities\ContestParticipant;
use Illuminate\Broadcasting\InteractsWithSockets;

class ContestPointOriginEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use ContestPointTrait;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->contest_participant = new ContestParticipant;
        $this->active_contract = $this->activeContractStoreByDate($invoice->salesOrder->store_id, confirmation_time($invoice)->format("Y-m-d"));
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
