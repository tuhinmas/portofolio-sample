<?php

use Modules\DataAcuan\Entities\FeeFollowUp;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * CREATE
 */
test("fee follow up, can create with valid data", function () {
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-follow-up", [
        "follow_up_days" => 61,
        "fee" => "5",
        "settle_days" => 40,
    ]);    
    $response->assertStatus(201);
});

test("fee follow up, can not create with same follow up days", function () {
    $exist_follow_up_days = FeeFollowUp::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-follow-up", [
        "follow_up_days" => 61,
        "fee" => "5",
        "settle_days" => 40,
    ]);

    $response->assertStatus(422);
});

/**
 * UPDATE
 */
test("fee follow up, can not update with valid data", function () {
    $exist_follow_up_days = FeeFollowUp::factory()->create();
    $exist_follow_up_days_2 = FeeFollowUp::factory()->create([
        "follow_up_days" => 11,
    ]);
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-follow-up/". $exist_follow_up_days->id, [
        "follow_up_days" => 61,
        "fee" => "5",
        "settle_days" => 40,
    ]);

    $response->assertStatus(200);
});

test("fee follow up, can not update with same follow up days", function () {
    $exist_follow_up_days = FeeFollowUp::factory()->create();
    $exist_follow_up_days_2 = FeeFollowUp::factory()->create([
        "follow_up_days" => 11,
    ]);
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-follow-up/". $exist_follow_up_days->id, [
        "follow_up_days" => 11,
        "fee" => "5",
        "settle_days" => 40,
    ]);

    $response->assertStatus(422);
});

