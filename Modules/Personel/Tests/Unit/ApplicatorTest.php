<?php

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Jobs\PersonelAsApplicatorJob;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("applicator list by marketing", function () {
    $applicator_position = Position::query()
        ->whereIn("name", applicator_positions())
        ->first();

    $personel = Personel::factory()->create();
    $applicator = Personel::factory()->create([
        "position_id" => $applicator_position->id,
        "supervisor_id" => $personel->id,
    ]);

    $response = actingAsSupport()->getJson("/api/v1/personnel/" . $personel->id . "/applicators");
    $response->assertStatus(200);
    expect(count($response->getData()->data))->toEqual(1);
});

test("applicator list by sub region, marketing did not handled area", function () {
    $applicator_position = Position::query()
        ->whereIn("name", applicator_positions())
        ->first();

    $district = MarketingAreaDistrict::factory()->create();
    $personel = Personel::factory()->create();
    $applicator = Personel::factory()->create([
        "position_id" => $applicator_position->id,
        "supervisor_id" => $personel->id,
    ]);

    $response = actingAsSupport()->getJson("/api/v1/data-acuan/marketing-area-subregion/" . $district->sub_region_id . "/applicators");
    $response->assertStatus(200);
    expect(count($response->getData()->data))->toEqual(1);
    expect(collect($response->getData()->data)->filter(fn($personel) => $personel->id == $applicator->id))->toHaveCOunt(0);
});

test("applicator list by sub region, marketing handled area", function () {
    $applicator_position = Position::query()
        ->whereIn("name", applicator_positions())
        ->first();

    $personel = Personel::factory()->create();
    $district = MarketingAreaDistrict::factory()->create();
    $district->personel_id = $personel->id;
    $district->save();

    $applicator = Personel::factory()->create([
        "position_id" => $applicator_position->id,
        "supervisor_id" => $personel->id,
    ]);

    $response = actingAsSupport()->getJson("/api/v1/data-acuan/marketing-area-subregion/" . $district->sub_region_id . "/applicators");
    $response->assertStatus(200);
    expect(count($response->getData()->data))->toEqual(1);
});

test("applicator on deactivate marketing", function () {
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

    $marketing = Personel::factory()->create([
        "position_id" => $rm_position->id,
        "supervisor_id" => $supervisor->id,
        "name" => "aplikator-test",
    ]);

    $applicator = Personel::factory()->create([
        "position_id" => $applicator_position->id,
        "supervisor_id" => $marketing->id,
    ]);

    User::factory()->create([
        "personel_id" => $marketing->id,
    ]);

    $district = MarketingAreaDistrict::factory()->create([
        "personel_id" => $marketing->id,
        "applicator_id" => $applicator->id
    ]);

    SubRegion::query()
        ->where("id", $district->sub_region_id)
        ->update([
            "personel_id" => $supervisor->id,
        ]);

    $response = actingAsSupport()->putJson("/api/v1/personnel/personnel/" . $marketing->id, [
        "status" => "3",
        "resign_date" => now(),
    ]);

    $response->assertStatus(200);
    $applicator->refresh();
    $district->refresh();
    
    expect($district->applicator_id)->toBeFalsy();
    expect($district->personel_id)->toEqual($supervisor->id);
});

test("applicator new assign to supervisor area", function () {
    $faker = Faker::create('id_ID');
    Bus::fake();
    $applicator_position = Position::factory()->create([
        "name" => "Aplikator",
    ]);

    $personel = Personel::factory()->create([
        "position_id" => $applicator_position->id,
        "name" => $faker->name,
    ]);

    $personel->delete();

    $response = actingAsSupport()->postJson("/api/v1/personnel/personnel", $personel->toArray());
    $response->assertStatus(200);
    Bus::assertDispatched(PersonelAsApplicatorJob::class);
});

test("applicator position change to marketing position, all area revoked", function () {

    $rm_position = Position::firstOrCreate([
        "name" => "Regional Marketing (RM)",
    ]);

    $applicator_position = Position::firstOrCreate([
        "name" => "Aplikator",
    ]);

    $marketing_area = MarketingAreaDistrict::factory()->marketingRMC()->create();
    $applicator = Personel::factory()->create([
        "position_id" => $applicator_position->id,
        "supervisor_id" => $marketing_area->personel_id,
        "status" => "1",
    ]);

    $marketing_area->applicator_id = $applicator->id;
    $marketing_area->save();
    $marketing_area->refresh();
    expect($marketing_area->applicator_id)->toBetruthy();

    $response = actingAsSupport()->putJson("/api/v1/personnel/personnel/" . $marketing_area->applicator_id, [
        "position_id" => $rm_position->id,
    ]);

    $marketing_area->refresh();
    $response->assertStatus(200);
    expect($marketing_area->applicator_id)->toBeNull();
});

test("applicator supervisor change, all area revoked", function () {

    $rm_position = Position::firstOrCreate([
        "name" => "Regional Marketing (RM)",
    ]);

    $applicator_position = Position::firstOrCreate([
        "name" => "Aplikator",
    ]);

    $marketing_area = MarketingAreaDistrict::factory()->marketingRMC()->create();
    $applicator = Personel::factory()->create([
        "position_id" => $applicator_position->id,
        "supervisor_id" => $marketing_area->personel_id,
        "status" => "1",
    ]);

    $marketing_area->applicator_id = $applicator->id;
    $marketing_area->save();
    $marketing_area->refresh();
    $marketing_rm = Personel::factory()->create([
        "position_id" => $rm_position->id,
    ]);
    $response = actingAsSupport()->putJson("/api/v1/personnel/personnel/" . $marketing_area->applicator_id, [
        "supervisor_id" => $marketing_rm->id,
    ]);

    $response->assertStatus(200);
    expect($marketing_area->applicator_id)->toEqual($applicator->id);
    expect($applicator->supervisor_id)->toEqual($marketing_area->personel_id);
    $marketing_area->refresh();
    $applicator->refresh();
    expect($marketing_area->applicator_id)->toBeNull();
    expect($applicator->supervisor_id)->toEqual($marketing_rm->id);
});

test("applicator supervisor not change, all area persist", function () {

    $rm_position = Position::firstOrCreate([
        "name" => "Regional Marketing (RM)",
    ]);

    $applicator_position = Position::firstOrCreate([
        "name" => "Aplikator",
    ]);

    $marketing_area = MarketingAreaDistrict::factory()->marketingRMC()->create();
    $applicator = Personel::factory()->create([
        "position_id" => $applicator_position->id,
        "supervisor_id" => $marketing_area->personel_id,
        "status" => "1",
    ]);

    $marketing_area->applicator_id = $applicator->id;
    $marketing_area->save();
    $marketing_area->refresh();
    $marketing_rm = Personel::factory()->create([
        "position_id" => $rm_position->id,
    ]);
    $response = actingAsSupport()->putJson("/api/v1/personnel/personnel/" . $marketing_area->applicator_id, [
        "name" => "joki steven",
    ]);

    $response->assertStatus(200);
    $marketing_area->refresh();
    $applicator->refresh();
    expect($marketing_area->applicator_id)->toEqual($applicator->id);
    expect($applicator->supervisor_id)->toEqual($marketing_area->personel_id);
});
