<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Address\Entities\Address;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * test includes
 * 1. area take over (sub and district in some condition in 4 area condition)
 * 2. reatiler take over
 * 3. distributor take over
 * 4. supervisor update
 */
test("region, marketing changes MDM to MDM", function () {

    /* create MM */
    $position_mm = Position::firstOrCreate([
        "name" => position_mm(),
    ]);

    Personel::factory()->create([
        "position_id" => $position_mm->id,
    ]);

    /*
    |---------------------------------------------------
    | area 1, district, sub region, region by MDM
    |-----------------------------------------------
     */
    $district_1 = MarketingAreaDistrict::factory()->marketingMDM()->create();
    $sub_region_1 = SubRegion::findOrFail($district_1->sub_region_id);
    $region_1 = Region::findOrFail($sub_region_1->region_id);

    /*
    |---------------------------------------------------
    | area 2, district RM, subregion n region MDM
    |-----------------------------------------------
     */
    $district_2 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $district_1->sub_region_id,
    ]);

    $sub_region_2 = $sub_region_1;
    $region_2 = $region_1;

    /* by default supervisor marketing is rmc, need to set to MDM */
    Personel::where("id", $district_2->personel_id)
        ->update([
            "supervisor_id" => $region_1->personel_id,
        ]);

    /*
    |---------------------------------------------------
    | area 3, district RMC, sub region RMC, region MDM
    |-----------------------------------------------
     */
    $district_3 = MarketingAreaDistrict::factory()->marketingRMC()->create();

    /* update subregion to same region with region 1 */
    SubRegion::query()
        ->where("id", $district_3->sub_region_id)
        ->update([
            "region_id" => $region_1->id,
        ]);

    $sub_region_3 = SubRegion::findOrFail($district_3->sub_region_id);
    $region_3 = Region::findOrFail($sub_region_3->region_id);

    /* need to set to MDM */
    Personel::where("id", $sub_region_3->personel_id)
        ->update([
            "supervisor_id" => $region_1->personel_id,
        ]);

    /*
    |---------------------------------------------------
    | area 4, district RM, sub region RMC, region MDM
    |-----------------------------------------------
     */
    $district_4 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $sub_region_3->id,
    ]);
    $sub_region_4 = $sub_region_3;
    $region_4 = $region_3;

    /* need to set to RMC */
    Personel::where("id", $district_4->personel_id)
        ->update([
            "supervisor_id" => $sub_region_3->personel_id,
        ]);

    /* another region */
    $another_region = Region::factory()->create();

    $agency_level_D1 = AgencyLevel::firstOrCreate([
        "name" => "D1",
    ]);

    $agency_level_D2 = AgencyLevel::firstOrCreate([
        "name" => "D2",
    ]);

    $agency_level_D2 = AgencyLevel::firstOrCreate([
        "name" => "D2",
    ]);

    $agency_level_R3 = AgencyLevel::firstOrCreate([
        "name" => "R3",
    ]);

    /* area 1, D1 */
    $dealer_D1 = Dealer::factory()->create([
        "personel_id" => $region_1->personel_id,
        "agency_level_id" => $agency_level_D1->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D1->id,
        "province_id" => $district_1->province_id,
        "city_id" => $district_1->city_id,
        "district_id" => $district_1->district_id,
    ]);

    /* area 1, D2 */
    $dealer_D2 = Dealer::factory()->create([
        "personel_id" => $region_1->personel_id,
        "agency_level_id" => $agency_level_D2->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D2->id,
        "province_id" => $district_1->province_id,
        "city_id" => $district_1->city_id,
        "district_id" => $district_1->district_id,
    ]);

    /* area 1, R3 */
    $dealer_R3 = Dealer::factory()->create([
        "personel_id" => $region_1->personel_id,
        "agency_level_id" => $agency_level_R3->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_R3->id,
        "province_id" => $district_1->province_id,
        "city_id" => $district_1->city_id,
        "district_id" => $district_1->district_id,
    ]);

    /* area 2, R3 */
    $dealer_R3_area_2 = Dealer::factory()->create([
        "personel_id" => $district_2->personel_id,
        "agency_level_id" => $agency_level_R3->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_R3_area_2->id,
        "province_id" => $district_2->province_id,
        "city_id" => $district_2->city_id,
        "district_id" => $district_2->district_id,
    ]);

    /* area 3, D2 */
    $dealer_D2_area_3 = Dealer::factory()->create([
        "personel_id" => $sub_region_3->personel_id,
        "agency_level_id" => $agency_level_D2->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D2_area_3->id,
        "province_id" => $district_3->province_id,
        "city_id" => $district_3->city_id,
        "district_id" => $district_3->district_id,
    ]);

    /* area 3, D1 */
    $dealer_D1_area_3 = Dealer::factory()->create([
        "personel_id" => $region_3->personel_id,
        "agency_level_id" => $agency_level_D1->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D1_area_3->id,
        "province_id" => $district_3->province_id,
        "city_id" => $district_3->city_id,
        "district_id" => $district_3->district_id,
    ]);

    /* area 4, R3 */
    $dealer_R3_area_4 = Dealer::factory()->create([
        "personel_id" => $district_4->personel_id,
        "agency_level_id" => $agency_level_R3->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_R3_area_4->id,
        "province_id" => $district_4->province_id,
        "city_id" => $district_4->city_id,
        "district_id" => $district_4->district_id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-region-update/" . $region_1->id, [
        "personel_id" => $another_region->personel_id,
    ]);

    $response->assertStatus(200);

    /* sub region change to new marketing */
    expect($response->getData()->data->personel_id)->toEqual($another_region->personel_id);

    /*
    | -----------------------------------
    | area 1
    |------------------
     */
    $district_1->refresh();
    $sub_region_1->refresh();
    $region_1->refresh();
    $dealer_D1->refresh();
    $dealer_D2->refresh();
    $dealer_R3->refresh();

    expect($district_1->personel_id)->toEqual($another_region->personel_id);
    expect($sub_region_1->personel_id)->toEqual($another_region->personel_id);
    expect($region_1->personel_id)->toEqual($another_region->personel_id);

    /* distributor D1 persist */
    expect($dealer_D1->personel_id)->toEqual($another_region->personel_id);

    /* distributor D2 take over by MDM */
    expect($dealer_D2->personel_id)->toEqual($another_region->personel_id);

    /* distributor D2 take over by MDM */
    expect($dealer_R3->personel_id)->toEqual($another_region->personel_id);

    /*
    | -----------------------------------
    | area 2
    |------------------
     */
    $rm_area_2 = DB::table('personels')->whereNull("deleted_at")->where("id", $district_2->personel_id)->first();
    $rm_on_retailer = $dealer_R3_area_2->personel_id;
    $district_2->refresh();
    $sub_region_2->refresh();
    $region_2->refresh();
    $dealer_R3_area_2->refresh();

    /* sub region take over by MDM */
    expect($sub_region_2->personel_id)->toEqual($another_region->personel_id);

    /* rm supervisor update */
    expect($rm_area_2->supervisor_id)->toEqual($another_region->personel_id);

    /* retailer still handled by RM */
    expect($dealer_R3_area_2->personel_id)->toEqual($district_2->personel_id);

    /*
    | -----------------------------------
    | area 3
    |------------------
     */
    $rmc_area_3 = DB::table('personels')->whereNull("deleted_at")->where("id", $sub_region_3->personel_id)->first();
    $district_3->refresh();
    $sub_region_3->refresh();
    $region_3->refresh();
    $dealer_D2_area_3->refresh();
    $dealer_D1_area_3->refresh();

    /* D1 handled by MDM */
    expect($dealer_D1_area_3->personel_id)->toEqual($another_region->personel_id);

    /* D2 still handled by RMC */
    expect($dealer_D2_area_3->personel_id)->toEqual($sub_region_3->personel_id);

    /* RMC supervisor update to MDM */
    expect($rmc_area_3->supervisor_id)->toEqual($another_region->personel_id);

    /*
    | -----------------------------------
    | area 4
    |------------------
     */
    $district_4->refresh();
    $sub_region_4->refresh();
    $region_4->refresh();
    $dealer_R3_area_4->refresh();

    /* dealer R3 still handled by RM */
    expect($dealer_R3_area_4->personel_id)->toEqual($district_4->personel_id);
});

