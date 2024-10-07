<?php

namespace Modules\Invoice\Listeners;

use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Modules\Invoice\Events\PaymentStatusCheckEvent;

class PaymentStatusCheckAfterDeletedPaymentListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Invoice $invoice, Payment $payment)
    {
        $this->invoice = $invoice;
        $this->payment = $payment;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PaymentStatusCheckEvent $event)
    {
        $total_payment = $this->payment->where("invoice_id", $event->payment->invoice_id)->sum("nominal");
        $invoice = $this->invoice->findOrFail($event->payment->invoice_id);
        if ($total_payment < $invoice->total + $invoice->ppn && $total_payment > 0) {
            $invoice->payment_status = "paid";
            $invoice->save();
        } elseif ($total_payment == 0) {
            $invoice->payment_status = "unpaid";
            $invoice->save();
        }
    }
}
