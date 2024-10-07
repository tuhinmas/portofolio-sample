<?php
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Address\Entities\AddressTemp;
use Modules\KiosDealer\Entities\SubDealerTemp;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * V2
 * can create batch
 */
test("sub dealer temp address: v2 batch, can create address with valid data", function () {
    $dealer_temp = SubDealerTemp::factory()->create();

    $response = actingAsMarketing()->postJson("/api/v2/address-detail-temp/batch", [
        "resources" => [
            [
                "province_id" => "94",
                "city_id" => "9433",
                "district_id" => "9433020",
                "type" => "sub_dealer",
                "parent_id" => $dealer_temp->id,
            ],
            [
                "province_id" => "94",
                "city_id" => "9433",
                "district_id" => "9433020",
                "type" => "sub_dealer_owner",
                "parent_id" => $dealer_temp->id,
            ],
        ],
    ]);
    $response->assertStatus(200);
});

/**
 * V2
 * can not create batch
 */
test("sub dealer temp address: v2 batch, can not create address if exist", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
    ]);

    $response = actingAsMarketing()->postJson("/api/v2/address-detail-temp/batch", [
        "resources" => [
            [
                "province_id" => $address_temp->province_id,
                "city_id" => $address_temp->city_id,
                "district_id" => $address_temp->district_id,
                "type" => $address_temp->type,
                "parent_id" => $address_temp->parent_id,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("sub dealer temp address: v2 batch, can not create duplicate address", function () {
    $dealer_temp = SubDealerTemp::factory()->create();

    $response = actingAsMarketing()->postJson("/api/v2/address-detail-temp/batch", [
        "resources" => [
            [
                "province_id" => "94",
                "city_id" => "94330",
                "district_id" => "9433020",
                "type" => "sub_dealer",
                "parent_id" => $dealer_temp->id,
            ],
            [
                "province_id" => "94",
                "city_id" => "94330",
                "district_id" => "9433020",
                "type" => "sub_dealer",
                "parent_id" => $dealer_temp->id,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

/**
 * V2
 * can create single
 */
test("sub dealer temp address: v2 single, can create address with valid data", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $response = actingAsMarketing()->postJson("/api/v2/address-detail-temp", [
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
        "parent_id" => $dealer_temp->id,
    ]);

    $response->assertStatus(200);
});

/**
 * V2
 * can not create single
 */
test("sub dealer temp address: v2 single, can not create address if exist", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
    ]);

    $response = actingAsMarketing()->postJson("/api/v2/address-detail-temp", [
        "province_id" => $address_temp->province_id,
        "city_id" => $address_temp->city_id,
        "district_id" => $address_temp->district_id,
        "type" => $address_temp->type,
        "parent_id" => $address_temp->parent_id,
    ]);

    $response->assertStatus(422);
});

/**
 * V2
 * can update batch
 */
test("sub dealer temp address: v2 batch, can update address with valid data", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp_1 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
    ]);

    $address_temp_2 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206080,
        "type" => "sub_dealer_owner",
    ]);

    $response = actingAsMarketing()->patchJson("/api/v2/address-detail-temp/batch", [
        "resources" => [
            $address_temp_1->id => [
                "parent_id" => $dealer_temp->id,
                "province_id" => $address_temp_1->province_id,
                "city_id" => $address_temp_1->city_id,
                "district_id" => $address_temp_1->district_id,
                "type" => $address_temp_1->type,
            ],
            $address_temp_2->id => [
                "parent_id" => $dealer_temp->id,
                "province_id" => $address_temp_2->province_id,
                "city_id" => $address_temp_2->city_id,
                "district_id" => $address_temp_2->district_id,
                "type" => $address_temp_2->type,
            ],
        ],
    ]);
    $response->assertStatus(200);
});

/**
 * V2
 * can not update batch
 */
test("sub dealer temp address: v2 batch, can not update address if exist", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp_1 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
    ]);

    $address_temp_2 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206080,
        "type" => "sub_dealer_owner",
    ]);

    $response = actingAsMarketing()->patchJson("/api/v2/address-detail-temp/batch", [
        "resources" => [
            $address_temp_1->id => [
                "parent_id" => $dealer_temp->id,
                "province_id" => 32,
                "city_id" => 3206,
                "district_id" => 3206080,
                "type" => "sub_dealer_owner",
            ],
        ],
    ]);
    $response->assertStatus(422);
});

