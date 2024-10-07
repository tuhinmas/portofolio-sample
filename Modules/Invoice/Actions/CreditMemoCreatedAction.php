<?php

namespace Modules\Invoice\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\CreditMemo;
use Modules\Invoice\Entities\CreditMemoHistory;
use Modules\Invoice\Entities\Payment;
use Modules\Invoice\Jobs\CreditMemoForOriginJob;
use Modules\Invoice\Jobs\CreditMemoJob;

class CreditMemoCreatedAction
{
    /**
     * credit memo action inclue
     * 1. set destination to retunr
     * 2. create history of memo
     * 3. create payment for destination
     * 4. cancel
     *
     * @param CreditMemo $credit_memo
     * @return void
     */
    public function __invoke(CreditMemo $credit_memo)
    {
        if ($credit_memo->status != "accepted") {
            return;
        }
        $credit_memo->loadMissing([
            "origin.salesOrderOnly.salesOrderDetail",
            "destination.salesOrderOnly",
            "origin.salesOrderOnly",
        ]);

        $origin_order = $credit_memo->origin->salesOrderOnly;
        $destination_order = $credit_memo->destination->salesOrderOnly;

        /* Destination status set to RETURNED */
        $destination_order->status = "returned";
        $destination_order->return = $credit_memo->date;
        $destination_order->returned_by = auth()->user()->personel_id;
        $destination_order->save();

        $destination_order->salesOrderHistoryChangeStatus()->create([
            "sales_order_id" => $destination_order->id,
            "type" => $destination_order->type,
            "status" => "returned",
            "personel_id" => auth()->user()->personel_id,
            "note" => " return dari kredit memo",
        ]);

        /* Make history of memo */
        CreditMemoHistory::create([
            "personel_id" => auth()->user()->personel_id,
            "credit_memo_id" => $credit_memo->id,
            "status" => $credit_memo->status,
        ]);

        /* make payment for destination */
        $payments = DB::table('payments as p')
            ->join("invoices as i", "i.id", "p.invoice_id")
            ->where("p.invoice_id", $credit_memo->destination_id)
            ->whereNull("p.deleted_at")
            ->whereNull("i.deleted_at")
            ->select("p.*", "i.total")
            ->get();

        $remaining_payment = ($credit_memo->destination->total + $credit_memo->destination->ppn) - ($payments->sum("nominal") + $credit_memo->total);

        Payment::create([
            "invoice_id" => $credit_memo->destination_id,
            "nominal" => $credit_memo->total,
            "reference_number" => $credit_memo->number,
            "remaining_payment" => $remaining_payment,
            "user_id" => auth()->id(),
            "payment_date" => $credit_memo->date,
            "is_credit_memo" => true,
            "credit_memo_id" => $credit_memo->id,
            "memo_status" => "accepted",
        ]);

        if ($remaining_payment <= 0) {
            $credit_memo->destination->payment_status = "settle";
            $credit_memo->destination->save();
        } else {
            $credit_memo->destination->payment_status = "paid";
            $credit_memo->destination->save();
        }

        /*
        |-------------------------------------------------
        | Credit Memo Job
        |-----------------------------------------
        |
        | including
        |
         *
         */

        Bus::chain([
            new CreditMemoForOriginJob($credit_memo, $origin_order, auth()->user()),
            new CreditMemoJob($destination_order),
        ])->dispatch();
    }
}
