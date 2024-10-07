<?php

use Faker\Factory as Faker;
use Modules\DataAcuan\Entities\Grading;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can store grading with full attributes", function(){
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/grading", [
        "name" => "test-grading",
        "bg_color" => "#000000",
        "fore_color" => "#ffffff",
        "bg_gradien" => "$000fff",
        "credit_limit" => 100000000,
        "description" => "test",
        "action" => "",
        "default" => false,
        "maximum_payment_days" => 50,
        "max_unsettle_proformas" => 3
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->name)->toEqual("test-grading");
});

test("can store grading with null max payment days", function(){
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/grading", [
        "name" => "test-grading",
        "bg_color" => "#000000",
        "fore_color" => "#ffffff",
        "bg_gradien" => "$000fff",
        "credit_limit" => 100000000,
        "description" => "test",
        "action" => "",
        "default" => false,
        "maximum_payment_days" => null,
        "max_unsettle_proformas" => null
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->name)->toEqual("test-grading");
});

test("can update grading with null max payment days", function(){
    $grading = Grading::factory()->create();

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/grading/". $grading->id, [
        "name" => "test-grading-2",
        "bg_color" => "#000000",
        "fore_color" => "#ffffff",
        "bg_gradien" => "$000fff",
        "credit_limit" => 100000000,
        "description" => "test",
        "action" => "",
        "default" => false,
        "maximum_payment_days" => null,
        "max_unsettle_proformas" => null
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->name)->toEqual("test-grading-2");
});

test("can update grading with full attributes", function(){
    $grading = Grading::factory()->create();

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/grading/". $grading->id, [
        "name" => "test-grading-2",
        "bg_color" => "#000000",
        "fore_color" => "#ffffff",
        "bg_gradien" => "$000fff",
        "credit_limit" => 100000000,
        "description" => "test",
        "action" => "",
        "default" => false,
        "maximum_payment_days" => 50,
        "max_unsettle_proformas" => 2
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->name)->toEqual("test-grading-2");
});

/**
 * can not
 */
test("can not store grading according minimum payment days", function(){
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/grading", [
        "name" => "test-grading",
        "bg_color" => "#000000",
        "fore_color" => "#ffffff",
        "bg_gradien" => "$000fff",
        "credit_limit" => 100000000,
        "description" => "test",
        "action" => "",
        "default" => false,
        "maximum_payment_days" => -1,
        "max_unsettle_proformas" => -1
    ]);

    $response->assertStatus(422);
});

test("can not update grading ccording minimum payment days", function(){
    $grading = Grading::factory()->create();

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/grading/". $grading->id, [
        "name" => "test-grading-2",
        "bg_color" => "#000000",
        "fore_color" => "#ffffff",
        "bg_gradien" => "$000fff",
        "credit_limit" => 100000000,
        "description" => "test",
        "action" => "",
        "default" => false,
        "maximum_payment_days" => -1,
    ]);

    $response->assertStatus(422);
});

test("can not update grading according max_unsettle_proformas", function(){
    $grading = Grading::factory()->create();

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/grading/". $grading->id, [
        "name" => "test-grading-2",
        "bg_color" => "#000000",
        "fore_color" => "#ffffff",
        "bg_gradien" => "$000fff",
        "credit_limit" => 100000000,
        "description" => "test",
        "action" => "",
        "default" => false,
        "max_unsettle_proformas" => -1,
    ]);

    $response->assertStatus(422);
});