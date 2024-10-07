<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Modules\Invoice\Jobs\CreditMemoJob;
use Modules\Invoice\Entities\CreditMemo;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Invoice\Entities\CreditMemoDetail;
use Modules\Invoice\Entities\CreditMemoHistory;
use Modules\Invoice\Jobs\CreditMemoForOriginJob;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\Distributor\Entities\DistributorContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * CAN CREATE
 */
test("credit memo: can create with valid data, payment_status destination settle", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 1
    ]);
    $invoice_2 = Invoice::factory()->create([
        "total" => 90000,
        "ppn" => 10000,
        "payment_status" => "unpaid"
    ]);
    $payment = Payment::factory()->create([
        "invoice_id" => $invoice_2->id,
        "nominal" => 40000,
    ]);

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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

    Bus::fake();

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 5,
                "unit_price_return" => 6000,
            ], [
                "product_id" => $product_2->product_id,
                "quantity_return" => 2,
                "unit_price_return" => 15000,
            ],
        ],
    ]);

    $response->assertStatus(201);
    $order_2->refresh();
    $memo_history = CreditMemoHistory::query()
        ->where("credit_memo_id", $response->getData()->data->id)
        ->first();

    $payments = DB::table('payments as p')
        ->whereNull("p.deleted_at")
        ->join("invoices as i", "i.id", "p.invoice_id")
        ->where("p.invoice_id", $response->getData()->data->destination_id)
        ->select("p.*", "i.total")
        ->orderByDesc("created_at")
        ->get();

    $status_history = DB::table('sales_order_history_change_statuses')
        ->whereNull("deleted_at")
        ->orderBy("created_at", "desc")
        ->first();

    $remaining_payment = ($invoice_2->total + $invoice_2->ppn) - $payments->sum("nominal");
    $invoice_2->refresh();

    expect($invoice_2->payment_status)->toEqual("settle");
    expect($memo_history)->toBeTruthy();
    expect($memo_history->status)->toBeTruthy();
    expect($memo_history->personel_id)->toBeTruthy();
    expect($memo_history->credit_memo_id)->toBeTruthy();
    expect($order_2->status)->toEqual("returned");
    expect(Carbon::parse($order_2->return)->format("Y-m-d"))->toEqual(Carbon::parse($response->getdata()->data->date)->format("Y-m-d"));

    expect($payments->first())->toBeTruthy();
    expect((int) $payments->first()->remaining_payment)->toEqual((int) $remaining_payment);
    expect($payments->first()->nominal)->toEqual($response->getData()->data->total);
    expect($payments->first()->reference_number)->toEqual($response->getData()->data->number);
    expect($payments->first()->payment_date)->toEqual($response->getData()->data->date);
    expect($payments->first()->is_credit_memo)->toBeTruthy();
    expect($payments->first()->credit_memo_id)->toEqual($response->getData()->data->id);
    expect($payments->first()->memo_status)->toEqual("accepted");

    expect($status_history->status)->toEqual("returned");

    Bus::assertChained([
        CreditMemoForOriginJob::class,
        CreditMemoJob::class,
    ]);
});

test("credit memo: can create with valid data, payment_status destination paid ", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 1
    ]);
    $invoice_2 = Invoice::factory()->create([
        "total" => 90000,
        "ppn" => 10000,
        "payment_status" => "unpaid"
    ]);

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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

    Bus::fake();

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 5,
                "unit_price_return" => 6000,
            ], [
                "product_id" => $product_2->product_id,
                "quantity_return" => 2,
                "unit_price_return" => 15000,
            ],
        ],
    ]);

    $response->assertStatus(201);
    $order_2->refresh();
    $memo_history = CreditMemoHistory::query()
        ->where("credit_memo_id", $response->getData()->data->id)
        ->first();

    $payments = DB::table('payments as p')
        ->whereNull("p.deleted_at")
        ->join("invoices as i", "i.id", "p.invoice_id")
        ->where("p.invoice_id", $response->getData()->data->destination_id)
        ->select("p.*", "i.total")
        ->orderByDesc("created_at")
        ->get();

    $status_history = DB::table('sales_order_history_change_statuses')
        ->whereNull("deleted_at")
        ->orderBy("created_at", "desc")
        ->first();

    $remaining_payment = ($invoice_2->total + $invoice_2->ppn) - $payments->sum("nominal");
    $invoice_2->refresh();

    expect($invoice_2->payment_status)->toEqual("paid");
    expect($memo_history)->toBeTruthy();
    expect($memo_history->status)->toBeTruthy();
    expect($memo_history->personel_id)->toBeTruthy();
    expect($memo_history->credit_memo_id)->toBeTruthy();
    expect($order_2->status)->toEqual("returned");
    expect(Carbon::parse($order_2->return)->format("Y-m-d"))->toEqual(Carbon::parse($response->getdata()->data->date)->format("Y-m-d"));

    expect($payments->first())->toBeTruthy();
    expect((int) $payments->first()->remaining_payment)->toEqual((int) $remaining_payment);
    expect($payments->first()->nominal)->toEqual($response->getData()->data->total);
    expect($payments->first()->reference_number)->toEqual($response->getData()->data->number);
    expect($payments->first()->payment_date)->toEqual($response->getData()->data->date);
    expect($payments->first()->is_credit_memo)->toBeTruthy();
    expect($payments->first()->credit_memo_id)->toEqual($response->getData()->data->id);
    expect($payments->first()->memo_status)->toEqual("accepted");

    expect($status_history->status)->toEqual("returned");

    Bus::assertChained([
        CreditMemoForOriginJob::class,
        CreditMemoJob::class,
    ]);
});

