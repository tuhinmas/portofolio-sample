<?php

use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * CREATE
 */
test("sales order v1, direct store status submited, follow up, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v1/sales-order/sales-order", [
        'store_id' => $dealer->id,
        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "follow_up",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->not->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(50);
});

test("sales order v1, direct store status submited, follow up, exist order before,  with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v1/sales-order/sales-order", [
        'store_id' => $dealer->id,
        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "follow_up",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->not->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(59);
});

test("sales order v1, direct store status submited, office, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v1/sales-order/sales-order", [
        'store_id' => $dealer->id,
        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "office",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeTruthy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(50);
});

test("sales order v1, direct store status submited, office, exist order before,  with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v1/sales-order/sales-order", [
        'store_id' => $dealer->id,
        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "office",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeTruthy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(59);
});

test("sales order v1, direct store status submited, marketing, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v1/sales-order/sales-order", [
        'store_id' => $dealer->id,
        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "marketing",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(0);
});

test("sales order v1, direct store status submited, marketing, exist order before,  with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v1/sales-order/sales-order", [
        'store_id' => $dealer->id,
        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "marketing",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(0);
});

/**
 * V2
 */
test("sales order v2, direct store status submited, follow up, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v2/sales-order/sales-order", [
        'store_id' => $dealer->id,

        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "follow_up",
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->not->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(50);
});

test("sales order v2, direct store status submited, follow up, exist order before,  with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v2/sales-order/sales-order", [
        'store_id' => $dealer->id,
        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "follow_up",
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->not->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(59);
});

test("sales order v2, direct store status submited, office, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v2/sales-order/sales-order", [
        'store_id' => $dealer->id,

        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "office",
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeTruthy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(50);
});

test("sales order v2, direct store status submited, office, exist order before,  with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v2/sales-order/sales-order", [
        'store_id' => $dealer->id,

        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "office",
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeTruthy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(59);
});

test("sales order v2, direct store status submited, marketing, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v2/sales-order/sales-order", [
        'store_id' => $dealer->id,

        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "marketing",
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(0);
});

test("sales order v2, direct store status submited, marketing, exist order before,  with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->postJson("/api/v2/sales-order/sales-order", [
        'store_id' => $dealer->id,

        'model' => "1",
        'type' => "1",
        "status" => "submited",
        "sales_mode" => "marketing",
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(0);
});

/**
 * UPDATE
 */
test("sales order v1, direct on status submited fill submited by and submited at", function () {
    $sales_order = SalesOrder::factory()->create([
        "status" => "draft",
        "type" => "1",
    ]);

    $response = actingAsMarketing(null, $sales_order->personel_id)->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->submited_by)->toEqual($sales_order->personel_id);
    expect($response->getData()->data->submited_at)->toBeTruthy();

});

test("sales order v2, direct on status submited fill submited by and submited at", function () {
    $sales_order = SalesOrder::factory()->create([
        "status" => "draft",
        "type" => "1",
    ]);

    $response = actingAsMarketing(null, $sales_order->personel_id)->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->submited_by)->toEqual($sales_order->personel_id);
    expect($response->getData()->data->submited_at)->toBeTruthy();

});

test("sales order v2, indirect on status submited fill submited by and submited at", function () {
    $sales_order = SalesOrder::factory()->create([
        "status" => "draft",
        "type" => "2",
    ]);

    $response = actingAsMarketing(null, $sales_order->personel_id)->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->submited_by)->toEqual($sales_order->personel_id);
    expect($response->getData()->data->submited_at)->toBeTruthy();

});

test("sales order v2, indirect on status reviewed by spv fill submited by and submited at", function () {
    $active_mm = Personel::factory()->marketingMM()->create();

    $sales_order = SalesOrder::factory()->create([
        "status" => "draft",
        "type" => "2",
        "personel_id" => $active_mm->id,
    ]);

    $response = actingAsMarketing(null, $sales_order->personel_id)->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "reviewed",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->submited_by)->toEqual($sales_order->personel_id);
    expect($response->getData()->data->submited_at)->toBeTruthy();
});

test("sales order v1, direct update status submited, follow up, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
        "store_id" => $dealer->id,
    ]);

    $response = actingAsSupport(null)->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "follow_up",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->not->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(50);
});

