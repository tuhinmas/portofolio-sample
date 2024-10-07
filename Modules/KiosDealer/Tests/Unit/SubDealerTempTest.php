<?php
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\SubDealerTemp;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * create
 */
test("can store with valid data", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $response->assertStatus(200);
});

test("can not store to sub dealer temp if sub dealer on submission of change", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "draft",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $response->assertStatus(422);
});

test("can not store to sub dealer temp if sub dealer on trasfer submission to dealer", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $dealer_temp = DealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "draft",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $response->assertStatus(422);
});

test("can not store to sub dealer temp if sub dealer has become dealer", function () {
    $faker = Faker::create('id_ID');
    $dealer = Dealer::factory()->create();
    $sub_dealer = SubDealer::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "transfered",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $response->assertStatus(422);
});

/**
 * update
 */
test("can update with valid data", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
    ]);
    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer-temp/" . $sub_dealer_temp->id, [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $response->assertStatus(200);
});

test("can not set null sub_dealer_id if was filled", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
    ]);
    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer-temp/" . $sub_dealer_temp->id, [
        "status" => "submission of changes",
        "sub_dealer_id" => null,
    ]);

    $response->assertStatus(422);
});

/**
 * sub dealer temp according kios
 */
test("can not store to sub dealer temp if it from kios and kios on sumission of changes", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $store_temp = StoreTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "filed",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(422);
});

test("can store to sub dealer temp if it from kios and kios on draft sumission of changes", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $store_temp = StoreTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "draft",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(200);
});

test("can not store to sub dealer temp if it from kios and has already on submission to sub dealer", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $dealer_temp = SubDealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "draft",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(422);
});

test("can not store to sub dealer temp if it from kios and has already on submission to dealer", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $dealer_temp = DealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "draft",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(422);
});

test("can not store to sub dealer temp if it from kios and has become sub dealer", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $store = Store::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(422);
});

test("can not store to sub dealer temp if it from kios and has become dealer", function () {
    $faker = Faker::create('id_ID');
    $dealer = Dealer::factory()->create();
    $store = Store::factory()->create([
        "dealer_id" => $dealer->id,
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(422);
});

test("can store to dealer temp if it from kios and has rejected", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $dealer_temp = SubDealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "change rejected",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp", [
        "address" => $faker->address,
        "change_note" => $faker->word,
        "email" => $faker->email,
        "entity_id" => $entity?->id,
        "gmaps_link" => $faker->url,
        "name" => $faker->name,
        "owner" => $faker->name,
        "owner_address" => $faker->address,
        "owner_ktp" => $faker->numberBetween(100000, 999999),
        "owner_npwp" => $faker->numberBetween(100000, 999999),
        "owner_telephone" => $faker->numberBetween(100000, 999999),
        "prefix" => $faker->word,
        "sufix" => $faker->word,
        "telephone" => $faker->numberBetween(100000, 999999),
        "latitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "longitude" => $faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response->assertStatus(200);
});

test("sub dealer temp: from kios and submission rejected, rollback kios to accepted", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "change rejected",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer-temp/" . $sub_dealer_temp->id, [
        "status" => "filed rejected",
    ]);
    $response->assertStatus(200);
    $store->refresh();
    expect($store->status)->toEqual("accepted");
});
