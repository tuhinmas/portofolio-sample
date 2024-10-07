<?php

use Faker\Factory as Faker;
use Modules\Event\Entities\Event;
use Modules\Personel\Entities\Personel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\Fee;
use Modules\DataAcuan\Entities\Product;

uses(Tests\TestCase::class, DatabaseTransactions::class);


test("Fetch Fee Product", function () {
    $response = actingAsSupport()->getJson("/api/v1/data-acuan/fee");
    $response->assertStatus(200);
});

test("can store fee product", function () {
    $product = Product::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/fee", [
        "year" => 2025, 
        "type" => 1, 
        "product_id" => $product->id, 
        "quantity" => 10, 
        "fee" => 1000, 
        "quartal" => 4 
    ]);

    $response->assertStatus(201);
});

test("can update fee product", function () {
    $product = Product::factory()->create();
    $fee = Fee::factory()->create();;

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/fee/".$fee->id,   [
        "year" => 2025, 
        "type" => 2, 
        "product_id" => $product->id, 
        "quantity" => 10, 
        "fee" => 1000, 
        "quartal" => 4 
    ]);

    $response->assertStatus(200);
});

test("Delete Fee Product", function () {
    $fee = Fee::factory()->create();;

    $response = actingAsSupport()->deleteJson("/api/v1/data-acuan/fee/". $fee->id);
    $response->assertStatus(200);
});

