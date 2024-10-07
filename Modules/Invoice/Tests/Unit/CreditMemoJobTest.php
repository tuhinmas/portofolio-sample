<?php

use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Modules\Invoice\Jobs\CreditMemoJob;
use Modules\Invoice\Entities\CreditMemo;
use Modules\Authentication\Entities\User;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Invoice\Entities\CreditMemoDetail;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\Invoice\Jobs\CreditMemoForOriginJob;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Modules\Distributor\Entities\DistributorContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\SalesOrder\Actions\Order\Origin\GenerateSalesOriginFromOrderAction;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("credit memo: job on create test", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
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

    CreditMemoDetail::factory()->create([
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

    $user = User::factory()->create();
    (new CreditMemoForOriginJob($credit_memo, $order_1, $user))->handle();
    (new CreditMemoJob($order_2))->handle();

    $origin_payment = DB::table('payments')
        ->where("invoice_id", $invoice_1->id)
        ->where("credit_memo_id", $credit_memo->id)
        ->where("is_credit_memo", false)
        ->whereNull("deleted_at")
        ->first();

    $product_1->refresh();
    $order_1->refresh();
    $order_2->refresh();

    expect((int) $product_1->total)->toEqual(36000);
    expect((int) $order_1->total)->toEqual(36000);
    expect((int) $product_1->returned_quantity)->toEqual(1);
    expect($order_1->afftected_by_return)->toEqual($order_2->id);
    expect($order_2->afftected_by_return)->toEqual($order_2->id);
    expect($origin_payment->nominal)->toEqual(-$credit_memo->total);
    expect($origin_payment->is_credit_memo)->toBeFalsy();
    expect($origin_payment->credit_memo_id)->toEqual($credit_memo->id);
});

test("credit memo: memo distributor job on create test", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
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

    DistributorContract::factory()->create([
        "dealer_id" => $order_1->store_id,
    ]);

    $credit_memo = CreditMemo::factory()->create([
        "personel_id" => $order_1->personel_id,
        "dealer_id" => $order_1->store_id,
        "origin_id" => $invoice_1->id,
        "destination_id" => $invoice_2->id,
    ]);

    CreditMemoDetail::factory()->create([
        "credit_memo_id" => $credit_memo->id,
        "product_id" => $product_1->product_id,
        "package_name" => $product_1->package_name,
        "quantity_on_package" => $product_1->quantity_on_package,
        "quantity_order" => $product_1->quantity,
        "quantity_return" => 7,
        "unit_price" => $product_1->unit_price - (0 / $product_1->quantity),
        "unit_price_return" => 5000,
        "total" => 5000,
    ]);

    (new GenerateSalesOriginFromOrderAction)($order_1);
    (new GenerateSalesOriginFromOrderAction)($order_2);

    $origin = SalesOrderOrigin::query()
        ->where("product_id", $product_1->product_id)
        ->where("sales_order_detail_id", $product_1->id)
        ->first();

    expect($origin->stock_ready)->toEqual(10);
    expect($origin->stock_out)->toEqual(0);
    $user = User::factory()->create();

    (new CreditMemoForOriginJob($credit_memo, $order_1, $user))->handle();
    (new CreditMemoJob($order_2))->handle();

    $product_1->refresh();
    $order_1->refresh();
    $order_2->refresh();
    $origin->refresh();

    expect((int) $product_1->total)->toEqual(12000);
    expect((int) $order_1->total)->toEqual(12000);
    expect((int) $product_1->returned_quantity)->toEqual(7);
    expect($order_1->afftected_by_return)->toEqual($order_2->id);
    expect($order_2->afftected_by_return)->toEqual($order_2->id);
    expect($origin->stock_ready)->toEqual(3);
    expect($origin->stock_out)->toEqual(7);
});
