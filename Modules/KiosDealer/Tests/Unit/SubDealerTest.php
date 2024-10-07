<?php
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Address\Entities\AddressTemp;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\SubDealerTemp;

uses(Tests\TestCase::class, DatabaseTransactions::class);

ini_set('max_execution_time', 6000); // Set max_execution_time to 60 seconds

test("delete sub dealer that become dealer", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $dealer = Dealer::factory()->create([
        "status" => "accepted",
        "status_color" => "000000",
    ]);

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer/" . $sub_dealer->id, [
        "status" => "transfered",
        "status_color" => "000000",
        "dealer_id" => $dealer->id,
    ]);
    
    $sub_dealer = DB::table('sub_dealers')->where("id", $sub_dealer->id)->first();
    $response->assertStatus(200);
    expect(0)->toBeFalsy($sub_dealer);
    
});

/**
 * ------------------------------------------
 * CREATE
 * -----------------------------------
 */
test("sub dealer, can create if has valid submission", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "filed",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer_owner",
    ]);

    $sub_dealer_temp = collect($sub_dealer_temp)
        ->only([
            "status",
            "personel_id",
            "distributor_id",
            "prefix",
            "name",
            "sufix",
            "address",
            "telephone",
            "second_telephone",
            "latitude",
            "longitude",
            "owner",
            "owner_address",
            "owner_ktp",
            "owner_npwp",
            "owner_telephone",
            "email",
            "entity_id",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer", $sub_dealer_temp);
    $response->assertStatus(200);
});

test("sub dealer, can not create if has no submission", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "filed",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer_owner",
    ]);
    $sub_dealer_temp = collect($sub_dealer_temp)
        ->except([
            "id",
            "prefix_id",
            "sub_dealer_id",
            "note",
            "change_note",
            "handover_status",
            "grading_id",
            "store_id",
            "submited_by",
            "submited_at",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            if ($field == "name") {
                $value = "xXxXxX";
            }
            return $value;
        })
        ->toArray();
    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer", $sub_dealer_temp);
    $response->assertStatus(422);
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("this sub dealer has no submission");
});

