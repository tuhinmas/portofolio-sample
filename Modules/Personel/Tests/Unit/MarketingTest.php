<?php

use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;
use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("detail marketing, product distribution per year", function () {
    $personel = Personel::factory()->create();

    $response = actingAsSupport()->getJson("/api/v1/personnel/marketing-list", [
        "personel_id" => $personel->supervisor_id,
    ]);

    $response->assertStatus(200);
});

test("detail marketing, direct sales recap per dealer in the last four qurter", function () {
    $personel = Personel::factory()->create();
    SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
    ]);

    $response = actingAsSupport()->getJson("/api/v1/personnel/marketing-sales-recap", [
        "personel_id" => $personel->id,
        "by_distributor" => true,
    ]);

    $response->assertStatus(200);
});

test("detail marketing, indirect sales recap per dealer in the last four qurter", function () {
    $personel = Personel::factory()->create();
    SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
    ]);

    $response = actingAsSupport()->getJson("/api/v1/personnel/marketing-sales-recap-indirect", [
        "personel_id" => $personel->id,
        "by_retailer" => true,
    ]);

    $response->assertStatus(200);
});

test("detail marketing, sales recap per year per month all order type", function () {
    $personel = Personel::factory()->create();
    SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
    ]);
    $response = actingAsSupport()->getJson("/api/v1/sales-order/sales-order-group-month-direct-indirect/" . $personel->id . "?order_type[0]=1,order_type[1]=2,order_type[2]=3,order_type[3]=4");
    $response->assertStatus(200);
});

test("detail marketing, list order per month", function () {
    $personel = Personel::factory()->create();
    SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
    ]);

    $response = actingAsSupport()->getJson("/api/v1/sales-order/detail-five-year/" . $personel->id, [
        "year" => now()->year,
        "month" => now()->format("m"),
        "personel_id" => $personel->id,
    ]);

    $response->assertStatus(200);
});

test("detail marketing, achievement per year per quartal", function () {
    $personel = Personel::factory()->create();
    SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
    ]);
    $response = actingAsSupport()->getJson("/api/v1/personnel/marketing-achievement-recap-per-year-per-quartal?personel_id=$personel->id");
    $response->assertStatus(200);
});

test("detail marketing, recap sales and fee in quartal per month", function () {
    $personel = Personel::factory()->create();
    SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
    ]);

    $year = now()->year;
    $quarter = now()->quarter;
    $response = actingAsSupport()->getJson("/api/v1/personnel/marketing-fee-recap-per-quartal?personel_id=$personel->id&year=$year&quartal=$quarter");
    $response->assertStatus(200);
});

test("detail marketing, recap sales in the last four quartal per quartal", function () {
    $personel = Personel::factory()->create();
    SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
    ]);

    $dealer = Dealer::factory()->create([
        "personel_id" => $personel->id
    ]);

    $dealer_id = $dealer->id;
    $response = actingAsSupport()->getJson("/api/v1/personnel/marketing-sales-recap-per-dealer-per-quartal/$dealer_id?by_retailer=true&type=1");

    $response->assertStatus(200);
});

test("marketing, record if marketing supervisor get changes", function () {
    $supervisor = Personel::factory()->marketingMDM()->create();
    $new_supervisor = Personel::factory()->marketingMDM()->create();

    $personel = Personel::factory()->create([
        "supervisor_id" => $supervisor->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/personnel/personnel/" . $personel->id, [
        "supervisor_id" => $new_supervisor->id,
    ]);

    $personel = Personel::findOrFail($personel->id);
    expect($personel->supervisor_id)->toBe($new_supervisor->id);
    $response->assertStatus(200);
});