test("credit memo: can create with unsettle origin on same destination", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "unpaid",
        "delivery_status" => 1
    ]);
    $invoice_2 = Invoice::factory()->create();

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_1->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 5,
                "unit_price_return" => 4500,
            ],
            [
                "product_id" => $product_2->product_id,
                "quantity_return" => 2,
                "unit_price_return" => 14000,
            ],
        ],
    ]);

    $response->assertStatus(201);
});

test("credit memo: can create if origin was return and same destination", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 1
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
    $order_1->status = "returned";
    $order_1->save();

    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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

    Bus::fake();

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_1->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 5,
                "unit_price_return" => 6000,
            ], [
                "product_id" => $product_2->product_id,
                "quantity_return" => 2,
                "unit_price_return" => 15000,
            ],
        ],
    ]);

    $response->assertStatus(201);
});

/**
 * CAN NOT CREATE
 */
test("credit memo: can not create with invalid products", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 1
    ]);
    $invoice_2 = Invoice::factory()->create();

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 5,
                "unit_price_return" => 4500,
            ],
            //  [
            //     "product_id" => $product_2->product_id,
            //     "quantity_return" => 2,
            //     "unit_price_return" => 14000,
            // ],
        ],
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toEqual("produk tidak sesuai dengan order proforma asal");
});

test("credit memo: can not create with unsettle origin on different destination", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "unpaid",
        "delivery_status" => 1
    ]);
    $invoice_2 = Invoice::factory()->create();

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 5,
                "unit_price_return" => 4500,
            ],
            [
                "product_id" => $product_2->product_id,
                "quantity_return" => 2,
                "unit_price_return" => 14000,
            ],
        ],
    ]);

    $origin = "memo.origin_id";
    $response->assertStatus(422);
    expect($response->getData()->data->$origin[0])->toBe('proforma asal harus sudah lunas');
});

test("credit memo: can not create store_id not same", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 1
    ]);
    $invoice_2 = Invoice::factory()->create();

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);

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

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 5,
                "unit_price_return" => 4500,
            ],
            [
                "product_id" => $product_2->product_id,
                "quantity_return" => 2,
                "unit_price_return" => 14000,
            ],
        ],
    ]);

    $origin = "memo.destination_id";
    $response->assertStatus(422);
    expect($response->getData()->data->$origin[0])->toBe('proforma tujuan harus di toko yang sama dengan proforma asal');
});

test("credit memo: can not create with unsettle destination", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 1
    ]);
    $invoice_2 = Invoice::factory()->create([
        "payment_status" => "settle",
    ]);

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 5,
                "unit_price_return" => 4500,
            ],
            [
                "product_id" => $product_2->product_id,
                "quantity_return" => 2,
                "unit_price_return" => 14000,
            ],
        ],
    ]);

    $origin = "memo.destination_id";
    $response->assertStatus(422);
    expect($response->getData()->data->$origin[0])->toBe('proforma yang sudah lunas tidak bisa dijadikan tujuan kecuali sama dengan asal');
});

test("credit memo: can not create with invalid quantity return - 1", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 1
    ]);
    $invoice_2 = Invoice::factory()->create();

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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
        "personel_id" => $order_1->personel_id,
        "dealer_id" => $order_1->store_id,
        "origin_id" => $invoice_1->id,
    ]);

    CreditMemoDetail::factory()->create([
        "credit_memo_id" => $credit_memo->id,
        "product_id" => $product_1->product_id,
        "package_name" => $product_1->package_name,
        "quantity_on_package" => $product_1->quantity_on_package,
        "quantity_order" => $product_1->quantity,
        "quantity_return" => 3,
        "unit_price" => $product_1->unit_price - (0 / $product_1->quantity),
        "unit_price_return" => 12000.50,
        "total" => 36001.50,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 8,
                "unit_price_return" => 4500,
            ],
            [
                "product_id" => $product_2->product_id,
                "quantity_return" => 3,
                "unit_price_return" => 14000,
            ],
        ],
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toBe('jumlah return melebihi maksimal yang bisa direturn');
});

