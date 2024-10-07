<?php

use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Modules\Invoice\Entities\CreditMemo;
use Modules\Authentication\Entities\User;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Invoice\Entities\CreditMemoDetail;
use Modules\Invoice\Jobs\CreditMemoCanceledJob;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\Invoice\Jobs\CreditMemoForOriginJob;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Modules\Distributor\Entities\DistributorContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\SalesOrder\Actions\Order\Origin\GenerateSalesOriginFromOrderAction;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("credit memo: job on cancel test, payment status after: paid", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "total" => 40000,
        "ppn" => 4000,
    ]);
    $invoice_2 = Invoice::factory()->create([
        "total" => 40000,
        "ppn" => 4000,
        "payment_status" => "unpaid", //  to make sure value is change
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
        "returned_quantity" => 3,
        "total" => 28000,
    ]);

    $credit_memo = CreditMemo::factory()->create([
        "personel_id" => $order_1->personel_id,
        "dealer_id" => $order_1->store_id,
        "origin_id" => $invoice_1->id,
        "destination_id" => $invoice_2->id,
        "total" => 16000,
        "status" => "canceled",
    ]);

    CreditMemoDetail::factory()->create([
        "credit_memo_id" => $credit_memo->id,
        "product_id" => $product_1->product_id,
        "package_name" => $product_1->package_name,
        "quantity_on_package" => $product_1->quantity_on_package,
        "quantity_order" => $product_1->quantity,
        "quantity_return" => 4,
        "unit_price" => $product_1->unit_price - (0 / $product_1->quantity),
        "unit_price_return" => 16000,
        "total" => 16000,
    ]);

    /**
     * original payment invoice 1
     */
    Payment::factory()->create([
        "invoice_id" => $invoice_1->id,
        "payment_date" => now()->subDays(2),
        "nominal" => 24000,
        "remaining_payment" => 20000,
        "is_credit_memo" => false,
        "created_at" => now()->subDays(2),
    ]);

    Payment::factory()->create([
        "invoice_id" => $invoice_1->id,
        "payment_date" => now()->subDay(),
        "nominal" => 20000,
        "remaining_payment" => 0,
        "is_credit_memo" => false,
        "created_at" => now()->subDay(),
    ]);

    $payment_origin = Payment::factory()->create([
        "invoice_id" => $invoice_1->id,
        "payment_date" => now()->subDay(),
        "nominal" => -16000,
        "remaining_payment" => 0,
        "is_credit_memo" => false,
        "credit_memo_id" => $credit_memo->id,
        "created_at" => now()->subDay(),
    ]);

    $payment = Payment::factory()->create([
        "invoice_id" => $invoice_2->id,
        "payment_date" => now()->subDay(),
        "nominal" => 28000,
        "remaining_payment" => 16000,
        "is_credit_memo" => false,
        "created_at" => now()->subDay(),
    ]);

    $payment_memo = Payment::factory()->create([
        "invoice_id" => $invoice_2->id,
        "nominal" => 16000,
        "memo_status" => "accepted",
        "remaining_payment" => 0,
        "credit_memo_id" => $credit_memo->id,
        "is_credit_memo" => true,
    ]);

    $user = User::factory()->create();
    (new CreditMemoCanceledJob($credit_memo, $user))->handle();
    $product_1->refresh();
    $order_1->refresh();
    $order_2->refresh();
    $payment->refresh();
    $invoice_1->refresh();
    $invoice_2->refresh();
    $payment_memo->refresh();
    $payment_origin->refresh();

    /* order total test */
    expect((int) $product_1->total)->toEqual(40000);
    expect((int) $product_1->returned_quantity)->toEqual(0);
    expect((int) $order_1->total)->toEqual(40000);

    /* payment status test */
    expect($payment_memo->memo_status)->toEqual("canceled");
    expect($payment_memo->nominal)->toEqual(0);
    expect($payment_memo->remaining_payment)->toEqual(16000);
    expect($invoice_2->payment_status)->toEqual("paid");
    expect($payment_memo->credit_memo_id)->toEqual($credit_memo->id);
    expect($payment_memo->is_credit_memo)->toBeTruthy();

    /* order status rollback test */
    $afftected_by_return_count = DB::table('sales_orders')
        ->where("store_id", $order_2->store_id)
        ->whereNotNull("afftected_by_return")
        ->count();

    $status_history = DB::table('sales_order_history_change_statuses')
        ->whereNull("deleted_at")
        ->where("sales_order_id", $order_2->id)
        ->orderBy("created_at", "desc")
        ->first();

    expect($afftected_by_return_count)->toEqual(0);
    expect($order_2->status)->toEqual("confirmed");
    expect($status_history->status)->toEqual("confirmed");
    expect($payment_origin->deleted_at)->not->toBeNull();;
});

