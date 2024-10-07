<?php

use Carbon\Carbon;
use Modules\DataAcuan\Entities\FeeFollowUpHistory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * CREATE
 */
test("fee follow up history, can create with valid data", function () {
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create();
    $fee_follow_up_history["date_start"] = now()->addDays(2);

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-follow-up-history", [
        "date_start" => now()->addDays(5),
        "fee_follow_up" => $fee_follow_up_history->fee_follow_up,
    ]);
    $response->assertStatus(201);
});

test("fee follow up history, can not create with same date start", function () {
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create([
        "date_start" => now()->addDays(2),
    ]);

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-follow-up-history", [
        "date_start" => now()->addDays(2),
        "fee_follow_up" => $fee_follow_up_history->fee_follow_up,
    ]);
    $response->assertStatus(422);
});

test("fee follow up history, can not create with incomplete fee follow up", function () {
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create([
        "date_start" => now()->addDays(2),
    ]);
    $fee_follow_up = $fee_follow_up_history->fee_follow_up;
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-follow-up-history", [
        "date_start" => now()->addDays(2),
        "fee_follow_up" => array_splice($fee_follow_up, 1),
    ]);
    $response->assertStatus(422);
});

test("fee follow up history, can not create with incomplete attribute", function () {
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create([
        "date_start" => now()->addDays(2),
    ]);
    $fee_follow_up = collect($fee_follow_up_history->fee_follow_up)
        ->map(function ($fee) {
            return array_splice($fee, 1);
        });

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-follow-up-history", [
        "date_start" => now()->addDays(2),
        "fee_follow_up" => $fee_follow_up,
    ]);
    $response->assertStatus(422);
});

/**
 * UPDATE
 */
test("fee follow up history, can update with valid data", function () {
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create();
    $fee_follow_up_history["date_start"] = now()->addDays(2);
    $id = $fee_follow_up_history->id;
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-follow-up-history/" . $id, [
        "date_start" => now()->addDays(2),
        "fee_follow_up" => $fee_follow_up_history->fee_follow_up,
    ]);
    $response->assertStatus(200);
});

test("fee follow up history, can update with same date start", function () {
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create();
    $fee_follow_up_history_2 = FeeFollowUpHistory::factory()->create([
        "date_start" => now()->addDays(2),
    ]);
    $fee_follow_up_history["date_start"] = now()->addDays(2);
    $id = $fee_follow_up_history->id;
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-follow-up-history/" . $id, [
        "date_start" => now()->addDays(2),
        "fee_follow_up" => $fee_follow_up_history->fee_follow_up,
    ]);
    $response->assertStatus(422);
});

test("fee follow up history, can update with fee follow up", function () {
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create();
    $fee_follow_up_history["date_start"] = now()->addDays(2);
    $id = $fee_follow_up_history->id;
    $fee_follow_up = $fee_follow_up_history->fee_follow_up;
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-follow-up-history/" . $id, [
        "date_start" => now()->addDays(2),
        "fee_follow_up" => array_splice($fee_follow_up, 1),
    ]);
    $response->assertStatus(422);
});

test("fee follow up history, can update with attribute", function () {
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create();
    $fee_follow_up_history["date_start"] = now()->addDays(2);
    $id = $fee_follow_up_history->id;
    $fee_follow_up = collect($fee_follow_up_history->fee_follow_up)
        ->map(function ($fee) {
            return array_splice($fee, 1);
        });
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-follow-up-history/" . $id, [
        "date_start" => now()->addDays(2),
        "fee_follow_up" => $fee_follow_up,
    ]);
    $response->assertStatus(422);
});

test("fee follow up history, can update with passed date start", function () {
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create([
        "date_start" => now()->subDays(2),
    ]);
    $id = $fee_follow_up_history->id;
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-follow-up-history/" . $id, [
        "date_start" => now()->addDays(2),
        "fee_follow_up" => $fee_follow_up_history->fee_follow_up,
    ]);
    $response->assertStatus(422);
});

/**
 * DELETE
 */
test("fee follow up history, can delete with future date start", function(){
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create([
        "date_start" => now()->addDays(2),
    ]);
    $id = $fee_follow_up_history->id;
    $response = actingAsSupport()->deleteJson("/api/v1/data-acuan/fee-follow-up-history/" . $id);
    $response->assertStatus(200);
});

test("fee follow up history, can not delete with passed date start", function(){
    $fee_follow_up_history = FeeFollowUpHistory::factory()->create([
        "date_start" => now()->subDays(2),
    ]);
    $id = $fee_follow_up_history->id;
    $response = actingAsSupport()->deleteJson("/api/v1/data-acuan/fee-follow-up-history/" . $id);
    $response->assertStatus(422);
});

/**
 * ACTIVE FEE FOLLOW UP
 */
test("fee follow up history, active fee follow up base history", function(){
    FeeFollowUpHistory::all()->each(fn($fee) => $fee->delete());
    FeeFollowUpHistory::factory()->create([
        "date_start" => now()->subDays(10)
    ]);
    $response = actingAsSupport()->getJson("/api/v1/data-acuan/active-fee-follow-up");
    $response->assertStatus(200);

    $fee_follow_up = DB::table('fee_follow_ups')->whereNull("deleted_at")->count();
    expect($response->getData()->data->fee_follow_up)->toBeArray();
    expect(count($response->getData()->data->fee_follow_up))->toEqual($fee_follow_up);
    expect(Carbon::parse($response->getData()->data->date_start)->format("Y-m-d"))->toEqual(now()->subDays(10)->format("Y-m-d"));
});

test("fee follow up history, active fee follow up base reference", function(){
    FeeFollowUpHistory::all()->each(fn($fee) => $fee->delete());
    $response = actingAsSupport()->getJson("/api/v1/data-acuan/active-fee-follow-up");
    $response->assertStatus(200);
    $fee_follow_up = DB::table('fee_follow_ups')->whereNull("deleted_at")->count();
    expect($response->getData()->data->fee_follow_up)->toBeArray();
    expect(count($response->getData()->data->fee_follow_up))->toEqual($fee_follow_up);
    expect(Carbon::parse($response->getData()->data->date_start)->format("Y-m-d"))->toEqual(now()->startOfYear()->format("Y-m-d"));
});
