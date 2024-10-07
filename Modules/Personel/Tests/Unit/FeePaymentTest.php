<?php

use Faker\Factory as Faker;
use Modules\Personel\Entities\Personel;
use Modules\Authentication\Entities\User;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\MarketingFeePayment;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can make fee payment report with valid payload", function () {
    $personel = Personel::factory()->create();
    $faker = Faker::create('id_ID');
    $response = actingAsSupport()->postJson("/api/v1/marketing/fee-payment", [
        "personel_id" => $personel->id,
        "year" => now()->year,
        "quarter" => now()->quarter,
        "amount" => 0,
        "reference_number" => $faker->word,
        "date" => now()->format("Y-m-d H:i:s"),
        "note" => "pembayaran fee",
        "link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/marketing/fee-payment/staging/note.jpg",
        "caption" => "nota pembayaran",
    ]);

    $response->assertStatus(200);
});

test("can display fee payment history", function () {
    $personel = Personel::factory()->create();
    $faker = Faker::create('id_ID');
    $response = actingAsSupport()->json("GET","/api/v1/marketing/fee-payment", [
        "personel_id" => $personel->id,
        "year" => now()->year,
        "sort_by" => "fee_last_reporter",
        "direction" => "desc",
    ]);

    $response->assertStatus(200);
    expect(count($response->getData()->data))->toEqual(4);
});

/**
 * CAN NOT
 */
test("can not make fee payment report with same reference number", function () {
    $personel = Personel::factory()->create();
    $faker = Faker::create('id_ID');
    $payment = MarketingFeePayment::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/marketing/fee-payment", [
        "personel_id" => $personel->id,
        "year" => now()->year,
        "quarter" => now()->quarter,
        "amount" => 0,
        "reference_number" => $payment->reference_number,
        "date" => now()->format("Y-m-d H:i:s"),
        "note" => "pembayaran fee",
        "link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/marketing/fee-payment/staging/note.jpg",
        "caption" => "nota pembayaran",
    ]);

    $response->assertStatus(422);
});

test("can not make fee payment report with amount higher than achieved fee", function () {
    $personel = Personel::factory()->create();
    $faker = Faker::create('id_ID');
    $fee = MarketingFee::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/marketing/fee-payment", [
        "personel_id" => $personel->id,
        "year" => now()->year,
        "quarter" => now()->quarter,
        "amount" => 999999999999999999999999999,
        "reference_number" => $faker->word,
        "date" => now()->format("Y-m-d H:i:s"),
        "note" => "pembayaran fee",
        "link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/marketing/fee-payment/production/note.jpg",
        "caption" => "nota pembayaran",
    ]);

    $response->assertStatus(422);
});

test("can not make fee payment report with invalid link", function () {
    $personel = Personel::factory()->create();
    $faker = Faker::create('id_ID');
    $fee = MarketingFee::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/marketing/fee-payment", [
        "personel_id" => $personel->id,
        "year" => now()->year,
        "quarter" => now()->quarter,
        "amount" => 999999999999999999999999999,
        "reference_number" => $faker->word,
        "date" => now()->format("Y-m-d H:i:s"),
        "note" => "pembayaran fee",
        "link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/marketing/fee-payment/staging/note.jpg",
        "caption" => "nota pembayaran",
    ]);

    $response->assertStatus(422);
});

test("can not make fee payment but support", function () {
    $personel = Personel::factory()->create();
    User::factory()->create([
        "personel_id" => $personel->id
    ]);

    $faker = Faker::create('id_ID');

    $response = actingAsMarketing(null, $personel->id)->postJson("/api/v1/marketing/fee-payment", [
        "personel_id" => $personel->id,
        "year" => now()->year,
        "quarter" => now()->quarter,
        "amount" => 0,
        "reference_number" => $faker->word,
        "date" => now()->format("Y-m-d H:i:s"),
        "note" => "pembayaran fee",
        "link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/marketing/fee-payment/staging/note.jpg",
        "caption" => "nota pembayaran",
    ]);

    $response->assertStatus(422);
});
