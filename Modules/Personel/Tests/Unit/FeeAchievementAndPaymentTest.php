<?php

use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\MarketingFee;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("fee achievement quarter and payment", function () {
    MarketingFee::factory()->create();

    $response = actingAsSupport()->json("GET", "/api/v1/marketing/fee-quarter-achievement", [
        "year" => now()->year,
        "quarter" => now()->quarter,
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data[0])->toHaveProperties([
        "marketing",
        "fee_reguler",
        "fee_target",
        "total_active",
        "fee_paid",
        "fee_paid_remaining",
    ]);

    expect($response->getData()->data[0]->marketing)->toHaveProperties([
        "id",
        "name",
        "position",
        "payment_status",
    ]);

    expect($response->getData()->data[0]->fee_reguler)->toHaveProperties([
        "total",
        "pending",
        "active",
    ]);

    expect($response->getData()->data[0]->fee_target)->toHaveProperties([
        "total",
        "pending",
        "active",
    ]);
});
