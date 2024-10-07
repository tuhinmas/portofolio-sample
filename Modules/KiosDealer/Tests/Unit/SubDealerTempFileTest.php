<?php
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\SubDealerTemp;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can store with valid data", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer_temp = SubDealerTemp::factory()
        ->has(SubDealer::factory(), "subDealerFix")
        ->create();

    Storage::fake('s3');
    $response = actingAsSupport()->postJson("/api/v1/dealer/dealer-file-temp-backup", [
        "dealer_id" => $sub_dealer_temp->id,
        "file_type" => "DEALER",
        "file" => UploadedFile::fake()->image('nota.jpeg'),
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "dealer_id",
        "file_type",
        "id",
        "data",
        "file_url",
    ]);

    expect($response->getData()->data->dealer_id)->toEqual($sub_dealer_temp->id);
    expect($response->getData()->data->file_type)->toEqual("DEALER");
    expect($response->getData()->data->data)->not->toBeNull();
    expect($response->getData()->data->file_url)->not->toBeNull();
    expect($response->getData()->data->file_url)->toContain("/dealer");

});
