<?php

namespace Modules\Invoice\Actions;

use Modules\Invoice\Entities\Invoice;
use Modules\SalesOrder\Entities\SalesOrder;

class DeleteInvoiceBySalesOrderAction
{
    public function __invoke($sales_order)
    {
        return Invoice::query()
            ->where("sales_order_id", $sales_order->id)
            ->get()
            ->each(function ($invoice) {
                $invoice->delete();
            });
    }
}
