<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\Porter;
use Modules\PickupOrder\Entities\PickupLoadHistory;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\PickupOrder\Entities\PickupOrderDetail;
use Modules\PickupOrder\Entities\PickupOrderDetailFile;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\PickupOrder\Entities\PickupOrderFile;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * DETAIL PICKUP
 */
test("detail: pickup detail contain needs attributes", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $pickup_order = PickupOrder::query()
        ->findOrFail($pickup_dispatch->pickup_order_id);

    $porter = Porter::factory()->create([
        "warehouse_id" => $pickup_order->warehouse_id,
    ]);
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

    PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "unload",
    ]);

    $response = actingAsSupportFrom($porter->personel_id)->getJson("api/v1/pickup-order/" . $pickup_dispatch->pickup_order_id . "/detail-for-load");
    $response->assertStatus(200);
    $response->getData();
    expect($response->getData()->data)->toHaveKeys([
        "is_meet_checked_rules",
        "is_meet_loaded_rules",
        "release_detail_list",
        "pickup_order_files",
        "unload_file_count",
        "capacity_armada",
        "pickup_number",
        "delivery_date",
        "dispatch_list",
        "capacity_left",
        "warehouse_id",
        "loading_list",
        "total_weight",
        "type_driver",
        "created_by",
        "driver_id",
        "warehouse",
        "status",
        "armada",
        "note",
    ]);

    expect($response->getData()->data->dispatch_list[0])->toHaveKeys([
        "dispatch_number",
        "delivery_address",
        "load_weight",
        "item_count",
        "proforma_number",
        "customer",
    ]);

    expect($response->getData()->data->loading_list)->toHaveKeys([
        "product_direct",
        "product_promotion",
    ]);
    expect($response->getData()->data->is_meet_loaded_rules)->toBeBool();
    expect($response->getData()->data->is_meet_checked_rules)->toBeBool();

    expect($response->getData()->data->loading_list->product_direct)->toHaveKeys([
        "products",
        "item_count",
    ]);

    expect($response->getData()->data->loading_list->product_promotion)->toHaveKeys([
        "products",
        "item_count",
    ]);

    expect($response->getData()->data->release_list[0])->toHaveKeys([
        "dispatch_number",
        "release_at",
        "release_by",
        "release_by_position",
        "release_reason",
    ]);

    expect($response->getData()->data->release_detail_list)->toHaveKeys([
        "product_direct",
        "product_promotion",
    ]);

    expect($response->getData()->data->release_detail_list->product_direct)->toHaveKeys([
        "products",
        "item_count",
        "alert",
    ]);

    expect($response->getData()->data->release_detail_list->product_promotion)->toHaveKeys([
        "products",
        "item_count",
        "alert",
    ]);
});

test("detail: uncheck pickup with valid alert", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);
    $pickup_order->status = "loaded";
    $pickup_order->save();
    $porter = Porter::factory()->create([
        "warehouse_id" => $pickup_order->warehouse_id,
    ]);

    foreach (mandatory_captions() as $mandatory_caption) {
        PickupOrderFile::factory()->create([
            "pickup_order_id" => $pickup_dispatch->pickup_order_id,
            "caption" => $mandatory_caption,
        ]);
    }

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
        "is_loaded" => true,
    ]);

    $unload = PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "unload",
        "is_loaded" => false,
        "quantity_actual_load" => 0,
    ]);

    /* load file */
    PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $load->id,
        "type" => "load",
    ]);

    /* unload file */
    PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $unload->id,
        "type" => "unload",
    ]);

    $response = actingAsSupportFrom($porter->personel_id)->getJson("api/v1/pickup-order/" . $pickup_dispatch->pickup_order_id . "/detail-for-load");
    $response->assertStatus(200);
    $response->getData();
    expect($response->getData()->data->release_detail_list->product_direct->alert)->toEqual("Produk belum dicek");
});

test("detail: porter unsigned warehose", function () {
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

    PickupOrderDetail::factory()->create([
        "pickup_order_id" => $pickup_dispatch->pickup_order_id,
        "pickup_type" => "unload",
    ]);

    $response = actingAsSupport()->getJson("api/v1/pickup-order/" . $pickup_dispatch->pickup_order_id . "/detail-for-load");
    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toEqual("Anda bukan porter gudang dari pickup order bersangkutan");
});
