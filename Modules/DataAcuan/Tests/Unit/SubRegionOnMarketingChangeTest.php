<?php

use Modules\Address\Entities\Address;
use Modules\DataAcuan\Entities\Region;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * test includes
 * 1. area take over
 * 2. reatiler take over
 * 3. distributor take over
 * 4. supervisor update
 */
test("sub region, marketing changes RMC to RMC", function () {
    $district = MarketingAreaDistrict::factory()->marketingRMC()->create();
    $district_2 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $district->sub_region_id,
    ]);

    $sub_region = SubRegion::findOrFail($district->sub_region_id);
    $sub_region_2 = SubRegion::factory()->create();
    $region = Region::findOrFail($sub_region->region_id);

    $agency_level_D1 = AgencyLevel::firstOrCreate([
        "name" => "D1",
    ]);

    $agency_level_D2 = AgencyLevel::firstOrCreate([
        "name" => "D2",
    ]);

    $dealer_D1 = Dealer::factory()->create([
        "personel_id" => $region->personel_id,
        "agency_level_id" => $agency_level_D1->id,
    ]);

    $dealer_D2 = Dealer::factory()->create([
        "personel_id" => $district->personel_id,
        "agency_level_id" => $agency_level_D2->id,
    ]);

    $address_1 = Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D1->id,
        "province_id" => $district->province_id,
        "city_id" => $district->city_id,
        "district_id" => $district->district_id,
    ]);

    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D2->id,
        "province_id" => $district->province_id,
        "city_id" => $district->city_id,
        "district_id" => $district->district_id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-subregion/" . $sub_region->id, [
        "personel_id" => $sub_region_2->personel_id,
    ]);

    $response->assertStatus(200);
    $dealer_D1->refresh();
    $dealer_D2->refresh();
    $district->refresh();

    $update = MarketingAreaDistrict::findOrFail($district->id);
    $rm2_detail = DB::table('personels')->whereNull("deleted_at")->where("id", $district_2->personel_id)->first();

    /* sub region change to new marketing */
    expect($response->getData()->data->personel_id)->toEqual($sub_region_2->personel_id);

    /* district take over by new M */
    expect($district->personel_id)->toEqual($sub_region_2->personel_id);

    /* distributor D2 take over by RMC */
    expect($dealer_D2->personel_id)->toEqual($sub_region_2->personel_id);

    /* distributor D1 persist */
    expect($dealer_D1->personel_id)->toEqual($region->personel_id);

    /* rm supervisor update */
    expect($rm2_detail->supervisor_id)->toEqual($sub_region_2->personel_id);
});

test("sub region, marketing changes RMC to MDM", function () {
    $district = MarketingAreaDistrict::factory()->marketingRMC()->create();
    $district_2 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $district->sub_region_id,
    ]);
    $sub_region = SubRegion::findOrFail($district->sub_region_id);
    $region = Region::findOrFail($sub_region->region_id);

    $agency_level_D1 = AgencyLevel::firstOrCreate([
        "name" => "D1",
    ]);

    $agency_level_D2 = AgencyLevel::firstOrCreate([
        "name" => "D2",
    ]);

    $dealer_D1 = Dealer::factory()->create([
        "personel_id" => $region->personel_id,
        "agency_level_id" => $agency_level_D1->id,
    ]);

    $dealer_D2 = Dealer::factory()->create([
        "personel_id" => $district->personel_id,
        "agency_level_id" => $agency_level_D2->id,
    ]);

    $address_1 = Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D1->id,
        "province_id" => $district->province_id,
        "city_id" => $district->city_id,
        "district_id" => $district->district_id,
    ]);

    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D2->id,
        "province_id" => $district->province_id,
        "city_id" => $district->city_id,
        "district_id" => $district->district_id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-subregion/" . $sub_region->id, [
        "personel_id" => $region->personel_id,
    ]);

    $response->assertStatus(200);
    $dealer_D1->refresh();
    $dealer_D2->refresh();
    $district->refresh();

    $update = MarketingAreaDistrict::findOrFail($district->id);
    $rmc_detail = DB::table('personels')->whereNull("deleted_at")->where("id", $sub_region->personel_id)->first();
    $rm2_detail = DB::table('personels')->whereNull("deleted_at")->where("id", $district_2->personel_id)->first();

    /* sub region change to new marketing */
    expect($response->getData()->data->personel_id)->toEqual($region->personel_id);

    /* district take over by new M */
    expect($district->personel_id)->toEqual($region->personel_id);

    /* distributor D2 take over by RMC */
    expect($dealer_D2->personel_id)->toEqual($region->personel_id);

    /* distributor D1 persist */
    expect($dealer_D1->personel_id)->toEqual($region->personel_id);

    /* supervisor rmc update */
    expect($rmc_detail->supervisor_id)->toEqual($region->personel_id);

    /* rm supervisor update */
    expect($rm2_detail->supervisor_id)->toEqual($region->personel_id);
});

