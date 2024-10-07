<?php

namespace Modules\Invoice\Events;

use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class PaymentOnSettleEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invoice;
    public $payment;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice, Payment $payment)
    {
        $this->invoice = $invoice;
        $this->payment = $payment;
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
