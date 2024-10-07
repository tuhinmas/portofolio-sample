<?php

use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\DataAcuan\Entities\ProvinceRegion;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can not delete province from region if there district under it", function () {
    $region = Region::factory()->create();
    ProvinceRegion::create([
        "region_id" => $region->id,
        "province_id" => "94",
    ]);

    $sub_region = SubRegion::factory()->create([
        "region_id" => $region->id,
    ]);

    $district = MarketingAreaDistrict::factory()->create([
        "sub_region_id" => $sub_region->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/data-acuan/marketing-area-region-update/". $region->id, [
        "parent_id"=> [
            "93",
            "92"
        ]
    ]);

    $response->assertStatus(422);
});
