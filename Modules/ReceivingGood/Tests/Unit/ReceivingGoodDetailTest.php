<?php

use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Product;
use Modules\PromotionGood\Entities\PromotionGood;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\PromotionGood\Entities\DispatchPromotion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\PromotionGood\Entities\PromotionGoodRequest;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("receiving good detail, can create with valid data", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail", [
        "product_id" => $dispatch_order_detail_1->id_product,
        "receiving_good_id" => $receiving_good->id,
        "quantity" => $dispatch_order_detail_1->quantity_unit,
        "status" => "delivered",
        "note" => "barang sesuai",
        "user_id" => auth()->id(),
    ]);
    
    $response->assertStatus(201);
});

test("receiving good detail batch, can create with valid data", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
        "resources" => [
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 10,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 5,
                "status" => "broken",
                "note" => "barang rusak",
                "user_id" => auth()->id(),
            ],
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 5,
                "status" => "incorrect",
                "note" => "barang salah",
                "user_id" => auth()->id(),
            ],
        ]
    ]);

    $response->assertStatus(200);
});

test("receiving good detail, can not create without product_id or promotion_good_id", function () {

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "2", // received
    ]);

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail", [
        "receiving_good_id" => $receiving_good->id,
        "quantity" => 1,
        "status" => "delivered",
        "note" => "barang sesuai",
        "user_id" => auth()->id(),
    ]);

    $response->assertStatus(422);
});

test("receiving good detail, can not create with product_id and promotion_good_id", function () {

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "2", // received
    ]);
    $product = Product::factory()->create();
    $promotion_good = PromotionGood::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail", [
        "receiving_good_id" => $receiving_good->id,
        "product_id" => $product->id,
        "promotion_good_id" => $promotion_good->id,
        "quantity" => 1,
        "status" => "delivered",
        "note" => "barang sesuai",
        "user_id" => auth()->id(),
    ]);

    $response->assertStatus(422);
});

test("receiving good detail, can not create with empty product_id and promotion_good_id", function () {

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "2", // received
    ]);
    $product = Product::factory()->create();
    $promotion_good = PromotionGood::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail", [
        "receiving_good_id" => $receiving_good->id,
        "product_id" => null,
        "promotion_good_id" => "",
        "quantity" => 1,
        "status" => "delivered",
        "note" => "barang sesuai",
        "user_id" => auth()->id(),
    ]);

    $response->assertStatus(422);
});

test("receiving good detail batch, can not create without product_id or promotion_good_id", function () {

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
        "resources" => [
            [
                "receiving_good_id" => $receiving_good->id,
                "product_id" => $product->id,
                "quantity" => 1,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 1,
                "status" => "incorrect",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("receiving good detail batch, can not create with product_id and promotion_good_id", function () {

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();
    $promotion_good = PromotionGood::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
        "resources" => [
            [
                "receiving_good_id" => $receiving_good->id,
                "product_id" => $product->id,
                "promotion_good_id" => $promotion_good->id,
                "product_id" => $product->id,
                "quantity" => 1,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "receiving_good_id" => $receiving_good->id,
                "product_id" => $product->id,
                "promotion_good_id" => $promotion_good->id,
                "quantity" => 1,
                "status" => "incorrect",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("receiving good detail batch, can not create with empty product_id and promotion_good_id", function () {

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();
    $promotion_good = PromotionGood::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
        "resources" => [
            [
                "receiving_good_id" => $receiving_good->id,
                "product_id" => "",
                "promotion_good_id" => "",
                "quantity" => 1,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "receiving_good_id" => $receiving_good->id,
                "product_id" => "",
                "promotion_good_id" => "",
                "quantity" => 1,
                "status" => "incorrect",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("receiving good detail, delivery order direct sales can not receive as promotion good", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
        "is_promotion" => false
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();
    $promotion_good = PromotionGood::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail", [
        "receiving_good_id" => $receiving_good->id,
        "promotion_good_id" => $promotion_good->id,
        "quantity" => $dispatch_order_detail_1->quantity_unit,
        "status" => "delivered",
        "note" => "barang sesuai",
        "user_id" => auth()->id(),
    ]);

    $response->assertStatus(422);
});

test("receiving good detail, delivery order promotion good can not receive as direct sales", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $promotion_good_request = PromotionGoodRequest::factory()->create();
    $promotion_good = PromotionGood::factory()->create([
        "promotion_good_request_id" => $promotion_good_request->id,
    ]);

    $dispatch_promotion = DispatchPromotion::factory()->create([
        "promotion_good_request_id" => $promotion_good_request->id,
    ]);
    $delivery_order = DeliveryOrder::factory()->create([
        "status" => "send",
        "is_promotion" => true,
        "dispatch_promotion_id" => $dispatch_promotion->id
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail", [
        "receiving_good_id" => $receiving_good->id,
        "product_id" => $product->id,
        "quantity" => $dispatch_order_detail_1->quantity_unit,
        "status" => "delivered",
        "note" => "barang sesuai",
        "user_id" => auth()->id(),
    ]);

    $response->assertStatus(422);
});

test("receiving good detail batch, delivery order direct sales can not receive as promotion good", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
        "is_promotion" => false
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();
    $promotion_good = PromotionGood::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
       "resources" => [
            [
                "receiving_good_id" => $receiving_good->id,
                "product_id" => $product->id,
                "quantity" => $dispatch_order_detail_1->quantity_unit,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "receiving_good_id" => $receiving_good->id,
                "promotion_good_id" => $promotion_good->id,
                "quantity" => $dispatch_order_detail_1->quantity_unit,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "receiving_good_id" => $receiving_good->id,
                "promotion_good_id" => $promotion_good->id,
                "quantity" => $dispatch_order_detail_1->quantity_unit,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ]
       ]
    ]);

    $response->assertStatus(422);
});

test("receiving good detail batch, delivery order promotion good can not receive as direct sales", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $promotion_good_request = PromotionGoodRequest::factory()->create();
    $promotion_good = PromotionGood::factory()->create([
        "promotion_good_request_id" => $promotion_good_request->id,
    ]);

    $dispatch_promotion = DispatchPromotion::factory()->create([
        "promotion_good_request_id" => $promotion_good_request->id,
    ]);
    $delivery_order = DeliveryOrder::factory()->create([
        "status" => "send",
        "is_promotion" => true,
        "dispatch_promotion_id" => $dispatch_promotion->id
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
       "resources" => [
            [
                "receiving_good_id" => $receiving_good->id,
                "product_id" => $product->id,
                "quantity" => $dispatch_order_detail_1->quantity_unit,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "receiving_good_id" => $receiving_good->id,
                "product_id" => $product->id,
                "quantity" => $dispatch_order_detail_1->quantity_unit,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "receiving_good_id" => $receiving_good->id,
                "promotion_good_id" => $promotion_good->id,
                "quantity" => $dispatch_order_detail_1->quantity_unit,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
       ]
    ]);

    $response->assertStatus(422);
});

test("receiving good detail, can not create with product_id not in dispatch order detail on non promotion good", function () {
    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
        "is_promotion" => false
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail", [
        "product_id" => $product->id,
        // "product_id" => $dispatch_order_detail_1->id_product,
        "receiving_good_id" => $receiving_good->id,
        "quantity" => $dispatch_order_detail_1->quantity_unit,
        "status" => "delivered",
        "note" => "barang sesuai",
        "user_id" => auth()->id(),
    ]);

    $response->assertStatus(422);
});

test("receiving good detail, can create with product_id in dispatch order detail on non promotion good", function () {
    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
        "is_promotion" => false
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail", [
        // "product_id" => $product->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "receiving_good_id" => $receiving_good->id,
        "quantity" => $dispatch_order_detail_1->quantity_unit,
        "status" => "delivered",
        "note" => "barang sesuai",
        "user_id" => auth()->id(),
    ]);

    $response->assertStatus(201);
});

test("receiving good detail batch, can not create with product_id not in dispatch order detail on non promotion good", function () {
    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
        "is_promotion" => false
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
        "resources" => [
            [
                "product_id" => $product->id,
                // "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => $dispatch_order_detail_1->quantity_unit,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "product_id" => $product->id,
                // "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => $dispatch_order_detail_1->quantity_unit,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
        ]
    ]);

    $response->assertStatus(422);
});

