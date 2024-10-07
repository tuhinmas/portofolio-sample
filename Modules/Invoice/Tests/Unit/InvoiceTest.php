<?php

use Illuminate\Support\Facades\Queue;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Modules\Invoice\Entities\CreditMemo;
use Modules\Authentication\Entities\User;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Invoice\Entities\InvoiceProforma;
use Modules\Invoice\Entities\CreditMemoDetail;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\Invoice\Jobs\InvoiceNotificationJob;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\Invoice\Jobs\InvoiceMobileNotificationJob;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

it("can create proforma with manual number", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $user = User::where("name", "support")->first();
    Queue::fake();

    $response = actingAsSupport()->postJson("/api/v1/invoice", [
        "sales_order_id" => $sales_order->id,
        "sub_total" => 30480000.00,
        "discount" => 762000.00,
        "total" => 29718000.00,
        "ppn" => 3268980,
        "invoice" => rand(10000, 1000000),
        "payment_status" => "unpaid",
        "user_id" => $user->id,
    ]);

    Queue::assertPushed(InvoiceNotificationJob::class);
    Queue::assertPushed(InvoiceMobileNotificationJob::class);
    $response->assertStatus(201);
});

it("can create proforma with auto generator number", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);
    $user = User::where("name", "support")->first();

    $response = actingAsSupport()->postJson("/api/v1/invoice", [
        "sales_order_id" => $sales_order->id,
        "sub_total" => 30480000.00,
        "discount" => 762000.00,
        "total" => 29718000.00,
        "ppn" => 3268980,
        "payment_status" => "unpaid",
        "user_id" => $user->id,
    ]);

    $response->assertStatus(201);
});

it("can create proforma from oreer that null personel", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
        "personel_id" => null,
    ]);
    $user = User::where("name", "support")->first();

    $response = actingAsSupport()->postJson("/api/v1/invoice", [
        "sales_order_id" => $sales_order->id,
        "sub_total" => 30480000.00,
        "discount" => 762000.00,
        "total" => 29718000.00,
        "ppn" => 3268980,
        "payment_status" => "unpaid",
        "user_id" => $user->id,
    ]);

    $response->assertStatus(201);
});

test("can update proforma", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $user = User::where("name", "support")->first();

    $response = actingAsSupport()->putJson("/api/v1/invoice/" . $invoice->id, [
        "sub_total" => 30480000.00,
        "discount" => 762000.00,
        "total" => 29718000.00,
        "ppn" => 3268980,
        "invoice" => rand(10000, 1000000),
        "payment_status" => "unpaid",
        "user_id" => $user->id,
    ]);
    $response->assertStatus(200);
});

test("can update proforma, amount of proforma from return", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "returned",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $user = User::where("name", "support")->first();

    $response = actingAsSupport()->putJson("/api/v1/invoice/" . $invoice->id, [
        "sub_total" => $invoice->sub_total - ($invoice->sub_total * 10 / 100),
        "discount" => $invoice->discount - ($invoice->discount * 10 / 100),
        "total" => $invoice->total - ($invoice->total * 10 / 100),
        "ppn" => $invoice->ppn - ($invoice->ppn * 10 / 100),
        "payment_status" => "unpaid",
        "user_id" => $user->id,
    ]);
    $response->assertStatus(200);
    $total = $invoice->total - ($invoice->total * 10 / 100);
    $sub_total = $invoice->sub_total - ($invoice->sub_total * 10 / 100);
    $discount = $invoice->discount - ($invoice->discount * 10 / 100);
    $ppn = $invoice->ppn - ($invoice->ppn * 10 / 100);
    $invoice->refresh();
    expect($invoice->total)->toEqual($total);
    expect($invoice->sub_total)->toEqual($sub_total);
    expect($invoice->discount)->toEqual($discount);
    expect($invoice->ppn)->toEqual($ppn);
});

test("can update proforma, add credit proforma", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "returned",
    ]);

    $invoice_1 = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $invoice_2 = Invoice::factory()->create();
    $user = User::where("name", "support")->first();

    $response = actingAsSupport()->putJson("/api/v1/invoice/" . $invoice_1->id, [
        "payment_status" => "unpaid",
        "user_id" => $user->id,
        "credit_invoice" => $invoice_2->id
    ]);
    $response->assertStatus(200);
    expect($response->getData()->data->credit_invoice)->toEqual($invoice_2->id);
});

test("can not delete if there invoice proforma", function () {
    $proforma = Invoice::factory()->create();

    $invoice = InvoiceProforma::factory()->create([
        "invoice_id" => $proforma->id,
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/invoice/" . $proforma->id);
    $response->assertStatus(422);
});

test("can not update if there invoice proforma", function () {
    $proforma = Invoice::factory()->create();

    $invoice = InvoiceProforma::factory()->create([
        "invoice_id" => $proforma->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/invoice/" . $proforma->id);
    $response->assertStatus(422);
});

/**
 * DELETE
 */
test("invoice delete, credit memo will deleted", function () {
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
    ]);

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_1->total = 40000;
    $order_1->save();
    $order_2 = SalesOrderV2::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

    $product_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice_1->sales_order_id,
        "discount" => 10000,
        "unit_price" => 5000,
        "quantity" => 10,
        "total" => 40000,
    ]);

    $product_2 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice_2->sales_order_id,
        "discount" => 2000,
        "unit_price" => 15000,
        "quantity" => 4,
        "total" => 60000,
    ]);

    $credit_memo = CreditMemo::factory()->create([
        "personel_id" => $order_1->personel_id,
        "dealer_id" => $order_1->store_id,
        "origin_id" => $invoice_1->id,
        "destination_id" => $invoice_2->id,
    ]);

    $credit_memo_detail = CreditMemoDetail::factory()->create([
        "credit_memo_id" => $credit_memo->id,
        "product_id" => $product_1->product_id,
        "package_name" => $product_1->package_name,
        "quantity_on_package" => $product_1->quantity_on_package,
        "quantity_order" => $product_1->quantity,
        "quantity_return" => 1,
        "unit_price" => $product_1->unit_price - (0 / $product_1->quantity),
        "unit_price_return" => 5000,
        "total" => 5000,
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/invoice/" . $invoice_1->id);

    $credit_memo->refresh();
    $credit_memo_detail->refresh();
    
    expect($credit_memo->deleted_at)->toBeTruthy();
    expect($credit_memo_detail->deleted_at)->toBeTruthy();
});