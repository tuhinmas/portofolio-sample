<?php

use Faker\Factory as Faker;
use Modules\DataAcuan\Entities\Grading;
use Modules\Personel\Entities\Personel;
use Modules\KiosDealerV2\Entities\DealerV2;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can create dealer grading block", function () {
    $faker = Faker::create();
    $grading = Grading::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/data-acuan/dealer-grading-block", [
        'grading_id' => $grading->id,
        'personel_id' => Personel::factory()->create()->id,
    ]);

    $dealerGet = DealerV2::where("grading_id", $response->getData()->data->grading_id)
        ->update([
            'grading_block_id' => $response->getData()->data->grading_id,
            'is_block_grading' => true,
            'deleted_at' => now()->format("Y-m-d"),
        ]);

    $response->assertStatus(201);
});
