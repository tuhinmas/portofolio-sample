<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;
use Modules\ReceivingGood\Entities\ReceivingGoodReceived;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("receiving good, can create with valid data", function () {
    $receiving_good = ReceivingGood::factory()->create();
    $receiving_good->delete();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good/", [
        "delivery_order_id" => $receiving_good->delivery_order_id,
        "date_received" => now()->format("Y-m-d"),
        "delivery_status" => "2",
    ]);

    $response->assertStatus(201);

    $receiving_good_received = DB::table('receiving_good_receiveds')
        ->where("receiving_good_id", $response->getData()->data->id)
        ->first();
    expect($receiving_good_received)->toBeTruthy();
});

test("receiving good, can not create with exist receiving good", function () {
    $receiving_good = ReceivingGood::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/receiving-good/", [
        "delivery_order_id" => $receiving_good->delivery_order_id,
        "date_received" => now()->format("Y-m-d"),
        "delivery_status" => "2",
    ]);

    $response->assertStatus(422);
});

test("receiving good, can not update with exist receiving good", function () {
    $receiving_good_1 = ReceivingGood::factory()->create([
        "delivery_status" => "2",
    ]);
    $receiving_good_2 = ReceivingGood::factory()->create([
        "delivery_order_id" => $receiving_good_1->delivery_order_id,
        "delivery_status" => "1",
    ]);
    $response = actingAsSupport()->putJson("/api/v1/receiving-good/" . $receiving_good_2->id, [
        "delivery_status" => "2",
    ]);

    $response->assertStatus(422);
});

test("receiving good, can update to delivered if receiving detail less or equal dispatch detail", function () {
    $receiving_good_1 = ReceivingGood::factory()->create([
        "delivery_status" => "1",
    ]);

    $dispatch_order = DB::table('discpatch_order as dis')
        ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
        ->where("dor.id", $receiving_good_1->delivery_order_id)
        ->first();

    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "quantity_unit" => 100,
    ]);

    ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 50,
        "status" => "delivered",
    ]);
    ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 25,
        "status" => "broken",
    ]);
    ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 25,
        "status" => "incorrect",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/receiving-good/" . $receiving_good_1->id, [
        "delivery_status" => "2",
    ]);

    $response->assertStatus(200);
    $receiving_good_received = DB::table('receiving_good_receiveds')
        ->where("receiving_good_id", $response->getData()->data->id)
        ->first();
    expect($receiving_good_received)->toBeTruthy();
});

test("receiving good, can not update to delivered if receiving detail more than dispatch detail", function () {
    $receiving_good_1 = ReceivingGood::factory()->create([
        "delivery_status" => "1",
    ]);

    $dispatch_order = DB::table('discpatch_order as dis')
        ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
        ->where("dor.id", $receiving_good_1->delivery_order_id)
        ->select("dis.*")
        ->first();

    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
        "quantity_unit" => 100,
    ]);

    ReceivingGoodDetail::factory()->create([
        "receiving_good_id" => $receiving_good_1->id,
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 100,
        "status" => "delivered",
    ]);
    ReceivingGoodDetail::factory()->create([
        "receiving_good_id" => $receiving_good_1->id,
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 50,
        "status" => "broken",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/receiving-good/" . $receiving_good_1->id, [
        "delivery_status" => "2",
    ]);

    $response->assertStatus(422);
});

test("receiving good, can delete valid data", function () {
    $receiving_good_1 = ReceivingGood::factory()->create([
        "delivery_status" => "1",
    ]);

    $dispatch_order = DB::table('discpatch_order as dis')
        ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
        ->where("dor.id", $receiving_good_1->delivery_order_id)
        ->first();

    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "quantity_unit" => 100,
    ]);

    ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 50,
        "status" => "delivered",
    ]);
    ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 25,
        "status" => "broken",
    ]);
    ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 25,
        "status" => "incorrect",
    ]);

    ReceivingGoodReceived::firstOrCreate([
        "delivery_order_id" => $receiving_good_1->delivery_order_id,
        "receiving_good_id" => $receiving_good_1->id,
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/receiving-good/" . $receiving_good_1->id);
    $response->assertStatus(200);

    $receiving_good_received = DB::table('receiving_good_receiveds')
        ->where("receiving_good_id",$receiving_good_1->id)
        ->first();

    expect($receiving_good_received)->toBeFalsy();
});

test("receiving good, can not delete if received", function () {
    $receiving_good_1 = ReceivingGood::factory()->create([
        "delivery_status" => "2",
    ]);

    $dispatch_order = DB::table('discpatch_order as dis')
        ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
        ->where("dor.id", $receiving_good_1->delivery_order_id)
        ->first();

    $dispatch_order_detail = DispatchOrderDetail::factory()->create([
        "quantity_unit" => 100,
    ]);

    ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 50,
        "status" => "delivered",
    ]);
    ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 25,
        "status" => "broken",
    ]);
    ReceivingGoodDetail::factory()->create([
        "product_id" => $dispatch_order_detail->id_product,
        "quantity" => 25,
        "status" => "incorrect",
    ]);

    ReceivingGoodReceived::firstOrCreate([
        "delivery_order_id" => $receiving_good_1->delivery_order_id,
        "receiving_good_id" => $receiving_good_1->id,
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/receiving-good/" . $receiving_good_1->id);
    $response->assertStatus(403);

    $receiving_good_received = DB::table('receiving_good_receiveds')
        ->where("receiving_good_id", $receiving_good_1->id)
        ->first();

    expect($receiving_good_received)->toBeTruthy();
});
