<?php

use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * UPDATE
 */
test("dispatch order detail, can update with valid adata", function () {
    $dispatch_order_detail = DispatchOrderDetail::factory()->create();
    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order_detail->id, [
        "quantity_unit" => 10,
    ]);
    $response->assertStatus(200);
});

test("dispatch order detail, can not update if there valid pickup", function () {
    $dispatch_order_detail = DispatchOrderDetail::factory()->create();
    PickupOrderDispatch::factory()->create([
        "dispatch_id" => $dispatch_order_detail->id_dispatch_order
    ]);
    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order_detail->id, [
        "quantity_unit" => 10,
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toEqual("Dispatch order detail tidak bisa di hapus karena sudah dipickup");
});

/**
 * can Delete
 */
test("dispatch order detail, can delete with valid adata", function () {
    $dispatch_order_detail = DispatchOrderDetail::factory()->create();

    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order_detail->id);
    $response->assertStatus(200);
});

test("dispatch order detail batch, can delete with valid adata", function () {
    $dispatch_order_detail = DispatchOrderDetail::factory()->create();

    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order-detail/batch", [
        "resources" => [$dispatch_order_detail->id],
    ]);
    $response->assertStatus(200);
});

/**
 * can not Delete
 */
test("can not delete, if there receiving good detail", function () {
    $dispatch_order_detail = DispatchOrderDetail::factory()->create();

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail->id_dispatch_order,
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order,
        "delivery_status" => "2",
    ]);

    ReceivingGoodDetail::factory()->create([
        "receiving_good_id" => $receiving_good->id,
        "product_id" => $dispatch_order_detail->id_product,
    ]);
    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order_detail->id);
    $response->assertStatus(422);
});

test("can not delete, if there pickup order", function () {
    $dispatch_order_detail = DispatchOrderDetail::factory()->create();

    PickupOrderDispatch::factory()->create([
        "dispatch_id" => $dispatch_order_detail->id_dispatch_order
    ]);
    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order_detail->id);
    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toEqual("Dispatch order detail tidak bisa di hapus karena sudah dipickup");
});

test("can not delete batch, if there receiving good detail", function () {
    $dispatch_order_detail = DispatchOrderDetail::factory()->create();

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order_detail->id_dispatch_order,
    ]);

    $receiving_good = ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order,
        "delivery_status" => "2",
    ]);

    ReceivingGoodDetail::factory()->create([
        "receiving_good_id" => $receiving_good->id,
        "product_id" => $dispatch_order_detail->id_product,
    ]);
    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order-detail/batch", [
        "resources" => [$dispatch_order_detail->id],
    ]);
    $response->assertStatus(422);
});