test("receiving good detail batch, can create with product_id in dispatch order detail on non promotion good", function () {
    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
        "is_promotion" => false,
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $product = Product::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
        "resources" => [
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 1,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 1,
                "status" => "broken",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
        ],
    ]);

    $response->assertStatus(200);
});

/**
 * maximum quantity
 */
test("receiving good detail, can create with valid qty", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail", [
        "product_id" => $dispatch_order_detail_1->id_product,
        "receiving_good_id" => $receiving_good->id,
        "quantity" => $dispatch_order_detail_1->quantity_unit,
        "status" => "delivered",
        "note" => "barang sesuai",
        "user_id" => auth()->id(),
    ]);
    
    $response->assertStatus(201);
});

test("receiving good detail batch, can create with valid qty", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
        "resources" => [
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 10,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 5,
                "status" => "broken",
                "note" => "barang rusak",
                "user_id" => auth()->id(),
            ],
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 5,
                "status" => "incorrect",
                "note" => "barang salah",
                "user_id" => auth()->id(),
            ],
        ]
    ]);

    $response->assertStatus(200);
});

test("receiving good detail, can not create with invalid qty", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail", [
        "product_id" => $dispatch_order_detail_1->id_product,
        "receiving_good_id" => $receiving_good->id,
        "quantity" => 21,
        "status" => "delivered",
        "note" => "barang sesuai",
        "user_id" => auth()->id(),
    ]);
    
    $response->assertStatus(422);
});

test("receiving good detail batch, can not create with invalid qty", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
        "resources" => [
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 21,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 5,
                "status" => "broken",
                "note" => "barang rusak",
                "user_id" => auth()->id(),
            ],
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 5,
                "status" => "incorrect",
                "note" => "barang salah",
                "user_id" => auth()->id(),
            ],
        ]
    ]);

    $response->assertStatus(422);
});

test("receiving good detail batch, can not create with invalid qty accumulation", function () {

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 2,
        "planned_package_weight" => 20,
        "planned_quantity_unit" => 20,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_detail_1->id_dispatch_order)
        ->select("s.*")
        ->first();

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 20,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail_1->id_dispatch_order,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-detail/batch", [
        "resources" => [
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 19,
                "status" => "delivered",
                "note" => "barang sesuai",
                "user_id" => auth()->id(),
            ],
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 10,
                "status" => "broken",
                "note" => "barang rusak",
                "user_id" => auth()->id(),
            ],
            [
                "product_id" => $dispatch_order_detail_1->id_product,
                "receiving_good_id" => $receiving_good->id,
                "quantity" => 10,
                "status" => "incorrect",
                "note" => "barang salah",
                "user_id" => auth()->id(),
            ],
        ]
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->messages[0])->toEqual("can not create receiving good detail, max quantity is 20 according dispatch");
});