test("sales order v1, direct update status submited, follow up, exist order before,  with valid data", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
    ]);

    SalesOrder::factory()->create([
        "store_id" => $sales_order->store_id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "follow_up",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->not->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(59);
});

test("sales order v1, direct update status submited, office, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
        "store_id" => $dealer->id,
    ]);

    $response = actingAsSupport(null)->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "office",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeTruthy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(50);
});

test("sales order v1, direct update status submited, office, exist order before,  with valid data", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
    ]);

    SalesOrder::factory()->create([
        "store_id" => $sales_order->store_id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "office",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeTruthy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(59);
});

test("sales order v1, direct update status submited, marketing, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
        "store_id" => $dealer->id,
    ]);


    $response = actingAsSupport(null)->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "marketing",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(0);
});

test("sales order v1, direct update status submited, marketing, exist order before,  with valid data", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
    ]);

    SalesOrder::factory()->create([
        "store_id" => $sales_order->store_id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "marketing",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(0);
});

/**
 * V2
 */
test("sales order v2, direct update status submited, follow up, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
        "store_id" => $dealer->id,
    ]);

    $response = actingAsSupport(null)->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "follow_up",
    ]);
    
    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->not->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(50);
    expect($response->getData()->data->sales_mode)->toEqual("follow_up");
});

test("sales order v2, direct update status submited, follow up, exist order before,  with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
        "store_id" => $dealer->id,
    ]);

    SalesOrder::factory()->create([
        "store_id" => $sales_order->store_id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "follow_up",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->not->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(59);
    expect($response->getData()->data->sales_mode)->toEqual("follow_up");
});

test("sales order v2, direct update status submited, office, no order before, with valid data", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDays(50),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
        "store_id" => $dealer->id,
    ]);

    $response = actingAsSupport(null)->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "office",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeTruthy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(50);
    expect($response->getData()->data->sales_mode)->toEqual("office");
});

test("sales order v2, direct update status submited, office, exist order before,  with valid data", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
    ]);

    SalesOrder::factory()->create([
        "store_id" => $sales_order->store_id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "office",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeTruthy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(59);
    expect($response->getData()->data->sales_mode)->toEqual("office");
});

test("sales order v2, direct update status submited, marketing, no order before, with valid data", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
    ]);

    $response = actingAsSupport(null)->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "marketing",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(0);
    expect($response->getData()->data->sales_mode)->toEqual("marketing");
});

test("sales order v2, direct update status submited, marketing, exist order before,  with valid data", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "draft",
    ]);

    SalesOrder::factory()->create([
        "store_id" => $sales_order->store_id,
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(60),
    ]);

    $response = actingAsSupport(null)->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "sales_mode" => "marketing",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->not->toBeNull();
    expect($response->getData()->data->is_office)->toBeFalsy();
    expect($response->getData()->data->counter_id)->toBeNull();
    expect($response->getData()->data->follow_up_days)->toEqual(0);
    expect($response->getData()->data->sales_mode)->toEqual("marketing");
});

/**
 * Support
 */
test("sales order v1, direct by support on status submited fill submited by and submited at", function () {
    $sales_order = SalesOrder::factory()->create([
        "status" => "submited",
        "type" => "1",
    ]);

    $data = collect($sales_order)
        ->except([
            "id",
            "date",
            "order_number",
            "reference_number",
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
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->submited_by)->toEqual($sales_order->personel_id);
    expect($response->getData()->data->submited_at)->toBeTruthy();

});

test("sales order v2, direct by support on status submited fill submited by and submited at", function () {
    $sales_order = SalesOrder::factory()->create([
        "status" => "submited",
        "type" => "1",
        "sales_mode" => "marketing",
    ]);

    $data = collect($sales_order)
        ->except([
            "id",
            "date",
            "order_number",
            "reference_number",
        ])
        ->toArray();

    $response = actingAsMarketing(null, $sales_order->personel_id)->postJson("/api/v2/sales-order/sales-order", $data);

    $response->assertStatus(201);
    expect($response->getData()->data->submited_by)->toEqual($sales_order->personel_id);
    expect($response->getData()->data->submited_at)->toBeTruthy();
});
