<?php
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Address\Entities\AddressTemp;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerTemp;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * -------------------------------------
 * CREATE
 * ------------------------------
 */
test("dealer v1, can create with valid submission", function () {
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner",
    ]);

    $dealer_temp = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer", $dealer_temp);
    $response->assertStatus(200);
});

test("dealer v1, can not create if has no valid submission", function () {
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner",
    ]);

    $dealer_temp = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer", $dealer_temp);
    $response->assertStatus(422);
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("this dealer has no submission");
});

test("dealer v1, can not createif has no address submission", function () {
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
    ]);

    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer", $dealer_temp_data);
    $response->assertStatus(422);
    $dealer_temp = DB::table('dealer_temps')
        ->where("id", $dealer_temp->id)
        ->first();
    expect($dealer_temp->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("dealer invalid address detail submission");
});

test("dealer v1, can not create if has no valid address submission - address count > 2", function () {
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner",
    ]);

    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer", $dealer_temp_data);
    $response->assertStatus(422);
    $dealer_temp = DB::table('dealer_temps')
        ->where("id", $dealer_temp->id)
        ->first();

    $dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer")
        ->first();

    $dealer_owner_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer_owner")
        ->first();

    expect($dealer_temp->deleted_at)->toBeNull();
    expect($dealer_address->deleted_at)->toBeNull();
    expect($dealer_owner_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("dealer invalid address detail submission");
});

test("dealer v1, can not create if has no valid address submission - address count < 2", function () {
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);

    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer", $dealer_temp_data);
    $response->assertStatus(422);
    $dealer_temp = DB::table('dealer_temps')
        ->where("id", $dealer_temp->id)
        ->first();

    $dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer")
        ->first();

    expect($dealer_temp->deleted_at)->toBeNull();
    expect($dealer_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("dealer invalid address detail submission");
});

test("dealer v1, can not create if has no valid address submission - address unique type count > 2", function () {
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner_owner",
    ]);

    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer", $dealer_temp_data);
    $response->assertStatus(422);
    $dealer_temp = DB::table('dealer_temps')
        ->where("id", $dealer_temp->id)
        ->first();

    $sub_dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer")
        ->first();

    $sub_dealer_owner_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer_owner")
        ->first();

    expect($dealer_temp->deleted_at)->toBeNull();
    expect($sub_dealer_address->deleted_at)->toBeNull();
    expect($sub_dealer_owner_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("dealer invalid address detail submission");
});

test("dealer v1, can not create if has no valid address submission - address unique type count < 2", function () {
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);

    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer", $dealer_temp_data);
    $response->assertStatus(422);
    $dealer_temp = DB::table('dealer_temps')
        ->where("id", $dealer_temp->id)
        ->first();

    $dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer")
        ->first();

    expect($dealer_temp->deleted_at)->toBeNull();
    expect($dealer_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("dealer invalid address detail submission");
});

/**
 * ------------------------------------------
 * UPDATE
 * -----------------------------------
 */
test("dealer v1, can update with valid submission", function () {
    $dealer = Dealer::factory()->create([
        "status" => "submission of changes",
    ]);

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner",
    ]);

    $dealer_temp = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer/" . $dealer->id, $dealer_temp);
    $response->assertStatus(200);
});

test("dealer v1, can not update if has no valid submission", function () {
    $dealer = Dealer::factory()->create([
        "status" => "submission of changes",
    ]);

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner",
    ]);

    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer/" . $dealer->id, $dealer_temp_data);
    $response->assertStatus(422);
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("this dealer has no submission");
});

test("dealer v1,  can not update if has no address submission", function () {
    $dealer = Dealer::factory()->create([
        "status" => "submission of changes",
    ]);

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
    ]);

    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer/" . $dealer->id, $dealer_temp_data);
    $response->assertStatus(422);

    $dealer_temp = DB::table('dealer_temps')
        ->where("id", $dealer_temp->id)
        ->first();

    expect($dealer_temp->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("dealer invalid address detail submission");
});

test("dealer v1,  can not update if has no valid address submission - address count > 2", function () {
    $dealer = Dealer::factory()->create([
        "status" => "submission of changes",
    ]);

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner",
    ]);

    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer/" . $dealer->id, $dealer_temp_data);
    $response->assertStatus(422);
    $dealer_temp = DB::table('dealer_temps')
        ->where("id", $dealer_temp->id)
        ->first();

    $dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer")
        ->first();

    $dealer_owner_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer_owner")
        ->first();

    expect($dealer_temp->deleted_at)->toBeNull();
    expect($dealer_address->deleted_at)->toBeNull();
    expect($dealer_owner_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("dealer invalid address detail submission");
});

test("dealer v1,  can not update if has no valid address submission - address count < 2", function () {
    $dealer = Dealer::factory()->create([
        "status" => "submission of changes",
    ]);

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);

    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer/" . $dealer->id, $dealer_temp_data);
    $response->assertStatus(422);
    $dealer_temp = DB::table('dealer_temps')
        ->where("id", $dealer_temp->id)
        ->first();

    $dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer")
        ->first();

    expect($dealer_temp->deleted_at)->toBeNull();
    expect($dealer_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("dealer invalid address detail submission");
});

test("dealer v1,  can not update if has no valid address submission - address unique type count > 2", function () {
    $dealer = Dealer::factory()->create([
        "status" => "submission of changes",
    ]);

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer_owner_owner",
    ]);

    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer/" . $dealer->id, $dealer_temp_data);
    $response->assertStatus(422);
    $dealer_temp = DB::table('dealer_temps')
        ->where("id", $dealer_temp->id)
        ->first();

    $sub_dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer")
        ->first();

    $sub_dealer_owner_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer_owner")
        ->first();

    expect($dealer_temp->deleted_at)->toBeNull();
    expect($sub_dealer_address->deleted_at)->toBeNull();
    expect($sub_dealer_owner_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("dealer invalid address detail submission");
});

test("dealer v1,  can not update if has no valid address submission - address unique type count < 2", function () {
    $dealer = Dealer::factory()->create([
        "status" => "submission of changes",
    ]);

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
    ]);

    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);
    AddressTemp::factory()->create([
        "parent_id" => $dealer_temp->id,
        "type" => "dealer",
    ]);


    $dealer_temp_data = collect($dealer_temp)
        ->only([
            "status",
            "status_color",
            "personel_id",
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

    $response = actingAsSupport()->putJson("/api/v1/dealer/dealer/" . $dealer->id, $dealer_temp_data);
    $response->assertStatus(422);
    $dealer_temp = DB::table('dealer_temps')
        ->where("id", $dealer_temp->id)
        ->first();

    $dealer_address = DB::table('address_with_detail_temps')
        ->whereNull("deleted_at")
        ->where("parent_id", $dealer_temp->id)
        ->where("type", "dealer")
        ->first();

    expect($dealer_temp->deleted_at)->toBeNull();
    expect($dealer_address->deleted_at)->toBeNull();
    expect($response->getData()->data->status)->toBeArray();
    expect($response->getData()->data->status[0])->toEqual("dealer invalid address detail submission");
});
