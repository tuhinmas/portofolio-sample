<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Modules\Authentication\Entities\User;
use Modules\Contest\Jobs\ContestPointCalculationByOrderJob;
use Modules\DataAcuan\Entities\Fee;
use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\Product;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Jobs\CalculateFeeMarketingPerProductJob;
use Modules\SalesOrderV2\Jobs\CalculateFeeRegulerSharingOriginJob;
use Modules\SalesOrderV2\Jobs\CalculateMarketingFeeByOrderJob;
use Modules\SalesOrderV2\Jobs\CalculateMarketingFeeTargetByOrderJob;
use Modules\SalesOrderV2\Jobs\FollowUpOrderJob;
use Modules\SalesOrderV2\Jobs\GenerateFeeRegulerSharingOriginJob;
use Modules\SalesOrderV2\Jobs\GenerateFeeTargetSharingOriginJob;
use Modules\SalesOrderV2\Jobs\Indirect\IndirectTotalAmountSetterJob;
use Modules\SalesOrderV2\Jobs\MarketingPointCalculationByOrderJob;
use Modules\SalesOrderV2\Jobs\Origin\SalesOrderOriginIndirectGeneratorJob;
use Modules\SalesOrderV2\Jobs\UpdateStatusFeeOnConfirmOrderJob;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Jobs\ReturnedOrderInQuarterCheckJob;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can create proforma and job dispatched", function () {
    $personel = Personel::factory()->create();

    $grading = Grading::factory()->create([
        "default" => true,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $product = Product::factory()->create();

    $fee_products = Fee::factory()->create([
        "product_id" => $product->id,
        "year" => now()->year,
        "quartal" => now()->quarter,
        "quantity" => 1,
        "fee" => 500,
    ]);

    $fee_products = Fee::factory()->create([
        "product_id" => $product->id,
        "year" => now()->year,
        "quartal" => now()->quarter,
        "quantity" => 10,
        "fee" => 300,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "personel_id" => $personel->id,
        "type" => 1,
        "model" => "1",
        "status" => "submited",
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product->id,
        "quantity" => 1000,
    ]);

    Bus::fake();

    $user = User::query()
        ->whereHas("profile", function ($QQQ) {
            return $QQQ->where("name", "support");
        })
        ->first();

    $response = actingAsSupport()->postJson("/api/v1/invoice", [
        "sales_order_id" => $sales_order->id,
        "user_id" => auth()->id(),
        "sub_total" => 20000,
        "discount" => 5000,
        "ppn" => 1000,
        "total" => 16000,
    ]);

    Bus::assertChained([
        ReturnedOrderInQuarterCheckJob::class,
        FollowUpOrderJob::class,
        UpdateStatusFeeOnConfirmOrderJob::class,
        CalculateFeeMarketingPerProductJob::class,
        GenerateFeeRegulerSharingOriginJob::class,
        CalculateFeeRegulerSharingOriginJob::class,
        CalculateMarketingFeeByOrderJob::class,

        GenerateFeeTargetSharingOriginJob::class,
        CalculateMarketingFeeTargetByOrderJob::class,
        MarketingPointCalculationByOrderJob::class,
        ContestPointCalculationByOrderJob::class,
    ]);

    Bus::assertChained([
        SalesOrderOriginIndirectGeneratorJob::class,
        IndirectTotalAmountSetterJob::class,
    ]);

    $sales_order = SalesOrder::findOrFail($sales_order->id);
    $response->assertStatus(201);
    expect($sales_order->status)->toEqual("confirmed");
});
