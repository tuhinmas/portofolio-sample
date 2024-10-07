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

test("can unload pickup dispatch with valid data", function () {
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

    $response = actingAsSupportFrom($porter->personel_id)->postJson("api/v2/pickup-order-dispatch/unload-dispatch/" . $pickup_dispatch->id, [
        "status_note" => "batalkan",
    ]);
    $pickup_order->refresh();
    $response->assertStatus(200);
    expect($pickup_order->status)->toEqual("canceled");
});

test("can not unload pickup dispatch on checked pickup", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);

    $pickup_order->status = "checked";
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

    $response = actingAsSupportFrom($porter->personel_id)->postJson("api/v2/pickup-order-dispatch/unload-dispatch/" . $pickup_dispatch->id, [
        "status_note" => "batalkan",
    ]);
    $response->assertStatus(422);
    $pickup_order->refresh();
    expect($response->getData()->data)->toEqual("pickup sudah dicek, tidak bisa direvisi");
});
