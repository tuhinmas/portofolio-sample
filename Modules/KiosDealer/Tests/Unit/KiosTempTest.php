<?php
use Faker\Factory as Faker;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can create kios temp", function () {
    $faker = Faker::create('id_ID');

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "telephone" => $faker->numberBetween($min = 1000000, $max = 9999999),
        "owner_name" => $faker->name,
        "second_telephone" => "085956289255",
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "province_id" => "34",
        "city_id" => "3403",
        "district_id" => "3403090",
        "phone_number_reference" => null,
    ]);

    $response->assertStatus(200);
});

test("can not create kios temp with same phone number", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "personel_id" => $store->personel_id,
        "telephone" => $store->telephone,
        "owner_name" => $faker->name,
        "second_telephone" => "085956289255",
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "province_id" => "34",
        "city_id" => "3403",
        "district_id" => "3403090",
        "phone_number_reference" => null,
    ]);

    $response->assertStatus(422);
});

test("can update kios temp", function () {
    $faker = Faker::create('id_ID');
    $kios = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "telephone" => $faker->numberBetween($min = 1000000, $max = 9999999),
        "owner_name" => $faker->name,
        "second_telephone" => "085956289255",
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "province_id" => "34",
        "city_id" => "3403",
        "district_id" => "3403090",
        "phone_number_reference" => null,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/store/store-temp/" . $kios->getData()->data->id, [
        "name" => "Steven He",
        "telephone" => $faker->numberBetween($min = 1000000, $max = 9999999),
        "owner_name" => $faker->name,
        "second_telephone" => "085956289255",
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "province_id" => "34",
        "city_id" => "3403",
        "district_id" => "3403090",
        "phone_number_reference" => null,
    ]);
    expect($response->getData()->data->name)->toBe("Steven He");
    $response->assertStatus(200);
});

test("can not update kios temp with same phone number", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $kios = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "personel_id" => $store->personel_id,
        "telephone" => $faker->numberBetween($min = 1000000, $max = 9999999),
        "owner_name" => $faker->name,
        "second_telephone" => "085956289255",
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "province_id" => "34",
        "city_id" => "3403",
        "district_id" => "3403090",
        "phone_number_reference" => null,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/store/store-temp/" . $kios->getData()->data->id, [
        "telephone" => $store->telephone,
    ]);

    $response->assertStatus(422);
});

test("can add farmer to kios temp", function () {
    $faker = Faker::create('id_ID');

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "telephone" => $faker->numberBetween($min = 1000000, $max = 9999999),
        "owner_name" => $faker->name,
        "second_telephone" => "085956289255",
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "province_id" => "34",
        "city_id" => "3403",
        "district_id" => "3403090",
        "phone_number_reference" => null,
    ]);

    $response = actingAsSupport()->postJson("/api/v1/store/core-farmer-temp", [
        "store_temp_id" => $response->getData()->data->id,
        "telephone" => "085956289255",
        "name" => "Pak Suroso",
        "address" => "Jl. Bayak no.32",
    ]);

    $response->assertStatus(200);
});

test("can update farmer temp in kios", function () {
    $faker = Faker::create('id_ID');

    $kios = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "telephone" => $faker->numberBetween($min = 1000000, $max = 9999999),
        "owner_name" => $faker->name,
        "second_telephone" => "085956289255",
        "address" => $faker->address,
        "gmaps_link" => $faker->name,
        "province_id" => "34",
        "city_id" => "3403",
        "district_id" => "3403090",
        "phone_number_reference" => null,
    ]);

    $farmer = actingAsSupport()->postJson("/api/v1/store/core-farmer-temp", [
        "store_temp_id" => $kios->getData()->data->id,
        "telephone" => "085956289255",
        "name" => "Pak Suroso",
        "address" => "Jl. Bayak no.32",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/store/core-farmer-temp/" . $farmer->getData()->data->id, [
        "store_temp_id" => $kios->getData()->data->id,
        "telephone" => "085956289255",
        "name" => "Pak Suroso updated",
        "address" => "Jl. Bayak no.32",
    ]);

    expect($response->getData()->data->name)->toBe("Pak Suroso updated");
    $response->assertStatus(200);
});

