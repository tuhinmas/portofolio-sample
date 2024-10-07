<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can get index sales order detail", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->getJson("/api/v1/sales-order-detail/sales-order-detail", [
        "sales_order_id" => $sales_order->id,
    ]);

    $response->assertStatus(200);
});

test("batch update quantity direct from draft", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "draft",
    ]);

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->patchJson("/api/v2/sales-order/sales-order-detail/batch", [
        "resources" => [
            $order_detail->id => [
                "sales_order_id" => $sales_order->id,
                "product_id" => $order_detail->product_id,
                "quantity" => 12,
                "only_unit" => true,
            ],
        ],
    ]);

    $response->assertStatus(200);
});

test("return direct v2, update quantity return and total", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "returned",
    ]);

    $order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "quantity" => 100,
    ]);

    $response = actingAsSupport()->patchJson("/api/v2/sales-order/sales-order-detail/batch", [
        "resources" => [
            $order_detail->id => [
                "sales_order_id" => $sales_order->id,
                "product_id" => $order_detail->product_id,
                "returned_quantity" => 50,
                "total" => $order_detail->unit_price * ($order_detail->quantity - 50),
                "only_unit" => true,
            ],
        ],
    ]);

    $total = $order_detail->unit_price * ($order_detail->quantity - 50);
    $sub_total = $order_detail->unit_price * ($order_detail->quantity - 50);
    $order_detail->refresh();
    $response->assertStatus(200);
    expect((int) $order_detail->total)->toEqual($total);
    expect((int) $order_detail->sub_total)->toEqual($sub_total);
});
