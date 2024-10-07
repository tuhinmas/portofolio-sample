<?php

use Modules\SalesOrder\Entities\SalesOrderDetail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);
ini_set('max_execution_time', 6000); // Set max_execution_time to 60 seconds

test("dispatch order, can activate, if quantity loaded is less than quantity order - 1", function () {
    $dispatch_order_1 = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_1->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*", "i.id as invoice_id")
        ->first();

    $dispatch_order_2 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
        "is_active" => false,
    ]);

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "quantity" => 100,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_1->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 50,
        "quantity_unit" => 50,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 50,
        "planned_quantity_unit" => 50,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_2->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 50,
        "quantity_unit" => 50,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 50,
        "planned_quantity_unit" => 50,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order_2->id, [
        "is_active" => 1,
    ]);

    $response->assertStatus(200);
});

test("dispatch order, can activate, if quantity loaded is less than quantity order - 2", function () {
    $dispatch_order_1 = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_1->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*", "i.id as invoice_id")
        ->first();

    $dispatch_order_2 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
        "is_active" => false,
    ]);

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "quantity" => 50,
    ]);

    /**
     * DOD 1
     */
    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_1->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 40,
        "quantity_unit" => 40,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 40,
        "planned_quantity_unit" => 40,
    ]);

    
    /**
     * DOD 2
     */
    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_2->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 10,
        "quantity_unit" => 10,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 10,
        "planned_quantity_unit" => 10,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order_2->id, [
        "is_active" => 1,
    ]);

    $response->assertStatus(200);
});

test("dispatch order, can not activate, if quantity loaded is higher than quantity order", function () {
    $dispatch_order_1 = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $sales_order = DB::table('sales_orders as s')
        ->join("invoices as i", "i.sales_order_id", "s.id")
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->where("dis.id", $dispatch_order_1->id)
        ->whereNull("s.deleted_at")
        ->whereNull("i.deleted_at")
        ->whereNull("dis.deleted_at")
        ->select("s.*", "i.id as invoice_id")
        ->first();

    $dispatch_order_2 = DispatchOrder::factory()->create([
        "invoice_id" => $sales_order->invoice_id,
        "is_active" => false,
    ]);

    DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_2->id
    ]);

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "quantity" => 50,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_1->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 40,
        "quantity_unit" => 40,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 40,
        "planned_quantity_unit" => 40,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_2->id,
        "id_product" => $order_detail->product_id,
        "quantity_packet_to_send" => 0,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 0,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order_2->id, [
        "is_active" => 1,
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->is_active[0])->toEqual("can not activate dispatch order, quantity loaded is higher than quantity order");
});
