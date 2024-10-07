<?php
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Contest\Entities\ContestParticipant;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\LogPhone\Entities\LogPhone;
use Modules\SalesOrder\Entities\SalesOrder;

uses(Tests\TestCase::class, DatabaseTransactions::class);

ini_set('max_execution_time', 6000); // Set max_execution_time to 60 seconds

test("sub dealer transfer to dealer", function () {
    $faker = Faker::create('id_ID');
    $sub_dealer = SubDealer::factory()->create();
    $dealer = Dealer::factory()->create([
        "status" => "accepted",
        "status_color" => "000000",
    ]);

    SalesOrder::factory()->create([
        "store_id" => $sub_dealer->id,
        "status" => "confirmed",
        "type" => "2",
        "date" => now(),
    ]);
    SalesOrder::factory()->create([
        "store_id" => $sub_dealer->id,
        "status" => "confirmed",
        "type" => "2",
        "date" => now()->subDay(),
    ]);

    $contest_participant = ContestParticipant::factory()->create([
        "dealer_id" => null,
        "sub_dealer_id" => $sub_dealer->id,
    ]);

    $log = LogPhone::factory()->create([
        "model_id" => $sub_dealer->id,
        "model" => get_class($sub_dealer),
        "type" => "phone"
    ]);

    $log->refresh();

    $entity = DB::table('entities')->whereNull("deleted_at")->first();

    $response = actingAsSupport()->putJson("/api/v1/dealer/sub-dealer/" . $sub_dealer->id, [
        "status" => "transfered",
        "status_color" => "000000",
        "dealer_id" => $dealer->id,
    ]);

    $sub_dealer->refresh();
    $response->assertStatus(200);
    expect($sub_dealer->deleted_at)->not->toBeNull();

    $sales_orders = DB::table('sales_orders')
        ->where("store_id", $dealer->id)
        ->get();


    $contest_participant->refresh();
    $log->refresh();

    expect($sales_orders)->toHaveCount(2);
    expect($sub_dealer->status)->toEqual("transfered");
    expect($contest_participant->dealer_id)->toEqual($dealer->id);
    expect($log->model_id)->toEqual($dealer->id);
    expect($log->model)->toEqual(get_class($dealer));
});
