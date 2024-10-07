<?php

namespace Modules\Invoice\Listeners;

use Modules\Invoice\Entities\Invoice;

class InvoiceOnDeletedPaymentListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $invoice = Invoice::query()
            ->where("id", $event->payment->invoice_id)
            ->update([
                "payment_status" => "unpaid"
            ]);

        return $invoice;
    }
}
