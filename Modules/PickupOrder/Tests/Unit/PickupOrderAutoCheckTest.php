<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Porter;
use Modules\PickupOrder\Entities\PickupLoadHistory;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\PickupOrder\Entities\PickupOrderDetail;
use Modules\PickupOrder\Entities\PickupOrderDetailFile;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\PickupOrder\Entities\PickupOrderFile;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * STATUS LOADED
 */
test("auto check on loaded with valid data", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);

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

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "loaded",
    ]);

    $pickup_order->load("pickupOrderFileMandatories");
    $response->assertStatus(200);
    $delivery_order = DB::table('delivery_orders as dor')
        ->join("discpatch_order as dis", "dis.id", "dor.dispatch_order_id")
        ->whereNull("dor.deleted_at")
        ->whereNull("dis.deleted_at")
        ->where("dor.dispatch_order_id", $pickup_dispatch->dispatch_id)
        ->select("dor.*", "dis.status as dispatch_status")
        ->first();

    expect($response->getData()->data->status)->toEqual("checked");
    expect($response->getData()->data->receipt_id)->toBeTruthy();
    expect($delivery_order)->toBeTruthy();
    expect($delivery_order->dispatch_status)->toEqual("delivered");
});

test("auto check on loaded: disable auto check on load", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);

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

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "loaded",
        "is_auto_check" => false,
    ]);

    $pickup_order->load("pickupOrderFileMandatories");
    $response->assertStatus(200);
    $delivery_order = DB::table('delivery_orders as dor')
        ->join("discpatch_order as dis", "dis.id", "dor.dispatch_order_id")
        ->whereNull("dor.deleted_at")
        ->whereNull("dis.deleted_at")
        ->where("dor.dispatch_order_id", $pickup_dispatch->dispatch_id)
        ->select("dor.*", "dis.status as dispatch_status")
        ->first();

    expect($response->getData()->data->status)->toEqual("loaded");
    expect($response->getData()->data->receipt_id)->toBeTruthy();
    expect($delivery_order)->toBeFalsy();
});

/**
 * V2
 */
test("auto check on loaded v2: auto check on load", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);

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

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v2/pickup-order/update/" . $pickup_dispatch->pickup_order_id, [
        "status" => "loaded",
    ]);

    $pickup_order->load("pickupOrderFileMandatories");
    $response->assertStatus(200);
    $delivery_order = DB::table('delivery_orders as dor')
        ->join("discpatch_order as dis", "dis.id", "dor.dispatch_order_id")
        ->whereNull("dor.deleted_at")
        ->whereNull("dis.deleted_at")
        ->where("dor.dispatch_order_id", $pickup_dispatch->dispatch_id)
        ->select("dor.*", "dis.status as dispatch_status")
        ->first();

    expect($response->getData()->data->status)->toEqual("checked");
    expect($response->getData()->data->receipt_id)->toBeTruthy();
    expect($delivery_order)->toBeTruthy();
    expect($delivery_order->dispatch_status)->toEqual("delivered");
});

test("auto check on loaded v2: disable auto check on load", function () {
    set_time_limit(6000); // Set the max execution time to 600 seconds (10 minutes)
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);

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

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v2/pickup-order/update/" . $pickup_dispatch->pickup_order_id, [
        "status" => "loaded",
        "is_auto_check" => false,
    ]);

    $pickup_order->load("pickupOrderFileMandatories");
    $response->assertStatus(200);
    $delivery_order = DB::table('delivery_orders as dor')
        ->join("discpatch_order as dis", "dis.id", "dor.dispatch_order_id")
        ->whereNull("dor.deleted_at")
        ->whereNull("dis.deleted_at")
        ->where("dor.dispatch_order_id", $pickup_dispatch->dispatch_id)
        ->select("dor.*", "dis.status as dispatch_status")
        ->first();

    expect($response->getData()->data->status)->toEqual("loaded");
    expect($response->getData()->data->receipt_id)->toBeTruthy();
    expect($delivery_order)->toBeFalsy();
});