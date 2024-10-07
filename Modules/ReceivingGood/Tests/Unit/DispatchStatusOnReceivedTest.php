<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\ReceivingGood\Entities\ReceivingGood;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("create: dispatch order status to received on valid data", function () {
    $receiving_good = ReceivingGood::factory()->create();
    $receiving_good->delete();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good/", [
        "delivery_order_id" => $receiving_good->delivery_order_id,
        "date_received" => now()->format("Y-m-d"),
        "delivery_status" => "2",
    ]);

    $response->assertStatus(201);
    $dispatch_order = DB::table('discpatch_order as dis')
        ->join("delivery_orders as do", "dis.id", "do.dispatch_order_id")
        ->whereNull("dis.deleted_at")
        ->whereNull("do.deleted_at")
        ->where("do.id", $receiving_good->delivery_order_id)
        ->select("dis.*")
        ->first();

    expect($dispatch_order->status)->toEqual("received");
});

test("create: dispatch order status still delivered on draft receiving good", function () {
    $receiving_good = ReceivingGood::factory()->create();
    $receiving_good->delete();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good/", [
        "delivery_order_id" => $receiving_good->delivery_order_id,
        "date_received" => now()->format("Y-m-d"),
        "delivery_status" => "1",
    ]);

    $response->assertStatus(201);
    $dispatch_order = DB::table('discpatch_order as dis')
        ->join("delivery_orders as do", "dis.id", "do.dispatch_order_id")
        ->whereNull("dis.deleted_at")
        ->whereNull("do.deleted_at")
        ->where("do.id", $receiving_good->delivery_order_id)
        ->select("dis.*")
        ->first();

    expect($dispatch_order->status)->toEqual("delivered");
});

/**
 * UPDATE
 */
test("update: dispatch order status to received on valid data", function () {
    $receiving_good = ReceivingGood::factory()->create();
    $response = actingAsSupport()->putJson("/api/v1/receiving-good/".$receiving_good->id, [
        "delivery_status" => "2",
    ]);

    $response->assertStatus(200);
    $dispatch_order = DB::table('discpatch_order as dis')
        ->join("delivery_orders as do", "dis.id", "do.dispatch_order_id")
        ->whereNull("dis.deleted_at")
        ->whereNull("do.deleted_at")
        ->where("do.id", $receiving_good->delivery_order_id)
        ->select("dis.*")
        ->first();

    expect($dispatch_order->status)->toEqual("received");
});

test("delete: dispatch order status to delivered on deleted receiving good", function () {
    $receiving_good = ReceivingGood::factory()->create([
        "delivery_status" => "1",
    ]);
    DispatchOrder::query()
        ->whereHas("deliveryOrder", function ($QQQ) use ($receiving_good) {
            return $QQQ->where("id", $receiving_good->delivery_order_id);
        })
        ->update([
            "status" => "received",
        ]);

    $response = actingAsSupport()->deleteJson("/api/v1/receiving-good/" . $receiving_good->id);

    $dispatch_order = DB::table('discpatch_order as dis')
        ->join("delivery_orders as do", "dis.id", "do.dispatch_order_id")
        ->whereNull("dis.deleted_at")
        ->whereNull("do.deleted_at")
        ->where("do.id", $receiving_good->delivery_order_id)
        ->select("dis.*")
        ->first();

    $response->assertStatus(200);
    $dispatch_order = DB::table('discpatch_order as dis')
        ->join("delivery_orders as do", "dis.id", "do.dispatch_order_id")
        ->whereNull("dis.deleted_at")
        ->whereNull("do.deleted_at")
        ->where("do.id", $receiving_good->delivery_order_id)
        ->select("dis.*")
        ->first();

    expect($dispatch_order->status)->toEqual("delivered");
});
