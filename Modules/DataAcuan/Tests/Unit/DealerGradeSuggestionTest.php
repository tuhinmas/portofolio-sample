<?php

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\DealerGradeSuggestion;
use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\PaymentMethod;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * Can store
 */
test("can store grade suggest with infinite settle days", function () {
    $faker = Faker::create('id_ID');

    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => true,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_last_minimum_amount" => $faker->numberBetween($min = 1, $max = 900000),
        "proforma_sequential" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => $faker->numberBetween($min = 1, $max = 900000),
        "proforma_count" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(201);
    expect($response->getdata()->data->maximum_settle_days)->toBeFalsy();
});

test("can store grade suggest", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_last_minimum_amount" => $faker->numberBetween($min = 1, $max = 900000),
        "proforma_sequential" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => $faker->numberBetween($min = 1, $max = 900000),
        "proforma_count" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(201);
});

test("can store grade suggest with zero proforma_last_minimum_amount", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_last_minimum_amount" => 0,
        "proforma_sequential" => 0,
        "proforma_total_amount" => $faker->numberBetween($min = 1, $max = 900000),
        "proforma_count" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(201);
});

test("can store grade suggest with null proforma_last_minimum_amount", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);
    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_last_minimum_amount" => null,
        "proforma_sequential" => null,
        "proforma_total_amount" => $faker->numberBetween($min = 1, $max = 900000),
        "proforma_count" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(201);
});

test("can store grade suggest with unset proforma_last_minimum_amount", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => $faker->numberBetween($min = 1, $max = 900000),
        "proforma_count" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(201);
});

test("can store grade suggest with zero proforma_total_amount", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => 0,
        "proforma_count" => 0,

        "proforma_last_minimum_amount" => $faker->numberBetween($min = 1, $max = 900000),
        "proforma_sequential" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(201);
});

test("can store grade suggest with null proforma_total_amount", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => null,
        "proforma_count" => null,
        "proforma_last_minimum_amount" => $faker->numberBetween($min = 1, $max = 900000),
        "proforma_sequential" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(201);
});

test("can store grade suggest with unset proforma_total_amount", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);
    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_last_minimum_amount" => $faker->numberBetween($min = 1, $max = 900000),
        "proforma_sequential" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(201);
});

/**
 * can not store
 */
test("can not store on all null value", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => null,
        "proforma_count" => null,
        "proforma_last_minimum_amount" => null,
        "proforma_sequential" => null,
    ]);

    $response->assertStatus(422);
});

test("can not store on null proforma_total_amount", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);
    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => null,
        "proforma_count" => null,
    ]);

    $response->assertStatus(422);
});

test("can not store on zero proforma_total_amount value", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => 0,
        "proforma_count" => 0,
    ]);

    $response->assertStatus(422);
});

test("can not store on zero proforma_count value", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => 100,
        "proforma_count" => 0,
    ]);

    $response->assertStatus(422);
});

test("can not store on proforma_last_minimum_amount null value", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_last_minimum_amount" => null,
        "proforma_sequential" => null,
    ]);

    $response->assertStatus(422);
});

test("can not store on proforma_last_minimum_amount zero value", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_last_minimum_amount" => 0,
        "proforma_sequential" => 0,
    ]);

    $response->assertStatus(422);
});

test("can not store on proforma_sequential zero value", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_last_minimum_amount" => 100,
        "proforma_sequential" => 0,
    ]);

    $response->assertStatus(422);
});

test("can not store on all zero value", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => 0,
        "proforma_count" => 0,

        "proforma_last_minimum_amount" => 0,
        "proforma_sequential" => 0,
    ]);

    $response->assertStatus(422);
});

test("can not store, proforma at least have one rule", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "payment_method_id" => $payment_method->id,
        "maximum_settle_days" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(422);
});

/**
 * unique store rule
 */

