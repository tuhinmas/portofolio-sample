<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\Porter;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
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
test("set status to loaded with valid data", function () {
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
        "is_auto_check" => false
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

test("set status to loaded: non warehouse porter", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);

    // $porter = Porter::factory()->create([
    //     "warehouse_id" => $pickup_order->warehouse_id,
    // ]);

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

    $response = actingAsSupport()->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "loaded",
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->status[0])->toEqual("Anda bukan porter gudang dari pickup order bersangkutan");
});

test("set status to loaded: armada not available", function () {
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

    $driver = Driver::findOrFail($pickup_order->driver_id);
    $driver->delete();
    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "loaded",
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->status[0])->toEqual("Armada sudah tidak tersedia");
});

test("set status to loaded: attachment not available", function () {
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

    // foreach (mandatory_captions() as $mandatory_caption) {
    //     PickupOrderFile::factory()->create([
    //         "pickup_order_id" => $pickup_dispatch->pickup_order_id,
    //         "caption" => $mandatory_caption,
    //     ]);
    // }

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

    $response->assertStatus(422);
    expect($response->getData()->data->status[0])->toEqual("Lampiran pelengkap kurang");
});

test("set status to loaded: pickup order detail not available", function () {
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

    // $load = PickupOrderDetail::factory()->create([
    //     "pickup_order_id" => $pickup_dispatch->pickup_order_id,
    //     "pickup_type" => "load",
    //     "is_loaded" => true,
    // ]);

    // $unload = PickupOrderDetail::factory()->create([
    //     "pickup_order_id" => $pickup_dispatch->pickup_order_id,
    //     "pickup_type" => "unload",
    //     "is_loaded" => false,
    //     "quantity_actual_load" => 0,
    // ]);

    // /* load file */
    // PickupOrderDetailFile::factory()->create([
    //     "pickup_order_detail_id" => $load->id,
    //     "type" => "load",
    // ]);

    // /* unload file */
    // PickupOrderDetailFile::factory()->create([
    //     "pickup_order_detail_id" => $unload->id,
    //     "type" => "unload",
    // ]);

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "loaded",
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->status[0])->toEqual("Produk pickup tidak ada");
});

test("set status to loaded: pickup order detail does not all loaded", function () {
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
        "is_loaded" => false,
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

    $response->assertStatus(422);
    expect($response->getData()->data->status[0])->toEqual("Produk pickup belum dimuat semua");
});

test("set status to loaded: pickup order detail does not all loaded, invalid quantity", function () {
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
        "quantity_unit_load" => 10,
        "quantity_actual_load" => 5,
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

    $response->assertStatus(422);
    expect($response->getData()->data->status[0])->toEqual("Produk pickup belum dimuat semua");
});

test("set status to loaded: pickup order detail file not available", function () {
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

    // /* load file */
    // PickupOrderDetailFile::factory()->create([
    //     "pickup_order_detail_id" => $load->id,
    //     "type" => "load",
    // ]);

    /* unload file */
    PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $unload->id,
        "type" => "unload",
    ]);

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "loaded",
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->status[0])->toEqual("Lampiran foto produk pickup belum lengkap");
});

test("set status to loaded: pickup order dispatch not available", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);

    $pickup_dispatch->delete();
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

    $response->assertStatus(422);
    expect($response->getData()->data->status[0])->toEqual("Pickup tidak memiliki dispatch");
});

test("set status to loaded: pickup order unload file not available", function () {
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

    // /* unload file */
    // PickupOrderDetailFile::factory()->create([
    //     "pickup_order_detail_id" => $unload->id,
    //     "type" => "unload",
    // ]);

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "loaded",
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->status[0])->toEqual("Lampiran foto penurunan produk belum lengkap");
});

test("set status to loaded: pickup order revision unload", function () {
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
        "quantity_actual_load" => 1,
    ]);

    /* load file */
    PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $load->id,
        "type" => "load",
    ]);

    // /* unload file */
    PickupOrderDetailFile::factory()->create([
        "pickup_order_detail_id" => $unload->id,
        "type" => "unload",
    ]);

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "loaded",
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->status[0])->toEqual("Produk pickup revisi belum diturunkan semua");
});

/**
 * STATUS CANCELED
 */
test("set status to canceled with valid data from delivered", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $pickup_dispatch->dispatch_id,
        "status" => "send",
    ]);

    $dispatch_order = DispatchOrder::findOrFail($pickup_dispatch->dispatch_id);

    $dispatch_order = DispatchOrder::findOrFail($pickup_dispatch->dispatch_id);
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);
    $pickup_order->status = "delivered";
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

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "canceled",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->status)->toEqual("canceled");

    $delivery_order->refresh();
    $dispatch_order->refresh();
    expect($delivery_order->status)->toEqual("failed");
    expect($dispatch_order->status)->toEqual("planned");
});

test("set status to canceled with valid data from loaded", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $pickup_dispatch->dispatch_id,
        "status" => "send",
    ]);

    $dispatch_order = DispatchOrder::findOrFail($pickup_dispatch->dispatch_id);
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
        "status" => "canceled",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->status)->toEqual("canceled");

    $delivery_order->refresh();
    $dispatch_order->refresh();
    expect($delivery_order->status)->toEqual("canceled");
    expect($dispatch_order->status)->toEqual("planned");
});

test("set status to canceled with valid data from planned", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $pickup_dispatch->dispatch_id,
        "status" => "send",
    ]);
    $dispatch_order = DispatchOrder::findOrFail($pickup_dispatch->dispatch_id);
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);
    $pickup_order->status = "planned";
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

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "canceled",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->status)->toEqual("canceled");

    $delivery_order->refresh();
    $dispatch_order->refresh();
    expect($delivery_order->status)->toEqual("canceled");
    expect($dispatch_order->status)->toEqual("planned");
});

/**
 * STATUS FAILED
 */
test("set status to failed with valid data from delivered", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $pickup_dispatch->dispatch_id,
        "status" => "send",
    ]);

    $dispatch_order = DispatchOrder::findOrFail($pickup_dispatch->dispatch_id);
    $pickup_order = PickupOrder::query()
        ->with([
            "armada",
            "pickupOrderFileMandatories",
        ])
        ->findOrFail($pickup_dispatch->pickup_order_id);
    $pickup_order->status = "delivered";
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

    $response = actingAsSupportFrom($porter->personel_id)->putJson("api/v1/pickup-order/pickup-order/" . $pickup_dispatch->pickup_order_id, [
        "status" => "failed",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->status)->toEqual("failed");

    $delivery_order->refresh();
    $dispatch_order->refresh();
    expect($delivery_order->status)->toEqual("failed");
    expect($dispatch_order->status)->toEqual("planned");
});

test("set status to failed with valid data from loaded", function () {
    $pickup_dispatch = PickupOrderDispatch::factory()->create();
    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $pickup_dispatch->dispatch_id,
        "status" => "send",
    ]);
    $dispatch_order = DispatchOrder::findOrFail($pickup_dispatch->dispatch_id);
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
        "status" => "failed",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->status)->toEqual("failed");

    $delivery_order->refresh();
    $dispatch_order->refresh();
    expect($delivery_order->status)->toEqual("canceled");
    expect($dispatch_order->status)->toEqual("planned");
});
