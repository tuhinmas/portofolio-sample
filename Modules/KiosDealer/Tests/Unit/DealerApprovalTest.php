<?php
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\Address;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Address\Entities\AddressTemp;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\KiosDealer\Entities\DealerFile;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\DealerGrading;
use Modules\KiosDealer\Entities\DealerFileTemp;
use Modules\KiosDealer\Entities\DealerChangeHistory;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\Distributor\Entities\DistributorContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("dealer approval: approve on draft submission", function () {
    $dealer = Dealer::factory()->create();
    $customer_id = $dealer->dealer_id;

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "submission of changes",
        "name" => "dealer",
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve");
    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toEqual("dealer submission status must 'wait approval'");
});

test("dealer approval: submission of changes from RM", function () {
    $dealer = Dealer::factory()->create();
    $customer_id = $dealer->dealer_id;

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
        "name" => "dealer",
    ]);

    DealerChangeHistory::create([
        "dealer_id" => $dealer->id,
        "dealer_temp_id" => $dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $dealer->personel_id,
        "confirmed_by" => $dealer->personel_id,
        "confirmed_at" => now(),
        "approved_at" => null,
        "approved_by" => null,
    ]);

    $area = MarketingAreaDistrict::factory()->create();

    $area_2 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $area->sub_region_id,
        "province_id" => "94",
        "city_id" => "9471",
        "district_id" => "9471020",
    ]);

    Address::factory()->createMany([
        [
            "district_id" => $area_2->district_id,
            "city_id" => $area_2->city_id,
            "province_id" => $area_2->province_id,
            "type" => "dealer",
            "parent_id" => $dealer->id,
        ],
        [
            "district_id" => $area_2->district_id,
            "city_id" => $area_2->city_id,
            "province_id" => $area_2->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer->id,
        ],
    ]);

    AddressTemp::factory()->createMany([
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer",
            "parent_id" => $dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer_temp->id,
        ],
    ]);

    DealerFile::factory()->createMany([
        [
            "dealer_id" => $dealer->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve");
    $response->assertStatus(200);

    $dealer_temp->refresh();
    $dealer->refresh();
    $dealer->load([
        "addressDetail",
        "dealerFile",
    ]);

    $dealer_temp->load([
        "storeFix",
        "dealerFix",
        "dealerFile",
        "subDealerFix",
        "addressDetail",
        "dealerChangeHistory.dealerDataHistory",
        "dealerChangeHistory.dealerDataHistory.dealerAddresses",
        "dealerChangeHistory.dealerDataHistory.dealerFileHistory",
    ]);

    expect(strtolower($dealer->name))->toEqual("dealer");
    expect(strtolower($dealer->status))->toEqual("accepted");
    expect($dealer->personel_id)->toEqual($area->personel_id);

    expect($dealer_temp->dealerChangeHistory->approved_at)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->approved_by)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory)->toBeObject();
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory->dealerAddresses)->toHaveCount(2);
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory->dealerFileHistories)->toHaveCount(2);

    expect($dealer->addressDetail)->toHaveCount(2);
    expect($dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
    expect($dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

    expect($dealer->dealerFile)->toHaveCount(2);
    expect($dealer->dealer_id)->toEqual($customer_id);
    expect($dealer_temp->deleted_at)->toBeTruthy();
});