test("region, marketing change MDM to MM", function () {

    /*
    |---------------------------------------------------
    | area 1, district, sub region, region by MDM
    |-----------------------------------------------
     */
    $district_1 = MarketingAreaDistrict::factory()->marketingMDM()->create();
    $sub_region_1 = SubRegion::findOrFail($district_1->sub_region_id);
    $region_1 = Region::findOrFail($sub_region_1->region_id);

    /*
    |---------------------------------------------------
    | area 2, district RM, subregion n region MDM
    |-----------------------------------------------
     */
    $district_2 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $district_1->sub_region_id,
    ]);

    $sub_region_2 = $sub_region_1;
    $region_2 = $region_1;

    /* by default supervisor marketing is rmc, need to set to MDM */
    Personel::where("id", $district_2->personel_id)
        ->update([
            "supervisor_id" => $region_1->personel_id,
        ]);

    /*
    |---------------------------------------------------
    | area 3, district RMC, sub region RMC, region MDM
    |-----------------------------------------------
     */
    $district_3 = MarketingAreaDistrict::factory()->marketingRMC()->create();

    /* update subregion to same region with region 1 */
    SubRegion::query()
        ->where("id", $district_3->sub_region_id)
        ->update([
            "region_id" => $region_1->id,
        ]);

    $sub_region_3 = SubRegion::findOrFail($district_3->sub_region_id);
    $region_3 = Region::findOrFail($sub_region_3->region_id);

    /* need to set to MDM */
    Personel::where("id", $sub_region_3->personel_id)
        ->update([
            "supervisor_id" => $region_1->personel_id,
        ]);

    /*
    |---------------------------------------------------
    | area 4, district RM, sub region RMC, region MDM
    |-----------------------------------------------
     */
    $district_4 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $sub_region_3->id,
    ]);
    $sub_region_4 = $sub_region_3;
    $region_4 = $region_3;

    /* need to set to RMC */
    Personel::where("id", $district_4->personel_id)
        ->update([
            "supervisor_id" => $sub_region_3->personel_id,
        ]);

    /* another region */
    $another_region = Region::factory()->create();

    $agency_level_D1 = AgencyLevel::firstOrCreate([
        "name" => "D1",
    ]);

    $agency_level_D2 = AgencyLevel::firstOrCreate([
        "name" => "D2",
    ]);

    $agency_level_D2 = AgencyLevel::firstOrCreate([
        "name" => "D2",
    ]);

    $agency_level_R3 = AgencyLevel::firstOrCreate([
        "name" => "R3",
    ]);

    /* area 1, D1 */
    $dealer_D1 = Dealer::factory()->create([
        "personel_id" => $region_1->personel_id,
        "agency_level_id" => $agency_level_D1->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D1->id,
        "province_id" => $district_1->province_id,
        "city_id" => $district_1->city_id,
        "district_id" => $district_1->district_id,
    ]);

    /* area 1, D2 */
    $dealer_D2 = Dealer::factory()->create([
        "personel_id" => $region_1->personel_id,
        "agency_level_id" => $agency_level_D2->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D2->id,
        "province_id" => $district_1->province_id,
        "city_id" => $district_1->city_id,
        "district_id" => $district_1->district_id,
    ]);

    /* area 1, R3 */
    $dealer_R3 = Dealer::factory()->create([
        "personel_id" => $region_1->personel_id,
        "agency_level_id" => $agency_level_R3->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_R3->id,
        "province_id" => $district_1->province_id,
        "city_id" => $district_1->city_id,
        "district_id" => $district_1->district_id,
    ]);

    /* area 2, R3 */
    $dealer_R3_area_2 = Dealer::factory()->create([
        "personel_id" => $district_2->personel_id,
        "agency_level_id" => $agency_level_R3->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_R3_area_2->id,
        "province_id" => $district_2->province_id,
        "city_id" => $district_2->city_id,
        "district_id" => $district_2->district_id,
    ]);

    /* area 3, D2 */
    $dealer_D2_area_3 = Dealer::factory()->create([
        "personel_id" => $sub_region_3->personel_id,
        "agency_level_id" => $agency_level_D2->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D2_area_3->id,
        "province_id" => $district_3->province_id,
        "city_id" => $district_3->city_id,
        "district_id" => $district_3->district_id,
    ]);

    /* area 3, D1 */
    $dealer_D1_area_3 = Dealer::factory()->create([
        "personel_id" => $region_3->personel_id,
        "agency_level_id" => $agency_level_D1->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D1_area_3->id,
        "province_id" => $district_3->province_id,
        "city_id" => $district_3->city_id,
        "district_id" => $district_3->district_id,
    ]);

    /* area 4, R3 */
    $dealer_R3_area_4 = Dealer::factory()->create([
        "personel_id" => $district_4->personel_id,
        "agency_level_id" => $agency_level_R3->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_R3_area_4->id,
        "province_id" => $district_4->province_id,
        "city_id" => $district_4->city_id,
        "district_id" => $district_4->district_id,
    ]);

    $marketing_MM = Personel::factory()->marketingMM()->create();
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-region-update/" . $region_1->id, [
        "personel_id" => $marketing_MM->id,
    ]);

    $response->assertStatus(200);

    /* sub region change to new marketing */
    expect($response->getData()->data->personel_id)->toEqual($marketing_MM->id);

    /*
    | -----------------------------------
    | area 1
    |------------------
     */
    $district_1->refresh();
    $sub_region_1->refresh();
    $region_1->refresh();
    $dealer_D1->refresh();
    $dealer_D2->refresh();
    $dealer_R3->refresh();

    expect($district_1->personel_id)->toEqual($marketing_MM->id);
    expect($sub_region_1->personel_id)->toEqual($marketing_MM->id);
    expect($region_1->personel_id)->toEqual($marketing_MM->id);

    /* distributor D1 persist */
    expect($dealer_D1->personel_id)->toEqual($marketing_MM->id);

    /* distributor D2 take over by MDM */
    expect($dealer_D2->personel_id)->toEqual($marketing_MM->id);

    /* distributor D2 take over by MDM */
    expect($dealer_R3->personel_id)->toEqual($marketing_MM->id);

    /*
    | -----------------------------------
    | area 2
    |------------------
     */
    $rm_area_2 = DB::table('personels')->whereNull("deleted_at")->where("id", $district_2->personel_id)->first();
    $rm_on_retailer = $dealer_R3_area_2->personel_id;
    $district_2->refresh();
    $sub_region_2->refresh();
    $region_2->refresh();
    $dealer_R3_area_2->refresh();

    /* sub region take over by MDM */
    expect($sub_region_2->personel_id)->toEqual($marketing_MM->id);

    /* rm supervisor update */
    expect($rm_area_2->supervisor_id)->toEqual($marketing_MM->id);

    /* retailer still handled by RM */
    expect($dealer_R3_area_2->personel_id)->toEqual($district_2->personel_id);

    /*
    | -----------------------------------
    | area 3
    |------------------
     */
    $rmc_area_3 = DB::table('personels')->whereNull("deleted_at")->where("id", $sub_region_3->personel_id)->first();
    $district_3->refresh();
    $sub_region_3->refresh();
    $region_3->refresh();
    $dealer_D2_area_3->refresh();
    $dealer_D1_area_3->refresh();

    /* D1 handled by MDM */
    expect($dealer_D1_area_3->personel_id)->toEqual($marketing_MM->id);

    /* D2 still handled by RMC */
    expect($dealer_D2_area_3->personel_id)->toEqual($sub_region_3->personel_id);

    /* RMC supervisor update to MDM */
    expect($rmc_area_3->supervisor_id)->toEqual($marketing_MM->id);

    /*
    | -----------------------------------
    | area 4
    |------------------
     */
    $district_4->refresh();
    $sub_region_4->refresh();
    $region_4->refresh();
    $dealer_R3_area_4->refresh();

    /* dealer R3 still handled by RM */
    expect($dealer_R3_area_4->personel_id)->toEqual($district_4->personel_id);

    // dd([
    //     "area_1" => [
    //         $district_1->personel_id,
    //         $sub_region_1->personel_id,
    //         $region_1->personel_id,
    //     ],
    //     "area_2" => [
    //         $district_2->personel_id,
    //         $sub_region_2->personel_id,
    //         $region_2->personel_id,
    //     ],
    //     "area_3" => [
    //         $district_3->personel_id,
    //         $sub_region_3->personel_id,
    //         $region_3->personel_id,
    //     ],
    //     "area_4" => [
    //         $district_4->personel_id,
    //         $sub_region_4->personel_id,
    //         $region_4->personel_id,
    //     ],
    // ]);
});

