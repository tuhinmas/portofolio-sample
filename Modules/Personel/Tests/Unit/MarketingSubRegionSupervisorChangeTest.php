<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Address\Entities\Address;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Observers\PersonelObserver;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("marketing sub region, RMC supervisor change to MDM, revoke from sub region and district", function () {

    /**
     * ----------------------------------------------------------------------------
     *
     *             mdm-1                         mdm-2
     *               |                             |
     *       .-------*--------.            .-------*--------.
     *       |                |            |                |
     *     rmc-1            rmc-1        rmc-1            rmc-1
     *       |                |            |                |
     *     rmc-1             rm-1        rmc-1             rm-2
     *  ----------------------------------------------------------------
     *     area 1           area 2       area 3           area 4
     *
     * rmc-1 supervisor is mdm-1 than change to mdm-2, the schema is wrong in any condition but happen
     * -------------------------------------------------------------------------------------------------------
     * 
     *             mdm-2                         mdm-2
     *               |                             |
     *       .-------*--------.            .-------*--------.
     *       |                |            |                |
     *     rmc-1            rmc-1        rmc-1            rmc-1
     *       |                |            |                |
     *     rmc-1             rm-1        rmc-1             rm-2
     *  ----------------------------------------------------------------
     *     area 1           area 2       area 3           area 4
     *
     *                      right schema should be
     */

    /* disable observer for factory */
    PersonelObserver::$enabled = false;

    /*
    |---------------------------------------------------
    | area 1: MDM-1, RMC-1, RMC-1
    |-----------------------------------------------
     */
    $district_1 = MarketingAreaDistrict::factory()->marketingRMC()->create();
    $sub_region_1 = SubRegion::findOrFail($district_1->sub_region_id);
    $region_1 = Region::findOrFail($sub_region_1->region_id);

    /*
    |---------------------------------------------------
    | area 2: MDM-1, RMC-1, RM-1
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
            "supervisor_id" => $sub_region_2->personel_id,
        ]);

    /*
    |---------------------------------------------------
    | area 3: MDM-2, RMC-1, RMC-1
    |-----------------------------------------------
     */
    $district_3 = MarketingAreaDistrict::factory()->marketingRMC()->create([
        "province_id" => "94",
        "city_id" => "9401",
        "district_id" => "9401040",
    ]);
    $sub_region_3 = SubRegion::findOrFail($district_3->sub_region_id);
    $region_3 = Region::findOrFail($sub_region_3->region_id);

    $sub_region_3->personel_id = $sub_region_1->personel_id;
    $sub_region_3->save();

    $district_3->personel_id = $sub_region_1->personel_id;
    $district_3->save();

    /*
    |---------------------------------------------------
    | area 4: MDM-2, RMC-1, RM-2
    |-----------------------------------------------
     */
    $district_4 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $sub_region_3->id,
        "province_id" => "94",
        "city_id" => "9401",
        "district_id" => "9401041",
    ]);

    Personel::where("id", $district_4->personel_id)
        ->update([
            "supervisor_id" => $sub_region_3->personel_id,
        ]);

    $sub_region_4 = $sub_region_3;
    $region_4 = $region_3;

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

    DealerV2::query()
        ->whereHas("addressDetail", function ($QQQ) use($district_1, $district_2, $district_3, $district_4){
            return $QQQ->whereIn("sub_region_id", [$district_1->sub_region_id, $district_2->sub_region_id, $district_3->sub_region_id, $district_4->sub_region_id]);
        })
        ->delete();

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

    $marketing_id = $sub_region_1->personel_id;
    $response = actingAsSupport()->putJson("/api/v1/personnel/personnel/" . $marketing_id, [
        "supervisor_id" => $region_4->personel_id,
    ]);

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

    /* marketing district update to MDM */
    expect($district_1->personel_id)->toEqual($sub_region_1->personel_id);

    /* marketing sub region update to MDM */
    expect($sub_region_1->personel_id)->toEqual($region_1->personel_id);

    /* retailer take over by MDM */
    expect($dealer_R3->personel_id)->toEqual($district_1->personel_id);

    /* distributor D2 take over by MDM */
    expect($dealer_D2->personel_id)->toEqual($sub_region_1->personel_id);

    /* distributor D1 still handle by MDM */
    expect($dealer_D1->personel_id)->toEqual($region_1->personel_id);

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
    expect($district_2->personel_id)->toEqual($sub_region_1->personel_id);

    /* sub region take over by marketing sub region */
    expect($sub_region_2->personel_id)->toEqual($region_1->personel_id);

    /* retailer take over by marketing new marketing district */
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
    $area_4_marketing =  $district_4->personel_id;
    $district_4->refresh();
    $sub_region_4->refresh();
    $region_4->refresh();
    $dealer_R3_area_4->refresh();

    /* district still handled by marketing */
    expect($district_4->personel_id)->toEqual($area_4_marketing);

    /* dealer R3 still handled by RM */
    expect($dealer_R3_area_4->personel_id)->toEqual($district_4->personel_id);
});

