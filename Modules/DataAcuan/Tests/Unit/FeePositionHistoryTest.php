<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\FeePositionHistory;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * create
 */
test("fee position history, can create with valid data", function () {
    $fee_position_history = FeePositionHistory::factory()->create();
    $fee_position_history["date_start"] = now()->addDays(1);
    unset($fee_position_history["id"]);    
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-position-history", $fee_position_history->toArray());
    $response->assertStatus(201);
});

test("fee position history, can not create with same date start", function () {
    $fee_position_history = FeePositionHistory::factory()->create();
    unset($fee_position_history["id"]);
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-position-history", $fee_position_history->toArray());
    $response->assertStatus(422);
});

test("fee position history, can not create with incomplete fee position", function () {
    $fee_position_history = FeePositionHistory::factory()->create();
    $fee_position_history["fee_position"] = array_slice($fee_position_history->toArray(), -1);
    unset($fee_position_history["id"]);
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-position-history", $fee_position_history->toArray());
    $response->assertStatus(422);
});

test("fee position history, can not create with incomplete attribute", function () {
    $fee_position_history = FeePositionHistory::factory()->create();
    $fee_position_history["fee_position"] = collect($fee_position_history["fee_position"])
        ->map(function ($fee) {
            return array_splice($fee, 1);
        })
        ->toArray();
    unset($fee_position_history["id"]);
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-position-history", $fee_position_history->toArray());
    $response->assertStatus(422);
});

/**
 * update
 */
test("fee position history, can update with valid data", function () {
    $fee_position_history = FeePositionHistory::factory()->create([
        "date_start" => now()->addDays(5),
    ]);
    $fee_position_history["date_start"] = now()->addDays(2);
    $id = $fee_position_history->id;
    unset($fee_position_history["id"]);
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-position-history/" . $id, $fee_position_history->toArray());
    $response->assertStatus(200);
});

test("fee position history, can not update with same date start", function () {
    $fee_position_history_1 = FeePositionHistory::factory()->create([
        "date_start" => now()->addDays(5),
    ]);
    $fee_position_history_2 = FeePositionHistory::factory()->create([
        "date_start" => now()->addDays(2),
    ]);
    $fee_position_history_1["date_start"] = now()->addDays(2);
    $id = $fee_position_history_1->id;
    unset($fee_position_history_1["id"]);
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-position-history/" . $id, $fee_position_history_1->toArray());
    $response->assertStatus(422);
});

test("fee position history, can not update with incomplete fee position", function () {
    $fee_position_history_1 = FeePositionHistory::factory()->create([
        "date_start" => now()->addDays(5),
    ]);
    $fee_position_history_2 = FeePositionHistory::factory()->create([
        "date_start" => now()->addDays(2),
    ]);
    $fee_position_history_1["fee_position"] = array_slice($fee_position_history_1->toArray(), -1);
    $fee_position_history_1["date_start"] = now()->addDays(2);
    $id = $fee_position_history_1->id;
    unset($fee_position_history_1["id"]);
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-position-history/" . $id, $fee_position_history_1->toArray());
    $response->assertStatus(422);
});

test("fee position history, can not update with incomplete attribute", function () {
    $fee_position_history_1 = FeePositionHistory::factory()->create([
        "date_start" => now()->addDays(5),
    ]);
    $fee_position_history_2 = FeePositionHistory::factory()->create([
        "date_start" => now()->addDays(2),
    ]);

    $fee_position_history_1["fee_position"] = collect($fee_position_history_1["fee_position"])
        ->map(function ($fee) {
            return array_splice($fee, 1);
        })
        ->toArray();

    $fee_position_history_1["date_start"] = now()->addDays(2);
    $id = $fee_position_history_1->id;
    unset($fee_position_history_1["id"]);
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-position-history/" . $id, $fee_position_history_1->toArray());
    $response->assertStatus(422);
});

test("fee position history, can not update with passed date start", function () {
    $fee_position_history_1 = FeePositionHistory::factory()->create([
        "date_start" => now()->subDays(5),
    ]);
    
    $id = $fee_position_history_1->id;
    unset($fee_position_history_1["id"]);
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-position-history/" . $id, [
        "date_start" => now()->addDays(5)
    ]);

    $response->assertStatus(422);
});

/**
 * delete
 */
test("fee position history, can delete with future date start", function () {
    $fee_position_history_1 = FeePositionHistory::factory()->create([
        "date_start" => now()->addDays(5),
    ]);
    $id = $fee_position_history_1->id;
    unset($fee_position_history_1["id"]);
    $response = actingAsSupport()->deleteJson("/api/v1/data-acuan/fee-position-history/" . $id, $fee_position_history_1->toArray());
    $response->assertStatus(200);
});

test("fee position history, can not delete with passed date start", function () {
    $fee_position_history_1 = FeePositionHistory::factory()->create([
        "date_start" => now()->subDays(5),
    ]);
    $id = $fee_position_history_1->id;
    unset($fee_position_history_1["id"]);
    $response = actingAsSupport()->deleteJson("/api/v1/data-acuan/fee-position-history/" . $id, $fee_position_history_1->toArray());
    $response->assertStatus(422);
});

/**
 * active fee position
 */
test("fee position history, active fee position", function(){
    $response = actingAsSupport()->getJson("/api/v1/data-acuan/active-fee-position");
    $response->assertStatus(200);
    expect($response->getData()->data)->toBeObject();
});
