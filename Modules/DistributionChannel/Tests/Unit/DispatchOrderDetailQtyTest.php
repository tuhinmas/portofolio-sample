<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);
ini_set('max_execution_time', 6000); // Set max_execution_time to 60 seconds

/**
 * create
 */
test("dispatch order detail, can create with valid qty", function () {
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

test("dispatch order detail batch, can create with valid qty", function () {
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
        "quantity" => 100,
    ]);
    $order_detail_2 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "quantity" => 50,
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

test("dispatch order detail, can not create with qty dispatch > qty order", function () {
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
        "planned_quantity_unit" => $order_detail->quantity + 10,
    ]);

    $response->assertStatus(422);
});

test("dispatch order detail batch, can not create with qty dispatch > qty order", function () {
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
                "planned_quantity_unit" => $order_detail_2->quantity + 10,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("dispatch order detail, can not create with qty dispatch > qty loaded", function () {

    /**
     * dispatch 1
     */
    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "2",
    ]);

    $receiving_good_detail = ReceivingGoodDetail::factory()->create([
        "receiving_good_id" => $receiving_good->id,
        "status" => "delivered",
        "quantity" => 50,
        "quantity_package" => 0,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
        ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
        ->where("dis.is_active", true)
        ->where("dor.status", "send")
        ->where("rg.delivery_status", "2")
        ->where("rg.id", $receiving_good->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->whereNull("dor.deleted_at")
        ->whereNull("rg.deleted_at")
        ->select("s.*", "i.id as invoice_id", "dis.id as dispatch_order_id", "dor.id as delivery_order_id")
        ->first();

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $receiving_good_detail->product_id,
        "quantity" => 100,
        "quantity_on_package" => 0,
        "package_name" => 0,
        "package_weight" => 0,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $sales_order->dispatch_order_id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 50,
        "quantity_unit" => 50,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 50,
        "planned_quantity_unit" => 50,
    ]);

    /**
     * dispatch 2
     */
    $dispatch_order_2 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_2->id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_2->id,
        "status" => "send",
    ]);

    /**
     * dispatch 3
     */
    $dispatch_order_3 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_3->id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/dispatch-order-detail", [
        "id_dispatch_order" => $dispatch_order_2->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 10,
        "planned_quantity_unit" => 10,
    ]);

    $response->assertStatus(422);
});

test("dispatch order detail batch, can not create with qty dispatch > qty loaded", function () {

    /**
     * dispatch 1
     */
    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "2",
    ]);

    $receiving_good_detail = ReceivingGoodDetail::factory()->create([
        "receiving_good_id" => $receiving_good->id,
        "status" => "delivered",
        "quantity" => 50,
        "quantity_package" => 0,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
        ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
        ->where("dis.is_active", true)
        ->where("dor.status", "send")
        ->where("rg.delivery_status", "2")
        ->where("rg.id", $receiving_good->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->whereNull("dor.deleted_at")
        ->whereNull("rg.deleted_at")
        ->select("s.*", "i.id as invoice_id", "dis.id as dispatch_order_id", "dor.id as delivery_order_id")
        ->first();

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $receiving_good_detail->product_id,
        "quantity" => 100,
        "quantity_on_package" => 0,
        "package_name" => 0,
        "package_weight" => 0,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $sales_order->dispatch_order_id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 50,
        "quantity_unit" => 50,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 50,
        "planned_quantity_unit" => 50,
    ]);

    /**
     * dispatch 2
     */
    $dispatch_order_2 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_2->id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_2->id,
        "status" => "send",
    ]);

    /**
     * dispatch 3
     */
    $dispatch_order_3 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_3->id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 10,
        "planned_quantity_unit" => 10,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/dispatch-order-detail/batch", [
        "resources" => [
            [
                "id_dispatch_order" => $dispatch_order_2->id,
                "id_product" => $order_detail->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => 0,
                "planned_package_weight" => 10,
                "planned_quantity_unit" => 10,
                "status" => "broken",
            ],
            [
                "id_dispatch_order" => $dispatch_order_2->id,
                "id_product" => $order_detail->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => 0,
                "planned_package_weight" => 30,
                "planned_quantity_unit" => 30,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

/**
 * update
 */
test("dispatch order detail, can update with valid qty", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 50,
        "planned_quantity_unit" => 50,
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
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 50,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order_detail->id, [
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 10,
        "quantity_unit" => 10,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => $order_detail->quantity / $order_detail->quantity_on_package,
        "planned_package_weight" => $order_detail->package_weight,
        "planned_quantity_unit" => $order_detail->quantity,
    ]);

    $response->assertStatus(200);
});

test("dispatch order detail batch, can update with valid qty", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 50,
        "planned_quantity_unit" => 50,
    ]);

    $dispatch_order_detail_2 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 100,
        "planned_quantity_unit" => 100,
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

    $order_detail_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 50,
    ]);

    $order_detail_2 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_2->id_product,
        "quantity" => 100,
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
                "planned_package_to_send" => 0,
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
                "planned_package_to_send" => 0,
                "planned_package_weight" => $order_detail_2->package_weight,
                "planned_quantity_unit" => $order_detail_2->quantity,
            ],
        ],
    ]);

    $response->assertStatus(200);
});

