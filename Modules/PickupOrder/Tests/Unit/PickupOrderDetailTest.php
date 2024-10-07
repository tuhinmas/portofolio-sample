<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\PickupOrder\Entities\PickupLoadHistory;
use Modules\PickupOrder\Entities\PickupOrderDetail;
use Modules\PickupOrder\Entities\PickupOrderDetailFile;
use Modules\PickupOrder\Entities\PickupOrderDispatch;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("pickup order detail from dispatch order list", function () {

    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    PickupLoadHistory::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "dispatch_id" => $pickup_dispatch->dispatch_id,
        "dispatch_type" => "dispatch_order",
        "dispatch" => null,
        "status" => "canceled",
    ]);

    PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "load",
    ]);

    $pickup_order_details = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "unload",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/pickup-order/pickup-order-detail/search", [
        "filters" => [
            [
                "field" => "pickup_order_id",
                "operator" => "=",
                "value" => $pickup_dispatch->pickup_order_id,
            ],
            [
                "field" => "detail_type",
                "operator" => "=",
                "value" => "dispatch_order",
            ],
            [
                "field" => "pickup_type",
                "operator" => "=",
                "value" => "load",
            ],
        ],
        "includes" => [
            [
                "relation" => "product",
            ],
            [
                "relation" => "pickupOrderDetailFiles",
            ],
        ],
    ]);

    expect($response->getData()->data)->toBeArray();
    expect(count($response->getData()->data))->toEqual(1);
});

/**
 * LOAD
 */
test("product is loaded with valid data", function () {

    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    PickupLoadHistory::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "dispatch_id" => $pickup_dispatch->dispatch_id,
        "dispatch_type" => "dispatch_order",
        "dispatch" => null,
        "status" => "canceled",
    ]);

    $pickup_order_details = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "load",
    ]);

    $pickup_order_detail_file = PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $pickup_order_details->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/pickup-order/pickup-order-detail/" . $pickup_order_details->id, [
        "quantity_actual_load" => $pickup_order_details->quantity_actual_load,
        "is_loaded" => true,
    ]);

    $response->assertStatus(200);
});

test("product is not loaded, attachment needed ", function () {

    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    PickupLoadHistory::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "dispatch_id" => $pickup_dispatch->dispatch_id,
        "dispatch_type" => "dispatch_order",
        "dispatch" => null,
        "status" => "canceled",
    ]);

    $pickup_order_details = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "load",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/pickup-order/pickup-order-detail/" . $pickup_order_details->id, [
        "quantity_actual_load" => $pickup_order_details->quantity_unit_load,
        "is_loaded" => true,
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->is_loaded[0])->toEqual("can not checked pickup, attachment needed");
});

test("product is not loaded, actual load needed ", function () {

    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    PickupLoadHistory::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "dispatch_id" => $pickup_dispatch->dispatch_id,
        "dispatch_type" => "dispatch_order",
        "dispatch" => null,
        "status" => "canceled",
    ]);

    $pickup_order_details = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "load",
    ]);

    $pickup_order_detail_file = PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $pickup_order_details->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/pickup-order/pickup-order-detail/" . $pickup_order_details->id, [
        "quantity_actual_load" => $pickup_order_details->quantity_unit_load - 1,
        "is_loaded" => true,
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->is_loaded[0])->toEqual("actual quantity load can not higher then quantity load");
});

test("can draft product load with valid data", function () {

    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    PickupLoadHistory::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "dispatch_id" => $pickup_dispatch->dispatch_id,
        "dispatch_type" => "dispatch_order",
        "dispatch" => null,
        "status" => "canceled",
    ]);

    $pickup_order_details = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "load",
    ]);

    $pickup_order_detail_file = PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $pickup_order_details->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/pickup-order/pickup-order-detail/" . $pickup_order_details->id, [
        "quantity_actual_load" => $pickup_order_details->quantity_unit_load - 1,
    ]);

    $response->assertStatus(200);
});


/**
 * UNLOAD
 */
test("product is unloaded with valid data", function () {

    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    PickupLoadHistory::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "dispatch_id" => $pickup_dispatch->dispatch_id,
        "dispatch_type" => "dispatch_order",
        "dispatch" => null,
        "status" => "canceled",
    ]);

    $load = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "load",
    ]);

    $unload = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "unload",
    ]);

    PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $load->id,
        "type" => "load",
    ]);

    $pickup_order_detail_file = PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $unload->id,
        "type" => "unload",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/pickup-order/pickup-order-detail/" . $unload->id, [
        "quantity_actual_load" => 0,
        "is_loaded" => false,
    ]);

    $response->assertStatus(200);
});

test("product is unloaded, attachment", function () {

    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    PickupLoadHistory::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "dispatch_id" => $pickup_dispatch->dispatch_id,
        "dispatch_type" => "dispatch_order",
        "dispatch" => null,
        "status" => "canceled",
    ]);

    $load = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "load",
    ]);

    $unload = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "unload",
    ]);

    PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $load->id,
        "type" => "load",
    ]);

    // $pickup_order_detail_file = PickupOrderDetailFile::factory()->create([
    //     "pickup_order_detail_id" => $unload->id,
    //     "type" => "unload",
    // ]);

    $response = actingAsSupport()->putJson("/api/v1/pickup-order/pickup-order-detail/" . $unload->id, [
        "quantity_actual_load" => 0,
        "is_loaded" => false,
    ]);

    $response->assertStatus(422);
});

test("product is unloaded, quantity must 0", function () {

    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    PickupLoadHistory::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "dispatch_id" => $pickup_dispatch->dispatch_id,
        "dispatch_type" => "dispatch_order",
        "dispatch" => null,
        "status" => "canceled",
    ]);

    $load = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "load",
    ]);

    $unload = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "unload",
    ]);

    PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $load->id,
        "type" => "load",
    ]);

    $pickup_order_detail_file = PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $unload->id,
        "type" => "unload",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/pickup-order/pickup-order-detail/" . $unload->id, [
        "quantity_actual_load" => 1,
        "is_loaded" => false,
    ]);

    $response->assertStatus(422);
});