test("credit memo: job on cancel test, payment status after: unpaid", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "total" => 40000,
        "ppn" => 4000,
    ]);
    $invoice_2 = Invoice::factory()->create([
        "total" => 40000,
        "ppn" => 4000,
        "payment_status" => "settle", // to make sure value is change
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
        "returned_quantity" => 3,
        "total" => 28000,
    ]);

    $credit_memo = CreditMemo::factory()->create([
        "personel_id" => $order_1->personel_id,
        "dealer_id" => $order_1->store_id,
        "origin_id" => $invoice_1->id,
        "destination_id" => $invoice_2->id,
        "total" => 16000,
        "status" => "canceled",
    ]);

    CreditMemoDetail::factory()->create([
        "credit_memo_id" => $credit_memo->id,
        "product_id" => $product_1->product_id,
        "package_name" => $product_1->package_name,
        "quantity_on_package" => $product_1->quantity_on_package,
        "quantity_order" => $product_1->quantity,
        "quantity_return" => 4,
        "unit_price" => $product_1->unit_price - (0 / $product_1->quantity),
        "unit_price_return" => 16000,
        "total" => 16000,
    ]);

    /**
     * original payment invoice 1
     */
    Payment::factory()->create([
        "invoice_id" => $invoice_1->id,
        "payment_date" => now()->subDays(2),
        "nominal" => 24000,
        "remaining_payment" => 20000,
        "is_credit_memo" => false,
        "created_at" => now()->subDays(2),
    ]);

    Payment::factory()->create([
        "invoice_id" => $invoice_1->id,
        "payment_date" => now()->subDay(),
        "nominal" => 20000,
        "remaining_payment" => 0,
        "is_credit_memo" => false,
        "created_at" => now()->subDay(),
    ]);

    $payment_origin = Payment::factory()->create([
        "invoice_id" => $invoice_1->id,
        "payment_date" => now()->subDay(),
        "nominal" => -16000,
        "remaining_payment" => 0,
        "is_credit_memo" => false,
        "credit_memo_id" => $credit_memo->id,
        "created_at" => now()->subDay(),
    ]);

    $payment_memo = Payment::factory()->create([
        "invoice_id" => $invoice_2->id,
        "nominal" => 44000,
        "memo_status" => "accepted",
        "remaining_payment" => 0,
        "credit_memo_id" => $credit_memo->id,
        "is_credit_memo" => true,
    ]);

    $user = User::factory()->create();
    (new CreditMemoCanceledJob($credit_memo, $user))->handle();
    $product_1->refresh();
    $order_1->refresh();
    $order_2->refresh();
    $invoice_1->refresh();
    $invoice_2->refresh();
    $payment_memo->refresh();
    $payment_origin->refresh();

    /* order total test */
    expect((int) $product_1->total)->toEqual(40000);
    expect((int) $product_1->returned_quantity)->toEqual(0);
    expect((int) $order_1->total)->toEqual(40000);

    /* payment status test */
    expect($payment_memo->memo_status)->toEqual("canceled");
    expect($payment_memo->nominal)->toEqual(0);
    expect($payment_memo->remaining_payment)->toEqual(44000);
    expect($invoice_2->payment_status)->toEqual("unpaid");
    expect($payment_memo->credit_memo_id)->toEqual($credit_memo->id);
    expect($payment_memo->is_credit_memo)->toBeTruthy();

    /* order status rollback test */
    $afftected_by_return_count = DB::table('sales_orders')
        ->where("store_id", $order_2->store_id)
        ->whereNotNull("afftected_by_return")
        ->count();

    $status_history = DB::table('sales_order_history_change_statuses')
        ->whereNull("deleted_at")
        ->where("sales_order_id", $order_2->id)
        ->orderBy("created_at", "desc")
        ->first();

    expect($afftected_by_return_count)->toEqual(0);
    expect($order_2->status)->toEqual("confirmed");
    expect($status_history->status)->toEqual("confirmed");
    expect($payment_origin->deleted_at)->not->toBeNull();;
});