test("dealer approval: submission of changes from RMC as retailer", function () {
    $dealer = Dealer::factory()->create();
    $customer_id = $dealer->dealer_id;
    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
        "name" => "dealer",
    ]);

    DealerChangeHistory::create([
        "dealer_id" => $dealer->id,
        "dealer_temp_id" => $dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $dealer->personel_id,
        "confirmed_by" => $dealer->personel_id,
        "confirmed_at" => now(),
        "approved_at" => null,
        "approved_by" => null,
    ]);

    $area = MarketingAreaDistrict::factory()->create([
        "personel_id" => $dealer->personel_id,
    ]);
    $area_2 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $area->sub_region_id,
        "province_id" => "94",
        "city_id" => "9471",
        "district_id" => "9471020",
    ]);

    $sub_region = SubRegion::find($area->sub_region_id);
    $sub_region->personel_id = $area->personel_id;
    $sub_region->save();

    Address::factory()->createMany([
        [
            "district_id" => $area_2->district_id,
            "city_id" => $area_2->city_id,
            "province_id" => $area_2->province_id,
            "type" => "dealer",
            "parent_id" => $dealer->id,
        ],
        [
            "district_id" => $area_2->district_id,
            "city_id" => $area_2->city_id,
            "province_id" => $area_2->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer->id,
        ],
    ]);

    AddressTemp::factory()->createMany([
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer",
            "parent_id" => $dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer_temp->id,
        ],
    ]);

    DealerFile::factory()->createMany([
        [
            "dealer_id" => $dealer->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve");
    $response->assertStatus(200);

    $area->load("subRegion", "subRegionOnly");
    $dealer->refresh();
    $dealer->load([
        "addressDetail",
        "dealerFile",
    ]);

    $dealer_temp->refresh();
    $dealer_temp->load([
        "storeFix",
        "dealerFix",
        "dealerFile",
        "subDealerFix",
        "addressDetail",
        "dealerChangeHistory.dealerDataHistory",
        "dealerChangeHistory.dealerDataHistory.dealerAddresses",
        "dealerChangeHistory.dealerDataHistory.dealerFileHistory",
    ]);

    expect(strtolower($dealer->name))->toEqual("dealer");
    expect(strtolower($dealer->status))->toEqual("accepted");
    expect($dealer->personel_id)->toEqual($area->subRegionOnly->personel_id);
    expect($dealer->personel_id)->toEqual($area->personel_id);

    expect($dealer_temp->dealerChangeHistory->approved_at)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->approved_by)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory)->toBeObject();
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory->dealerAddresses)->toHaveCount(2);
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory->dealerFileHistories)->toHaveCount(2);

    expect($dealer->addressDetail)->toHaveCount(2);
    expect($dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
    expect($dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

    expect($dealer->dealerFile)->toHaveCount(2);
    expect($dealer->dealer_id)->toEqual($customer_id);
    expect($dealer_temp->deleted_at)->toBeTruthy();
});

test("dealer approval: submission of changes from RMC as distributor D2", function () {
    $dealer = Dealer::factory()->create();
    $agency_level = AgencyLevel::firstOrCreate([
        "name" => "D2",
    ]);

    DistributorContract::factory()->create([
        "dealer_id" => $dealer->id,
        "distributor_level" => $agency_level->id,
    ]);

    $customer_id = $dealer->dealer_id;
    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
        "name" => "dealer",
    ]);

    DealerChangeHistory::create([
        "dealer_id" => $dealer->id,
        "dealer_temp_id" => $dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $dealer->personel_id,
        "confirmed_by" => $dealer->personel_id,
        "confirmed_at" => now(),
        "approved_at" => null,
        "approved_by" => null,
    ]);

    $area = MarketingAreaDistrict::factory()->create();
    $area_2 = MarketingAreaDistrict::factory()->create([
        "personel_id" => $dealer->personel_id,
        "sub_region_id" => $area->sub_region_id,
        "province_id" => "94",
        "city_id" => "9471",
        "district_id" => "9471020",
    ]);

    Address::factory()->createMany([
        [
            "district_id" => $area_2->district_id,
            "city_id" => $area_2->city_id,
            "province_id" => $area_2->province_id,
            "type" => "dealer",
            "parent_id" => $dealer->id,
        ],
        [
            "district_id" => $area_2->district_id,
            "city_id" => $area_2->city_id,
            "province_id" => $area_2->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer->id,
        ],
    ]);

    AddressTemp::factory()->createMany([
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer",
            "parent_id" => $dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer_temp->id,
        ],
    ]);

    DealerFile::factory()->createMany([
        [
            "dealer_id" => $dealer->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve");
    $response->assertStatus(200);

    $area->load("subRegion", "subRegionOnly");
    $dealer->refresh();
    $dealer->load([
        "addressDetail",
        "dealerFile",
    ]);

    $dealer_temp->refresh();
    $dealer_temp->load([
        "storeFix",
        "dealerFix",
        "dealerFile",
        "subDealerFix",
        "addressDetail",
        "dealerChangeHistory.dealerDataHistory",
        "dealerChangeHistory.dealerDataHistory.dealerAddresses",
        "dealerChangeHistory.dealerDataHistory.dealerFileHistory",
    ]);

    $sub_region = SubRegion::find($area->sub_region_id);

    expect(strtolower($dealer->name))->toEqual("dealer");
    expect(strtolower($dealer->status))->toEqual("accepted");
    expect($dealer->personel_id)->toEqual($area->subRegionOnly->personel_id);

    expect($dealer_temp->dealerChangeHistory->approved_at)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->approved_by)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory)->toBeObject();
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory->dealerAddresses)->toHaveCount(2);
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory->dealerFileHistories)->toHaveCount(2);

    expect($dealer->addressDetail)->toHaveCount(2);
    expect($dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
    expect($dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

    expect($dealer->dealerFile)->toHaveCount(2);
    expect($dealer->dealer_id)->toEqual($customer_id);
    expect($dealer_temp->deleted_at)->toBeTruthy();
});