test("marketing sub region, MDM supervisor change to MM active", function () {

    /**
     * ----------------------------------------------------------------------------
     *
     *              mm-1                         mdm-2
     *               |                             |
     *       .-------*--------.            .-------*--------.
     *       |                |            |                |
     *     mdm-1            mdm-1        mdm-2            mdm-2
     *       |                |            |                |
     *     mdm-1             rm-1        mdm-2             rm-2
     *  ----------------------------------------------------------------
     *     area 1           area 2       area 3           area 4
     *
     * in condition this schema was not true, but if happen than need to be right
     * ---------------------------------------------------------------------------------------
     *
     *
     *              mdm-1                         mdm-2
     *               |                             |
     *       .-------*--------.            .-------*--------.
     *       |                |            |                |
     *     mdm-1            mdm-1        mdm-2            mdm-2
     *       |                |            |                |
     *     mdm-1             rm-1        mdm-2             rm-2
     *  ----------------------------------------------------------------
     *     area 1           area 2       area 3           area 4
     *
     *  right schema should be
     */

    /* disable observer for factory */
    PersonelObserver::$enabled = false;
    $mm_position = DB::table('positions')
        ->whereIn("name", position_mm())
        ->first();

    $mm_factory = Personel::factory()->create([
        "position_id" => $mm_position->id,
        "status" => "1",
    ]);

    $active_mm = Personel::firstOrCreate([
        "position_id" => $mm_position->id,
        "status" => 1,
    ],
        $mm_factory->toArray()
    );

    $inactive_mm = Personel::factory()->create([
        "position_id" => $mm_position->id,
        "status" => "3",
    ]);

    /*
    |---------------------------------------------------
    | area 1: MM-1, MDM-1, MDM-1
    |-----------------------------------------------
     */
    $district_1 = MarketingAreaDistrict::factory()->marketingMDM()->create();
    $sub_region_1 = SubRegion::findOrFail($district_1->sub_region_id);
    $region_1 = Region::findOrFail($sub_region_1->region_id);
    $region_1->personel_id = $inactive_mm->id;
    $region_1->save();

    Personel::query()
        ->where("id", $region_1->personel_id)
        ->update([
            "supervisor_id" => $inactive_mm->id,
        ]);

    /*
    |---------------------------------------------------
    | area 2: MM-1, MDM-1, RM-1
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
            "supervisor_id" => $sub_region_2->personel_id,
        ]);

    /*
    |---------------------------------------------------
    | area 3: MDM-2, MDM-2, MDM-2
    |-----------------------------------------------
     */
    $district_3 = MarketingAreaDistrict::factory()->marketingMDM()->create([
        "province_id" => "94",
        "city_id" => "9401",
        "district_id" => "9401040",
    ]);
    $sub_region_3 = SubRegion::findOrFail($district_3->sub_region_id);
    $region_3 = Region::findOrFail($sub_region_3->region_id);
    Personel::query()
        ->where("id", $region_3->personel_id)
        ->update([
            "supervisor_id" => $inactive_mm->id,
        ]);

    /*
    |---------------------------------------------------
    | area 4: MDM-2, MDM-2, RM-2
    |-----------------------------------------------
     */
    $district_4 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $sub_region_3->id,
        "province_id" => "94",
        "city_id" => "9401",
        "district_id" => "9401041",
    ]);

    Personel::where("id", $district_4->personel_id)
        ->update([
            "supervisor_id" => $sub_region_3->personel_id,
        ]);

    $sub_region_4 = $sub_region_3;
    $region_4 = $region_3;

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

    DealerV2::query()
        ->whereHas("addressDetail", function ($QQQ) use ($district_1, $district_2, $district_3, $district_4) {
            return $QQQ->whereIn("sub_region_id", [$district_1->sub_region_id, $district_2->sub_region_id, $district_3->sub_region_id, $district_4->sub_region_id]);
        })
        ->delete();

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
        "personel_id" => $sub_region_2->personel_id,
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
        "personel_id" => $district_1->personel_id,
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
    //         "dealer_d1" => [
    //             "id" => $dealer_D1->id,
    //             "personel_id" => $dealer_D1->personel_id,
    //             "agency" => $agency_level_D1->id,
    //         ],
    //         "district" => [
    //             "id" => $district_1->id,
    //             "personel_id" => $district_1->personel_id,
    //             "district_id" => $district_1->district_id,
    //         ],
    //         "sub_region_id" => $sub_region_1->id,
    //         "region_id" => $region_1->id,
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
    //         "dealer_R3_area_2" => [
    //             "id" => $dealer_R3_area_2->id,
    //             "persoenel_id" => $dealer_R3_area_2->personel_id,
    //         ],
    //         "sub_region_id" => $sub_region_2->id,
    //         "region_id" => $region_2->id,
    //     ],
    //     "area_3" => [
    //         $region_3->personel_id,
    //         $sub_region_3->personel_id,
    //         $district_3->personel_id,
    //         "dealer_D2_area_3" => [
    //             "id" => $dealer_D2_area_3->id,
    //             "personel_id" => $dealer_D2_area_3->personel_id,
    //         ],
    //         "dealer_D1_area_3" => [
    //             "id" => $dealer_D1_area_3->id,
    //             "personel_id" => $dealer_D1_area_3->personel_id,
    //         ],
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
    //         "dealer_R3_area_4" => [
    //             "id" => $dealer_R3_area_4->id,
    //             "personel_id" => $dealer_R3_area_4->personel_id,
    //         ],
    //     ],
    // ]);

    $marketing_id = $sub_region_1->personel_id;
    $response = actingAsSupport()->putJson("/api/v1/personnel/personnel/" . $marketing_id, [
        "supervisor_id" => $active_mm->id,
    ]);

    $response->assertStatus(200);
    $marketing = DB::table('personels')
        ->whereNull("deleted_at")
        ->where("id", $marketing_id)
        ->first();

    /* superviosr updated */
    expect($marketing->supervisor_id)->toEqual($active_mm->id);

    /*
    | -----------------------------------
    | area 1
    |------------------
     */
    $marketing_district = $district_1->personel_id;
    $marketing_sub_region = $sub_region_1->personel_id;
    $marketing_region = $region_1->personel_id;
    $district_1->refresh();
    $sub_region_1->refresh();
    $region_1->refresh();
    $dealer_D1->refresh();
    $dealer_D2->refresh();
    $dealer_R3->refresh();

    /* marketing district has no change */
    expect($district_1->personel_id)->toEqual($marketing_district);

    /* marketing sub region has no change */
    expect($sub_region_1->personel_id)->toEqual($marketing_sub_region);

    /* region take over by MDM */
    expect($region_1->personel_id)->toEqual($sub_region_1->personel_id);

    /* retailer has no change  */
    expect($dealer_R3->personel_id)->toEqual($district_1->personel_id);

    /* distributor D2 has no change  */
    expect($dealer_D2->personel_id)->toEqual($sub_region_1->personel_id);

    /* distributor D1 take over by MDM */
    expect($dealer_D1->personel_id)->toEqual($region_1->personel_id);


    /*
    | -----------------------------------
    | area 2
    |------------------
     */
    $rm_area_2 = DB::table('personels')->whereNull("deleted_at")->where("id", $district_2->personel_id)->first();
    $rm_on_retailer = $dealer_R3_area_2->personel_id;
    $marketing_district = $district_2->personel_id;
    $marketing_sub_region = $sub_region_2->personel_id;
    $marketing_region = $region_2->personel_id;
    $district_2->refresh();
    $sub_region_2->refresh();
    $region_2->refresh();
    $dealer_R3_area_2->refresh();

    /* marketing district not change */
    expect($district_2->personel_id)->toEqual($marketing_district);

    /* marketing sub region not changen */
    expect($sub_region_2->personel_id)->toEqual($marketing_sub_region);

    /* retailer take over by marketing new marketing district */
    expect($dealer_R3_area_2->personel_id)->toEqual($district_2->personel_id);


    /*
    | -----------------------------------
    | area 3
    |------------------
     */
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
    $area_4_marketing = $district_4->personel_id;
    $district_4->refresh();
    $sub_region_4->refresh();
    $region_4->refresh();
    $dealer_R3_area_4->refresh();

    // dump([
    //     "area_1" => [
    //         $region_1->personel_id,
    //         $sub_region_1->personel_id,
    //         $district_1->personel_id,
    //         "dealer_d1" => [
    //             "id" => $dealer_D1->id,
    //             "personel_id" => $dealer_D1->personel_id,
    //             "agency" => $agency_level_D1->id,
    //         ],
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
    //         "dealer_R3_area_2" => [
    //             "id" => $dealer_R3_area_2->id,
    //             "persoenel_id" => $dealer_R3_area_2->personel_id,
    //         ],
    //         "sub_region_id" => $sub_region_2->id,
    //     ],
    //     "area_3" => [
    //         $region_3->personel_id,
    //         $sub_region_3->personel_id,
    //         $district_3->personel_id,
    //         "dealer_D2_area_3" => [
    //             "id" => $dealer_D2_area_3->id,
    //             "personel_id" => $dealer_D2_area_3->personel_id,
    //         ],
    //         "dealer_D1_area_3" => [
    //             "id" => $dealer_D1_area_3->id,
    //             "personel_id" => $dealer_D1_area_3->personel_id,
    //         ],
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
    //         "dealer_R3_area_4" => [
    //             "id" => $dealer_R3_area_4->id,
    //             "personel_id" => $dealer_R3_area_4->personel_id,
    //         ],
    //     ],
    // ]);

    /* district still handled by marketing */
    expect($district_4->personel_id)->toEqual($area_4_marketing);

    /* dealer R3 still handled by RM */
    expect($dealer_R3_area_4->personel_id)->toEqual($district_4->personel_id);
});

