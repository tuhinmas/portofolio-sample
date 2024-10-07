<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\Package;
use Modules\DataAcuan\Entities\Product;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\Invoice\Entities\Invoice;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * PRODUCT PACKET
 */
test("order detail, qty dispathced must correct, partially received produck package", function () {

    /**
     * qty dispatched total
     * qty received
     * qty already deliverd
     * qty remaining
     * delivery status
     */
    $sales_order = SalesOrder::factory()->create([
        "status" => "confirmed",
        "type" => "1",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "created_at" => now()->subDay(4),
        "updated_at" => now()->subDay(4),
    ]);

    $product = Product::factory()->create();
    $package = Package::factory()->create([
        'product_id' => $product->id,
        'quantity_per_package' => 10,
        'weight' => 10,
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product->id,
        "quantity" => 120,
        "quantity_order" => 140,
        "package_id" => $package->id,
        "package_name" => $package->packaging,
        "quantity_on_package" => $package->quantity_per_package,
        "package_weight" => $package->weight,
    ]);

    /**
     * DISPATCH 1, delivered
     */
    $dispatch_order_1 = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_1->id,
        "id_product" => $sales_order_detail->product_id,
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 4,
        "planned_package_weight" => 40,
        "planned_quantity_unit" => 40,
    ]);

    $delivery_order_1 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_1->id,
        "status" => "send",
    ]);

    /**
     * DISPATCH 2
     */
    $dispatch_order_2 = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    $dispatch_order_detail_2 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_2->id,
        "id_product" => $sales_order_detail->product_id,
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 4,
        "planned_package_weight" => 40,
        "planned_quantity_unit" => 40,
    ]);

    /**
     * DISPATCH 3, receoived
     */
    $dispatch_order = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $sales_order_detail->product_id,
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 3,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $receiving_detail = ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "receiving_good_id" => $receiving_good->id,
        "quantity" => 10,
        "quantity_package" => 1,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v1/sales-order-detail/sales-order-detail", [
        "sales_order_id" => $sales_order_detail->sales_order_id,
    ]);

    $data = $response->getData()->data[0];

    expect($data->quantity_unit_received)->toEqual(10);
    expect($data->quantity_package_received)->toEqual(1);
    expect($data->quantity_unit_loaded)->toEqual(70);
    expect($data->quantity_package_loaded)->toEqual(7);
    expect($data->load_weight)->toEqual(120);
    expect($data->sent_unit)->toEqual(40);
    expect($data->sent_package_unit)->toEqual(4);
    expect($data->remaining_unit)->toEqual(5);
    expect($data->delivery_status)->toEqual("undone");
    $response->assertStatus(200);
});

test("order detail, qty dispathced must correct, full received produck package", function () {

    /**
     * qty dispatched total
     * qty received
     * qty already deliverd
     * qty remaining
     * delivery status
     */
    $sales_order = SalesOrder::factory()->create([
        "status" => "confirmed",
        "type" => "1",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "created_at" => now()->subDay(4),
        "updated_at" => now()->subDay(4),
    ]);

    $product = Product::factory()->create();
    $package = Package::factory()->create([
        'product_id' => $product->id,
        'quantity_per_package' => 10,
        'weight' => 10,
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product->id,
        "quantity" => 120,
        "quantity_order" => 140,
        "package_id" => $package->id,
        "package_name" => $package->packaging,
        "quantity_on_package" => $package->quantity_per_package,
        "package_weight" => $package->weight,
    ]);

    $dispatch_order_1 = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id
    ]);

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_1->id,
        "id_product" => $sales_order_detail->product_id,
        "quantity_packet_to_send" => 11,
        "package_weight" => 110,
        "quantity_unit" => 110,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 11,
        "planned_package_weight" => 110,
        "planned_quantity_unit" => 110,
    ]);

    $dispatch_order = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
        "is_active" => true
    ]);

    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $sales_order_detail->product_id,
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 3,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "status" => "send",
    ]);

    $delivery_order_2 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_1->id,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);
    $receiving_good_2 = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order_2->id,
        "delivery_status" => "2", // received
    ]);

    $receiving_detail = ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "receiving_good_id" => $receiving_good->id,
        "quantity" => 10,
        "quantity_package" => 1,
    ]);
    $receiving_detail_2 = ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail_1->id_product,
        "receiving_good_id" => $receiving_good_2->id,
        "quantity" => 110,
        "quantity_package" => 11,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v1/sales-order-detail/sales-order-detail", [
        "sales_order_id" => $sales_order_detail->sales_order_id,
    ]);

    $data = $response->getData()->data[0];
    expect($data->quantity_unit_received)->toEqual(120);
    expect($data->quantity_package_received)->toEqual(12);
    expect($data->quantity_unit_loaded)->toEqual(120);
    expect($data->quantity_package_loaded)->toEqual(12);
    expect($data->load_weight)->toEqual(120);
    expect($data->sent_unit)->toEqual(130);
    expect($data->sent_package_unit)->toEqual(13);
    expect($data->remaining_unit)->toEqual(0);
    expect($data->delivery_status)->toEqual("done");
    $response->assertStatus(200);
});

/**
 * PRODUCT NON PACKET
 */
