<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\Fee;
use Modules\DataAcuan\Entities\Product;
use Modules\Invoice\Entities\Invoice;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Jobs\Origin\SalesOrderOriginIndirectGeneratorJob;
use Modules\SalesOrder\Actions\Order\Origin\GenerateSalesOriginFromOrderAction;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can proofing indirect sales and job dispatched", function () {
    $personel = Personel::factory()->create();

    /* reset fee first */
    MarketingFee::updateOrCreate(
        [
            "personel_id" => $personel->id,
            "year" => now()->format("Y"),
            "quarter" => now()->quarter,

        ],
        [
            "fee_reguler_total" => 0,
            "fee_reguler_settle" => 0,
            "fee_reguler_settle_pending" => 0,
            "fee_target_total" => 0,
            "fee_target_settle" => 0,
            "fee_target_settle_pending" => 0,
        ]
    );

    $product = Product::factory()->create();
    Fee::factory()->create([
        "product_id" => $product->id,
        "year" => now()->year,
        "quartal" => now()->quarter,
        "type" => "1",
    ]);

    Fee::query()
        ->where("product_id", $product->id)
        ->where("year", now()->year)
        ->where("quartal", now()->quarter)
        ->delete();

    Fee::factory()->create([
        "type" => "1",
        "product_id" => $product->id,
        "quantity" => 1,
        "fee" => 1000,
    ]);

    Fee::factory()->create([
        "type" => "2",
        "product_id" => $product->id,
        "quantity" => 10,
        "fee" => 1000,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "personel_id" => $personel->id,
        "type" => 2,
        "date" => now(),
        "status" => "confirmed",
    ]);

    $product_3 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product->id,
        "quantity" => 25,
    ]);

    $pickup_1 = SalesOrder::factory()->create([
        "store_id" => $sales_order->distributor_id,
        "personel_id" => $personel->id,
        "type" => 1,
        "status" => "confirmed",
    ]);

    $product_1 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $pickup_1->id,
        "product_id" => $product->id,
        "quantity" => 10,
        "unit_price" => 12500,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $pickup_1->id,
        "payment_status" => "settle",
        "created_at" => now()->subDay(2),
    ]);

    $pickup_2 = SalesOrder::factory()->create([
        "store_id" => $sales_order->distributor_id,
        "personel_id" => $personel->id,
        "type" => 1,
        "status" => "confirmed",
    ]);

    $product_2 = SalesOrderDetail::factory()->create([
        "sales_order_id" => $pickup_2->id,
        "product_id" => $product->id,
        "quantity" => 15,
        "unit_price" => 15000,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $pickup_2->id,
        "payment_status" => "settle",
        "created_at" => now()->subDay(3),
    ]);

    (new GenerateSalesOriginFromOrderAction)($pickup_1);
    (new GenerateSalesOriginFromOrderAction)($pickup_2);
    (new SalesOrderOriginIndirectGeneratorJob($sales_order))->handle(new GenerateSalesOriginFromOrderAction);

    $product_3->refresh();
    expect((int) $product_3->unit_price)->toEqual(14000);
    expect((int) $product_3->total)->toEqual(350000);
});
