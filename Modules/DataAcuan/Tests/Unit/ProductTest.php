<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("store product", function () {
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/product", [
        "name" => "Super Green",
        "size" => "1kg",
        "unit" => "botol",
        "type" => "liquide",
        "category_id" => 3,
        "weight" => "3",
        "stock" => 100,
        "volume" => "Kilo",
        "metric_unit" => "Kg",
        "is_active" => true,
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->category_id)->toEqual("3");
    expect($response->getData()->data->category)->toEqual("3");
});