test("sub dealer, can not create if has no address submission", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "filed",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->except([
            "id",
            "prefix_id",
            "sub_dealer_id",
            "note",
            "change_note",
            "handover_status",
            "grading_id",
            "store_id",
            "submited_by",
            "submited_at",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();
    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer", $sub_dealer_temp_data);
    $response->assertStatus(422);

    $sub_dealer_temp = DB::table('sub_dealer_temps')
        ->where("id", $sub_dealer_temp->id)
        ->first();

    expect($sub_dealer_temp->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("sub dealer invalid address detail submission");
});

test("sub dealer, can not create if has no valid address submission - address count > 2", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "filed",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer_owner",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->except([
            "id",
            "prefix_id",
            "sub_dealer_id",
            "note",
            "change_note",
            "handover_status",
            "grading_id",
            "store_id",
            "submited_by",
            "submited_at",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();
    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer", $sub_dealer_temp_data);
    $response->assertStatus(422);
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("sub dealer invalid address detail submission");

    $sub_dealer_temp = DB::table('sub_dealer_temps')
        ->where("id", $sub_dealer_temp->id)
        ->first();

    $sub_dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer")
        ->first();

    $sub_dealer_owner_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer_owner")
        ->first();

    expect($sub_dealer_temp->deleted_at)->toBeNull();
    expect($sub_dealer_address->deleted_at)->toBeNull();
    expect($sub_dealer_owner_address->deleted_at)->toBeNull();

});

test("sub dealer, can not create if has no valid address submission - address count < 2", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "filed",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->except([
            "id",
            "prefix_id",
            "sub_dealer_id",
            "note",
            "change_note",
            "handover_status",
            "grading_id",
            "store_id",
            "submited_by",
            "submited_at",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();
    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer", $sub_dealer_temp_data);
    $response->assertStatus(422);
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("sub dealer invalid address detail submission");

    $sub_dealer_temp = DB::table('sub_dealer_temps')
        ->where("id", $sub_dealer_temp->id)
        ->first();

    $sub_dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer")
        ->first();

    expect($sub_dealer_temp->deleted_at)->toBeNull();
    expect($sub_dealer_address->deleted_at)->toBeNull();
});

test("sub dealer, can not create if has no valid address submission - address unique type count > 2", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "filed",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer_owner",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer_owner_rrr",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->except([
            "id",
            "prefix_id",
            "sub_dealer_id",
            "note",
            "change_note",
            "handover_status",
            "grading_id",
            "store_id",
            "submited_by",
            "submited_at",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();
    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer", $sub_dealer_temp_data);
    $response->assertStatus(422);
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("sub dealer invalid address detail submission");
    $sub_dealer_temp = DB::table('sub_dealer_temps')
        ->where("id", $sub_dealer_temp->id)
        ->first();

    $sub_dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer")
        ->first();

    $sub_dealer_owner_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer_owner")
        ->first();

    expect($sub_dealer_temp->deleted_at)->toBeNull();
    expect($sub_dealer_address->deleted_at)->toBeNull();
    expect($sub_dealer_owner_address->deleted_at)->toBeNull();
});

test("sub dealer, can not create if has no valid address submission - address unique type count < 2", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "filed",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->except([
            "id",
            "prefix_id",
            "sub_dealer_id",
            "note",
            "change_note",
            "handover_status",
            "grading_id",
            "store_id",
            "submited_by",
            "submited_at",
            "created_at",
            "updated_at",
            "deleted_at",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();
    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer", $sub_dealer_temp_data);
    $response->assertStatus(422);
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("sub dealer invalid address detail submission");
    $sub_dealer_temp = DB::table('sub_dealer_temps')
        ->where("id", $sub_dealer_temp->id)
        ->first();

    $sub_dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer")
        ->first();

    expect($sub_dealer_temp->deleted_at)->toBeNull();
    expect($sub_dealer_address->deleted_at)->toBeNull();
});

/**
 * ------------------------------------------
 * UPDATE
 * -----------------------------------
 */
test("sub dealer, can update if has valid submission", function () {
    $sub_dealer = SubDealer::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "submission of changes",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer_owner",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
            "distributor_id",
            "prefix",
            "name",
            "sufix",
            "address",
            "telephone",
            "second_telephone",
            "latitude",
            "longitude",
            "owner",
            "owner_address",
            "owner_ktp",
            "owner_npwp",
            "owner_telephone",
            "email",
            "entity_id",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer/" . $sub_dealer->id, $sub_dealer_temp_data);
    $response->assertStatus(200);
});

test("sub dealer, can not update if has no valid submission", function () {
    $sub_dealer = SubDealer::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "submission of changes",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer_owner",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
            "distributor_id",
            "prefix",
            "name",
            "sufix",
            "address",
            "telephone",
            "second_telephone",
            "latitude",
            "longitude",
            "owner",
            "owner_address",
            "owner_ktp",
            "owner_npwp",
            "owner_telephone",
            "email",
            "entity_id",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            if ($field == "name") {
                $value = "xXxXxX";
            }
            return $value;
        })
        ->toArray();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer/" . $sub_dealer->id, $sub_dealer_temp_data);
    $response->assertStatus(422);

    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("this sub dealer has no submission");
});

test("sub dealer, can not update if has no address submission", function () {
    $sub_dealer = SubDealer::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "submission of changes",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
            "distributor_id",
            "prefix",
            "name",
            "sufix",
            "address",
            "telephone",
            "second_telephone",
            "latitude",
            "longitude",
            "owner",
            "owner_address",
            "owner_ktp",
            "owner_npwp",
            "owner_telephone",
            "email",
            "entity_id",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer/" . $sub_dealer->id, $sub_dealer_temp_data);
    $response->assertStatus(422);

    $sub_dealer_temp = DB::table('sub_dealer_temps')
        ->where("id", $sub_dealer_temp->id)
        ->first();
    expect($sub_dealer_temp->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("sub dealer invalid address detail submission");
});

test("sub dealer, can not update if has no valid address submission - address count > 2", function () {
    $sub_dealer = SubDealer::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "submission of changes",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer_owner",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
            "distributor_id",
            "prefix",
            "name",
            "sufix",
            "address",
            "telephone",
            "second_telephone",
            "latitude",
            "longitude",
            "owner",
            "owner_address",
            "owner_ktp",
            "owner_npwp",
            "owner_telephone",
            "email",
            "entity_id",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer/" . $sub_dealer->id, $sub_dealer_temp_data);
    $response->assertStatus(422);

    $sub_dealer_temp = DB::table('sub_dealer_temps')
        ->where("id", $sub_dealer_temp->id)
        ->first();

    $sub_dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer")
        ->first();

    $sub_dealer_owner_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer_owner")
        ->first();

    expect($sub_dealer_temp->deleted_at)->toBeNull();
    expect($sub_dealer_address->deleted_at)->toBeNull();
    expect($sub_dealer_owner_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("sub dealer invalid address detail submission");
});

test("sub dealer, can not update if has no valid address submission - address count < 2", function () {
    $sub_dealer = SubDealer::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "submission of changes",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
            "distributor_id",
            "prefix",
            "name",
            "sufix",
            "address",
            "telephone",
            "second_telephone",
            "latitude",
            "longitude",
            "owner",
            "owner_address",
            "owner_ktp",
            "owner_npwp",
            "owner_telephone",
            "email",
            "entity_id",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer/" . $sub_dealer->id, $sub_dealer_temp_data);
    $response->assertStatus(422);

    $sub_dealer_temp = DB::table('sub_dealer_temps')
        ->where("id", $sub_dealer_temp->id)
        ->first();

    $sub_dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer")
        ->first();

    expect($sub_dealer_temp->deleted_at)->toBeNull();
    expect($sub_dealer_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("sub dealer invalid address detail submission");
});

test("sub dealer, can not update if has no valid address submission - address unique type count > 2", function () {
    $sub_dealer = SubDealer::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "submission of changes",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer_owner",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer_owner_rrr",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
            "distributor_id",
            "prefix",
            "name",
            "sufix",
            "address",
            "telephone",
            "second_telephone",
            "latitude",
            "longitude",
            "owner",
            "owner_address",
            "owner_ktp",
            "owner_npwp",
            "owner_telephone",
            "email",
            "entity_id",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer/" . $sub_dealer->id, $sub_dealer_temp_data);
    $response->assertStatus(422);
    $sub_dealer_temp = DB::table('sub_dealer_temps')
        ->where("id", $sub_dealer_temp->id)
        ->first();

    $sub_dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer")
        ->first();

    $sub_dealer_owner_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer_owner")
        ->first();

    expect($sub_dealer_temp->deleted_at)->toBeNull();
    expect($sub_dealer_address->deleted_at)->toBeNull();
    expect($sub_dealer_owner_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("sub dealer invalid address detail submission");
});

test("sub dealer,can not update if has no valid address submission - address unique type count < 2", function () {
    $sub_dealer = SubDealer::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "sub_dealer_id" => $sub_dealer->id,
        "status" => "submission of changes",
        "latitude" => "-7.4541444",
        "longitude" => "110.4412777",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $sub_dealer_temp->id,
        "type" => "sub_dealer",
    ]);

    $sub_dealer_temp_data = collect($sub_dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
            "distributor_id",
            "prefix",
            "name",
            "sufix",
            "address",
            "telephone",
            "second_telephone",
            "latitude",
            "longitude",
            "owner",
            "owner_address",
            "owner_ktp",
            "owner_npwp",
            "owner_telephone",
            "email",
            "entity_id",
        ])
        ->map(function ($value, $field) {
            if ($field == "status") {
                $value = "accepted";
            }
            if ($field == "status_color") {
                $value = "000000";
            }
            return $value;
        })
        ->toArray();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer/" . $sub_dealer->id, $sub_dealer_temp_data);
    $response->assertStatus(422);
    $sub_dealer_temp = DB::table('sub_dealer_temps')
        ->where("id", $sub_dealer_temp->id)
        ->first();

    $sub_dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $sub_dealer_temp->id)
        ->where("type", "sub_dealer")
        ->first();

    expect($sub_dealer_temp->deleted_at)->toBeNull();
    expect($sub_dealer_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("sub dealer invalid address detail submission");
});