test("credit memo: job on cancel test, payment status after: settle", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "total" => 40000,
        "ppn" => 4000,
    ]);
    $invoice_2 = Invoice::factory()->create([
        "total" => 40000,
        "ppn" => 4000,
        "payment_status" => "unpaid", // to make sure value is change
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
        "returned_quantity" => 3,
        "total" => 28000,
    ]);

    $credit_memo = CreditMemo::factory()->create([
        "personel_id" => $order_1->personel_id,
        "dealer_id" => $order_1->store_id,
        "origin_id" => $invoice_1->id,
        "destination_id" => $invoice_2->id,
        "total" => 10000,
        "status" => "canceled",
    ]);

    CreditMemoDetail::factory()->create([
        "credit_memo_id" => $credit_memo->id,
        "product_id" => $product_1->product_id,
        "package_name" => $product_1->package_name,
        "quantity_on_package" => $product_1->quantity_on_package,
        "quantity_order" => $product_1->quantity,
        "quantity_return" => 4,
        "unit_price" => $product_1->unit_price - (0 / $product_1->quantity),
        "unit_price_return" => 16000,
        "total" => 16000,
    ]);

    /**
     * original payment invoice 1
     */
    Payment::factory()->create([
        "invoice_id" => $invoice_1->id,
        "payment_date" => now()->subDays(2),
        "nominal" => 24000,
        "remaining_payment" => 20000,
        "is_credit_memo" => false,
        "created_at" => now()->subDays(2),
    ]);

    Payment::factory()->create([
        "invoice_id" => $invoice_1->id,
        "payment_date" => now()->subDay(),
        "nominal" => 20000,
        "remaining_payment" => 0,
        "is_credit_memo" => false,
        "created_at" => now()->subDay(),
    ]);

    $payment_origin = Payment::factory()->create([
        "invoice_id" => $invoice_1->id,
        "payment_date" => now()->subDay(),
        "nominal" => -10000,
        "remaining_payment" => 0,
        "is_credit_memo" => false,
        "credit_memo_id" => $credit_memo->id,
        "created_at" => now()->subDay(),
    ]);

    /**
     * payment invoice 2
     */
    $payment = Payment::factory()->create([
        "invoice_id" => $invoice_2->id,
        "payment_date" => now()->subDay(),
        "nominal" => 28000,
        "remaining_payment" => 16000,
        "is_credit_memo" => false,
        "created_at" => now()->subDays(2),
    ]);

    $payment_memo = Payment::factory()->create([
        "invoice_id" => $invoice_2->id,
        "nominal" => 10000,
        "memo_status" => "accepted",
        "remaining_payment" => 6000,
        "credit_memo_id" => $credit_memo->id,
        "is_credit_memo" => true,
        "created_at" => now()->subDays(1),
    ]);

    $payment_2 = Payment::factory()->create([
        "invoice_id" => $invoice_2->id,
        "payment_date" => now()->subDay(),
        "nominal" => 20000,
        "remaining_payment" => -14000,
        "is_credit_memo" => false,
        "created_at" => now(),
    ]);

    $user = User::factory()->create();
    (new CreditMemoCanceledJob($credit_memo, $user))->handle();
    $product_1->refresh();
    $order_1->refresh();
    $order_2->refresh();
    $payment->refresh();
    $payment_2->refresh();
    $invoice_1->refresh();
    $invoice_2->refresh();
    $payment_memo->refresh();
    $payment_origin->refresh();

    /* order total test */
    expect((int) $product_1->total)->toEqual(40000);
    expect((int) $product_1->returned_quantity)->toEqual(0);
    expect((int) $order_1->total)->toEqual(40000);

    /* payment status test */
    expect($payment_memo->memo_status)->toEqual("canceled");
    expect($payment_memo->nominal)->toEqual(0);
    expect($payment_memo->remaining_payment)->toEqual(16000);
    expect($invoice_2->payment_status)->toEqual("settle");
    expect($payment_memo->credit_memo_id)->toEqual($credit_memo->id);
    expect($payment_memo->is_credit_memo)->toBeTruthy();

    expect((int) $payment_2->remaining_payment)->toEqual(-4000);

    /* order status rollback test */
    $afftected_by_return_count = DB::table('sales_orders')
        ->where("store_id", $order_2->store_id)
        ->whereNotNull("afftected_by_return")
        ->count();

    $status_history = DB::table('sales_order_history_change_statuses')
        ->whereNull("deleted_at")
        ->where("sales_order_id", $order_2->id)
        ->orderBy("created_at", "desc")
        ->first();

    expect($afftected_by_return_count)->toEqual(0);
    expect($order_2->status)->toEqual("confirmed");
    expect($status_history->status)->toEqual("confirmed");
    expect($payment_origin->deleted_at)->not->toBeNull();;
});