test("credit memo: can not create with invalid quantity return - 2", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 1
    ]);
    $invoice_2 = Invoice::factory()->create();

    $order_1 = SalesOrder::findOrFail($invoice_1->sales_order_id);
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 80,
                "unit_price_return" => 4500,
            ],
            [
                "product_id" => $product_2->product_id,
                "quantity_return" => 30,
                "unit_price_return" => 14000,
            ],
        ],
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toBe('jumlah return melebihi maksimal yang bisa direturn');
});

test("credit memo: can not create if higher than distributor stock", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    
    $distributor_contract = DistributorContract::factory()->create();

    $order_1 = SalesOrder::factory()->create([
        "store_id" => $distributor_contract->dealer_id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed"
    ]);
    $order_2 = SalesOrder::factory()->create([
        "store_id" => $distributor_contract->dealer_id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed"
    ]);

    $order_3 = SalesOrder::factory()->create([
        "distributor_id" => $distributor_contract->dealer_id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()
    ]);

    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "sales_order_id" => $order_1->id,
        "delivery_status" => 1
    ]);

    $invoice_2 = Invoice::factory()->create([
        "payment_status" => "unpaid",
        "sales_order_id" => $order_2->id
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

    $product_3 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $order_3->id,
        "quantity" => 4,
        "product_id" =>$product_1->product_id
    ]);

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 10,
                "unit_price_return" => 4500,
            ],
            [
                "product_id" => $product_2->product_id,
                "quantity_return" => 2,
                "unit_price_return" => 14000,
            ],
        ],
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toEqual("maksimal return adalah maksimal stok");
});

test("credit memo: can not create if origin was return and different destination", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 1
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
    $order_1->status = "returned";
    $order_1->save();

    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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

    Bus::fake();

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 5,
                "unit_price_return" => 6000,
            ], [
                "product_id" => $product_2->product_id,
                "quantity_return" => 2,
                "unit_price_return" => 15000,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $origin = "memo.origin_id";
    expect($response->getData()->data->$origin[0])->toBe('proforma asal sudah pernah return, tidak bisa untuk kredit memo proforma lain');
});