test("can not create store as submission of change, if kios is on transfer submission to sub dealer", function () {
    $faker = Faker::create('id_ID');

    $store = Store::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "draft",
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->first();

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "personel_id" => $personel->id,
        'telephone' => $faker->numberBetween($min = 1000000, $max = 9999999),
        "address" => $faker->address,
        "province_id" => "32",
        "city_id" => "3208",
        "district_id" => "3208101",
        "gmaps_link" => $faker->url,
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(422);
});

test("can not create store as submission of change, if kios has become sub dealer", function () {
    $faker = Faker::create('id_ID');

    $sub_dealer = SubDealer::factory()->create();
    $store = Store::factory()->create([
        "sub_dealer_id" => $sub_dealer->id
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->first();

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "personel_id" => $personel->id,
        'telephone' => $faker->numberBetween($min = 1000000, $max = 9999999),
        "address" => $faker->address,
        "province_id" => "32",
        "city_id" => "3208",
        "district_id" => "3208101",
        "gmaps_link" => $faker->url,
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(422);
});

test("can not create store as submission of change, if kios is on transfer submission to dealer", function () {
    $faker = Faker::create('id_ID');

    $store = Store::factory()->create();
    $sub_dealer_temp = DealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "draft",
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->first();

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "personel_id" => $personel->id,
        'telephone' => $faker->numberBetween($min = 1000000, $max = 9999999),
        "address" => $faker->address,
        "province_id" => "32",
        "city_id" => "3208",
        "district_id" => "3208101",
        "gmaps_link" => $faker->url,
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(422);
});

test("can not create store as submission of change, if kios has become dealer", function () {
    $faker = Faker::create('id_ID');

    $dealer = Dealer::factory()->create();
    $store = Store::factory()->create([
        "dealer_id" => $dealer->id
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->first();

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "personel_id" => $personel->id,
        'telephone' => $faker->numberBetween($min = 1000000, $max = 9999999),
        "address" => $faker->address,
        "province_id" => "32",
        "city_id" => "3208",
        "district_id" => "3208101",
        "gmaps_link" => $faker->url,
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(422);
});

test("can store as submission of change, if kios is on transfer submission to sub dealer but rejected", function () {
    $faker = Faker::create('id_ID');

    $store = Store::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "filed rejected",
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->first();

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "personel_id" => $personel->id,
        'telephone' => $faker->numberBetween($min = 1000000, $max = 9999999),
        "address" => $faker->address,
        "province_id" => "32",
        "city_id" => "3208",
        "district_id" => "3208101",
        "gmaps_link" => $faker->url,
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(200);
});

test("can store as submission of change, if kios is on transfer submission to dealer but rejected", function () {
    $faker = Faker::create('id_ID');

    $store = Store::factory()->create();
    $sub_dealer_temp = DealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "filed rejected",
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->first();

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "personel_id" => $personel->id,
        'telephone' => $faker->numberBetween($min = 1000000, $max = 9999999),
        "address" => $faker->address,
        "province_id" => "32",
        "city_id" => "3208",
        "district_id" => "3208101",
        "gmaps_link" => $faker->url,
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(200);
});

test("can store as submission of change, if kios is on draft submission of change", function () {
    $faker = Faker::create('id_ID');

    $store = Store::factory()->create();
    $sub_dealer_temp = StoreTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "draft",
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->first();

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "personel_id" => $personel->id,
        'telephone' => $faker->numberBetween($min = 1000000, $max = 9999999),
        "address" => $faker->address,
        "province_id" => "32",
        "city_id" => "3208",
        "district_id" => "3208101",
        "gmaps_link" => $faker->url,
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(200);
});

test("can not store as submission of change, if kios is on submission of change", function () {
    $faker = Faker::create('id_ID');

    $store = Store::factory()->create();
    $sub_dealer_temp = StoreTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "submission of changes",
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->first();

    $response = actingAsSupport()->postJson("/api/v1/store/store-temp", [
        "name" => $faker->name,
        "personel_id" => $personel->id,
        'telephone' => $faker->numberBetween($min = 1000000, $max = 9999999),
        "address" => $faker->address,
        "province_id" => "32",
        "city_id" => "3208",
        "district_id" => "3208101",
        "gmaps_link" => $faker->url,
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(422);
});