test("credit memo: memo distributor job on cancel test", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "total" => 40000,
        "ppn" => 4000,
    ]);
    $invoice_2 = Invoice::factory()->create([
        "total" => 40000,
        "ppn" => 4000,
        "payment_status" => "paid",
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
        "returned_quantity" => 3,
        "total" => 28000,
    ]);

    $credit_memo = CreditMemo::factory()->create([
        "personel_id" => $order_1->personel_id,
        "dealer_id" => $order_1->store_id,
        "origin_id" => $invoice_1->id,
        "destination_id" => $invoice_2->id,
        "status" => "canceled",
    ]);

    CreditMemoDetail::factory()->create([
        "credit_memo_id" => $credit_memo->id,
        "product_id" => $product_1->product_id,
        "package_name" => $product_1->package_name,
        "quantity_on_package" => $product_1->quantity_on_package,
        "quantity_order" => $product_1->quantity,
        "quantity_return" => 3,
        "unit_price" => $product_1->unit_price - (0 / $product_1->quantity),
        "unit_price_return" => 5000,
        "total" => 5000,
    ]);

    $payment = Payment::factory()->create([
        "invoice_id" => $invoice_2->id,
        "nominal" => 40000,
        "memo_status" => "accepted",
        "credit_memo_id" => $credit_memo->id,
        "is_credit_memo" => true,
    ]);

    DistributorContract::factory()->create([
        "dealer_id" => $order_1->store_id,
    ]);

    $user = User::factory()->create();

    /* origin generator for direct sales */
    (new GenerateSalesOriginFromOrderAction)($order_1);

    /* job on created credit memo */
    (new CreditMemoForOriginJob($credit_memo, $order_1, $user))->handle();

    /* job on canceled memo */
    (new CreditMemoCanceledJob($credit_memo, $user))->handle();

    $product_1->refresh();

    $origin = SalesOrderOrigin::query()
        ->where("product_id", $product_1->product_id)
        ->where("sales_order_detail_id", $product_1->id)
        ->orderBy("created_at")
        ->get();

    expect($origin->count())->toEqual(2);
    expect($origin->first()->stock_ready)->toEqual(7);
    expect($origin->skip(1)->first()->stock_ready)->toEqual(3);
});
