<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Events\MarketingAreaOnChangeEvent;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * move to job
 */
// test("marketing change, all dealer on district will handover to new marketing", function () {
//     $dealer = DealerV2::query()
//         ->with([
//             "areaDistrictDealer",
//         ])
//         ->whereHas("areaDistrictDealer", function ($QQQ) {
//             return $QQQ->whereNotNull("personel_id");
//         })
//         ->whereHas("agencyLevel", function ($QQQ) {
//             return $QQQ->whereNotIn("name", ["D1", "D2"]);
//         })
//         ->first();

//     $rm = DB::table('positions')->whereNull("deleted_at")->where("name", "Regional Marketing (RM)")->first();

//     $personel = Personel::factory()->create([
//         "position_id" => $rm->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-district/" . $dealer->areaDistrictDealer->id, [
//         "personel_id" => $personel->id,
//     ]);

//     $dealer = DealerV2::findOrFail($dealer->id);
//     $response->assertStatus(200);
//     expect($dealer->personel_id)->toEqual($personel->id);
// });

// test("marketing change, all sub dealer on district will handover to new marketing", function () {
//     $sub_dealer = SubDealer::query()
//         ->with([
//             "areaDistrictDealer",
//         ])
//         ->whereHas("areaDistrictDealer", function ($QQQ) {
//             return $QQQ->whereNotNull("personel_id");
//         })
//         ->first();

//     $rm = DB::table('positions')->whereNull("deleted_at")->where("name", "Regional Marketing (RM)")->first();

//     $personel = Personel::factory()->create([
//         "position_id" => $rm->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-district/" . $sub_dealer->areaDistrictDealer->id, [
//         "personel_id" => $personel->id,
//     ]);

//     $sub_dealer = SubDealer::findOrFail($sub_dealer->id);
//     $response->assertStatus(200);
//     expect($sub_dealer->personel_id)->toEqual($personel->id);
// });

// test("unconfirmed indirect order should not takover by new marketing", function () {
//     $sub_dealer = SubDealer::query()
//         ->with([
//             "areaDistrictDealer",
//         ])
//         ->whereHas("areaDistrictDealer", function ($QQQ) {
//             return $QQQ->whereNotNull("personel_id");
//         })
//         ->first();

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $sub_dealer->id,
//         "model" => "2",
//         "type" => "2",
//         "status" => "draft",
//     ]);

//     $rm = DB::table('positions')->whereNull("deleted_at")->where("name", "Regional Marketing (RM)")->first();

//     $personel = Personel::factory()->create([
//         "position_id" => $rm->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-district/" . $sub_dealer->areaDistrictDealer->id, [
//         "personel_id" => $personel->id,
//     ]);

//     $sub_dealer = SubDealer::findOrFail($sub_dealer->id);
//     $sales_order_after_marketing_change = SalesOrder::findorfail($sales_order->id);

//     $response->assertStatus(200);
//     expect($sub_dealer->personel_id)->toEqual($personel->id);
//     expect($sales_order_after_marketing_change->personel_id)->toEqual($sales_order->personel_id);
// });

// test("unconfirmed direct order should not takover by new marketing", function () {
//     $dealer = DealerV2::query()
//         ->with([
//             "areaDistrictDealer",
//         ])
//         ->whereHas("areaDistrictDealer", function ($QQQ) {
//             return $QQQ->whereNotNull("personel_id");
//         })
//         ->whereHas("agencyLevel", function ($QQQ) {
//             return $QQQ->whereNotIn("name", ["D1", "D2"]);
//         })
//         ->first();

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $dealer->id,
//         "model" => "2",
//         "status" => "submited",
//         "type" => "1",
//     ]);

//     $rm = DB::table('positions')->whereNull("deleted_at")->where("name", "Regional Marketing (RM)")->first();

//     $personel = Personel::factory()->create([
//         "position_id" => $rm->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-district/" . $dealer->areaDistrictDealer->id, [
//         "personel_id" => $personel->id,
//     ]);

//     $dealer = DealerV2::findOrFail($dealer->id);
//     $sales_order_after_marketing_change = SalesOrder::findorfail($sales_order->id);

//     $response->assertStatus(200);
//     expect($dealer->personel_id)->toEqual($personel->id);
//     expect($sales_order_after_marketing_change->personel_id)->toEqual($sales_order->personel_id);
// });