test("grade suggest must unique suggested_grading_id depends on garding_id ", function () {
    $faker = Faker::create('id_ID');

    $dealer_suggestion = DealerGradeSuggestion::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->randomDigit,
        "proforma_last_minimum_amount" => $faker->randomFloat($nbMaxDecimals = 2, $min = 0, $max = 99999999999),
        "proforma_sequential" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => $faker->randomFloat($nbMaxDecimals = 2, $min = 0, $max = 99999999999),
        "proforma_count" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(422);
});

test("suggest rule with minimum last proforma", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => 10,
        "proforma_last_minimum_amount" => $faker->randomFloat($nbMaxDecimals = 2, $min = 0, $max = 99999999999),
        "proforma_sequential" => $faker->numberBetween(1, 5),
    ]);

    $response->assertStatus(201);
});

test("suggest rule with accumulative proforma", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_suggest = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_suggest->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $faker->randomDigit,
        "proforma_total_amount" => $faker->randomFloat($nbMaxDecimals = 2, $min = 0, $max = 99999999999),
        "proforma_count" => $faker->numberBetween(1, 999999),
    ]);

    $response->assertStatus(201);
});

test("grade suggest must unique proforma_last_minimum_amount", function () {
    $faker = Faker::create('id_ID');
    DealerGradeSuggestion::query()->delete();

    $dealer_suggestion = DealerGradeSuggestion::factory()->create();

    $grading = Grading::factory()->create([
        "default" => false,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $grading->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $faker->randomFloat($nbMaxDecimals = 2, $min = $dealer_suggestion->proforma_last_minimum_amount, $max = 999999999999),
        "proforma_count" => $faker->numberBetween(1, 999999),
    ]);
    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toEqual("tidak bisa membuat saran grade dealer, saran grade dealer dengan nilai ini sudah ada");
});

test("grade suggest must unique proforma_total_amount", function () {
    $faker = Faker::create('id_ID');
    DealerGradeSuggestion::query()->delete();

    $dealer_suggestion = DealerGradeSuggestion::factory()->create();

    $grading = Grading::factory()->create([
        "default" => false,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grade-suggestions", [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $grading->id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $faker->randomFloat($nbMaxDecimals = 2, $min = $dealer_suggestion->proforma_last_minimum_amount, $max = 999999999999),
        "proforma_sequential" => $faker->numberBetween(1, 999999),
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toEqual("tidak bisa membuat saran grade dealer, saran grade dealer dengan nilai ini sudah ada");
});

/**
 * can update
 */

test("can update dealer grade suggestion", function () {
    $faker = Faker::create('id_ID');

    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response->assertStatus(200);
});

test("can update dealer grade suggestion, proforma_total_amount to null", function () {
    $faker = Faker::create('id_ID');

    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => null,
        "proforma_count" => null,
    ]);

    $response->assertStatus(200);
});

/**
 * can not update
 */

test("can not update grading_id to another grading if that grading have same rule", function () {
    $faker = Faker::create('id_ID');

    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_2 = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_2->id,
    ]);

    $dealer_suggestion_2 = DealerGradeSuggestion::factory()->create([
        "grading_id" => Grading::factory()->create()->id,
        "suggested_grading_id" => Grading::factory()->create()->id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion_2->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => $dealer_suggestion->is_infinite_settle_days,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response->assertStatus(422);
});

test("can not update suggested_grading_id to another if exist in the same grading", function () {
    $faker = Faker::create('id_ID');

    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_2 = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_2->id,
    ]);

    $dealer_suggestion_2 = DealerGradeSuggestion::factory()->create([
        "grading_id" => Grading::factory()->create()->id,
        "suggested_grading_id" => Grading::factory()->create()->id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion_2->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response->assertStatus(422);
});

test("can not update all to null", function () {
    $faker = Faker::create('id_ID');

    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    DealerGradeSuggestion::query()
        ->where("grading_id", $grading->id)
        ->delete();

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $payment_method = PaymentMethod::query()
        ->where("name", "Cash")
        ->first();

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => null,
        "proforma_sequential" => null,
        "proforma_total_amount" => null,
        "proforma_count" => null,
    ]);

    $response->assertStatus(422);
});