test("dispatch order detail, can not update with qty dispatch > qty order", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 50,
        "planned_quantity_unit" => 50,
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
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 50,
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
        "planned_quantity_unit" => $order_detail->quantity + 10,
    ]);

    $response->assertStatus(422);
});

test("dispatch order detail batch, can update with qty dispatch > qty order", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 50,
        "planned_quantity_unit" => 50,
    ]);

    $dispatch_order_detail_2 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 100,
        "planned_quantity_unit" => 100,
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

    $order_detail_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_1->id_product,
        "quantity" => 50,
    ]);

    $order_detail_2 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $dispatch_order_detail_2->id_product,
        "quantity" => 100,
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
                "planned_package_to_send" => 0,
                "planned_package_weight" => $order_detail_1->package_weight,
                "planned_quantity_unit" => $order_detail_1->quantity + 10,
            ],
            $dispatch_order_detail_2->id => [
                "id_dispatch_order" => $dispatch_order->id,
                "id_product" => $order_detail_2->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => 0,
                "planned_package_weight" => $order_detail_2->package_weight,
                "planned_quantity_unit" => $order_detail_2->quantity + 10,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("dispatch order detail, can not update with qty dispatch > qty loaded", function () {

    /**
     * dispatch 1
     */
    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "2",
    ]);

    $receiving_good_detail = ReceivingGoodDetail::factory()->create([
        "receiving_good_id" => $receiving_good->id,
        "status" => "delivered",
        "quantity" => 50,
        "quantity_package" => 0,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
        ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
        ->where("dis.is_active", true)
        ->where("dor.status", "send")
        ->where("rg.delivery_status", "2")
        ->where("rg.id", $receiving_good->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->whereNull("dor.deleted_at")
        ->whereNull("rg.deleted_at")
        ->select("s.*", "i.id as invoice_id", "dis.id as dispatch_order_id", "dor.id as delivery_order_id")
        ->first();

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $receiving_good_detail->product_id,
        "quantity" => 100,
        "quantity_on_package" => 0,
        "package_name" => 0,
        "package_weight" => 0,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $sales_order->dispatch_order_id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 50,
        "quantity_unit" => 50,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 50,
        "planned_quantity_unit" => 50,
    ]);

    /**
     * dispatch 2
     */
    $dispatch_order_2 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_2->id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_2->id,
        "status" => "send",
    ]);

    /**
     * dispatch 3
     */
    $dispatch_order_3 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
    ]);

    $dispatch_order_detail_3 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_3->id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order_detail_3->id, [
        "id_dispatch_order" => $dispatch_order_3->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 40,
        "planned_quantity_unit" => 40,
    ]);

    $response->assertStatus(422);
});

test("dispatch order detail batch, can not update with qty dispatch > qty loaded", function () {

    /**
     * dispatch 1
     */
    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "2",
    ]);

    $receiving_good_detail = ReceivingGoodDetail::factory()->create([
        "receiving_good_id" => $receiving_good->id,
        "status" => "delivered",
        "quantity" => 50,
        "quantity_package" => 0,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
        ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
        ->where("dis.is_active", true)
        ->where("dor.status", "send")
        ->where("rg.delivery_status", "2")
        ->where("rg.id", $receiving_good->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->whereNull("dor.deleted_at")
        ->whereNull("rg.deleted_at")
        ->select("s.*", "i.id as invoice_id", "dis.id as dispatch_order_id", "dor.id as delivery_order_id")
        ->first();

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $receiving_good_detail->product_id,
        "quantity" => 100,
        "quantity_on_package" => 0,
        "package_name" => 0,
        "package_weight" => 0,
    ]);

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $sales_order->dispatch_order_id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 50,
        "quantity_unit" => 50,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 50,
        "planned_quantity_unit" => 50,
    ]);

    /**
     * dispatch 2
     */
    $dispatch_order_2 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
    ]);

    $dispatch_order_detail_2 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_2->id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_2->id,
        "status" => "send",
    ]);

    /**
     * dispatch 3
     */
    $dispatch_order_3 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
    ]);

    $dispatch_order_detail_3 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_3->id,
        "id_product" => $receiving_good_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 0,
        "quantity_unit" => 0,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 10,
        "planned_quantity_unit" => 10,
    ]);

    $response = actingAsSupport()->patchJson("/api/v1/distribution-channel/dispatch-order-detail/batch", [
        "resources" => [
            $dispatch_order_detail_2->id => [
                "id_dispatch_order" => $dispatch_order_2->id,
                "id_product" => $order_detail->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 40,
                "quantity_unit" => 40,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => 0,
                "planned_package_weight" => 40,
                "planned_quantity_unit" => 40,
            ],
            $dispatch_order_detail_3->id => [
                "id_dispatch_order" => $dispatch_order_3->id,
                "id_product" => $order_detail->product_id,
                "quantity_packet_to_send" => 0,
                "package_weight" => 0,
                "quantity_unit" => 0,
                "date_received" => now()->addDays(2),
                "planned_package_to_send" => 0,
                "planned_package_weight" => 20,
                "planned_quantity_unit" => 20,
            ],
        ],
    ]);

    $response->assertStatus(422);
});