test("marketing sub region, RMC supervisor change to MM active", function () {

    /**
     * ----------------------------------------------------------------------------
     *
     *      mm-1(inactive mm)                    mdm-2
     *               |                             |
     *       .-------*--------.            .-------*--------.
     *       |                |            |                |
     *     rmc-1            rmc-1        rmc-2            rmc-2
     *       |                |            |                |
     *     rmc-1             rm-1        rmc-2             rm-2
     *  ----------------------------------------------------------------
     *     area 1           area 2       area 3           area 4
     *
     * in condition this schema was not true, but if happen than need to be right
     * ---------------------------------------------------------------------------------------
     *
     *
     *         mm-1(inactive mm)                  mdm-2
     *               |                             |
     *       .-------*--------.            .-------*--------.
     *       |                |            |                |
     *     rmc-1            rmc-1        rmc-2            rmc-2
     *       |                |            |                |
     *     rmc-1             rm-1        rmc-2             rm-2
     *  ----------------------------------------------------------------
     *     area 1           area 2       area 3           area 4
     *
     *  right schema should be
     */

    /* disable observer for factory */
    PersonelObserver::$enabled = false;
    $mm_position = DB::table('positions')
        ->whereIn("name", position_mm())
        ->first();

    $mm_factory = Personel::factory()->create([
        "position_id" => $mm_position->id,
        "status" => "1",
    ]);

    $active_mm = Personel::firstOrCreate([
        "position_id" => $mm_position->id,
        "status" => 1,
    ],
        $mm_factory->toArray()
    );

    $inactive_mm = Personel::factory()->create([
        "position_id" => $mm_position->id,
        "status" => "3",
    ]);

    /*
    |---------------------------------------------------
    | area 1: MM-1, RMC-1, RMC-1
    |-----------------------------------------------
     */
    $district_1 = MarketingAreaDistrict::factory()->marketingRMC()->create();
    $sub_region_1 = SubRegion::findOrFail($district_1->sub_region_id);
    $region_1 = Region::findOrFail($sub_region_1->region_id);
    $region_1->personel_id = $inactive_mm->id;
    $region_1->save();

    Personel::query()
        ->where("id", $sub_region_1->personel_id)
        ->update([
            "supervisor_id" => $inactive_mm->id,
        ]);

    /*
    |---------------------------------------------------
    | area 2: MM-1, RMC-1, RM-1
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
            "supervisor_id" => $sub_region_2->personel_id,
        ]);

    /*
    |---------------------------------------------------
    | area 3: MDM-2, RMC2-2, RMC-2
    |-----------------------------------------------
     */
    $district_3 = MarketingAreaDistrict::factory()->marketingRMC()->create([
        "province_id" => "94",
        "city_id" => "9401",
        "district_id" => "9401040",
    ]);
    $sub_region_3 = SubRegion::findOrFail($district_3->sub_region_id);
    $region_3 = Region::findOrFail($sub_region_3->region_id);

    /*
    |---------------------------------------------------
    | area 4: MDM-2, RMC-2, RM-2
    |-----------------------------------------------
     */
    $district_4 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $sub_region_3->id,
        "province_id" => "94",
        "city_id" => "9401",
        "district_id" => "9401041",
    ]);

    Personel::where("id", $district_4->personel_id)
        ->update([
            "supervisor_id" => $sub_region_3->personel_id,
        ]);

    $sub_region_4 = $sub_region_3;
    $region_4 = $region_3;

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

    DealerV2::query()
        ->whereHas("addressDetail", function ($QQQ) use ($district_1, $district_2, $district_3, $district_4) {
            return $QQQ->whereIn("sub_region_id", [$district_1->sub_region_id, $district_2->sub_region_id, $district_3->sub_region_id, $district_4->sub_region_id]);
        })
        ->delete();

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
        "personel_id" => $sub_region_2->personel_id,
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
        "personel_id" => $district_1->personel_id,
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
    //         "dealer_d1" => [
    //             "id" => $dealer_D1->id,
    //             "personel_id" => $dealer_D1->personel_id,
    //             "agency" => $agency_level_D1->id,
    //         ],
    //         "district" => [
    //             "id" => $district_1->id,
    //             "personel_id" => $district_1->personel_id,
    //             "district_id" => $district_1->district_id,
    //         ],
    //         "sub_region_id" => $sub_region_1->id,
    //         "region_id" => $region_1->id,
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
    //         "dealer_R3_area_2" => [
    //             "id" => $dealer_R3_area_2->id,
    //             "persoenel_id" => $dealer_R3_area_2->personel_id,
    //         ],
    //         "sub_region_id" => $sub_region_2->id,
    //         "region_id" => $region_2->id,
    //     ],
    //     "area_3" => [
    //         $region_3->personel_id,
    //         $sub_region_3->personel_id,
    //         $district_3->personel_id,
    //         "dealer_D2_area_3" => [
    //             "id" => $dealer_D2_area_3->id,
    //             "personel_id" => $dealer_D2_area_3->personel_id,
    //         ],
    //         "dealer_D1_area_3" => [
    //             "id" => $dealer_D1_area_3->id,
    //             "personel_id" => $dealer_D1_area_3->personel_id,
    //         ],
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
    //         "dealer_R3_area_4" => [
    //             "id" => $dealer_R3_area_4->id,
    //             "personel_id" => $dealer_R3_area_4->personel_id,
    //         ],
    //     ],
    // ]);

    $marketing_id = $sub_region_1->personel_id;
    $response = actingAsSupport()->putJson("/api/v1/personnel/personnel/" . $marketing_id, [
        "supervisor_id" => $active_mm->id,
    ]);


    $response->assertStatus(200);
    $marketing = DB::table('personels')
        ->whereNull("deleted_at")
        ->where("id", $marketing_id)
        ->first();

    /* superviosr updated */
    expect($marketing->supervisor_id)->toEqual($active_mm->id);

    /*
    | -----------------------------------
    | area 1
    |------------------
     */
    $marketing_district = $district_1->personel_id;
    $marketing_sub_region = $sub_region_1->personel_id;
    $marketing_region = $region_1->personel_id;
    $district_1->refresh();
    $sub_region_1->refresh();
    $region_1->refresh();
    $dealer_D1->refresh();
    $dealer_D2->refresh();
    $dealer_R3->refresh();

    /* marketing district has no change */
    expect($district_1->personel_id)->toEqual($marketing_district);

    /* marketing sub region has no change */
    expect($sub_region_1->personel_id)->toEqual($marketing_sub_region);

    /* region take over by MDM */
    expect($region_1->personel_id)->toEqual($active_mm->id);

    /* retailer has no change  */
    expect($dealer_R3->personel_id)->toEqual($district_1->personel_id);

    /* distributor D2 has no change  */
    expect($dealer_D2->personel_id)->toEqual($sub_region_1->personel_id);

    /* distributor D1 take over by MDM */
    expect($dealer_D1->personel_id)->toEqual($region_1->personel_id);

    /*
    | -----------------------------------
    | area 2
    |------------------
     */
    $rm_area_2 = DB::table('personels')->whereNull("deleted_at")->where("id", $district_2->personel_id)->first();
    $rm_on_retailer = $dealer_R3_area_2->personel_id;
    $marketing_district = $district_2->personel_id;
    $marketing_sub_region = $sub_region_2->personel_id;
    $marketing_region = $region_2->personel_id;
    $district_2->refresh();
    $sub_region_2->refresh();
    $region_2->refresh();
    $dealer_R3_area_2->refresh();

    /* marketing district not change */
    expect($district_2->personel_id)->toEqual($marketing_district);

    /* marketing sub region not changen */
    expect($sub_region_2->personel_id)->toEqual($marketing_sub_region);

    /* retailer take over by marketing new marketing district */
    expect($dealer_R3_area_2->personel_id)->toEqual($district_2->personel_id);

    /*
    | -----------------------------------
    | area 3
    |------------------
     */
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
    $area_4_marketing = $district_4->personel_id;
    $district_4->refresh();
    $sub_region_4->refresh();
    $region_4->refresh();
    $dealer_R3_area_4->refresh();
    
    // dump([
    //     "area_1" => [
    //         $region_1->personel_id,
    //         $sub_region_1->personel_id,
    //         $district_1->personel_id,
    //         "dealer_d1" => [
    //             "id" => $dealer_D1->id,
    //             "personel_id" => $dealer_D1->personel_id,
    //             "agency" => $agency_level_D1->id,
    //         ],
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
    //         "dealer_R3_area_2" => [
    //             "id" => $dealer_R3_area_2->id,
    //             "persoenel_id" => $dealer_R3_area_2->personel_id,
    //         ],
    //         "sub_region_id" => $sub_region_2->id,
    //     ],
    //     "area_3" => [
    //         $region_3->personel_id,
    //         $sub_region_3->personel_id,
    //         $district_3->personel_id,
    //         "dealer_D2_area_3" => [
    //             "id" => $dealer_D2_area_3->id,
    //             "personel_id" => $dealer_D2_area_3->personel_id,
    //         ],
    //         "dealer_D1_area_3" => [
    //             "id" => $dealer_D1_area_3->id,
    //             "personel_id" => $dealer_D1_area_3->personel_id,
    //         ],
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
    //         "dealer_R3_area_4" => [
    //             "id" => $dealer_R3_area_4->id,
    //             "personel_id" => $dealer_R3_area_4->personel_id,
    //         ],
    //     ],
    // ]);
    
    /* district still handled by marketing */
    expect($district_4->personel_id)->toEqual($area_4_marketing);

    /* dealer R3 still handled by RM */
    expect($dealer_R3_area_4->personel_id)->toEqual($district_4->personel_id);
});