test("dealer approval: submission of changes from MDM as retailer", function () {
    $dealer = Dealer::factory()->create();
    $customer_id = $dealer->dealer_id;

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
        "name" => "dealer",
    ]);

    DealerChangeHistory::create([
        "dealer_id" => $dealer->id,
        "dealer_temp_id" => $dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $dealer->personel_id,
        "confirmed_by" => $dealer->personel_id,
        "confirmed_at" => now(),
        "approved_at" => null,
        "approved_by" => null,
    ]);

    $area = MarketingAreaDistrict::factory()->marketingRmUnderMDM()->create();
    $area_2 = MarketingAreaDistrict::factory()->create([
        "personel_id" => $dealer->personel_id,
        "province_id" => "94",
        "city_id" => "9471",
        "district_id" => "9471020",
    ]);

    Address::factory()->createMany([
        [
            "district_id" => $area_2->district_id,
            "city_id" => $area_2->city_id,
            "province_id" => $area_2->province_id,
            "type" => "dealer",
            "parent_id" => $dealer->id,
        ],
        [
            "district_id" => $area_2->district_id,
            "city_id" => $area_2->city_id,
            "province_id" => $area_2->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer->id,
        ],
    ]);

    AddressTemp::factory()->createMany([
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer",
            "parent_id" => $dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer_temp->id,
        ],
    ]);

    DealerFile::factory()->createMany([
        [
            "dealer_id" => $dealer->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve");
    $response->assertStatus(200);

    $area->load("subRegion", "subRegionOnly.region");

    $dealer->refresh();
    $dealer->load([
        "addressDetail",
        "dealerFile",
    ]);

    $dealer_temp->refresh();
    $dealer_temp->load([
        "storeFix",
        "dealerFix",
        "dealerFile",
        "subDealerFix",
        "addressDetail",
        "dealerChangeHistory.dealerDataHistory",
        "dealerChangeHistory.dealerDataHistory.dealerAddresses",
        "dealerChangeHistory.dealerDataHistory.dealerFileHistory",
    ]);

    expect(strtolower($dealer->name))->toEqual("dealer");
    expect(strtolower($dealer->status))->toEqual("accepted");
    expect($dealer->personel_id)->toEqual($area->personel_id);

    expect($dealer_temp->dealerChangeHistory->approved_at)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->approved_by)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory)->toBeObject();
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory->dealerAddresses)->toHaveCount(2);
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory->dealerFileHistories)->toHaveCount(2);

    expect($dealer->addressDetail)->toHaveCount(2);
    expect($dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
    expect($dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

    expect($dealer->dealerFile)->toHaveCount(2);
    expect($dealer->dealer_id)->toEqual($customer_id);
    expect($dealer_temp->deleted_at)->toBeTruthy();
});

