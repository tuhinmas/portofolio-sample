<?php

use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Product;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\DistributionChannel\Actions\GetProductDispatchAction;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * DISPATCH ORDER
 */
test("considered receiving: dispatch order with valid data", function () {
    $delivery_order = DeliveryOrder::factory()->create();

    $invoice = DB::table('invoices as i')
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->join("delivery_orders as deo", "deo.dispatch_order_id", "dis.id")
        ->where("deo.id", $delivery_order->id)
        ->select("i.*", "dis.id as dispatch_order_id")
        ->first();

    $product_1 = Product::factory()->create([
        "name" => "produk test 1",
    ]);
    $product_2 = Product::factory()->create([
        "name" => "produk test 2",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice->sales_order_id,
        "product_id" => $product_1->id,
        "quantity" => 100,
        "quantity_on_package" => 20,
        "package_name" => "dus",
    ]);

    SalesOrderDetail::factory()->create([
        "product_id" => $product_2->id,
        "sales_order_id" => $invoice->sales_order_id,
        "quantity" => 100,
        "quantity_on_package" => 25,
        "package_name" => "dus besar",
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $invoice->dispatch_order_id,
        "id_product" => $product_1->id,
        "quantity_packet_to_send" => 5,
        "package_weight" => 100,
        "quantity_unit" => 100,
        "planned_package_to_send" => 5,
        "planned_package_weight" => 100,
        "planned_quantity_unit" => 100,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $invoice->dispatch_order_id,
        "id_product" => $product_2->id,
        "quantity_packet_to_send" => 4,
        "package_weight" => 100,
        "quantity_unit" => 100,
        "planned_package_to_send" => 4,
        "planned_package_weight" => 100,
        "planned_quantity_unit" => 100,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/considered-receiving-good", [
        "delivery_order_id" => $delivery_order->id,
        "date_received" => now(),
        "products" => [
            [
                "product_id" => $product_1->id,
                "quantity" => 100,
                "quantity_package" => 5,
                "status" => "delivered",
                "note" => null,
            ],
            [
                "product_id" => $product_1->id,
                "quantity" => 0,
                "quantity_package" => 0,
                "status" => "broken",
                "note" => null,
            ],
            [
                "product_id" => $product_1->id,
                "quantity" => 0,
                "quantity_package" => 0,
                "status" => "incorrect",
                "note" => null,
            ],
            [
                "product_id" => $product_2->id,
                "quantity" => 100,
                "quantity_package" => 4,
                "status" => "delivered",
                "note" => null,
            ],
            [
                "product_id" => $product_2->id,
                "quantity" => 0,
                "quantity_package" => 0,
                "status" => "broken",
                "note" => null,
            ],
            [
                "product_id" => $product_2->id,
                "quantity" => 0,
                "quantity_package" => 0,
                "status" => "incorrect",
                "note" => null,
            ],
        ],
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "delivery_order_id",
        "date_received",
        "received_by",
        "delivery_status",
        "note",
    ]);

    expect($response->getData()->data->receiving_good_detail)->toHaveCount(6);
});

