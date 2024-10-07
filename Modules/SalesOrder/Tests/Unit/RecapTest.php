<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrder\Entities\SalesOrder;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("dealer product distribution per year", function () {
    $dealer = Dealer::factory()->create();
    $sales_order = SalesOrder::factory()->create([
        "type" => "2",
        "model" => "1",
        "store_id" => $dealer->id,
        "status" => "confirmed",
        "date" => now()->subDays(2),
    ]);

    $response = actingAsSupport()->getJson("/api/v1/sales-order/product-sale-by-store/" . $dealer->id, [
        "sort_by_total_product" => true,
        "year" => Carbon::parse($sales_order->date)->format("Y"),
        "direction" => "desc",
    ]);

    $response->assertStatus(200);
});
