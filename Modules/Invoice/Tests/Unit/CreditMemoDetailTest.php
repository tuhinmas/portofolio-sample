<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Invoice\Entities\CreditMemo;
use Modules\Invoice\Entities\CreditMemoDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("credit memo: detail memo", function () {
    $credit_memo = CreditMemo::factory()->create();
    CreditMemoDetail::factory()->create([
        "credit_memo_id" => $credit_memo->id,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v1/credit-memo/" . $credit_memo->id, [
        "includes" => [
            [
                "relation" => "dealer.addressDetail.district",
            ],
            [
                "relation" => "dealer.addressDetail.city",
            ],
            [
                "relation" => "dealer.addressDetail.province",
            ],
            [
                "relation" => "origin",
            ],
            [
                "relation" => "destination",
            ],
            [
                "relation" => "creditMemoDetail.product.categoryProduct",
            ],
        ],
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->dealer)->toBeObject();
    expect($response->getData()->data->origin)->toBeObject();
    expect($response->getData()->data->destination)->toBeObject();
    expect($response->getData()->data->credit_memo_detail)->toBeArray();
    expect(count($response->getData()->data->credit_memo_detail))->toBeGreaterThan(0);
});
