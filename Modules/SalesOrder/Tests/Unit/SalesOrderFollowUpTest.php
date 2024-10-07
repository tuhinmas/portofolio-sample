<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\SalesOrder\Entities\SalesOrder;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("sales order v1, direct follow up personel_id according store", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $sales_order = SalesOrder::factory()->create([
        "status" => "draft",
        "type" => "1",
    ]);

    SalesOrder::factory()->create([
        "store_id" => $sales_order->store_id,
        "status" => "confirmed",
        "type" => "2",
        "date" => now()->subDay(10),
    ]);

    $response = actingAsMarketing(null, $sales_order->personel_id)->postJson("/api/v1/sales-order/sales-order", [
        "store_id" => $sales_order->store_id,
        "type" => "1",
        "model" => 1,
        "grading_id" => "1",
        "delivery_address" => "Jawa",
        "recipient_phone_number" => "8123456",
        "estimated_done" => "2023-01-03",
        "note" => "factory",
        "status" => "submited",
        "follow_up_days" => 5,
        "sales_mode" => "follow_up"
    ]);

    $store = DB::table('dealers')
        ->where("id", $sales_order->store_id)
        ->first();

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->toEqual($store->personel_id);
    expect($response->getData()->data->follow_up_days)->toEqual(9);
});

test("sales order v2, direct follow up personel_id according store", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $sales_order = SalesOrder::factory()->create([
        "status" => "draft",
        "type" => "1",
    ]);

    SalesOrder::factory()->create([
        "store_id" => $sales_order->store_id,
        "status" => "confirmed",
        "type" => "2",
        "date" => now()->subDay(10),
    ]);

    $response = actingAsMarketing(null, $sales_order->personel_id)->postJson("/api/v2/sales-order/sales-order", [
        "store_id" => $sales_order->store_id,
        "type" => "1",
        "model" => 1,
        "grading_id" => "1",
        "delivery_address" => "Jawa",
        "recipient_phone_number" => "8123456",
        "estimated_done" => "2023-01-03",
        "note" => "factory",
        "status" => "submited",
        "follow_up_days" => 5,
        "sales_mode" => "follow_up"
    ]);

    $store = DB::table('dealers')
        ->where("id", $sales_order->store_id)
        ->first();

    $response->assertStatus(201);
    expect($response->getData()->data->personel_id)->toEqual($store->personel_id);
    expect($response->getData()->data->follow_up_days)->toEqual(9);

});