test("dealer approval: submission of changes from MDM as distributor", function () {
    $dealer = Dealer::factory()->create();

    $agency_level = AgencyLevel::firstOrCreate([
        "name" => "D1",
    ]);

    DistributorContract::factory()->create([
        "dealer_id" => $dealer->id,
        "distributor_level" => $agency_level->id,
    ]);
    $customer_id = $dealer->dealer_id;

    $dealer_temp = DealerTemp::factory()->create([
        "dealer_id" => $dealer->id,
        "status" => "wait approval",
        "name" => "dealer",
    ]);

    DealerChangeHistory::create([
        "dealer_id" => $dealer->id,
        "dealer_temp_id" => $dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $dealer->personel_id,
        "confirmed_by" => $dealer->personel_id,
        "confirmed_at" => now(),
        "approved_at" => null,
        "approved_by" => null,
    ]);

    $area = MarketingAreaDistrict::factory()->marketingRmUnderMDM()->create();

    $area_2 = MarketingAreaDistrict::factory()->create();

    Address::factory()->createMany([
        [
            "district_id" => $area_2->district_id,
            "city_id" => $area_2->city_id,
            "province_id" => $area_2->province_id,
            "type" => "dealer",
            "parent_id" => $dealer->id,
        ],
        [
            "district_id" => $area_2->district_id,
            "city_id" => $area_2->city_id,
            "province_id" => $area_2->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer->id,
        ],
    ]);

    AddressTemp::factory()->createMany([
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer",
            "parent_id" => $dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer_temp->id,
        ],
    ]);

    DealerFile::factory()->createMany([
        [
            "dealer_id" => $dealer->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve");
    $response->assertStatus(200);

    $area->load("subRegion", "subRegionOnly.region");

    $dealer->refresh();
    $dealer->load([
        "addressDetail",
        "dealerFile",
    ]);

    $dealer_temp->refresh();
    $dealer_temp->load([
        "storeFix",
        "dealerFix",
        "dealerFile",
        "subDealerFix",
        "addressDetail",
        "dealerChangeHistory.dealerDataHistory",
        "dealerChangeHistory.dealerDataHistory.dealerAddresses",
        "dealerChangeHistory.dealerDataHistory.dealerFileHistory",
    ]);

    expect(strtolower($dealer->name))->toEqual("dealer");
    expect(strtolower($dealer->status))->toEqual("accepted");
    expect($dealer->personel_id)->toEqual($area->subRegionOnly->region->personel_id);

    expect($dealer_temp->dealerChangeHistory->approved_at)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->approved_by)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory)->toBeObject();
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory->dealerAddresses)->toHaveCount(2);
    expect($dealer_temp->dealerChangeHistory->dealerDataHistory->dealerFileHistories)->toHaveCount(2);

    expect($dealer->addressDetail)->toHaveCount(2);
    expect($dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
    expect($dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

    expect($dealer->dealerFile)->toHaveCount(2);
    expect($dealer->dealer_id)->toEqual($customer_id);
    expect($dealer_temp->deleted_at)->toBeTruthy();
});

test("dealer approval: new dealer from RM", function () {
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
        "name" => "dealer-xxx-yyy-123",
    ]);

    DealerChangeHistory::create([
        "dealer_temp_id" => $dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $dealer_temp->personel_id,
        "confirmed_by" => $dealer_temp->personel_id,
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
            "type" => "dealer",
            "parent_id" => $dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer_temp->id,
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve");
    $response->assertStatus(200);

    $dealer = Dealer::query()
        ->with([
            "addressDetail",
            "dealerFile",
        ])
        ->where("name", "dealer-xxx-yyy-123")
        ->first();

    $dealer_temp->refresh();
    $dealer_temp->load([
        "storeFix",
        "dealerFix",
        "dealerFile",
        "subDealerFix",
        "addressDetail",
    ]);

    $agency_level_id = DB::table('agency_levels')->where('name', 'R3')->first();
    $grading_id = DB::table('gradings')->where("default", true)->first();
    $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "R")->first();

    expect($dealer->status_fee)->toEqual($status_fee->id);
    expect($dealer->agency_level_id)->toEqual($agency_level_id->id);
    expect($dealer->grading_id)->toEqual($grading_id->id);


    $dealer_grading = DealerGrading::query()
        ->where("dealer_id", $response->getData()->data->id)
        ->first();

    $defautl_grade = DB::table('gradings')
        ->where("default", true)
        ->whereNull("deleted_at")
        ->first();

    expect($dealer_grading)->not->toBeNull();
    expect($dealer_grading->grading_id)->toEqual($defautl_grade->id);

    expect(strtolower($dealer->name))->toEqual("dealer-xxx-yyy-123");
    expect(strtolower($dealer->status))->toEqual("accepted");
    expect($dealer->personel_id)->toEqual($area->personel_id);

    expect($dealer_temp->dealerChangeHistory->approved_at)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->approved_by)->toBeTruthy();

    expect($dealer->addressDetail)->toHaveCount(2);
    expect($dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
    expect($dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

    expect($dealer->dealerFile)->toHaveCount(2);
    expect($dealer_temp->deleted_at)->toBeTruthy();
});

test("dealer approval: new dealer transfer from subdealer", function () {
    $sub_dealer = SubDealer::factory()->create();
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
        "name" => "dealer-xxx-yyy-123",
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    DealerChangeHistory::create([
        "dealer_temp_id" => $dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $dealer_temp->personel_id,
        "confirmed_by" => $dealer_temp->personel_id,
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
            "type" => "dealer",
            "parent_id" => $dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer_temp->id,
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve");
    $response->assertStatus(200);

    $dealer = Dealer::query()
        ->with([
            "addressDetail",
            "dealerFile",
        ])
        ->where("name", "dealer-xxx-yyy-123")
        ->first();

    $sub_dealer = SubDealer::query()
        ->withTrashed()
        ->findOrFail($dealer_temp->sub_dealer_id);

    $dealer_temp->refresh();
    $dealer_temp->load([
        "storeFix",
        "dealerFix",
        "dealerFile",
        "subDealerFix",
        "addressDetail",
    ]);

    $agency_level_id = DB::table('agency_levels')->where('name', 'R3')->first();
    $grading_id = DB::table('gradings')->where("default", true)->first();
    $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "R")->first();

    expect($dealer->status_fee)->toEqual($status_fee->id);
    expect($dealer->agency_level_id)->toEqual($agency_level_id->id);
    expect($dealer->grading_id)->toEqual($grading_id->id);

    $dealer_grading = DealerGrading::query()
        ->where("dealer_id", $response->getData()->data->id)
        ->first();

    $defautl_grade = DB::table('gradings')
        ->where("default", true)
        ->whereNull("deleted_at")
        ->first();

    expect($dealer_grading)->not->toBeNull();
    expect($dealer_grading->grading_id)->toEqual($defautl_grade->id);

    expect(strtolower($dealer->name))->toEqual("dealer-xxx-yyy-123");
    expect(strtolower($dealer->status))->toEqual("accepted");
    expect($dealer->personel_id)->toEqual($area->personel_id);

    expect($dealer_temp->dealerChangeHistory->approved_at)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->approved_by)->toBeTruthy();

    expect($dealer->addressDetail)->toHaveCount(2);
    expect($dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
    expect($dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

    expect($dealer->dealerFile)->toHaveCount(2);

    expect($sub_dealer->status)->toEqual("transfered");
    expect($sub_dealer->dealer_id)->toEqual($dealer->id);
    expect($sub_dealer->deleted_at)->toBeTruthy();
    expect($dealer_temp->deleted_at)->toBeTruthy();
});

test("dealer approval: new dealer transfer from store", function () {
    $store = Store::factory()->create();
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
        "name" => "dealer-xxx-yyy-123",
        "store_id" => $store->id,
    ]);

    DealerChangeHistory::create([
        "dealer_temp_id" => $dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $dealer_temp->personel_id,
        "confirmed_by" => $dealer_temp->personel_id,
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
            "type" => "dealer",
            "parent_id" => $dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer_temp->id,
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $response = actingAsSupport()->putJson("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve");
    $response->assertStatus(200);

    $dealer = Dealer::query()
        ->with([
            "addressDetail",
            "dealerFile",
        ])
        ->where("name", "dealer-xxx-yyy-123")
        ->first();

    $store = Store::query()
        ->withTrashed()
        ->findOrFail($dealer_temp->store_id);

    $dealer_temp->refresh();
    $dealer_temp->load([
        "storeFix",
        "dealerFix",
        "dealerFile",
        "subDealerFix",
        "addressDetail",
    ]);

    $agency_level_id = DB::table('agency_levels')->where('name', 'R3')->first();
    $grading_id = DB::table('gradings')->where("default", true)->first();
    $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "R")->first();

    expect($dealer->status_fee)->toEqual($status_fee->id);
    expect($dealer->agency_level_id)->toEqual($agency_level_id->id);
    expect($dealer->grading_id)->toEqual($grading_id->id);
    
    $dealer_grading = DealerGrading::query()
        ->where("dealer_id", $response->getData()->data->id)
        ->first();

    $defautl_grade = DB::table('gradings')
        ->where("default", true)
        ->whereNull("deleted_at")
        ->first();

    expect($dealer_grading)->not->toBeNull();
    expect($dealer_grading->grading_id)->toEqual($defautl_grade->id);

    expect(strtolower($dealer->name))->toEqual("dealer-xxx-yyy-123");
    expect(strtolower($dealer->status))->toEqual("accepted");
    expect($dealer->personel_id)->toEqual($area->personel_id);

    expect($dealer_temp->dealerChangeHistory->approved_at)->toBeTruthy();
    expect($dealer_temp->dealerChangeHistory->approved_by)->toBeTruthy();

    expect($dealer->addressDetail)->toHaveCount(2);
    expect($dealer->addressDetail[0]->district_id)->toEqual($area->district_id);
    expect($dealer->addressDetail[1]->district_id)->toEqual($area->district_id);

    expect($dealer->dealerFile)->toHaveCount(2);

    expect($store->status)->toEqual("transfered");
    expect($store->dealer_id)->toEqual($dealer->id);
    expect($store->deleted_at)->toBeFalsy();
    expect($dealer_temp->deleted_at)->toBeTruthy();
});