test("sub region, marketing changes MDM to RMC", function () {

    /* district, sub region, region must handle by MDM */
    $district = MarketingAreaDistrict::factory()->marketingMDM()->create();
    $district_2 = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $district->sub_region_id,
    ]);

    $sub_region = SubRegion::findOrFail($district->sub_region_id);
    $region = Region::findOrFail($sub_region->region_id);

    /* by default supervisor marketing is rmc, need to set to MDM */
    Personel::where("id", $district_2->personel_id)
        ->update([
            "supervisor_id" => $region->personel_id,
        ]);

    $sub_region_2 = SubRegion::factory()->create();

    $agency_level_D1 = AgencyLevel::firstOrCreate([
        "name" => "D1",
    ]);

    $agency_level_D2 = AgencyLevel::firstOrCreate([
        "name" => "D2",
    ]);

    $dealer_D1 = Dealer::factory()->create([
        "personel_id" => $region->personel_id,
        "agency_level_id" => $agency_level_D1->id,
    ]);

    $dealer_D2 = Dealer::factory()->create([
        "personel_id" => $region->personel_id,
        "agency_level_id" => $agency_level_D2->id,
    ]);

    $address_1 = Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D1->id,
        "province_id" => $district->province_id,
        "city_id" => $district->city_id,
        "district_id" => $district->district_id,
    ]);

    Address::factory()->create([
        "type" => "dealer",
        "parent_id" => $dealer_D2->id,
        "province_id" => $district->province_id,
        "city_id" => $district->city_id,
        "district_id" => $district->district_id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-subregion/" . $sub_region->id, [
        "personel_id" => $sub_region_2->personel_id,
    ]);

    $response->assertStatus(200);
    $dealer_D1->refresh();
    $dealer_D2->refresh();
    $district->refresh();

    $update = MarketingAreaDistrict::findOrFail($district->id);
    $rmc_detail = DB::table('personels')->whereNull("deleted_at")->where("id", $sub_region_2->personel_id)->first();
    $rm2_detail = DB::table('personels')->whereNull("deleted_at")->where("id", $district_2->personel_id)->first();

    /* sub region change to new marketing */
    expect($response->getData()->data->personel_id)->toEqual($sub_region_2->personel_id);

    /* district take over by new M */
    expect($district->personel_id)->toEqual($sub_region_2->personel_id);

    /* distributor D2 take over by RMC */
    expect($dealer_D2->personel_id)->toEqual($sub_region_2->personel_id);

    /* distributor D1 persist */
    expect($dealer_D1->personel_id)->toEqual($region->personel_id);

    /* supervisor rmc to mdm */
    expect($rmc_detail->supervisor_id)->toEqual($region->personel_id);

    /* rm supervisor update */
    expect($rm2_detail->supervisor_id)->toEqual($sub_region_2->personel_id);
});

test("sub region, marketing changes MDM to MDM", function () {

  /* district, sub region, region must handle by MDM */
  $district = MarketingAreaDistrict::factory()->marketingMDM()->create();
  $district_2 = MarketingAreaDistrict::factory()->create([
      "sub_region_id" => $district->sub_region_id,
  ]);

  $sub_region = SubRegion::findOrFail($district->sub_region_id);
  $region = Region::findOrFail($sub_region->region_id);
  $region_2 = Region::factory()->create();

  /* by default supervisor marketing is rmc, need to set to MDM */
  Personel::where("id", $district_2->personel_id)
      ->update([
          "supervisor_id" => $region->personel_id,
      ]);

  $agency_level_D1 = AgencyLevel::firstOrCreate([
      "name" => "D1",
  ]);

  $agency_level_D2 = AgencyLevel::firstOrCreate([
      "name" => "D2",
  ]);

  $dealer_D1 = Dealer::factory()->create([
      "personel_id" => $region->personel_id,
      "agency_level_id" => $agency_level_D1->id,
  ]);

  $dealer_D2 = Dealer::factory()->create([
      "personel_id" => $region->personel_id,
      "agency_level_id" => $agency_level_D2->id,
  ]);

  $address_1 = Address::factory()->create([
      "type" => "dealer",
      "parent_id" => $dealer_D1->id,
      "province_id" => $district->province_id,
      "city_id" => $district->city_id,
      "district_id" => $district->district_id,
  ]);

  Address::factory()->create([
      "type" => "dealer",
      "parent_id" => $dealer_D2->id,
      "province_id" => $district->province_id,
      "city_id" => $district->city_id,
      "district_id" => $district->district_id,
  ]);

  $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-subregion/" . $sub_region->id, [
      "personel_id" => $region_2->personel_id,
  ]);

  $response->assertStatus(200);
  $dealer_D1->refresh();
  $dealer_D2->refresh();
  $district->refresh();

  $update = MarketingAreaDistrict::findOrFail($district->id);
  $rmc_detail = DB::table('personels')->whereNull("deleted_at")->where("id", $sub_region->personel_id)->first();
  $rm2_detail = DB::table('personels')->whereNull("deleted_at")->where("id", $district_2->personel_id)->first();

  /* sub region change to new marketing */
  expect($response->getData()->data->personel_id)->toEqual($region_2->personel_id);

  /* district take over by new M */
  expect($district->personel_id)->toEqual($region_2->personel_id);

  /* distributor D2 take over by RMC */
  expect($dealer_D2->personel_id)->toEqual($region_2->personel_id);

  /* distributor D1 persist */
  expect($dealer_D1->personel_id)->toEqual($region_2->personel_id);

  /* rm supervisor update */
  expect($rm2_detail->supervisor_id)->toEqual($region_2->personel_id);
});
