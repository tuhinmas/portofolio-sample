<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Address\Entities\Address;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Observers\PersonelObserver;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("marketing district, supervisor change, revoke from district", function () {

    /* disable observer for factory */
    PersonelObserver::$enabled = false;

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

    $district_2 = MarketingAreaDistrict::factory()->marketingRmUnderMDM()->create([
        "sub_region_id" => $district_1->sub_region_id,
    ]);

    $sub_region_2 = $sub_region_1;
    $region_2 = $region_1;

    /* by default supervisor marketing is rmc, need to set to MDM */
    Personel::where("id", $district_2->personel_id)
        ->update([
            "supervisor_id" => $sub_region_2->personel_id,
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

    $district_4->personel_id = $district_2->personel_id;
    $district_4->save();
    $sub_region_4 = $sub_region_3;
    $region_4 = $region_3;

    // dd([
    //     "area_1" => [
    //         $region_1->personel_id,
    //         $sub_region_1->personel_id,
    //         $district_1->personel_id,
    //     ],
    //     "area_2" => [
    //         $region_2->personel_id,
    //         $sub_region_2->personel_id,
    //         $district_2->personel_id,
    //     ],
    //     "area_3" => [
    //         $region_3->personel_id,
    //         $sub_region_3->personel_id,
    //         $district_3->personel_id,
    //     ],
    //     "area_4" => [
    //         $region_4->personel_id,
    //         $sub_region_4->personel_id,
    //         $district_4->personel_id,
    //     ],
    // ]);

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

    PersonelObserver::$enabled = true;

    // dump([
    //     "area_1" => [
    //         $region_1->personel_id,
    //         $sub_region_1->personel_id,
    //         $district_1->personel_id,
    //         // "dealer" => [
    //         //     "dealer_d1" => $dealer_D1->personel_id,
    //         // ],
    //         "district" => [
    //             "id" => $district_1->id,
    //             "personel_id" => $district_1->personel_id,
    //             "district_id" => $district_1->district_id,
    //         ],
    //         "sub_region_id" => $sub_region_1->id,
    //     ],
    //     "area_2" => [
    //         $region_2->personel_id,
    //         $sub_region_2->personel_id,
    //         $district_2->personel_id,
    //         "district" => [
    //             "id" => $district_2->id,
    //             "personel_id" => $district_2->personel_id,
    //             "district_id" => $district_2->district_id,
    //         ],
    //         "dealer" => [
    //             "id" => $dealer_R3_area_2->id,
    //             "persoenel_id" => $dealer_R3_area_2->personel_id,
    //         ],
    //         "sub_region_id" => $sub_region_2->id,
    //     ],
    //     "area_3" => [
    //         $region_3->personel_id,
    //         $sub_region_3->personel_id,
    //         $district_3->personel_id,
    //         "district" => [
    //             "id" => $district_3->id,
    //             "personel_id" => $district_3->personel_id,
    //             "district_id" => $district_3->district_id,
    //         ],
    //         "sub_region_id" => $sub_region_3->id,
    //     ],
    //     "area_4" => [
    //         $region_4->personel_id,
    //         $sub_region_4->personel_id,
    //         $district_4->personel_id,
    //         "district" => [
    //             "id" => $district_4->id,
    //             "personel_id" => $district_4->personel_id,
    //             "district_id" => $district_4->district_id,
    //         ],
    //         "sub_region_id" => $sub_region_4->id,
    //         "dealer" => [
    //             "id" => $dealer_R3_area_4->id,
    //             "personel_id" => $dealer_R3_area_4->personel_id,
    //         ],
    //     ],
    // ]);

    $marketing_id = $district_2->personel_id;
    $response = actingAsSupport()->putJson("/api/v1/personnel/personnel/" . $marketing_id, [
        "supervisor_id" => $sub_region_4->personel_id,
    ]);

    // dd($response->getData());

    $response->assertStatus(200);

    $marketing = DB::table('personels')
        ->whereNull("deleted_at")
        ->where("id", $district_2->personel_id)
        ->first();

    /* superviosr updated */
    expect($marketing->supervisor_id)->toEqual($sub_region_4->personel_id);

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

    expect($district_1->personel_id)->toEqual($sub_region_1->personel_id);

    expect($dealer_R3->personel_id)->toEqual($sub_region_1->personel_id);

    /* distributor D2 still handle by MDM */
    expect($dealer_D2->personel_id)->toEqual($sub_region_1->personel_id);

    /* distributor D1 still handle by MDM */
    expect($dealer_D1->personel_id)->toEqual($sub_region_1->personel_id);

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

    /* district take over by marketing sub region */
    expect($district_2->personel_id)->toEqual($sub_region_2->personel_id);

    /* retailer still handled by RM */
    expect($dealer_R3_area_2->personel_id)->toEqual($sub_region_2->personel_id);

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
    expect($dealer_D1_area_3->personel_id)->toEqual($region_3->personel_id);

    /* D2 still handled by RMC */
    expect($dealer_D2_area_3->personel_id)->toEqual($sub_region_3->personel_id);

    /* district check */
    expect($district_3->personel_id)->toEqual($sub_region_3->personel_id);

    /*
    | -----------------------------------
    | area 4
    |------------------
     */
    $district_4->refresh();
    $sub_region_4->refresh();
    $region_4->refresh();
    $dealer_R3_area_4->refresh();

    // dump([
    //     "area_1" => [
    //         $region_1->personel_id,
    //         $sub_region_1->personel_id,
    //         $district_1->personel_id,
    //     ],
    //     "area_2" => [
    //         $region_2->personel_id,
    //         $sub_region_2->personel_id,
    //         $district_2->personel_id,
    //         "district" => [
    //             "id" => $district_2->id,
    //             "personel_id" => $district_2->personel_id,
    //         ],
    //         "dealer" => [
    //             "id" => $dealer_R3_area_2->id,
    //             "persoenel_id" => $dealer_R3_area_2->personel_id,
    //         ],
    //     ],
    //     "area_3" => [
    //         $region_3->personel_id,
    //         $sub_region_3->personel_id,
    //         $district_3->personel_id,
    //     ],
    //     "area_4" => [
    //         $region_4->personel_id,
    //         $sub_region_4->personel_id,
    //         $district_4->personel_id,
    //     ],
    // ]);

    // dump([
    //     "marketing_id" => $marketing_id,
    //     "marketing_district" => $district_4->personel_id,
    //     "marketing_dealer" => $dealer_R3_area_4->personel_id,
    // ]);

    /* district still handled by marketing */
    expect($district_4->personel_id)->toEqual($marketing_id);

    /* dealer R3 still handled by RM */
    expect($dealer_R3_area_4->personel_id)->toEqual($district_4->personel_id);
});
