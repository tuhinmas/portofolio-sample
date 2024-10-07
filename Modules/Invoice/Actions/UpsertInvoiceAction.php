<?php

namespace Modules\Invoice\Actions;

use Modules\Invoice\Entities\Invoice;

class UpsertInvoiceAction
{
    public function __invoke(array $data, Invoice $invoice = null): Invoice
    {
        return Invoice::updateOrCreate(
            ["id" => $invoice?->id],
            $data
        );
    }
}
