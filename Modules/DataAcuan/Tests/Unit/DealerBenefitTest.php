<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\DealerBenefit;
use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\PaymentMethod;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can create dealer beneffit, default", function () {
    DealerBenefit::query()->delete();

    $grading = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::factory()->create([
        "name" => "Cash",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-benefit", [
        "grading_id" => $grading->id,
        "payment_method_id" => $payment_method->id,
        "agency_level_id" => [
            AgencyLevel::inRandomOrder()->first()->id,
        ],
        "old_price_usage" => true,
        "old_price_usage_limit" => 1,
        "old_price_days_limit" => 10,
        "benefit_discount" => [
            [
                "type" => "always",
                "stage" => 1,
                "discount" => [
                    [
                        "discount" => 2.5,
                        "minimum_order" => 0,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
                "sibling_discount" => true,
            ],
            [
                "type" => "threshold",
                "stage" => 2,
                "discount" => [
                    [
                        "discount" => 0.5,
                        "minimum_order" => 300000000,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
                "sibling_discount" => true,
            ],
        ],
    ]);

    $response->assertStatus(201);
});

test("can not create dealer benefit, wrong period date", function () {
    DealerBenefit::query()->delete();

    $grading = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::factory()->create([
        "name" => "Cash",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-benefit", [
        "grading_id" => $grading->id,
        "payment_method_id" => $payment_method->id,
        "agency_level_id" => [
            AgencyLevel::inRandomOrder()->first()->id,
        ],
        "old_price_usage" => true,
        "old_price_usage_limit" => 1,
        "old_price_days_limit" => 10,
        "benefit_discount" => [
            [
                "type" => "always",
                "stage" => 1,
                "discount" => [
                    [
                        "discount" => 2.5,
                        "minimum_order" => 0,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
            ],
            [
                "type" => "threshold",
                "stage" => 2,
                "discount" => [
                    [
                        "discount" => 0.5,
                        "minimum_order" => 300000000,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
            ],
        ],
        "start_period" => "xxx",
        "end_period" => "xxx",
    ]);

    $response->assertStatus(422);
});

test("can not create dealer benefit, wrong benefit discount format", function () {
    DealerBenefit::query()->delete();

    $grading = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::factory()->create([
        "name" => "Cash",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-benefit", [
        "grading_id" => $grading->id,
        "payment_method_id" => $payment_method->id,
        "agency_level_id" => [
            AgencyLevel::inRandomOrder()->first()->id,
        ],
        "old_price_usage" => true,
        "old_price_usage_limit" => 1,
        "old_price_days_limit" => 10,
        "benefit_discount" => [
            [
                "type" => "always",
                "stage" => 1,
                "discount" => [
                    [
                        "discount" => 2.5,
                        "minimum_order" => 0,
                        "maximum_discount" => 0,
                    ],
                ],
            ],
            [
                "type" => "threshold",
                "stage" => 2,
                "discount" => [
                    [
                        "discount" => 0.5,
                        "minimum_order" => 300000000,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("can not create dealer benefit, benefeit grade was set", function () {
    DealerBenefit::query()->delete();

    $grading = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::factory()->create([
        "name" => "Cash",
    ]);
    DealerBenefit::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-benefit", [
        "grading_id" => $grading->id,
        "payment_method_id" => $payment_method->id,
        "agency_level_id" => [
            AgencyLevel::inRandomOrder()->first()->id,
        ],
        "old_price_usage" => true,
        "old_price_usage_limit" => 1,
        "old_price_days_limit" => 10,
        "benefit_discount" => [
            [
                "type" => "always",
                "stage" => 1,
                "discount" => [
                    [
                        "discount" => 2.5,
                        "minimum_order" => 0,
                        "maximum_discount" => 0,
                    ],
                ],
            ],
            [
                "type" => "threshold",
                "stage" => 2,
                "discount" => [
                    [
                        "discount" => 0.5,
                        "minimum_order" => 300000000,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("can not create dealer benefit, sibling benefit not set", function () {
    DealerBenefit::query()->delete();

    $grading = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::factory()->create([
        "name" => "Cash",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-benefit", [
        "grading_id" => $grading->id,
        "payment_method_id" => $payment_method->id,
        "agency_level_id" => [
            AgencyLevel::inRandomOrder()->first()->id,
        ],
        "old_price_usage" => true,
        "old_price_usage_limit" => 1,
        "old_price_days_limit" => 10,
        "benefit_discount" => [
            [
                "type" => "always",
                "stage" => 1,
                "discount" => [
                    [
                        "discount" => 2.5,
                        "minimum_order" => 0,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
                "sibling_discount" => true,
            ],
            [
                "type" => "threshold",
                "stage" => 2,
                "discount" => [
                    [
                        "discount" => 0.5,
                        "minimum_order" => 300000000,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
            ],
        ],
    ]);

    $response->assertStatus(422);
});

/**
 * update test
 */
test("can update dealer benefit", function () {
    DealerBenefit::query()->delete();

    $grading = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::factory()->create([
        "name" => "Cash",
    ]);
    $benefit_1 = DealerBenefit::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-benefit/" . $benefit_1->id, [
        "grading_id" => $benefit_1->grading_id,
        "payment_method_id" => $payment_method->id,
        "agency_level_id" => [
            AgencyLevel::inRandomOrder()->first()->id,
        ],
        "old_price_usage" => true,
        "old_price_usage_limit" => 1,
        "old_price_days_limit" => 10,
        "benefit_discount" => [
            [
                "type" => "always",
                "stage" => 1,
                "discount" => [
                    [
                        "discount" => 2.5,
                        "minimum_order" => 0,
                        "maximum_discount" => 0,
                    ],
                ],
            ],
            [
                "type" => "threshold",
                "stage" => 2,
                "discount" => [
                    [
                        "discount" => 0.5,
                        "minimum_order" => 300000000,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("can not update dealer benefit, benefeit grade was set", function () {
    DealerBenefit::query()->delete();

    $grading = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::factory()->create([
        "name" => "Cash",
    ]);

    $benefit_1 = DealerBenefit::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $benefit_2 = DealerBenefit::factory()->create();

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-benefit/" . $benefit_1->id, [
        "grading_id" => $benefit_2->grading_id,
        "payment_method_id" => $payment_method->id,
        "agency_level_id" => [
            AgencyLevel::inRandomOrder()->first()->id,
        ],
        "old_price_usage" => true,
        "old_price_usage_limit" => 1,
        "old_price_days_limit" => 10,
        "benefit_discount" => [
            [
                "type" => "always",
                "stage" => 1,
                "discount" => [
                    [
                        "discount" => 2.5,
                        "minimum_order" => 0,
                        "maximum_discount" => 0,
                    ],
                ],
            ],
            [
                "type" => "threshold",
                "stage" => 2,
                "discount" => [
                    [
                        "discount" => 0.5,
                        "minimum_order" => 300000000,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("can not create dealer beneffit, without agency level", function () {
    DealerBenefit::query()->delete();

    $grading = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::factory()->create([
        "name" => "Cash",
    ]);
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-benefit", [
        "grading_id" => $grading->id,
        "payment_method_id" => $payment_method->id,
        "agency_level_id" => [
            "",
        ],
        "old_price_usage" => true,
        "old_price_usage_limit" => 1,
        "old_price_days_limit" => 10,
        "benefit_discount" => [
            [
                "type" => "always",
                "stage" => 1,
                "discount" => [
                    [
                        "discount" => 2.5,
                        "minimum_order" => 0,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
            ],
            [
                "type" => "threshold",
                "stage" => 2,
                "discount" => [
                    [
                        "discount" => 0.5,
                        "minimum_order" => 300000000,
                        "maximum_discount" => 0,
                    ],
                ],
                "product_category" => [
                    1,
                ],
            ],
        ],
    ]);

    $response->assertStatus(422);
});
