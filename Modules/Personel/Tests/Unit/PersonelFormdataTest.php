<?php

use App\Models\Address;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\Bank;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonelBank;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("personel form data test", function () {
    $spv = Personel::factory()->create();
    $personel = Personel::factory()->create([
        "supervisor_id" => $spv->id,
    ]);

    $address = Address::factory()->create([
        "type" => "kos",
        "parent_id" => $personel->id,
    ]);

    Address::factory()->create([
        "type" => "rumah",
        "parent_id" => $personel->id,
    ]);

    Bank::factory()->create();
    $bank_1 = PersonelBank::factory()->create([
        "personel_id" => $personel->id,
    ]);
    $bank_2 = PersonelBank::factory()->create([
        "personel_id" => $personel->id,
    ]);

    Contact::factory()->create([
        "parent_id" => $personel->id,
    ]);

    $response = actingAsSupport()->getJson("/api/v1/personnel/personnel-form-data/" . $personel->id);
    $response->assertStatus(200);

    expect($response->getdata()->data)->toHaveKeys([
        "detail",
        "address",
        "banks",
        "contacts",
    ]);
    expect($response->getdata()->data->detail)->toHaveKeys([
        "supervisor_name",
        "position_name",
        "organisation_name",
        "religion_name",
        "citizenship_id",
        "citizenship_name",
    ]);

    expect(count($response->getdata()->data->address))->toEqual(2);
    expect($response->getdata()->data->address[0])->toHaveKeys([
        "id",
        "type",
        "gmaps_link",
        "address",
    ]);

    expect(count($response->getdata()->data->banks))->toEqual(2);
    expect($response->getdata()->data->banks[0])->toHaveKeys([
        "id",
        "owner",
        "rek_number",
        "name",
    ]);

    expect(count($response->getdata()->data->contacts))->toEqual(1);
    expect($response->getdata()->data->contacts[0])->toHaveKeys([
        "id",
        "contact_type",
        "data",
    ]);
});