test("region, marketing change MM to MDM", function () {

    /*
    |---------------------------------------------------
    | area 1, district, sub region, region by MDM
    |-----------------------------------------------
     */
    $district_1 = MarketingAreaDistrict::factory()->marketingMM()->create();
    $sub_region_1 = SubRegion::findOrFail($district_1->sub_region_id);
    $region_1 = Region::findOrFail($sub_region_1->region_id);

    $marketing_MM = $district_1->personel_id;

    /*
    |---------------------------------------------------
    | area 2, district RM, subregion n region MM
    |-----------------------------------------------
     */
    $district_2 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $district_1->sub_region_id,
    ]);

    $sub_region_2 = $sub_region_1;
    $region_2 = $region_1;

    /* by default supervisor marketing is rmc, need to set to MDM */
    Personel::where("id", $district_2->personel_id)
        ->update([
            "supervisor_id" => $region_1->personel_id,
        ]);

    /*
    |---------------------------------------------------
    | area 3, district RMC, sub region RMC, region MM
    |-----------------------------------------------
     */
    $district_3 = MarketingAreaDistrict::factory()->marketingRMC()->create();

    /* update subregion to same region with region 1 */
    SubRegion::query()
        ->where("id", $district_3->sub_region_id)
        ->update([
            "region_id" => $region_1->id,
        ]);

    $sub_region_3 = SubRegion::findOrFail($district_3->sub_region_id);
    $region_3 = Region::findOrFail($sub_region_3->region_id);

    /* need to set to MDM */
    Personel::where("id", $sub_region_3->personel_id)
        ->update([
            "supervisor_id" => $region_1->personel_id,
        ]);

    /*
    |---------------------------------------------------
    | area 4, district RM, sub region RMC, region MDM
    |-----------------------------------------------
     */
    $district_4 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $sub_region_3->id,
    ]);
    $sub_region_4 = $sub_region_3;
    $region_4 = $region_3;

    /* need to set to RMC */
    Personel::where("id", $district_4->personel_id)
        ->update([
            "supervisor_id" => $sub_region_3->personel_id,
        ]);

    /* another region */
    $another_region = Region::factory()->create();

    $agency_level_D1 = AgencyLevel::firstOrCreate([
        "name" => "D1",
    ]);

    $agency_level_D2 = AgencyLevel::firstOrCreate([
        "name" => "D2",
    ]);

    $agency_level_D2 = AgencyLevel::firstOrCreate([
        "name" => "D2",
    ]);

    $agency_level_R3 = AgencyLevel::firstOrCreate([
        "name" => "R3",
    ]);

    /* area 1, D1 */
    $dealer_D1 = Dealer::factory()->create([
        "personel_id" => $region_1->personel_id,
        "agency_level_id" => $agency_level_D1->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D1->id,
        "province_id" => $district_1->province_id,
        "city_id" => $district_1->city_id,
        "district_id" => $district_1->district_id,
    ]);

    /* area 1, D2 */
    $dealer_D2 = Dealer::factory()->create([
        "personel_id" => $region_1->personel_id,
        "agency_level_id" => $agency_level_D2->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D2->id,
        "province_id" => $district_1->province_id,
        "city_id" => $district_1->city_id,
        "district_id" => $district_1->district_id,
    ]);

    /* area 1, R3 */
    $dealer_R3 = Dealer::factory()->create([
        "personel_id" => $region_1->personel_id,
        "agency_level_id" => $agency_level_R3->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_R3->id,
        "province_id" => $district_1->province_id,
        "city_id" => $district_1->city_id,
        "district_id" => $district_1->district_id,
    ]);

    /* area 2, R3 */
    $dealer_R3_area_2 = Dealer::factory()->create([
        "personel_id" => $district_2->personel_id,
        "agency_level_id" => $agency_level_R3->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_R3_area_2->id,
        "province_id" => $district_2->province_id,
        "city_id" => $district_2->city_id,
        "district_id" => $district_2->district_id,
    ]);

    /* area 3, D2 */
    $dealer_D2_area_3 = Dealer::factory()->create([
        "personel_id" => $sub_region_3->personel_id,
        "agency_level_id" => $agency_level_D2->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D2_area_3->id,
        "province_id" => $district_3->province_id,
        "city_id" => $district_3->city_id,
        "district_id" => $district_3->district_id,
    ]);

    /* area 3, D1 */
    $dealer_D1_area_3 = Dealer::factory()->create([
        "personel_id" => $region_3->personel_id,
        "agency_level_id" => $agency_level_D1->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D1_area_3->id,
        "province_id" => $district_3->province_id,
        "city_id" => $district_3->city_id,
        "district_id" => $district_3->district_id,
    ]);

    /* area 4, R3 */
    $dealer_R3_area_4 = Dealer::factory()->create([
        "personel_id" => $district_4->personel_id,
        "agency_level_id" => $agency_level_R3->id,
    ]);
    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_R3_area_4->id,
        "province_id" => $district_4->province_id,
        "city_id" => $district_4->city_id,
        "district_id" => $district_4->district_id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-region-update/" . $region_1->id, [
        "personel_id" => $another_region->personel_id,
    ]);

    $response->assertStatus(200);

    /* sub region change to new marketing */
    expect($response->getData()->data->personel_id)->toEqual($another_region->personel_id);

    $marketing_MDM = DB::table('personels')->where("id", $another_region->personel_id)->first();
    expect($marketing_MDM->supervisor_id)->toEqual($marketing_MM);

    /*
    | -----------------------------------
    | area 1
    |------------------
     */
    $district_1->refresh();
    $sub_region_1->refresh();
    $region_1->refresh();
    $dealer_D1->refresh();
    $dealer_D2->refresh();
    $dealer_R3->refresh();

    expect($district_1->personel_id)->toEqual($another_region->personel_id);
    expect($sub_region_1->personel_id)->toEqual($another_region->personel_id);
    expect($region_1->personel_id)->toEqual($another_region->personel_id);

    /* distributor D1 persist */
    expect($dealer_D1->personel_id)->toEqual($another_region->personel_id);

    /* distributor D2 take over by MDM */
    expect($dealer_D2->personel_id)->toEqual($another_region->personel_id);

    /* distributor D2 take over by MDM */
    expect($dealer_R3->personel_id)->toEqual($another_region->personel_id);

    /*
    | -----------------------------------
    | area 2
    |------------------
     */
    $rm_area_2 = DB::table('personels')->whereNull("deleted_at")->where("id", $district_2->personel_id)->first();
    $rm_on_retailer = $dealer_R3_area_2->personel_id;
    $district_2->refresh();
    $sub_region_2->refresh();
    $region_2->refresh();
    $dealer_R3_area_2->refresh();

    /* sub region take over by MDM */
    expect($sub_region_2->personel_id)->toEqual($another_region->personel_id);

    /* rm supervisor update */
    expect($rm_area_2->supervisor_id)->toEqual($another_region->personel_id);

    /* retailer still handled by RM */
    expect($dealer_R3_area_2->personel_id)->toEqual($district_2->personel_id);

    /*
    | -----------------------------------
    | area 3
    |------------------
     */
    $rmc_area_3 = DB::table('personels')->whereNull("deleted_at")->where("id", $sub_region_3->personel_id)->first();
    $district_3->refresh();
    $sub_region_3->refresh();
    $region_3->refresh();
    $dealer_D2_area_3->refresh();
    $dealer_D1_area_3->refresh();

    /* D1 handled by MDM */
    expect($dealer_D1_area_3->personel_id)->toEqual($another_region->personel_id);

    /* D2 still handled by RMC */
    expect($dealer_D2_area_3->personel_id)->toEqual($sub_region_3->personel_id);

    /* RMC supervisor update to MDM */
    expect($rmc_area_3->supervisor_id)->toEqual($another_region->personel_id);

    /*
    | -----------------------------------
    | area 4
    |------------------
     */
    $district_4->refresh();
    $sub_region_4->refresh();
    $region_4->refresh();
    $dealer_R3_area_4->refresh();

    /* dealer R3 still handled by RM */
    expect($dealer_R3_area_4->personel_id)->toEqual($district_4->personel_id);
});