test("credit memo: can not create if origin not yet received all", function () {
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
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

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

    Bus::fake();

    $response = actingAsSupport()->postJson("/api/v1/credit-memo", [
        "memo" => [
            "personel_id" => $order_1->personel_id,
            "dealer_id" => $order_1->store_id,
            "origin_id" => $invoice_1->id,
            "destination_id" => $invoice_2->id,
            "date" => now()->addDay(),
            "tax_invoice" => "010.000-24.00000001",
            "reason" => "testing memo",
        ],
        "products" => [
            [
                "product_id" => $product_1->product_id,
                "quantity_return" => 5,
                "unit_price_return" => 6000,
            ], [
                "product_id" => $product_2->product_id,
                "quantity_return" => 2,
                "unit_price_return" => 15000,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $origin = "memo.origin_id";
    expect($response->getData()->data->$origin[0])->toBe('proforma asal belum diterima semua');
});

/**
 * FORM DATA CREDIT MEMO
 */
test("credit memo: form data", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 3
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
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

    $product_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice_1->sales_order_id,
        "discount" => 1000,
        "unit_price" => 5000,
        "quantity" => 10,
        "total" => 50000,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v1/credit-memo-form-data", [
        "invoice_id" => $invoice_1->id,
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "dealer",
        "products",
        "proforma_destination",
    ]);

    expect($response->getData()->data->dealer)->toHaveKeys([
        "customer_id",
        "name",
        "agency_level",
        "owner",
        "address",
    ]);

    $dealer = DB::table('dealers')
        ->where("id", $order_1->store_id)
        ->first();

    expect($response->getData()->data->dealer->customer_id)->toEqual($dealer->dealer_id);
    expect($response->getData()->data->dealer->owner)->toEqual($dealer->owner);

    expect($response->getData()->data->products[0])->toHaveKeys([
        "product_id",
        "product_name",
        "product_size",
        "product_unit",
        "product_category",
        "package_name",
        "quantity_on_package",
        "quantity",
        "unit_price",
        "was_return",
    ]);

    expect($response->getData()->data->products[0]->product_id)->toEqual($product_1->product_id);
    expect($response->getData()->data->products[0]->package_name)->toEqual($product_1->package_name);
    expect($response->getData()->data->products[0]->quantity_on_package)->toEqual($product_1->quantity_on_package);

    expect($response->getData()->data->proforma_destination[0])->toHaveKeys([
        "id",
        "invoice",
    ]);

    expect(count($response->getData()->data->proforma_destination))->toEqual(2);

    $destination_count = collect($response->getData()->data->proforma_destination)
        ->filter(function ($destination) use ($invoice_2, $invoice_1) {
            return in_array($destination->id, [$invoice_1->id, $invoice_2->id]);
        })
        ->count();

    expect($destination_count)->toEqual(2);
});

test("credit memo: form data destination not yet received", function () {
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
    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

    $product_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice_1->sales_order_id,
        "discount" => 1000,
        "unit_price" => 5000,
        "quantity" => 10,
        "total" => 50000,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v1/credit-memo-form-data", [
        "invoice_id" => $invoice_1->id,
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "dealer",
        "products",
        "proforma_destination",
    ]);

    expect($response->getData()->data->dealer)->toHaveKeys([
        "customer_id",
        "name",
        "agency_level",
        "owner",
        "address",
    ]);

    $dealer = DB::table('dealers')
        ->where("id", $order_1->store_id)
        ->first();

    expect($response->getData()->data->dealer->customer_id)->toEqual($dealer->dealer_id);
    expect($response->getData()->data->dealer->owner)->toEqual($dealer->owner);
    expect($response->getData()->data->products[0])->toHaveKeys([
        "product_id",
        "product_name",
        "product_size",
        "product_unit",
        "product_category",
        "package_name",
        "quantity_on_package",
        "quantity",
        "unit_price",
        "was_return",
    ]);

    expect($response->getData()->data->products[0]->product_id)->toEqual($product_1->product_id);
    expect($response->getData()->data->products[0]->package_name)->toEqual($product_1->package_name);
    expect($response->getData()->data->products[0]->quantity_on_package)->toEqual($product_1->quantity_on_package);

    $destination_count = collect($response->getData()->data->proforma_destination)
        ->filter(function ($destination) use ($invoice_2, $invoice_1) {
            return in_array($destination->id, [$invoice_1->id, $invoice_2->id]);
        })
        ->count();

    expect($destination_count)->toEqual(0);
});

test("credit memo: form data with returned origin", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $invoice_1 = Invoice::factory()->create([
        "payment_status" => "settle",
        "delivery_status" => 1
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
    $order_1->status = "returned";
    $order_1->save();

    $order_2 = SalesOrder::findOrFail($invoice_2->sales_order_id);
    $order_2->store_id = $order_1->store_id;
    $order_2->save();

    $product_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice_1->sales_order_id,
        "discount" => 1000,
        "unit_price" => 5000,
        "quantity" => 10,
        "total" => 50000,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v1/credit-memo-form-data", [
        "invoice_id" => $invoice_1->id,
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "dealer",
        "products",
        "proforma_destination",
    ]);

    expect($response->getData()->data->dealer)->toHaveKeys([
        "customer_id",
        "name",
        "agency_level",
        "owner",
        "address",
    ]);

    $dealer = DB::table('dealers')
        ->where("id", $order_1->store_id)
        ->first();

    expect($response->getData()->data->dealer->customer_id)->toEqual($dealer->dealer_id);
    expect($response->getData()->data->dealer->owner)->toEqual($dealer->owner);

    expect($response->getData()->data->products[0])->toHaveKeys([
        "product_id",
        "product_name",
        "product_size",
        "product_unit",
        "product_category",
        "package_name",
        "quantity_on_package",
        "quantity",
        "unit_price",
        "was_return",
    ]);

    expect($response->getData()->data->products[0]->product_id)->toEqual($product_1->product_id);
    expect($response->getData()->data->products[0]->package_name)->toEqual($product_1->package_name);
    expect($response->getData()->data->products[0]->quantity_on_package)->toEqual($product_1->quantity_on_package);

    expect($response->getData()->data->proforma_destination[0])->toHaveKeys([
        "id",
        "invoice",
    ]);

    expect($response->getData()->data->proforma_destination[0]->id)->toEqual($invoice_1->id);

    $destination_count = collect($response->getData()->data->proforma_destination)
        ->filter(function ($destination) use ($invoice_2, $invoice_1) {
            return in_array($destination->id, [$invoice_1->id, $invoice_2->id]);
        })
        ->count();

    expect($destination_count)->toEqual(1);
});