test("sync retailer to marketing", function () {
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-district-sync-marketing");
    $response->assertStatus(200);
});

test("sync dealer D1 to MDM or marketing region", function () {
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-district-sync-dealer-d1");
    $response->assertStatus(200);
});

test("sync dealer D2 to RMC or marketing sub region", function () {
    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-district-sync-dealer-d2");
    $response->assertStatus(200);
});

/*
|-----------------------
| APPLICATOR
| move to job
|-----------------
 */
// test("applicator will revoked from area if marketing area is not his supervisor, single store", function () {
//     $applicator_position = Position::query()
//         ->whereIn("name", applicator_positions())
//         ->first();

//     $rm_position = Position::query()
//         ->where("name", "Regional Marketing (RM)")
//         ->first();

//     $rmc_position = Position::query()
//         ->where("name", "Regional Marketing Coordinator (RMC)")
//         ->first();

//     $supervisor = Personel::factory()->create([
//         "position_id" => $rmc_position->id,
//         "name" => "supervisor-test",
//     ]);

//     $marketing_1 = Personel::factory()->create([
//         "position_id" => $rm_position->id,
//         "supervisor_id" => $supervisor->id,
//         "name" => "aplikator-test-1",
//     ]);

//     $marketing_2 = Personel::factory()->create([
//         "position_id" => $rm_position->id,
//         "supervisor_id" => $supervisor->id,
//         "name" => "aplikator-test-2",
//     ]);

//     $applicator = Personel::factory()->create([
//         "position_id" => $applicator_position->id,
//         "supervisor_id" => $marketing_1->id,
//     ]);

//     $district = MarketingAreaDistrict::first();
//     $district->delete();

//     $response = actingAsSupport()->postJson("/api/v1/data-acuan/marketing-area-district", [
//         "personel_id" => $marketing_2->id,
//         "applicator_id" => $applicator->id,
//         "province_id" => $district->province_id,
//         "city_id" => $district->city_id,
//         "district_id" => [$district->district_id],
//         "sub_region_id" => $district->sub_region_id,
//     ]);

//     $response->assertStatus(200);
//     expect($response->getData()->data->applicator_id)->toEqual(null);
// });

// test("applicator will revoked from area if marketing area is not his supervisor, single update", function () {
//     $applicator_position = Position::query()
//         ->whereIn("name", applicator_positions())
//         ->first();

//     $rm_position = Position::query()
//         ->where("name", "Regional Marketing (RM)")
//         ->first();

//     $rmc_position = Position::query()
//         ->where("name", "Regional Marketing Coordinator (RMC)")
//         ->first();

//     $supervisor = Personel::factory()->create([
//         "position_id" => $rmc_position->id,
//         "name" => "supervisor-test",
//     ]);

//     $marketing_1 = Personel::factory()->create([
//         "position_id" => $rm_position->id,
//         "supervisor_id" => $supervisor->id,
//         "name" => "aplikator-test-1",
//     ]);

//     $marketing_2 = Personel::factory()->create([
//         "position_id" => $rm_position->id,
//         "supervisor_id" => $supervisor->id,
//         "name" => "aplikator-test-2",
//     ]);

//     $applicator = Personel::factory()->create([
//         "position_id" => $applicator_position->id,
//         "supervisor_id" => $marketing_1->id,
//     ]);

//     $district = MarketingAreaDistrict::first();
//     $district->personel_id = $marketing_1->id;
//     $district->applicator_id = $applicator->id;
//     $district->save();

//     $response = null;
//     $response = actingAsSupport()->patchJson("/api/v1/data-acuan/marketing-area-district/" . $district->id, [
//         "personel_id" => $marketing_2->id,
//     ]);

//     $response->assertStatus(200);
//     expect($response->getData()->data->applicator_id)->toEqual(null);
// });

// test("applicator will revoked from area if marketing area is not his supervisor, batch update", function () {
//     $applicator_position = Position::query()
//         ->whereIn("name", applicator_positions())
//         ->first();

//     $rm_position = Position::query()
//         ->where("name", "Regional Marketing (RM)")
//         ->first();