test("can not update proforma_last_minimum_amount to null if proforma_total_amount was null", function () {
    $faker = Faker::create('id_ID');

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "proforma_total_amount" => 0,
        "proforma_count" => 0,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => null,
        "proforma_sequential" => null,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response->assertStatus(422);
});

test("can not update proforma_last_minimum_amount to zero if proforma_total_amount was zero", function () {
    $faker = Faker::create('id_ID');

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "proforma_total_amount" => 0,
        "proforma_count" => 0,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => 0,
        "proforma_sequential" => 0,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response->assertStatus(422);
});

test("can not update proforma_total_amount to null if proforma_last_minimum_amount was null", function () {
    $faker = Faker::create('id_ID');

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "proforma_last_minimum_amount" => 0,
        "proforma_sequential" => 0,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => null,
        "proforma_count" => null,
    ]);

    $response->assertStatus(422);
});

test("can not update proforma_total_amount to zero if proforma_last_minimum_amount was null", function () {
    $faker = Faker::create('id_ID');

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "proforma_last_minimum_amount" => 0,
        "proforma_sequential" => 0,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => 0,
        "proforma_count" => 0,
    ]);

    $response->assertStatus(422);
});

/**
 * unique update rule
 */
test("grade suggest must unique grading_id, update", function () {
    $faker = Faker::create('id_ID');

    $dealer_suggestion = DealerGradeSuggestion::factory()->create();
    $dealer_suggestion_2 = DealerGradeSuggestion::factory()->create();

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion_2->grading_id,
    ]);

    $response->assertStatus(422);
});

test("can not update proforma_total_amount if proforma_last_minimum_amount exist", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_2 = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_2->id,
    ]);

    $dealer_suggestion_2 = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
        "suggested_grading_id" => Grading::factory()->create()->id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => $dealer_suggestion->is_infinite_settle_days,
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion_2->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response->assertStatus(422);
});

test("can not update proforma_count if proforma_last_minimum_amount exist", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_2 = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_2->id,
    ]);

    $dealer_suggestion_2 = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
        "suggested_grading_id" => Grading::factory()->create()->id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => $dealer_suggestion->is_infinite_settle_days,
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion_2->proforma_count,
    ]);

    $response->assertStatus(422);
});

test("can not update proforma_last_minimum_amount if proforma_total_amount exist", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_2 = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_2->id,
    ]);

    $dealer_suggestion_2 = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
        "suggested_grading_id" => Grading::factory()->create()->id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => $dealer_suggestion->is_infinite_settle_days,
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion_2->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,

        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response->assertStatus(422);
});

test("can not update proforma_sequential if proforma_total_amount exist", function () {
    $faker = Faker::create('id_ID');
    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $grading_2 = Grading::factory()->create([
        "default" => false,
    ]);

    $payment_method = PaymentMethod::firstOrCreate([
        "name" => "cash",
    ]);

    $dealer_suggestion = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
        "suggested_grading_id" => $grading_2->id,
    ]);

    $dealer_suggestion_2 = DealerGradeSuggestion::factory()->create([
        "grading_id" => $grading->id,
        "suggested_grading_id" => Grading::factory()->create()->id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => false,
        "payment_methods" => [
            "cash",
            "kredit",
            "bilyet giro",
        ],
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion->proforma_sequential,
        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "grading_id" => $dealer_suggestion->grading_id,
        "suggested_grading_id" => $dealer_suggestion->suggested_grading_id,
        "valid_from" => $dealer_suggestion->valid_from,
        "is_infinite_settle_days" => $dealer_suggestion->is_infinite_settle_days,
        "maximum_settle_days" => $dealer_suggestion->maximum_settle_days,

        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion_2->proforma_sequential,

        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/dealer-grade-suggestions/" . $dealer_suggestion->id, [
        "proforma_last_minimum_amount" => $dealer_suggestion->proforma_last_minimum_amount,
        "proforma_sequential" => $dealer_suggestion_2->proforma_sequential,

        "proforma_total_amount" => $dealer_suggestion->proforma_total_amount,
        "proforma_count" => $dealer_suggestion->proforma_count,
    ]);

    $response->assertStatus(422);
});
