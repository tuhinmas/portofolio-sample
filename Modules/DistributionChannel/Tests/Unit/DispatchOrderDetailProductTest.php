<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Product;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * create
 */
test("dispatch ordee detail, can create with valid data", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*")
        ->first();

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/dispatch-order-detail", [
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => $order_detail->quantity / $order_detail->quantity_on_package,
        "planned_package_weight" => $order_detail->package_weight,
        "planned_quantity_unit" => $order_detail->quantity,
    ]);

    $response->assertStatus(201);
});

test("dispatch ordee detail batch, can create with valid data", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*")
        ->first();

    $order_detail_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);
    $order_detail_2 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/dispatch-order-detail/batch", [
        "resources" => [
            [
                "id_dispatch_order" => $dispatch_order->id,
                "id_product" => $order_detail_1->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => $order_detail_1->quantity / $order_detail_1->quantity_on_package,
                "planned_package_weight" => $order_detail_1->package_weight,
                "planned_quantity_unit" => $order_detail_1->quantity,
            ],
            [
                "id_dispatch_order" => $dispatch_order->id,
                "id_product" => $order_detail_2->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => $order_detail_2->quantity / $order_detail_2->quantity_on_package,
                "planned_package_weight" => $order_detail_2->package_weight,
                "planned_quantity_unit" => $order_detail_2->quantity,
            ],
        ],
    ]);

    $response->assertStatus(200);
});

test("dispatch ordee detail, can not create with invalid product", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*")
        ->first();

    $product_1 = Product::factory()->create();
    $product_2 = Product::factory()->create();
    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product_1->id,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/dispatch-order-detail", [
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $product_2->id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => $order_detail->quantity / $order_detail->quantity_on_package,
        "planned_package_weight" => $order_detail->package_weight,
        "planned_quantity_unit" => $order_detail->quantity,
    ]);

    $response->assertStatus(422);
});

test("dispatch ordee detail batch, can not create with invalid product", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*")
        ->first();

    $order_detail_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);
    $order_detail_2 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $product = Product::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/dispatch-order-detail/batch", [
        "resources" => [
            [
                "id_dispatch_order" => $dispatch_order->id,
                "id_product" => $product->id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => $order_detail_1->quantity / $order_detail_1->quantity_on_package,
                "planned_package_weight" => $order_detail_1->package_weight,
                "planned_quantity_unit" => $order_detail_1->quantity,
            ],
            [
                "id_dispatch_order" => $dispatch_order->id,
                "id_product" => $order_detail_2->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => $order_detail_2->quantity / $order_detail_2->quantity_on_package,
                "planned_package_weight" => $order_detail_2->package_weight,
                "planned_quantity_unit" => $order_detail_2->quantity,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

/**
 * update
 */
test("dispatch order detail, can update with valid data", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
    ]);
    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*")
        ->first();

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order_detail->id, [
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => $order_detail->quantity / $order_detail->quantity_on_package,
        "planned_package_weight" => $order_detail->package_weight,
        "planned_quantity_unit" => $order_detail->quantity,
    ]);

    $response->assertStatus(200);
});

test("dispatch order detail batch, can update with valid data", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*")
        ->first();

    $order_detail_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);
    $order_detail_2 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $order_detail_1->product_id,
    ]);

    $dispatch_order_detail_2 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $order_detail_2->product_id,
    ]);

    $response = actingAsSupport()->patchJson("/api/v1/distribution-channel/dispatch-order-detail/batch", [
        "resources" => [
            $dispatch_order_detail_1->id => [
                "id_dispatch_order" => $dispatch_order->id,
                "id_product" => $order_detail_1->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => $order_detail_1->quantity / $order_detail_1->quantity_on_package,
                "planned_package_weight" => $order_detail_1->package_weight,
                "planned_quantity_unit" => $order_detail_1->quantity,
            ],
            $dispatch_order_detail_2->id => [
                "id_dispatch_order" => $dispatch_order->id,
                "id_product" => $order_detail_2->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => $order_detail_2->quantity / $order_detail_2->quantity_on_package,
                "planned_package_weight" => $order_detail_2->package_weight,
                "planned_quantity_unit" => $order_detail_2->quantity,
            ],
        ],
    ]);

    $response->assertStatus(200);
});

test("dispatch order detail, can not update with invalid product", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
    ]);
    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*")
        ->first();

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $product = Product::factory()->create();
    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order_detail->id, [
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $product->id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => $order_detail->quantity / $order_detail->quantity_on_package,
        "planned_package_weight" => $order_detail->package_weight,
        "planned_quantity_unit" => $order_detail->quantity,
    ]);

    $response->assertStatus(422);
});

test("dispatch order detail batch, can update with invalid product", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*")
        ->first();

    $order_detail_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);
    $order_detail_2 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $order_detail_1->product_id,
    ]);

    $dispatch_order_detail_2 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $order_detail_2->product_id,
    ]);

    $product = Product::factory()->create();
    $response = actingAsSupport()->patchJson("/api/v1/distribution-channel/dispatch-order-detail/batch", [
        "resources" => [
            $dispatch_order_detail_1->id => [
                "id_dispatch_order" => $dispatch_order->id,
                "id_product" => $product->id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => $order_detail_1->quantity / $order_detail_1->quantity_on_package,
                "planned_package_weight" => $order_detail_1->package_weight,
                "planned_quantity_unit" => $order_detail_1->quantity,
            ],
            $dispatch_order_detail_2->id => [
                "id_dispatch_order" => $dispatch_order->id,
                "id_product" => $order_detail_2->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => $order_detail_2->quantity / $order_detail_2->quantity_on_package,
                "planned_package_weight" => $order_detail_2->package_weight,
                "planned_quantity_unit" => $order_detail_2->quantity,
            ],
        ],
    ]);

    $response->assertStatus(422);
});