//     $rmc_position = Position::query()
//         ->where("name", "Regional Marketing Coordinator (RMC)")
//         ->first();

//     $supervisor = Personel::factory()->create([
//         "position_id" => $rmc_position->id,
//         "name" => "supervisor-test",
//     ]);

//     $marketing_1 = Personel::factory()->create([
//         "position_id" => $rm_position->id,
//         "supervisor_id" => $supervisor->id,
//         "name" => "aplikator-test-1",
//     ]);

//     $marketing_2 = Personel::factory()->create([
//         "position_id" => $rm_position->id,
//         "supervisor_id" => $supervisor->id,
//         "name" => "aplikator-test-2",
//     ]);

//     $applicator = Personel::factory()->create([
//         "position_id" => $applicator_position->id,
//         "supervisor_id" => $marketing_1->id,
//     ]);

//     $district = MarketingAreaDistrict::first();
//     $district->personel_id = $marketing_1->id;
//     $district->applicator_id = $applicator->id;
//     $district->save();

//     $response = null;
//     $response = actingAsSupport()->patchJson("/api/v1/data-acuan/marketing-area-district/batch", [
//         "resources" => [
//             $district->id => [
//                 "personel_id" => $marketing_2->id,
//             ],
//         ],
//     ]);

//     $response->assertStatus(200);
//     expect($response->getData()->data[0]->applicator_id)->toEqual(null);
// });

/**
 * can not
 */
test("can not assign applicator to area if marketing area is not his supervisor, batch update", function () {
    $applicator_position = Position::query()
        ->whereIn("name", applicator_positions())
        ->first();

    $rm_position = Position::query()
        ->where("name", "Regional Marketing (RM)")
        ->first();

    $rmc_position = Position::query()
        ->where("name", "Regional Marketing Coordinator (RMC)")
        ->first();

    $supervisor = Personel::factory()->create([
        "position_id" => $rmc_position->id,
        "name" => "supervisor-test",
    ]);

    $marketing_1 = Personel::factory()->create([
        "position_id" => $rm_position->id,
        "supervisor_id" => $supervisor->id,
        "name" => "aplikator-test-1",
    ]);

    $marketing_2 = Personel::factory()->create([
        "position_id" => $rm_position->id,
        "supervisor_id" => $supervisor->id,
        "name" => "aplikator-test-2",
    ]);

    $applicator = Personel::factory()->create([
        "position_id" => $applicator_position->id,
        "supervisor_id" => $marketing_1->id,
    ]);

    $district = MarketingAreaDistrict::factory()->create();
    $district->personel_id = $marketing_1->id;
    $district->applicator_id = $applicator->id;
    $district->save();

    $response = null;
    $response = actingAsSupport()->patchJson("/api/v1/data-acuan/marketing-area-district/batch", [
        "resources" => [
            $district->id => [
                "personel_id" => $marketing_2->id,
                "applicator_id" => $applicator->id,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("can not assign applicator to area if marketing area is not his supervisor, single update", function () {
    $applicator_position = Position::query()
        ->whereIn("name", applicator_positions())
        ->first();

    $rm_position = Position::query()
        ->where("name", "Regional Marketing (RM)")
        ->first();

    $rmc_position = Position::query()
        ->where("name", "Regional Marketing Coordinator (RMC)")
        ->first();

    $supervisor = Personel::factory()->create([
        "position_id" => $rmc_position->id,
        "name" => "supervisor-test",
    ]);

    $marketing_1 = Personel::factory()->create([
        "position_id" => $rm_position->id,
        "supervisor_id" => $supervisor->id,
        "name" => "aplikator-test-1",
    ]);

    $marketing_2 = Personel::factory()->create([
        "position_id" => $rm_position->id,
        "supervisor_id" => $supervisor->id,
        "name" => "aplikator-test-2",
    ]);

    $applicator = Personel::factory()->create([
        "position_id" => $applicator_position->id,
        "supervisor_id" => $marketing_1->id,
    ]);

    $district = MarketingAreaDistrict::factory()->create();

    $response = null;
    $response = actingAsSupport()->patchJson("/api/v1/data-acuan/marketing-area-district/" . $district->id, [
        "personel_id" => $marketing_2->id,
        "applicator_id" => $applicator->id,
    ]);

    $response->assertStatus(422);
});
