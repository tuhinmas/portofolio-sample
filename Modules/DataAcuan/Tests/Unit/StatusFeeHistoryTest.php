<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\StatusFeeHistory;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * CREATE
 */
test("status fee history, can create with valid data", function () {
    $status_fee = StatusFeeHistory::factory()->create();
    $status_fee = collect($status_fee)
        ->map(function ($fee, $field) {
            if ($field == "date_start") {
                $fee = now()->addDays(10);
            }
            return $fee;
        })
        ->except([
            "id",
            "date_end",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->toArray();
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-handover-status-history", $status_fee);
    $response->assertStatus(201);
});

test("status fee history, can not create with same date start", function () {
    $status_fee = StatusFeeHistory::factory()->create();
    $status_fee = collect($status_fee)
        ->except([
            "id",
            "date_end",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->toArray();
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-handover-status-history", $status_fee);
    $response->assertStatus(422);
});

test("status fee history, can not create with incomplete status fee", function () {
    $status_fee = StatusFeeHistory::factory()->create();

    $status_fee = collect($status_fee)
        ->map(function ($fee, $field) {
            if ($field == "status_fee") {
                return array_splice($fee, 1);
            }
            return $fee;
        })
        ->except([
            "id",
            "date_end",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->toArray();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-handover-status-history", $status_fee);
    $response->assertStatus(422);
});

test("status fee history, can not create with incomplete attribute", function () {
    $status_fee = StatusFeeHistory::factory()->create();

    $status_fee = collect($status_fee)
        ->map(function ($fee, $field) {
            if ($field == "date_start") {
                $fee = now()->addDays(10);
            } elseif ($field == "status_fee") {
                return collect($fee)
                    ->map(function ($status_fee) {
                        return array_splice($status_fee, 1);
                    });
            }
            return $fee;
        })
        ->except([
            "id",
            "date_end",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->toArray();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee-handover-status-history", $status_fee);
    $response->assertStatus(422);
});

/**
 * UPDATE
 */
test("status fee history, can update with valid data", function () {
    $status_fee = StatusFeeHistory::factory()->create();
    $id = $status_fee->id;
    $status_fee = collect($status_fee)
        ->map(function ($fee, $field) {
            if ($field == "date_start") {
                $fee = now()->addDays(10);
            }
            return $fee;
        })
        ->except([
            "id",
            "date_end",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->toArray();
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-handover-status-history/" . $id, $status_fee);
    $response->assertStatus(200);
});

test("status fee history, can not update with same date start", function () {
    $status_fee_1 = StatusFeeHistory::factory()->create();
    $status_fee_2 = StatusFeeHistory::factory()->create();
    $id = $status_fee_1->id;
    $status_fee_1 = collect($status_fee_1)
        ->except([
            "id",
            "date_end",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->toArray();
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-handover-status-history/" . $id, $status_fee_1);
    $response->assertStatus(422);
});

test("status fee history, can not update with incomplete status fee", function () {
    $status_fee_1 = StatusFeeHistory::factory()->create();
    $id = $status_fee_1->id;
    $status_fee_1 = collect($status_fee_1)
        ->map(function ($fee, $field) {
            if ($field == "date_start") {
                $fee = now()->addDays(10);
            }
            return $fee;
        })
        ->map(function ($fee, $field) {
            if ($field == "status_fee") {
                return array_splice($fee, 1);
            }
            return $fee;
        })
        ->except([
            "id",
            "date_end",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->toArray();
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-handover-status-history/" . $id, $status_fee_1);
    $response->assertStatus(422);
});

test("status fee history, can not update with incomplete attribute", function () {
    $status_fee_1 = StatusFeeHistory::factory()->create();
    $id = $status_fee_1->id;
    $status_fee_1 = collect($status_fee_1)
        ->map(function ($fee, $field) {
            if ($field == "date_start") {
                $fee = now()->addDays(10);
            } elseif ($field == "status_fee") {
                return collect($fee)
                    ->map(function ($status_fee) {
                        return array_splice($status_fee, 1);
                    });
            }
            return $fee;
        })
        ->except([
            "id",
            "date_end",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->toArray();
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee-handover-status-history/" . $id, $status_fee_1);
    $response->assertStatus(422);
});

/**
 * DELETE
 */
test("status fee history, can delete with future date start", function () {
    $status_fee_1 = StatusFeeHistory::factory()->create();
    $id = $status_fee_1->id;
    $response = actingAsSupport()->deleteJson("/api/v1/data-acuan/fee-handover-status-history/" . $id);
    $response->assertStatus(200);
});

test("status fee history, can delete with passed date start", function () {
    $status_fee_1 = StatusFeeHistory::factory()->create([
        "date_start" => now()->subDays(5),
    ]);
    $id = $status_fee_1->id;
    $response = actingAsSupport()->deleteJson("/api/v1/data-acuan/fee-handover-status-history/" . $id);
    $response->assertStatus(422);
});

/**
 * active fee position
 */
test("status fee history, active status fee", function () {
    $response = actingAsSupport()->getJson("/api/v1/data-acuan/active-fee-handover-status");
    $response->assertStatus(200);
    expect($response->getData()->data)->toBeObject();
});
