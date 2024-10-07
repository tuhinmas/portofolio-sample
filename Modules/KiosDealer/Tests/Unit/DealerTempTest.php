<?php
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\DataAcuan\Entities\Entity;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\SubDealerTemp;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("create: can create dealer temp", function () {
    $faker = Faker::create('id_ID');
    $entity = Entity::inRandomOrder()->first();
    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
        "prefix" => "PT",
        "name" => $faker->name(),
        "sufix" => Str::random(2),
        "address" => "Jlan Raya Pojok",
        "telephone" => $faker->numerify('##########'),
        "second_telephone" => $faker->numerify('##########'),
        "status" => "draft",
        "status_color" => "c2c2c2",
        "owner" => "Mastuhin",
        "owner_address" => "Jl. Janti",
        "owner_ktp" => "0124512082367",
        "owner_npwp" => "123123npwp",
        "owner_telephone" => "123123",
        "email" => "email@ail.com",
        "entity_id" => $entity->id,
        "latitude" => -7.12345,
        "longitude" => 123.2323,
    ]);

    $response->assertStatus(200);
});

/* dealer according dealer */
test("create: can not store to dealer temp if it on submission on changes", function () {
    $faker = Faker::create('id_ID');
    $dealer = Dealer::factory()->create();
    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "draft",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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
        "dealer_id" => $dealer->id,
    ]);

    $response->assertStatus(422);
});

test("create: can store to dealer temp if it has submission on changes and already rejected", function () {
    $faker = Faker::create('id_ID');
    $dealer = Dealer::factory()->create();
    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "change rejected",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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
        "dealer_id" => $dealer->id,
    ]);

    $response->assertStatus(200);
});

/* dealer according sub dealer */
test("create: can store with valid data", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $entity = DB::table('entities')->whereNull("deleted_at")->first();
    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("create: from sub dealer, sub dealer status will transfered", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create([
        "status" => "accepted"
    ]);
    $entity = DB::table('entities')->whereNull("deleted_at")->first();
    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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
    $sub_dealer->refresh();
    expect($sub_dealer->status)->toEqual("transfered");
});

test("create: can not store to dealer temp if it from sub dealer and has already on submission to dealer", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $dealer_temp = DealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "draft",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("create: can not store to dealer temp if it from sub dealer and has already on submission of changes", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "draft",
    ]);
    $entity = DB::table('entities')->whereNull("deleted_at")->first();
    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("create: can store to dealer temp if it from sub dealer and has already rejected", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "change rejected",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("create: can not store to dealer temp if it from sub dealer and has already become dealer", function () {
    $faker = Faker::create('id_ID');
    $dealer = Dealer::factory()->create();

    $sub_dealer = SubDealer::factory()->create([
        "dealer_id" => $dealer->id,
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("update: can update with valid data", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $dealer_temp = DealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $data = collect($dealer_temp)
    ->map(function($value, $field){

    })
    ->toArray();
    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer-temp/" . $dealer_temp->id, [
        "status" => "submission of changes",
        "sub_dealer_id" => null,
    ]);

    $response->assertStatus(422);
});

/* check */
test("update: sub dealer transfer to dealer filed rejcted rollback sub dealer to accepted", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create([
        "status" => "transfered",
    ]);
    $dealer = DealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer-temp/" . $dealer->id, [
        "status" => "filed rejected",
    ]);

    $response->assertStatus(200);
    $sub_dealer->refresh();
    expect($sub_dealer->status)->toEqual("accepted");
});

test("update: can not set null sub_dealer_id if was filled", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $dealer_temp = DealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
    ]);
    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer-temp/" . $dealer_temp->id, [
        "status" => "submission of changes",
        "sub_dealer_id" => null,
    ]);

    $response->assertStatus(422);
});

/* dealer according kios */
test("can not store to dealer temp if it from kios and on submission on change", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $dealer_temp = StoreTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "submission of changes",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("can store to dealer temp if it from kios and on draft submission on change", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $dealer_temp = StoreTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "draft",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("can store to dealer temp if it from kios and has submission on change but rejected", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $dealer_temp = StoreTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "change rejected",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("can not store to dealer temp if it from kios and has already on submission to sub dealer", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $dealer_temp = SubDealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "draft",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("can store to dealer temp if it from kios and has submission to sub dealer but rejected", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $dealer_temp = SubDealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "filed rejected",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("can not store to dealer temp if it from kios and has already on submission to dealer", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create();
    $dealer_temp = DealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "draft",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("can not store to dealer temp if it from kios and has already become dealer", function () {
    $faker = Faker::create('id_ID');
    $dealer = Dealer::factory()->create();
    $store = Store::factory()->create([
        "dealer_id" => $dealer->id,
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

test("can not store to dealer temp if it from kios and has already become sub dealer", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $store = Store::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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
    $dealer_temp = DealerTemp::factory()->create([
        "store_id" => $store->id,
        "status" => "change rejected",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-temp", [
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

/* check */
test("dealer temp: kios transfer to dealer filed rejcted rollback kios to accepted", function () {
    $faker = Faker::create('id_ID');
    $store = Store::factory()->create([
        "status" => "transfered",
    ]);
    $dealer = DealerTemp::factory()->create([
        "store_id" => $store->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer-temp/" . $dealer->id, [
        "status" => "filed rejected",
    ]);

    $response->assertStatus(200);
    $store->refresh();
    expect($store->status)->toEqual("accepted");
});

/* delete dealer temp rule */
test("delete dealer temp, can not except draft or wait approval", function () {
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "submission of changes",
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/dealer/dealer-temp/" . $dealer_temp->id);
    $response->assertStatus(422);
});

test("delete dealer temp, update dealer origin if from dealer", function () {
    $dealer = Dealer::factory()->create([
        "status" => "transferred",
    ]);

    $dealer_temp = DealerTemp::factory()->create([
        "status" => "draft",
        "dealer_id" => $dealer->id,
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/dealer/dealer-temp/" . $dealer_temp->id);
    $response->assertStatus(200);
    $dealer = Dealer::find($dealer->id);
    expect($dealer->status)->toEqual("accepted");
});

test("delete dealer temp, update sub dealer origin if from sub dealer", function () {
    $sub_dealer = SubDealer::factory()->create([
        "status" => "transfered",
    ]);

    $dealer_temp = DealerTemp::factory()->create([
        "status" => "draft",
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/dealer/dealer-temp/" . $dealer_temp->id);
    $response->assertStatus(200);
    $sub_dealer = SubDealer::find($sub_dealer->id);
    expect($sub_dealer->status)->toEqual("accepted");
});

test("delete dealer temp, update store origin if it from store", function () {
    $store = Store::factory()->create([
        "status" => "transfered",
    ]);

    $dealer_temp = DealerTemp::factory()->create([
        "status" => "draft",
        "store_id" => $store->id,
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/dealer/dealer-temp/" . $dealer_temp->id);
    $response->assertStatus(200);
    $store = Store::find($store->id);
    expect($store->status)->toEqual("accepted");
});
