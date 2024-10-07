<?php
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\Address;
use Modules\KiosDealer\Entities\Store;
use Modules\Address\Entities\AddressTemp;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\DealerFile;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Modules\KiosDealer\Entities\DealerFileTemp;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\KiosDealer\Entities\SubDealerChangeHistory;

uses(Tests\TestCase::class, DatabaseTransactions::class);

// test("sub dealer approval: approve on draft submission", function () {
//     $sub_dealer = SubDealer::factory()->create();
//     $customer_id = $sub_dealer->sub_dealer_id;

//     $sub_dealer_temp = SubDealerTemp::factory()->create([
//         "sub_dealer_id" => $sub_dealer->id,
//         "status" => "draft",
//         "name" => "sub dealer",
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/dealer/sub-dealer-temp/" . $sub_dealer_temp->id . "/approve");
//     $response->assertStatus(422);
// });

// test("sub dealer approval: submission of changes", function () {
//     $sub_dealer = SubDealer::factory()->create();
//     $customer_id = $sub_dealer->sub_dealer_id;

//     $sub_dealer_temp = SubDealerTemp::factory()->create([
//         "sub_dealer_id" => $sub_dealer->id,
//         "status" => "submission of changes",
//         "name" => "sub dealer",
//     ]);

//     SubDealerChangeHistory::create([
//         "sub_dealer_id" => $sub_dealer->id,
//         "sub_dealer_temp_id" => $sub_dealer_temp->id,
//         "submited_at" => now(),
//         "submited_by" => $sub_dealer->personel_id,
//         "confirmed_by" => $sub_dealer->personel_id,
//         "confirmed_at" => now(),
//         "approved_at" => null,
//         "approved_by" => null,
//     ]);

//     $area = MarketingAreaDistrict::factory()->create();

//     $area_2 = MarketingAreaDistrict::factory()->create([
//         "sub_region_id" => $area->sub_region_id,
//         "province_id" => "94",
//         "city_id" => "9471",
//         "district_id" => "9471020",
//     ]);

//     Address::factory()->createMany([
//         [
//             "district_id" => $area_2->district_id,
//             "city_id" => $area_2->city_id,
//             "province_id" => $area_2->province_id,
//             "type" => "sub_dealer",
//             "parent_id" => $sub_dealer->id,
//         ],
//         [
//             "district_id" => $area_2->district_id,
//             "city_id" => $area_2->city_id,
//             "province_id" => $area_2->province_id,
//             "type" => "sub_dealer_owner",
//             "parent_id" => $sub_dealer->id,
//         ],
//     ]);

//     AddressTemp::factory()->createMany([
//         [
//             "district_id" => $area->district_id,
//             "city_id" => $area->city_id,
//             "province_id" => $area->province_id,
//             "type" => "sub_dealer",
//             "parent_id" => $sub_dealer_temp->id,
//         ],
//         [
//             "district_id" => $area->district_id,
//             "city_id" => $area->city_id,
//             "province_id" => $area->province_id,
//             "type" => "sub_dealer_owner",
//             "parent_id" => $sub_dealer_temp->id,
//         ],
//     ]);

//     DealerFile::factory()->createMany([
//         [
//             "dealer_id" => $sub_dealer->id,
//             "file_type" => "SUBDEALER",
//             "data" => "xxx.jpd",
//         ],
//         [
//             "dealer_id" => $sub_dealer->id,
//             "file_type" => "SIM",
//             "data" => "xxx.jpd",
//         ],
//     ]);

//     DealerFileTemp::factory()->createMany([
//         [
//             "dealer_id" => $sub_dealer_temp->id,
//             "file_type" => "SUBDEALER",
//             "data" => "xxx.jpd",
//         ],
//         [
//             "dealer_id" => $sub_dealer_temp->id,
//             "file_type" => "SIM",
//             "data" => "xxx.jpd",
//         ],
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/dealer/sub-dealer-temp/" . $sub_dealer_temp->id . "/approve");
//     $response->assertStatus(200);

//     $sub_dealer->refresh();
//     $sub_dealer->load([
//         "addressDetail",
//         "subDealerFile",
//     ]);

//     $sub_dealer_temp->refresh();
//     $sub_dealer_temp->load([
//         "storeFix",
//         "subDealerFile",
//         "subDealerFix",
//         "addressDetail",
//         "subDealerChangeHistory.subDealerDataHistory",
//         "subDealerChangeHistory.subDealerDataHistory.subDealerAddresses",
//         "subDealerChangeHistory.subDealerDataHistory.subDealerFileHistories",
//     ]);

//     expect(strtolower($sub_dealer->name))->toEqual(strtolower($sub_dealer_temp->name));
//     expect(strtolower($sub_dealer->status))->toEqual("accepted");
//     expect($sub_dealer->personel_id)->toEqual($area->personel_id);

//     expect($sub_dealer_temp->subDealerChangeHistory->approved_at)->toBeTruthy();
//     expect($sub_dealer_temp->subDealerChangeHistory->approved_by)->toBeTruthy();
//     expect($sub_dealer_temp->subDealerChangeHistory->subDealerDataHistory)->toBeObject();
//     expect($sub_dealer_temp->subDealerChangeHistory->subDealerDataHistory->subDealerAddresses)->toHaveCount(2);
//     expect($sub_dealer_temp->subDealerChangeHistory->subDealerDataHistory->subDealerFileHistories)->toHaveCount(2);

