<?php

use Illuminate\Support\Facades\Bus;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Jobs\MarketingAreaDistrict\DeletedDistrictJob;
use Modules\DataAcuan\Jobs\MarketingAreaDistrict\UpdatedDistrictJob;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("district, deleted district all dealer will revoke from marketing", function(){
    $district = MarketingAreaDistrict::factory()->create();

    Bus::fake();

    $response = actingAsSupport()->deleteJson("/api/v1/data-acuan/marketing-area-district/".$district->id);
    $response->assertStatus(200);
    Bus::assertDispatched(DeletedDistrictJob::class);
});

test("district, change marketing", function(){
    $district_1 = MarketingAreaDistrict::factory()->create();
    $district_2 = MarketingAreaDistrict::factory()->create();
    Bus::fake();

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-district/".$district_1->id, [
        "personel_id" => $district_2->personel_id
    ]);
    $response->assertStatus(200);
    Bus::assertDispatched(UpdatedDistrictJob::class);
});