<?php

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\Religion;
use Modules\Invoice\Entities\Invoice;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("marketing product distribution per year", function () {
    $personel = Personel::factory()->create();
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
        "personel_id" => $personel->id,
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $year = Carbon::parse($invoice->created_at)->format("Y");
    $personel_id = $sales_order->personel_id;
    $product_id = $sales_order_detail->product_id;

    $response = actingAsSupport()->json("GET", "/api/v1/personnel/marketing-product-distribution-per-year", [
        "personel_id" => $personel_id,
        "year" => $year,
        "product_id" => $product_id,
    ]);

    $response->assertStatus(200);
});

test("can not create personel position with same or less supervisor position", function () {
    $faker = Faker::create('id_ID');

    $personel = Personel::factory()->create();
    $religion_id = Religion::inRandomOrder(1)->first()->id;
    $citizenship = DB::table('countries')->whereNull("deleted_at")->first()->id;
    $organisation_id = DB::table('organisations')->whereNull("deleted_at")->first()->id;
    $response = actingAsSupport()->postJson("/api/v1/personnel/personnel", [
        "supervisor_id" => $personel->id,
        "position_id" => $personel->position_id,
        "name" => $faker->word,
        "born_place" => $faker->word,
        "born_date" => $faker->date,
        "religion_id" => $religion_id,
        "gender" => "L",
        "citizenship" => $citizenship,
        "organisation_id" => $organisation_id,
        "identity_card_type" => "5",
        "identity_number" => "1212121",
        "blood_group" => "A",
        "join_date" => $faker->date,
    ]);

    $response->assertStatus(422);
});

test("can not update personel position with same or less supervisor position", function () {
    $faker = Faker::create('id_ID');

    $supervisor = Personel::factory()->create();
    $personel = Personel::factory()->create([
        "supervisor_id" => $supervisor->id,
    ]);
    $religion_id = Religion::inRandomOrder(1)->first()->id;
    $citizenship = DB::table('countries')->whereNull("deleted_at")->first()->id;
    $organisation_id = DB::table('organisations')->whereNull("deleted_at")->first()->id;
    $response = actingAsSupport()->putJson("/api/v1/personnel/personnel/" . $personel->id, [
        "position_id" => $supervisor->position_id,
    ]);

    $response->assertStatus(422);
});
