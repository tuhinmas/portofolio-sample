<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can create personnel status history", function () {
    $area = MarketingAreaDistrict::factory()->marketingRmUnderMDM()->create();
    $personel = Personel::findOrFail($area->personel_id);
    $dealer = Dealer::factory()->create([
        "personel_id" => $personel->id,
    ]);

    $sub_dealer = SubDealer::factory()->create([
        "personel_id" => $personel->id,
    ]);

    $sales_order_confirm = SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "status" => "confirmed",
    ]);

    $sales_order_submited = SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "status" => "submited",
    ]);

    $sales_order_draft = SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "status" => "draft",
    ]);

    $sales_order_onhold = SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "status" => "onhold",
    ]);

    $response = actingAsSupport()->postJson("/api/v1/personnel/personnel-status-history", [
        "start_date" => now()->subDays(),
        "change_by" => Personel::factory()->create()->id,
        "personel_id" => $personel->id,
        "status" => "3",
    ]);

    $response->assertStatus(201);

    $area->refresh();
    $dealer->refresh();
    $sub_dealer->refresh();
    $sales_order_submited->refresh();
    $sales_order_draft->refresh();
    $sales_order_onhold->refresh();

    expect($area->personel_id)->toEqual($personel->supervisor_id);
    expect($dealer->personel_id)->toEqual($personel->supervisor_id);
    expect($sub_dealer->personel_id)->toEqual($personel->supervisor_id);
    expect($sales_order_submited->personel_id)->toEqual($personel->supervisor_id);
    expect($sales_order_draft->personel_id)->toEqual($personel->supervisor_id);
    expect($sales_order_onhold->personel_id)->toEqual($personel->supervisor_id);
    expect($sales_order_confirm->personel_id)->toEqual($personel->id);

});
