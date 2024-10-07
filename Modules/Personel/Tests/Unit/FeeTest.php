<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\Fee;
use Modules\Invoice\Entities\Invoice;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("populate fee sharing to position", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $fee_product = Fee::factory()->create();
    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $fee_product->product_id,
        "quantity" => 100,
    ]);

    $response = actingAsSupport()->getJson("/api/v1/personnel/fee-sharing-to-fee-position/" . $sales_order->id);
    $response->assertStatus(200);
});