test("sub dealer temp address: v2 batch, can not update duplicate address", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp_1 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
    ]);

    $address_temp_2 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206080,
        "type" => "sub_dealer_owner",
    ]);

    $response = actingAsMarketing()->patchJson("/api/v2/address-detail-temp/batch", [
        "resources" => [
            $address_temp_1->id => [
                "parent_id" => $dealer_temp->id,
                "province_id" => 32,
                "city_id" => 3206,
                "district_id" => 3206080,
                "type" => "sub_dealer",
            ],
            $address_temp_2->id => [
                "parent_id" => $dealer_temp->id,
                "province_id" => 32,
                "city_id" => 3206,
                "district_id" => 3206080,
                "type" => "sub_dealer",
            ],
        ],
    ]);

    $response->assertStatus(422);
});

/**
 * V2
 * can update single
 */
test("sub dealer temp address: v2 single, can update address with valid data", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp_1 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/address-detail-temp/" . $address_temp_1->id, [
        "parent_id" => $dealer_temp->id,
        "province_id" => $address_temp_1->province_id,
        "city_id" => $address_temp_1->city_id,
        "district_id" => 3206070,
        "type" => $address_temp_1->type,
    ]);
    $response->assertStatus(200);
});

/**
 * V2
 * can not update single
 */
test("sub dealer temp address: v2 single, can not update address if there exist", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp_1 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
    ]);
    
    $address_temp_2 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206070,
        "type" => "sub_dealer",
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/address-detail-temp/" . $address_temp_1->id, [
        "parent_id" => $dealer_temp->id,
        "province_id" => $address_temp_1->province_id,
        "city_id" => $address_temp_1->city_id,
        "district_id" => 3206070,
        "type" => $address_temp_1->type,
    ]);
    
    $response->assertStatus(422);
});

/**
 * ================================================================================================================================
 * V1
 * can create batch
 */
test("sub dealer temp address: v1 batch, can create address with valid data", function () {
    $dealer_temp = SubDealerTemp::factory()->create();

    $response = actingAsMarketing()->postJson("/api/v1/address-detail-temp/batch", [
        "resources" => [
            [
                "province_id" => "94",
                "city_id" => "9433",
                "district_id" => "9433020",
                "type" => "sub_dealer",
                "parent_id" => $dealer_temp->id,
            ],
            [
                "province_id" => "94",
                "city_id" => "9433",
                "district_id" => "9433020",
                "type" => "sub_dealer_owner",
                "parent_id" => $dealer_temp->id,
            ],
        ],
    ]);
    $response->assertStatus(200);
});

/**
 * V1
 * can not create batch
 */
test("sub dealer temp address: v1 batch, can not create address if exist", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
    ]);

    $response = actingAsMarketing()->postJson("/api/v1/address-detail-temp/batch", [
        "resources" => [
            [
                "province_id" => $address_temp->province_id,
                "city_id" => $address_temp->city_id,
                "district_id" => $address_temp->district_id,
                "type" => $address_temp->type,
                "parent_id" => $address_temp->parent_id,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("sub dealer temp address: v1 batch, can not create duplicate address", function () {
    $dealer_temp = SubDealerTemp::factory()->create();

    $response = actingAsMarketing()->postJson("/api/v1/address-detail-temp/batch", [
        "resources" => [
            [
                "province_id" => "94",
                "city_id" => "94330",
                "district_id" => "9433020",
                "type" => "sub_dealer",
                "parent_id" => $dealer_temp->id,
            ],
            [
                "province_id" => "94",
                "city_id" => "94330",
                "district_id" => "9433020",
                "type" => "sub_dealer",
                "parent_id" => $dealer_temp->id,
            ],
        ],
    ]);
    $response->assertStatus(422);
});

/**
 * V1
 * can create single
 */
test("sub dealer temp address: v1 single, can create address with valid data", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $response = actingAsMarketing()->postJson("/api/v1/address-detail-temp", [
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
        "parent_id" => $dealer_temp->id,
    ]);

    $response->assertStatus(201);
});

/**
 * V1
 * can not create single
 */
test("sub dealer temp address: v1 single, can not create address if exist", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
    ]);

    $response = actingAsMarketing()->postJson("/api/v1/address-detail-temp", [
        "province_id" => $address_temp->province_id,
        "city_id" => $address_temp->city_id,
        "district_id" => $address_temp->district_id,
        "type" => $address_temp->type,
        "parent_id" => $address_temp->parent_id,
    ]);

    $response->assertStatus(422);
});

