<?php

namespace Modules\SalesOrder\Actions;

use Modules\Authentication\Entities\User;
use Modules\Invoice\Actions\DeleteInvoiceBySalesOrderAction;
use Modules\Invoice\Actions\UpsertInvoiceAction;
use Modules\Invoice\Entities\Invoice;
use Modules\SalesOrderV2\Actions\UpsertSalesOrderStatusHistoryAction;
use Modules\SalesOrderV2\Entities\SalesOrderHistoryChangeStatus;
use Spatie\Activitylog\Contracts\Activity;

class CanceledDirectSaleAction
{
    public function __invoke($sales_order, Invoice $invoice, User $user, $canceled_at, bool $create_log = false)
    {
        $delete_invoice_action = new DeleteInvoiceBySalesOrderAction();
        $upsert_invoice_action = new UpsertInvoiceAction();

        if ($invoice) {
            $upsert_invoice_action(["canceled_at" => $canceled_at ? $canceled_at : now()], $invoice);
            $delete_invoice_action($sales_order);
            if ($create_log) {
                $log = activity()
                    ->by($user)
                    ->performedOn($invoice)
                    ->event('canceled')
                    ->withProperties([
                        "attributes" => $invoice,
                    ])
                    ->tap(function (Activity $activity) use ($user) {
                        $activity->log_name = 'deleted';
                    })
                    ->log('delete from cancel direct');

                $log->causer_id = $user->id;
                $log->save();
            }
        }

        /* indirect sales already in controller no need to add in this section */
        if ($sales_order->type == "1") {

            /* create sales order status history changes payload */
            $note = "set to " . $sales_order->status . " in " . ($canceled_at ? $canceled_at : now());

            $status_history = SalesOrderHistoryChangeStatus::query()
                ->where("sales_order_id", $sales_order->id)
                ->orderBy("updated_at", "desc")
                ->first();

            $sales_order_status_history = new UpsertSalesOrderStatusHistoryAction();
            $payload = [
                "sales_order_id" => $sales_order->id,
                "type" => $sales_order->type,
                "status" => $sales_order->status,
                "personel_id" => $user->personel_id,
                "note" => $note,
            ];

            $sales_order_status_history($payload, ($status_history ? ($status_history->status == $sales_order->status ? $status_history : null) : null));
        }

        return $sales_order->status;
    }
}
