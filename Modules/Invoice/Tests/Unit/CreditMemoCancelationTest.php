<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\Invoice\Entities\CreditMemo;
use Modules\Invoice\Entities\CreditMemoHistory;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Modules\Invoice\Jobs\CreditMemoCanceledJob;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("credit memo: can cancel credit memo", function () {
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
    ]);
    $invoice_2 = Invoice::factory()->create([
        "total" => 90000,
        "ppn" => 10000,
    ]);
    $payment = Payment::factory()->create([
        "invoice_id" => $invoice_2->id,
        "nominal" => 40000,
        "created_at" => now()->subDay(),
        "remaining_payment" => 60000,
    ]);

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

    $order_2->salesOrderHistoryChangeStatus()->create([
        "sales_order_id" => $order_2->id,
        "type" => $order_2->type,
        "status" => "confirmed",
        "personel_id" => $order_2->personel_id,
        "note" => "confirmed",
        "created_at" => now()->subDay(),
    ]);

    $product_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice_1->sales_order_id,
        "discount" => 1000,
        "unit_price" => 5000,
        "quantity" => 10,
        "total" => 50000,
    ]);

    $product_2 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice_1->sales_order_id,
        "discount" => 2000,
        "unit_price" => 15000,
        "quantity" => 4,
        "total" => 60000,
    ]);

    $credit_memo = CreditMemo::factory()->create([
        "origin_id" => $invoice_1->id,
        "destination_id" => $invoice_2->id,
        "date" => now()->addDay(),
        "tax_invoice" => "010.000-24.00000001",
        "reason" => "cancel memo",
        "total" => 60000,
    ]);

    $payment_from_memo = Payment::factory()->create([
        "invoice_id" => $invoice_2->id,
        "nominal" => 60000,
        "is_credit_memo" => true,
        "credit_memo_id" => $credit_memo->id,
        "memo_status" => "accepted",
    ]);

    Queue::fake();
    $response = actingAsSupport()->putJson("/api/v1/credit-memo/" . $credit_memo->id . "/cancel", [
        "cancelation_note" => "batalkan memo",
    ]);

    $response->assertStatus(200);
    $order_2->refresh();
    $memo_history = CreditMemoHistory::query()
        ->where("credit_memo_id", $response->getData()->data->id)
        ->first();

    expect($memo_history)->toBeTruthy();
    expect($memo_history->status)->toEqual("canceled");
    expect($memo_history->personel_id)->toBeTruthy();
    expect($memo_history->credit_memo_id)->toBeTruthy();

    Queue::assertPushedOn('order', CreditMemoCanceledJob::class);
});
