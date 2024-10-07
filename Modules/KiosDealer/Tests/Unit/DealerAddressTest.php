<?php
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Address\Entities\Address;
use Modules\KiosDealer\Entities\Dealer;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * can create batch
 */
test("dealer address: v1 batch, can create address with valid data", function () {
    $dealer = Dealer::factory()->create();

    $response = actingAsMarketing()->postJson("/api/v1/address-detail/batch", [
        "resources" => [
            [
                "province_id" => "94",
                "city_id" => "9433",
                "district_id" => "9433020",
                "type" => "dealer",
                "parent_id" => $dealer->id,
            ],
            [
                "province_id" => "94",
                "city_id" => "9433",
                "district_id" => "9433020",
                "type" => "dealer_owner",
                "parent_id" => $dealer->id,
            ],
        ],
    ]);

    $response->assertStatus(200);
});

/**
 * cat not create batch
 */
test("dealer address: v1 batch, can not create address if exist", function () {
    $dealer = Dealer::factory()->create([
        "status" => "draft",
    ]);
    $address = Address::factory()->create([
        "parent_id" => $dealer->id,
    ]);

    $response = actingAsMarketing()->postJson("/api/v1/address-detail/batch", [
        "resources" => [
            [
                "province_id" => $address->province_id,
                "city_id" => $address->city_id,
                "district_id" => $address->district_id,
                "type" => $address->type,
                "parent_id" => $address->parent_id,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $dealer = DB::table('dealers')->where("id", $dealer->id)->first();
    expect($dealer->deleted_at)->toBeTruthy();
});

test("dealer address: v1 batch, can not create duplicate address", function () {
    $dealer = Dealer::factory()->create([
        "status" => "accepted",
    ]);
    $response = actingAsMarketing()->postJson("/api/v1/address-detail/batch", [
        "resources" => [
            [
                "province_id" => "94",
                "city_id" => "94330",
                "district_id" => "9433020",
                "type" => "dealer",
                "parent_id" => $dealer->id,
            ],
            [
                "province_id" => "94",
                "city_id" => "94330",
                "district_id" => "9433020",
                "type" => "dealer",
                "parent_id" => $dealer->id,
            ],
        ],
    ]);
    $response->assertStatus(422);
    $dealer = DB::table('dealers')->where("id", $dealer->id)->first();
    expect($dealer->deleted_at)->toBefalsy();
});

/**
 * can create single
 */
test("dealer address: v1 single, can create address if exist", function () {
    $dealer = Dealer::factory()->create();
    $response = actingAsMarketing()->postJson("/api/v1/address-detail", [
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "dealer",
        "parent_id" => $dealer->id,
    ]);

    $response->assertStatus(201);
});

/**
 * can not create single
 */
test("dealer address: v1 single, can not create address if exist 1", function () {
    $dealer = Dealer::factory()->create([
        "status" => "draft",
    ]);
    $address = Address::factory()->create([
        "parent_id" => $dealer->id,
    ]);

    $response = actingAsMarketing()->postJson("/api/v1/address-detail", [
        "province_id" => $address->province_id,
        "city_id" => $address->city_id,
        "district_id" => $address->district_id,
        "type" => $address->type,
        "parent_id" => $address->parent_id,
    ]);

    $response->assertStatus(422);
    $dealer = DB::table('dealers')->where("id", $dealer->id)->first();
    expect($dealer->deleted_at)->toBeTruthy();
});

test("dealer address: v1 single, can not create address if exist 2", function () {
    $dealer = Dealer::factory()->create([
        "status" => "accepted",
    ]);
    $address = Address::factory()->create([
        "parent_id" => $dealer->id,
    ]);

    $response = actingAsMarketing()->postJson("/api/v1/address-detail", [
        "province_id" => $address->province_id,
        "city_id" => $address->city_id,
        "district_id" => $address->district_id,
        "type" => $address->type,
        "parent_id" => $address->parent_id,
    ]);

    $response->assertStatus(422);
    $dealer = DB::table('dealers')->where("id", $dealer->id)->first();
    expect($dealer->deleted_at)->toBefalsy();
});

/**
 * can update batch
 */
test("dealer temp address: v1 batch, can update address with valid data", function () {
    $dealer = Dealer::factory()->create();
    $address_1 = Address::factory()->create([
        "parent_id" => $dealer->id,
        "parent_id" => $dealer->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "dealer",
    ]);

    $address_2 = Address::factory()->create([
        "parent_id" => $dealer->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206080,
        "type" => "dealer_owner",
    ]);

    $response = actingAsMarketing()->patchJson("/api/v1/address-detail/batch", [
        "resources" => [
            $address_1->id => [
                "parent_id" => $dealer->id,
                "province_id" => $address_1->province_id,
                "city_id" => $address_1->city_id,
                "district_id" => $address_1->district_id,
                "type" => $address_1->type,
            ],
            $address_2->id => [
                "parent_id" => $dealer->id,
                "province_id" => $address_2->province_id,
                "city_id" => $address_2->city_id,
                "district_id" => $address_2->district_id,
                "type" => $address_2->type,
            ],
        ],
    ]);
    $response->assertStatus(200);
});

/**
 * can not update batch
 */
test("dealer temp address: v1 batch, can not update address if exist", function () {
    $dealer = Dealer::factory()->create([
        "status" => "draft",
    ]);
    $address_1 = Address::factory()->create([
        "parent_id" => $dealer->id,
        "parent_id" => $dealer->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "dealer",
    ]);

    $address_2 = Address::factory()->create([
        "parent_id" => $dealer->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206080,
        "type" => "dealer_owner",
    ]);

    $response = actingAsMarketing()->patchJson("/api/v1/address-detail/batch", [
        "resources" => [
            $address_1->id => [
                "parent_id" => $dealer->id,
                "province_id" => 32,
                "city_id" => 3206,
                "district_id" => 3206080,
                "type" => "dealer_owner",
            ],
        ],
    ]);
    $response->assertStatus(422);
    $dealer = DB::table('dealers')->where("id", $dealer->id)->first();
    expect($dealer->deleted_at)->toBeTruthy();
});

test("dealer temp address: v1 batch, can not update duplicate address", function () {
    $dealer = Dealer::factory()->create([
        "status" => "draft",
    ]);
    $address_1 = Address::factory()->create([
        "parent_id" => $dealer->id,
        "parent_id" => $dealer->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "dealer",
    ]);

    $address_2 = Address::factory()->create([
        "parent_id" => $dealer->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206080,
        "type" => "dealer_owner",
    ]);

    $response = actingAsMarketing()->patchJson("/api/v1/address-detail/batch", [
        "resources" => [
            $address_1->id => [
                "parent_id" => $dealer->id,
                "province_id" => 32,
                "city_id" => 3206,
                "district_id" => 3206080,
                "type" => "dealer",
            ],
            $address_2->id => [
                "parent_id" => $dealer->id,
                "province_id" => 32,
                "city_id" => 3206,
                "district_id" => 3206080,
                "type" => "dealer",
            ],
        ],
    ]);

    $response->assertStatus(422);
    $dealer = DB::table('dealers')->where("id", $dealer->id)->first();
    expect($dealer->deleted_at)->toBeTruthy();
});

/**
 * can update single
 */
test("dealer temp address: v1 single, can update address with valid data", function () {
    $dealer = Dealer::factory()->create();
    $address_1 = Address::factory()->create([
        "parent_id" => $dealer->id,
        "parent_id" => $dealer->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "dealer",
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/address-detail/" . $address_1->id, [
        "parent_id" => $dealer->id,
        "province_id" => $address_1->province_id,
        "city_id" => $address_1->city_id,
        "district_id" => 3206070,
        "type" => $address_1->type,
    ]);
    $response->assertStatus(200);
});

/**
 * can not update single
 */
test("dealer temp address: v1 single, can not update address if there exist", function () {
    $dealer = Dealer::factory()->create([
        "status" => "draft",
    ]);
    $address_1 = Address::factory()->create([
        "parent_id" => $dealer->id,
        "parent_id" => $dealer->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "dealer",
    ]);

    $address_2 = Address::factory()->create([
        "parent_id" => $dealer->id,
        "parent_id" => $dealer->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206070,
        "type" => "dealer",
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/address-detail/" . $address_1->id, [
        "parent_id" => $dealer->id,
        "province_id" => $address_1->province_id,
        "city_id" => $address_1->city_id,
        "district_id" => 3206070,
        "type" => $address_1->type,
    ]);

    $response->assertStatus(422);
    $dealer = DB::table('dealers')->where("id", $dealer->id)->first();
    expect($dealer->deleted_at)->toBeTruthy();
});