test("considered receiving: invalid product", function () {
    $delivery_order = DeliveryOrder::factory()->create();

    $invoice = DB::table('invoices as i')
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->join("delivery_orders as deo", "deo.dispatch_order_id", "dis.id")
        ->where("deo.id", $delivery_order->id)
        ->select("i.*", "dis.id as dispatch_order_id")
        ->first();

    $product_1 = Product::factory()->create([
        "name" => "produk test 1",
    ]);
    $product_2 = Product::factory()->create([
        "name" => "produk test 2",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice->sales_order_id,
        "product_id" => $product_1->id,
        "quantity" => 100,
        "quantity_on_package" => 20,
        "package_name" => "dus",
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $invoice->dispatch_order_id,
        "id_product" => $product_1->id,
        "quantity_packet_to_send" => 5,
        "package_weight" => 100,
        "quantity_unit" => 100,
        "planned_package_to_send" => 5,
        "planned_package_weight" => 100,
        "planned_quantity_unit" => 100,
    ]);


    $response = actingAsSupport()->postJson("/api/v1/considered-receiving-good", [
        "delivery_order_id" => $delivery_order->id,
        "date_received" => now(),
        "products" => [
            [
                "product_id" => $product_2->id,
                "quantity" => 100,
                "quantity_package" => 5,
                "status" => "delivered",
                "note" => null,
            ],
            [
                "product_id" => $product_2->id,
                "quantity" => 0,
                "quantity_package" => 0,
                "status" => "broken",
                "note" => null,
            ],
            [
                "product_id" => $product_2->id,
                "quantity" => 0,
                "quantity_package" => 0,
                "status" => "incorrect",
                "note" => null,
            ]
        ],
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->products[0])->toEqual("produk tidak sesuai muatan");
});

test("considered receiving: invalid product status", function () {
    $delivery_order = DeliveryOrder::factory()->create();

    $invoice = DB::table('invoices as i')
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->join("delivery_orders as deo", "deo.dispatch_order_id", "dis.id")
        ->where("deo.id", $delivery_order->id)
        ->select("i.*", "dis.id as dispatch_order_id")
        ->first();

    $product_1 = Product::factory()->create([
        "name" => "produk test 1",
    ]);
    $product_2 = Product::factory()->create([
        "name" => "produk test 2",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice->sales_order_id,
        "product_id" => $product_1->id,
        "quantity" => 100,
        "quantity_on_package" => 20,
        "package_name" => "dus",
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $invoice->dispatch_order_id,
        "id_product" => $product_1->id,
        "quantity_packet_to_send" => 5,
        "package_weight" => 100,
        "quantity_unit" => 100,
        "planned_package_to_send" => 5,
        "planned_package_weight" => 100,
        "planned_quantity_unit" => 100,
    ]);


    $response = actingAsSupport()->postJson("/api/v1/considered-receiving-good", [
        "delivery_order_id" => $delivery_order->id,
        "date_received" => now(),
        "products" => [
            [
                "product_id" => $product_1->id,
                "quantity" => 100,
                "quantity_package" => 5,
                "status" => "delivered",
                "note" => null,
            ],
            [
                "product_id" => $product_1->id,
                "quantity" => 0,
                "quantity_package" => 0,
                "status" => "delivered",
                "note" => null,
            ],
            [
                "product_id" => $product_1->id,
                "quantity" => 0,
                "quantity_package" => 0,
                "status" => "incorrect",
                "note" => null,
            ]
        ],
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->products[0])->toEqual("status penerimaan tidak sesuai, masing-masing produk harus tiga data dengan status delivered, broken, incorrect");
});

test("considered receiving: invalid product quantity", function () {
    $delivery_order = DeliveryOrder::factory()->create();

    $invoice = DB::table('invoices as i')
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->join("delivery_orders as deo", "deo.dispatch_order_id", "dis.id")
        ->where("deo.id", $delivery_order->id)
        ->select("i.*", "dis.id as dispatch_order_id")
        ->first();

    $product_1 = Product::factory()->create([
        "name" => "produk test 1",
    ]);
    $product_2 = Product::factory()->create([
        "name" => "produk test 2",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice->sales_order_id,
        "product_id" => $product_1->id,
        "quantity" => 100,
        "quantity_on_package" => 20,
        "package_name" => "dus",
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $invoice->dispatch_order_id,
        "id_product" => $product_1->id,
        "quantity_packet_to_send" => 5,
        "package_weight" => 100,
        "quantity_unit" => 100,
        "planned_package_to_send" => 5,
        "planned_package_weight" => 100,
        "planned_quantity_unit" => 100,
    ]);


    $response = actingAsSupport()->postJson("/api/v1/considered-receiving-good", [
        "delivery_order_id" => $delivery_order->id,
        "date_received" => now(),
        "products" => [
            [
                "product_id" => $product_1->id,
                "quantity" => 100,
                "quantity_package" => 6,
                "status" => "delivered",
                "note" => null,
            ],
            [
                "product_id" => $product_1->id,
                "quantity" => 0,
                "quantity_package" => 0,
                "status" => "broken",
                "note" => null,
            ],
            [
                "product_id" => $product_1->id,
                "quantity" => 0,
                "quantity_package" => 0,
                "status" => "incorrect",
                "note" => null,
            ]
        ],
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->products[0])->toEqual("quantity tidak sesuai dispatch");
});

/**
 * DISPATCH PROMOTION
 */
test("considered receiving: dispatch promotion with valid data", function () {
    $delivery_order = DeliveryOrder::factory()->dispatchPromotion()->create();

    $products = (new GetProductDispatchAction)($delivery_order->id);

    $product_data = collect($products)
        ->map(function ($product) {
            return [
                [
                    "product_id" => $product->product_id,
                    "quantity" => $product->sent_unit_quantity,
                    "quantity_package" => $product->sent_package_quantity,
                    "status" => "delivered",
                    "note" => null,
                    "promotion_good_id" => $product->promotion_good_id,
                ],
                [
                    "product_id" => $product->product_id,
                    "quantity" =>0,
                    "quantity_package" => 0,
                    "status" => "broken",
                    "note" => null,
                    "promotion_good_id" => $product->promotion_good_id,
                ],
                [
                    "product_id" => $product->product_id,
                    "quantity" =>0,
                    "quantity_package" => 0,
                    "status" => "incorrect",
                    "note" => null,
                    "promotion_good_id" => $product->promotion_good_id,
                ],
            ];
        })
        ->toArray();

    $response = actingAsSupport()->postJson("/api/v1/considered-receiving-good", [
        "delivery_order_id" => $delivery_order->id,
        "date_received" => now(),
        "products" => $product_data[0],
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "delivery_order_id",
        "date_received",
        "received_by",
        "delivery_status",
        "note",
    ]);

    expect($response->getData()->data->receiving_good_detail)->toHaveCount(3);
});

/**
 * DISPATCH PROMOTION NON PRODUCT
 */
test("considered receiving: dispatch promotion non product with valid data", function () {
    $delivery_order = DeliveryOrder::factory()->dispatchPromotionNonProduct()->create();

    $products = (new GetProductDispatchAction)($delivery_order->id);

    $product_data = collect($products)
        ->map(function ($product) {
            return [
                [
                    "product_id" => $product->product_id,
                    "quantity" => $product->sent_unit_quantity,
                    "quantity_package" => $product->sent_package_quantity,
                    "status" => "delivered",
                    "note" => null,
                    "promotion_good_id" => $product->promotion_good_id,
                ],
                [
                    "product_id" => $product->product_id,
                    "quantity" =>0,
                    "quantity_package" => 0,
                    "status" => "broken",
                    "note" => null,
                    "promotion_good_id" => $product->promotion_good_id,
                ],
                [
                    "product_id" => $product->product_id,
                    "quantity" =>0,
                    "quantity_package" => 0,
                    "status" => "incorrect",
                    "note" => null,
                    "promotion_good_id" => $product->promotion_good_id,
                ],
            ];
        })
        ->toArray();

    $response = actingAsSupport()->postJson("/api/v1/considered-receiving-good", [
        "delivery_order_id" => $delivery_order->id,
        "date_received" => now(),
        "products" => $product_data[0],
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "delivery_order_id",
        "date_received",
        "received_by",
        "delivery_status",
        "note",
    ]);

    expect($response->getData()->data->receiving_good_detail)->toHaveCount(3);
});
