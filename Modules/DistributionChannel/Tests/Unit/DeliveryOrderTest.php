<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DeliveryOrderNumber;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Observers\DeliveryOrderObserver;
use Modules\PromotionGood\Entities\DispatchPromotion;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\SalesOrder\Entities\SalesOrder;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * Create
 * DIRECT
 */
test("delivery order direct, can create with valid payload", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now(),
        "is_promotion" => false,
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    $response->assertStatus(201);
    expect($response->getData()->data->delivery_order_number)->toEqual($delivery->delivery_order_number);

    $history = DB::table('delivery_order_histories')
        ->where("delivery_order_id", $response->getData()->data->id)
        ->where("status", "accepted")
        ->first();

    expect($history)->not->toBeNull();
});

test("delivery order direct, can create if does not have status send", function () {
    DeliveryOrderObserver::$enabled = false;
    $dispatch_order = DispatchOrder::factory()->create();
    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "status" => "canceled",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now(),
        "is_promotion" => false,
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    $response->assertStatus(201);
    expect($response->getData()->data->delivery_order_number)->toEqual($delivery->delivery_order_number);
});

test("delivery order direct, can create on null marketing in order", function () {
    $dispatch_order = DispatchOrder::factory()->create();

    $sales_order = SalesOrder::query()
        ->whereHas("invoice", function ($QQQ) use ($dispatch_order) {
            return $QQQ->where("id", $dispatch_order->invoice_id);
        })
        ->update([
            "personel_id" => null,
        ]);
    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now(),
        "is_promotion" => false,
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    $response->assertStatus(201);
    expect($response->getData()->data->delivery_order_number)->toEqual($delivery->delivery_order_number);
});

test("delivery order direct, can not create if there status send", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "status" => "send",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now(),
        "is_promotion" => false,
    ]);

    $response->assertStatus(422);
});

test("delivery order direct, delivery order deleted on deleted delivery", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    DeliveryOrderObserver::$enabled = true;
    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now(),
        "is_promotion" => false,
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    $response->assertStatus(201);
    expect($response->getData()->data->delivery_order_number)->toEqual($delivery->delivery_order_number);

    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/delivery-order/" . $response->getData()->data->id);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();
    expect($delivery)->toBeNull();
});

test("delivery order direct, delivery order deleted on canceled delivery", function () {
    $dispatch_order = DispatchOrder::factory()->create();
     DeliveryOrderObserver::$enabled = true;
    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now(),
        "is_promotion" => false,
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    $response->assertStatus(201);
    expect($response->getData()->data->delivery_order_number)->toEqual($delivery->delivery_order_number);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/delivery-order/" . $response->getData()->data->id, [
        "status" => "canceled",
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    expect($delivery)->toBeNull();
});

/**
 * Update
 */
test("delivery order direct, can update with valid data", function () {
    $delivery_order = DeliveryOrder::factory()->create([
        "status" => "send",
    ]);
    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/delivery-order/" . $delivery_order->id, [
        "status" => "canceled",
    ]);
    $response->assertStatus(200);

    $history = DB::table('delivery_order_histories')
        ->where("delivery_order_id", $response->getData()->data->id)
        ->where("status", "canceled")
        ->first();

    expect($history)->not->toBeNull();
});

test("delivery order direct, can update with invalid received", function () {
    $delivery_order = DeliveryOrder::factory()->create();
    ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "1",
    ]);
    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/delivery-order/" . $delivery_order->id, [
        "status" => "send",
    ]);
    $response->assertStatus(200);
});

test("delivery order direct, can not update if has received", function () {
    $delivery_order = DeliveryOrder::factory()->create();
    ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2",
    ]);
    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/delivery-order/" . $delivery_order->id, [
        "status" => "send",
    ]);
    $response->assertStatus(422);
});

test("delivery order direct, can not cancel after received", function () {
    $delivery_order = DeliveryOrder::factory()->create();
    ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2",
    ]);
    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/delivery-order/" . $delivery_order->id, [
        "status" => "canceled",
    ]);
    $response->assertStatus(422);
});

/**
 * Delete
 */
test("delivery order direct, can delete with valid data", function () {
    $delivery_order = DeliveryOrder::factory()->create();
    DeliveryOrderObserver::$enabled = true;
    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/delivery-order/" . $delivery_order->id);
    $response->assertStatus(200);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    expect($delivery)->toBeNull();
});

test("delivery order direct, can not delete has receiving", function () {
    $delivery_order = DeliveryOrder::factory()->create();
    ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2",
    ]);
    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/delivery-order/" . $delivery_order->id);
    $response->assertStatus(422);
});

test("delivery order direct batch, can delete with valid data", function () {
    $delivery_order = DeliveryOrder::factory()->create();
    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/delivery-order/batch", [
        "resources" => [$delivery_order->id],
    ]);
    $response->assertStatus(200);
});

test("delivery order direct batch, can not delete has receiving", function () {
    $delivery_order = DeliveryOrder::factory()->create();
    ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2",
    ]);
    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/delivery-order/batch", [
        "resources" => [$delivery_order->id],
    ]);
    $response->assertStatus(422);
});

/**
 * PROMOTION
 */
test("delivery order promotion, can create with valid payload", function () {
    $promotion_good = DispatchPromotion::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_promotion_id" => $promotion_good->id,
        "date_delivery" => now(),
        "is_promotion" => true,
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    $response->assertStatus(201);
    expect($response->getData()->data->delivery_order_number)->toEqual($delivery->delivery_order_number);
});

test("delivery order promotion, can create if does not have status send", function () {
    $promotion_good = DispatchPromotion::factory()->create();
    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_promotion_id" => $promotion_good->id,
        "status" => "canceled",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_promotion_id" => $promotion_good->id,
        "date_delivery" => now(),
        "is_promotion" => true,
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    $response->assertStatus(201);
    expect($response->getData()->data->delivery_order_number)->toEqual($delivery->delivery_order_number);
});

test("delivery order promotion, can not create if there status send", function () {
    $promotion_good = DispatchPromotion::factory()->create();
    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_promotion_id" => $promotion_good->id,
        "status" => "send",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_promotion_id" => $promotion_good->id,
        "date_delivery" => now(),
        "is_promotion" => true,
    ]);

    $response->assertStatus(422);
});

test("delivery order promotion, delivery order deleted on deleted delivery", function () {
    $promotion_good = DispatchPromotion::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_promotion_id" => $promotion_good->id,
        "date_delivery" => now(),
        "is_promotion" => true,
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    $response->assertStatus(201);
    expect($response->getData()->data->delivery_order_number)->toEqual($delivery->delivery_order_number);

    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/delivery-order/" . $response->getData()->data->id);
    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    expect($delivery)->toBeNull();
});

test("delivery order promotion, delivery order deleted on canceled delivery", function () {
    $promotion_good = DispatchPromotion::factory()->create();
    DeliveryOrderObserver::$enabled = true;
    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/delivery-order", [
        "dispatch_promotion_id" => $promotion_good->id,
        "date_delivery" => now(),
        "is_promotion" => true,
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    $response->assertStatus(201);
    expect($response->getData()->data->delivery_order_number)->toEqual($delivery->delivery_order_number);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/delivery-order/" . $response->getData()->data->id, [
        "status" => "canceled",
    ]);

    $delivery = DeliveryOrderNumber::query()
        ->where("delivery_order_id", $response->getData()->data->id)
        ->first();

    expect($delivery)->toBeNull();
});