//     expect($sub_dealer->addressDetail)->toHaveCount(2);
//     expect($sub_dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
//     expect($sub_dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

//     expect($sub_dealer->subDealerFile)->toHaveCount(2);
//     expect($sub_dealer->sub_dealer_id)->toEqual($customer_id);
//     expect($sub_dealer_temp->deleted_at)->toBeTruthy();
// });

test("sub dealer approval: new sub dealer", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "submission of changes",
        "name" => "dealer-xxx-yyy-123",
    ]);

    SubDealerChangeHistory::create([
        "sub_dealer_temp_id" => $sub_dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $sub_dealer_temp->personel_id,
        "confirmed_by" => $sub_dealer_temp->personel_id,
        "confirmed_at" => now(),
        "approved_at" => null,
        "approved_by" => null,
    ]);

    $area = MarketingAreaDistrict::factory()->create();

    AddressTemp::factory()->createMany([
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "sub_dealer",
            "parent_id" => $sub_dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "sub_dealer_owner",
            "parent_id" => $sub_dealer_temp->id,
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $sub_dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $sub_dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/sub-dealer-temp/" . $sub_dealer_temp->id . "/approve");
    $response->assertStatus(200);

    $sub_dealer = SubDealer::query()
        ->with([
            "addressDetail",
            "subDealerFile",
        ])
        ->where("name", "dealer-xxx-yyy-123")
        ->first();

    $sub_dealer_temp->refresh();
    $sub_dealer_temp->load([
        "storeFix",
        "subDealerFile",
        "subDealerFix",
        "addressDetail",
    ]);
    $agency_level_id = DB::table('agency_levels')->where('name', 'R3')->first();
    $grading_id = DB::table('gradings')->where("default", true)->first();
    $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "R")->first();

    expect($sub_dealer->status_fee)->toEqual($status_fee->id);
    expect($sub_dealer->agency_level_id)->toEqual($agency_level_id->id);
    expect($sub_dealer->grading_id)->toEqual($grading_id->id);

    expect(strtolower($sub_dealer->name))->toEqual("dealer-xxx-yyy-123");
    expect(strtolower($sub_dealer->status))->toEqual("accepted");
    expect($sub_dealer->personel_id)->toEqual($area->personel_id);
    expect($sub_dealer->sub_dealer_id)->toBeTruthy();

    expect($sub_dealer_temp->subDealerChangeHistory->approved_at)->toBeTruthy();
    expect($sub_dealer_temp->subDealerChangeHistory->approved_by)->toBeTruthy();

    expect($sub_dealer->addressDetail)->toHaveCount(2);
    expect($sub_dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
    expect($sub_dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

    expect($sub_dealer->subDealerFile)->toHaveCount(2);
    expect($sub_dealer_temp->deleted_at)->toBeTruthy();
});

test("sub dealer approval: new sub dealer transfer from store", function () {
    $store = Store::factory()->create();
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "submission of changes",
        "name" => "dealer-xxx-yyy-123",
        "store_id" => $store->id,
    ]);

    SubDealerChangeHistory::create([
        "sub_dealer_temp_id" => $sub_dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $sub_dealer_temp->personel_id,
        "confirmed_by" => $sub_dealer_temp->personel_id,
        "confirmed_at" => now(),
        "approved_at" => null,
        "approved_by" => null,
    ]);

    $area = MarketingAreaDistrict::factory()->create();

    AddressTemp::factory()->createMany([
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "sub_dealer",
            "parent_id" => $sub_dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "sub_dealer_owner",
            "parent_id" => $sub_dealer_temp->id,
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $sub_dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $sub_dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/sub-dealer-temp/" . $sub_dealer_temp->id . "/approve");
    $response->assertStatus(200);

    $sub_dealer = SubDealer::query()
        ->with([
            "addressDetail",
            "subDealerFile",
        ])
        ->where("name", "dealer-xxx-yyy-123")
        ->first();

    $store = Store::query()
        ->withTrashed()
        ->findOrFail($sub_dealer_temp->store_id);

    $sub_dealer_temp->refresh();
    $sub_dealer_temp->load([
        "storeFix",
        "subDealerFile",
        "subDealerFix",
        "addressDetail",
    ]);
    $agency_level_id = DB::table('agency_levels')->where('name', 'R3')->first();
    $grading_id = DB::table('gradings')->where("default", true)->first();
    $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "R")->first();

    expect($sub_dealer->status_fee)->toEqual($status_fee->id);
    expect($sub_dealer->agency_level_id)->toEqual($agency_level_id->id);
    expect($sub_dealer->grading_id)->toEqual($grading_id->id);

    expect(strtolower($sub_dealer->name))->toEqual("dealer-xxx-yyy-123");
    expect(strtolower($sub_dealer->status))->toEqual("accepted");
    expect($sub_dealer->personel_id)->toEqual($area->personel_id);

    expect($sub_dealer_temp->subDealerChangeHistory->approved_at)->toBeTruthy();
    expect($sub_dealer_temp->subDealerChangeHistory->approved_by)->toBeTruthy();

    expect($sub_dealer->addressDetail)->toHaveCount(2);
    expect($sub_dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
    expect($sub_dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

    expect($sub_dealer->subDealerFile)->toHaveCount(2);
    expect($store->status)->toEqual("transfered");
    expect($store->sub_dealer_id)->toEqual($sub_dealer->id);
    expect($store->deleted_at)->toBeFalsy();
    expect($sub_dealer_temp->deleted_at)->toBeTruthy();
});
