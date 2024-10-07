<?php
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class,DatabaseTransactions::class);

test("can create kios", function () {
    $faker = Faker::create('id_ID');

    $response = actingAsSupport()->postJson("/api/v1/store/store", [
        "name" => $faker->name,
        "telephone" => "085956289255",
        "owner_name" => $faker->name,
        "second_telephone" => "085956289255",
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "provinsi" => "32",
        "kabupaten" => "3208",
        "kecamatan" => "3208101",
        "status" => "accepted",
        "status_color" => "000000",
    ]);

    $response->assertStatus(200);
});

test("can create kios with latitude", function () {
    $faker = Faker::create('id_ID');

    $response = actingAsSupport()->postJson("/api/v1/store/store", [
        "name" => $faker->name,
        "telephone" => "085956289255",
        "owner_name" => $faker->name,
        "second_telephone" => "085956289255",
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "provinsi" => "32",
        "kabupaten" => "3208",
        "kecamatan" => "3208101",
        "status" => "accepted",
        "status_color" => "000000",
        "latitude" => "-7.7183981003776365",
        "longitude" => "110.38356207937147",
    ]);

    $response->assertStatus(200);
});

test("can update kios", function () {
    $faker = Faker::create('id_ID');
    $kios = actingAsSupport()->postJson("/api/v1/store/store", [
        "name" => $faker->name,
        "telephone" => $faker->numberBetween($min = 000000000000, $max = 99999999999),
        "owner_name" => $faker->name,
        "second_telephone" => $faker->numberBetween($min = 000000000000, $max = 99999999999),
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "provinsi" => "32",
        "kabupaten" => "3208",
        "kecamatan" => "3208101",
        "status" => "accepted",
        "status_color" => "000000",
        "latitude" => "-7.7183981003776365",
        "longitude" => "110.38356207937147",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/store/store/" . $kios->getData()->data->id, [
        "name" => "Steven He",
        "telephone" => $kios->getData()->data->telephone,
        "owner_name" => $faker->name,
        "second_telephone" => $kios->getData()->data->second_telephone,
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "provinsi" => "32",
        "kabupaten" => "3208",
        "kecamatan" => "3208101",
        "status" => "accepted",
        "status_color" => "000000",
        "latitude" => "-7.7183981003776365",
        "longitude" => "110.38356207937147",
    ]);

    expect($response->getData()->data->name)->toBe("Steven He");
    $response->assertStatus(200);
});

test("can add farmer to kios", function () {
    $faker = Faker::create('id_ID');

    $kios = actingAsSupport()->postJson("/api/v1/store/store", [
        "name" => $faker->name,
        "telephone" => $faker->numberBetween($min = 000000000000, $max = 99999999999),
        "owner_name" => $faker->name,
        "second_telephone" => $faker->numberBetween($min = 000000000000, $max = 99999999999),
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "provinsi" => "32",
        "kabupaten" => "3208",
        "kecamatan" => "3208101",
        "status" => "accepted",
        "status_color" => "000000",
        "latitude" => "-7.7183981003776365",
        "longitude" => "110.38356207937147",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/store/core-farmer", [
        "store_id" => $kios->getData()->data->id,
        "telephone" => "085956289255",
        "name" => "Pak Suroso",
        "address" => "Jl. Bayak no.32",
    ]);

    $response->assertStatus(200);
});

test("can update farmer in kios", function () {
    $faker = Faker::create('id_ID');

    $kios = actingAsSupport()->postJson("/api/v1/store/store", [
        "name" => $faker->name,
        "telephone" => $faker->numberBetween($min = 000000000000, $max = 99999999999),
        "owner_name" => $faker->name,
        "second_telephone" => $faker->numberBetween($min = 000000000000, $max = 99999999999),
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "provinsi" => "32",
        "kabupaten" => "3208",
        "kecamatan" => "3208101",
        "status" => "accepted",
        "status_color" => "000000",
        "latitude" => "-7.7183981003776365",
        "longitude" => "110.38356207937147",
    ]);

    $farmer = actingAsSupport()->postJson("/api/v1/store/core-farmer", [
        "store_id" => $kios->getData()->data->id,
        "telephone" => "085956289255",
        "name" => "Pak Suroso",
        "address" => "Jl. Bayak no.32",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/store/core-farmer/" . $farmer->getData()->data->id, [
        "store_id" => $kios->getData()->data->id,
        "telephone" => "085956289256",
        "name" => "Pak Suroso updated",
        "address" => "Jl. Bayak no.33",
    ]);

    expect($response->getData()->data->name)->toBe("Pak Suroso updated");
    $response->assertStatus(200);
});
