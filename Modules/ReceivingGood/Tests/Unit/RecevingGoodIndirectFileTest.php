<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\ReceivingGood\Entities\ReceivingGoodIndirectSale;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("indirect: receiving good test", function () {
    $receiving_good = ReceivingGoodIndirectSale::factory()->create();

    Storage::fake('s3');

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-indirect-file", [
        "caption" => "test",
        "receiving_good_id" => $receiving_good->id,
        "attachment_status" => "confirm",
        "file" => UploadedFile::fake()->image('nota.jpg'),
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data)->toHaveKeys([
        "link",
        "caption",
        "attachment",
        "attachment_status",
    ]);

    expect($response->getData()->data->link)->not->toBeNull();
    expect($response->getData()->data->attachment)->not->toBeNull();
    expect($response->getData()->data->link)->toContain('/indirect/receiving-good/attachment/');
});

test("indirect: receiving good test with jpeg", function () {
    $receiving_good = ReceivingGoodIndirectSale::factory()->create();

    Storage::fake('s3');

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-indirect-file", [
        "caption" => "test",
        "receiving_good_id" => $receiving_good->id,
        "attachment_status" => "confirm",
        "file" => UploadedFile::fake()->image('nota.jpeg'),
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data)->toHaveKeys([
        "link",
        "caption",
        "attachment",
        "attachment_status",
    ]);

    expect($response->getData()->data->link)->not->toBeNull();
    expect($response->getData()->data->attachment)->not->toBeNull();
    expect($response->getData()->data->link)->toContain('/indirect/receiving-good/attachment/');
});

test("indirect: receiving good test with png", function () {
    $receiving_good = ReceivingGoodIndirectSale::factory()->create();

    Storage::fake('s3');

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-indirect-file", [
        "caption" => "test",
        "receiving_good_id" => $receiving_good->id,
        "attachment_status" => "confirm",
        "file" => UploadedFile::fake()->image('nota.png'),
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data)->toHaveKeys([
        "link",
        "caption",
        "attachment",
        "attachment_status",
    ]);

    expect($response->getData()->data->link)->not->toBeNull();
    expect($response->getData()->data->attachment)->not->toBeNull();
    expect($response->getData()->data->link)->toContain('/indirect/receiving-good/attachment/');
});

test("indirect: receiving good test with heic", function () {
    $receiving_good = ReceivingGoodIndirectSale::factory()->create();

    Storage::fake('s3');

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-indirect-file", [
        "caption" => "test",
        "receiving_good_id" => $receiving_good->id,
        "attachment_status" => "confirm",
        "file" => UploadedFile::fake()->image('nota.heic'),
    ]);

    $response->assertStatus(422);
});

test("indirect: receiving good test with pdf", function () {
    $receiving_good = ReceivingGoodIndirectSale::factory()->create();

    Storage::fake('s3');

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-indirect-file", [
        "caption" => "test",
        "receiving_good_id" => $receiving_good->id,
        "attachment_status" => "confirm",
        "file" => UploadedFile::fake()->image('nota.pdf'),
    ]);

    $response->assertStatus(422);
});

test("indirect: receiving good test attachment required if file not present", function () {
    $receiving_good = ReceivingGoodIndirectSale::factory()->create();

    Storage::fake('s3');

    $response = actingAsSupport()->postJson("/api/v1/receiving-good-indirect-file", [
        "caption" => "test",
        "receiving_good_id" => $receiving_good->id,
        "attachment_status" => "confirm",
    ]);

    $response->assertStatus(422);
});