test("order detail, qty dispathced must correct, partially received produck non package", function () {

    /**
     * qty dispatched total
     * qty received
     * qty already deliverd
     * qty remaining
     * delivery status
     */
    $sales_order = SalesOrder::factory()->create([
        "status" => "confirmed",
        "type" => "1",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "created_at" => now()->subDay(4),
        "updated_at" => now()->subDay(4),
    ]);

    $product = Product::factory()->create([
        'weight' => 0.5
    ]);
    $package = Package::factory()->create([
        'product_id' => $product->id,
        'quantity_per_package' => 10,
        'weight' => 10,
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product->id,
        "quantity" => 120,
        "quantity_order" => 140,
        "package_id" => null,
        "package_name" => null,
        "quantity_on_package" => null,
        "package_weight" => null,
    ]);

    /**
     * DISPATCH 1, delivered
     */
    $dispatch_order_1 = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_1->id,
        "id_product" => $sales_order_detail->product_id,
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 4,
        "planned_package_weight" => 40,
        "planned_quantity_unit" => 40,
    ]);

    $delivery_order_1 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_1->id,
        "status" => "send",
    ]);

    /**
     * DISPATCH 2
     */
    $dispatch_order_2 = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    $dispatch_order_detail_2 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_2->id,
        "id_product" => $sales_order_detail->product_id,
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 4,
        "planned_package_weight" => 40,
        "planned_quantity_unit" => 40,
    ]);

    /**
     * DISPATCH 3, receoived
     */
    $dispatch_order = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $sales_order_detail->product_id,
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 3,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);

    $receiving_detail = ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "receiving_good_id" => $receiving_good->id,
        "quantity" => 10,
        "quantity_package" => 1,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v1/sales-order-detail/sales-order-detail", [
        "sales_order_id" => $sales_order_detail->sales_order_id,
    ]);

    $data = $response->getData()->data[0];

    expect($data->quantity_unit_received)->toEqual(10);
    expect($data->quantity_package_received)->toEqual(1);

    /* total diaptched and received */
    expect($data->quantity_unit_loaded)->toEqual(70);
    expect($data->quantity_package_loaded)->toEqual(7);
    expect($data->load_weight)->toEqual(60);

    /* qty has delivery order */
    expect($data->sent_unit)->toEqual(40);
    expect($data->sent_package_unit)->toEqual(4);

    /* qty remaining to dispatch */
    expect($data->remaining_unit)->toEqual(50);
    expect($data->delivery_status)->toEqual("undone");
    $response->assertStatus(200);
});

test("order detail, qty dispathced must correct, full received produck non package", function () {

    /**
     * qty dispatched total
     * qty received
     * qty already deliverd
     * qty remaining
     * delivery status
     */
    $sales_order = SalesOrder::factory()->create([
        "status" => "confirmed",
        "type" => "1",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "created_at" => now()->subDay(4),
        "updated_at" => now()->subDay(4),
    ]);

    $product = Product::factory()->create();
    $package = Package::factory()->create([
        'product_id' => $product->id,
        'quantity_per_package' => 10,
        'weight' => 10,
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product->id,
        "quantity" => 120,
        "quantity_order" => 140,
        "package_id" => null,
        "package_name" => null,
        "quantity_on_package" => null,
        "package_weight" => null,
    ]);

    $dispatch_order_1 = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
    ]);

    $dispatch_order_detail_1 = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order_1->id,
        "id_product" => $sales_order_detail->product_id,
        "quantity_packet_to_send" => 11,
        "package_weight" => 110,
        "quantity_unit" => 110,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 11,
        "planned_package_weight" => 110,
        "planned_quantity_unit" => 110,
    ]);

    $dispatch_order = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "id_product" => $sales_order_detail->product_id,
        "quantity_packet_to_send" => 2,
        "package_weight" => 20,
        "quantity_unit" => 20,
        "date_received" => now()->addDays(2),
        "planned_package_to_send" => 3,
        "planned_package_weight" => 30,
        "planned_quantity_unit" => 30,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "status" => "send",
    ]);

    $delivery_order_2 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_1->id,
        "status" => "send",
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2", // received
    ]);
    $receiving_good_2 = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order_2->id,
        "delivery_status" => "2", // received
    ]);

    $receiving_detail = ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "receiving_good_id" => $receiving_good->id,
        "quantity" => 10,
        "quantity_package" => 1,
    ]);
    $receiving_detail_2 = ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail_1->id_product,
        "receiving_good_id" => $receiving_good_2->id,
        "quantity" => 110,
        "quantity_package" => 11,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v1/sales-order-detail/sales-order-detail", [
        "sales_order_id" => $sales_order_detail->sales_order_id,
    ]);

    $data = $response->getData()->data[0];
    expect($data->quantity_unit_received)->toEqual(120);
    expect($data->quantity_package_received)->toEqual(12);
    expect($data->quantity_unit_loaded)->toEqual(120);
    expect($data->quantity_package_loaded)->toEqual(12);
    expect($data->load_weight)->toEqual(60);
    expect($data->sent_unit)->toEqual(130);
    expect($data->sent_package_unit)->toEqual(13);
    expect($data->remaining_unit)->toEqual(0);
    expect($data->delivery_status)->toEqual("done");
    $response->assertStatus(200);
});