/**
 * V1
 * can update batch
 */
test("sub dealer temp address: v1 batch, can update address with valid data", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp_1 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
    ]);

    $address_temp_2 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206080,
        "type" => "sub_dealer_owner",
    ]);

    $response = actingAsMarketing()->patchJson("/api/v1/address-detail-temp/batch", [
        "resources" => [
            $address_temp_1->id => [
                "parent_id" => $dealer_temp->id,
                "province_id" => $address_temp_1->province_id,
                "city_id" => $address_temp_1->city_id,
                "district_id" => $address_temp_1->district_id,
                "type" => $address_temp_1->type,
            ],
            $address_temp_2->id => [
                "parent_id" => $dealer_temp->id,
                "province_id" => $address_temp_2->province_id,
                "city_id" => $address_temp_2->city_id,
                "district_id" => $address_temp_2->district_id,
                "type" => $address_temp_2->type,
            ],
        ],
    ]);
    $response->assertStatus(200);
});

/**
 * V1
 * can not update batch
 */
test("sub dealer temp address: v1 batch, can not update address if exist", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp_1 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
    ]);

    $address_temp_2 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206080,
        "type" => "sub_dealer_owner",
    ]);

    $response = actingAsMarketing()->patchJson("/api/v1/address-detail-temp/batch", [
        "resources" => [
            $address_temp_1->id => [
                "parent_id" => $dealer_temp->id,
                "province_id" => 32,
                "city_id" => 3206,
                "district_id" => 3206080,
                "type" => "sub_dealer_owner",
            ],
        ],
    ]);
    $response->assertStatus(422);
});

test("sub dealer temp address: v1 batch, can not update duplicate address", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp_1 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
    ]);

    $address_temp_2 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206080,
        "type" => "sub_dealer_owner",
    ]);

    $response = actingAsMarketing()->patchJson("/api/v2/address-detail-temp/batch", [
        "resources" => [
            $address_temp_1->id => [
                "parent_id" => $dealer_temp->id,
                "province_id" => 32,
                "city_id" => 3206,
                "district_id" => 3206080,
                "type" => "sub_dealer",
            ],
            $address_temp_2->id => [
                "parent_id" => $dealer_temp->id,
                "province_id" => 32,
                "city_id" => 3206,
                "district_id" => 3206080,
                "type" => "sub_dealer",
            ],
        ],
    ]);

    $response->assertStatus(422);
});

/**
 * V1
 * can update single
 */
test("sub dealer temp address: v1 single, can update address with valid data", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp_1 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/address-detail-temp/" . $address_temp_1->id, [
        "parent_id" => $dealer_temp->id,
        "province_id" => $address_temp_1->province_id,
        "city_id" => $address_temp_1->city_id,
        "district_id" => 3206070,
        "type" => $address_temp_1->type,
    ]);
    $response->assertStatus(200);
});

 /**
 * V1
 * can not update single
 */
test("sub dealer temp address: v1 single, can not update address if there exist", function () {
    $dealer_temp = SubDealerTemp::factory()->create();
    $address_temp_1 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206061,
        "type" => "sub_dealer",
    ]);
    
    $address_temp_2 = AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "parent_id" => $dealer_temp->id,
        "province_id" => 32,
        "city_id" => 3206,
        "district_id" => 3206070,
        "type" => "sub_dealer",
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/address-detail-temp/" . $address_temp_1->id, [
        "parent_id" => $dealer_temp->id,
        "province_id" => $address_temp_1->province_id,
        "city_id" => $address_temp_1->city_id,
        "district_id" => 3206070,
        "type" => $address_temp_1->type,
    ]);
    
    $response->assertStatus(422);
});