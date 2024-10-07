<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Modules\Contest\Entities\Contest;
use Modules\Contest\Jobs\ContestPointCalculationByOrderJob;
use Modules\DataAcuan\Entities\Fee;
use Modules\DataAcuan\Entities\Product;
use Modules\Invoice\Entities\Invoice;
use Modules\Personel\Entities\MarketingFee;
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
        "status" => "submited",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product->id,
        "quantity" => 100,
    ]);

    $distributor_pickup = SalesOrder::factory()->create([
        "store_id" => $sales_order->distributor_id,
        "personel_id" => $personel->id,
        "type" => 1,
        "status" => "confirmed",
    ]);

    $distributor_pickup_product = SalesOrderDetail::factory()->create([
        "sales_order_id" => $distributor_pickup->id,
        "product_id" => $product->id,
        "quantity" => 1000,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $distributor_pickup->id,
        "payment_status" => "settle",
        "created_at" => now()->subDay(2),
    ]);

    $fee_products = Fee::query()
        ->where("product_id", $product->id)
        ->where("year", now()->format("Y"))
        ->where("quartal", now()->quarter)
        ->get();

    Bus::fake();

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "confirmed",
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

    $response->assertStatus(200);
});

/**
 * CONTEST POINT TEST
 */
// test("contest point after proofing, point origin stored", function () {
//     $personel = Personel::factory()->create();
//     $sub_dealer = SubDealer::factory()->create();
//     $product = Product::query()
//         ->first();

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $sub_dealer->id,
//         "model" => "2",
//         "personel_id" => $personel->id,
//         "type" => 2,
//         "date" => now(),
//         "status" => "submited",
//         "is_office" => false,
//     ]);

//     $distributor_area = DistributorArea::query()
//         ->whereHas("contract", function ($QQQ) use ($sales_order) {
//             return $QQQ->where("dealer_id", $sales_order->distributor_id);
//         })
//         ->first();

//     Address::factory()->create([
//         "type" => "sub_dealer",
//         "parent_id" => $sub_dealer->id,
//         "province_id" => $distributor_area->province_id,
//         "city_id" => $distributor_area->city_id,
//         "district_id" => $distributor_area->district_id,
//     ]);

//     $sales_order_detail = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $sales_order->id,
//         "product_id" => $product->id,
//         "quantity" => 100,
//     ]);

//     $distributor_pickup = SalesOrder::factory()->create([
//         "store_id" => $sales_order->distributor_id,
//         "personel_id" => $personel->id,
//         "type" => 1,
//         "status" => "confirmed",
//     ]);

//     $distributor_pickup_product = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "product_id" => $product->id,
//         "quantity" => 1000,
//     ]);

//     Invoice::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "payment_status" => "settle",
//         "created_at" => now()->subDay(2),
//     ]);

//     /* contest prepared */
//     $contest = Contest::factory()->create();
//     ContestPointReference::query()
//         ->where("product_id", $product->id)
//         ->delete();

//     ContestPointReference::factory()->create([
//         "contest_id" => $contest->id,
//         "product_id" => $product->id,
//         "minimum_quantity" => 1,
//         "product_point" => 1,
//         "periodic_status" => false,
//         "periodic_start_date" => $contest->period_date_start,
//         "periodic_end_date" => $contest->period_date_end,

//     ]);

//     $contest_prize = ContestPrize::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     ContestParticipant::factory()->create([
//         "personel_id" => $personel->id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->distributor_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     ContestParticipant::factory()->create([
//         "personel_id" => $sales_order->personel_id,
//         "contest_id" => $contest->id,
//         "sub_dealer_id" => $sales_order->store_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
//         "status" => "confirmed",
//     ]);

//     $contest_point_origin = ContestPointOrigin::query()
//         ->where("sales_order_details_id", $sales_order_detail->id)
//         ->first();

//     $response->assertStatus(200);
//     expect($contest_point_origin->point)->toEqual(100);
// });

// test("contest point after proofing, store_id from dealer", function () {
//     $personel = Personel::factory()->create();
//     $grading = Grading::query()
//         ->where("default", false)
//         ->first();

//     $dealer = Dealer::factory()->create([
//         "grading_id" => $grading->id,
//     ]);

//     $product = Product::query()
//         ->first();

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $dealer->id,
//         "model" => "1",
//         "personel_id" => $personel->id,
//         "type" => 2,
//         "date" => now(),
//         "status" => "submited",
//         "is_office" => false,
//     ]);

//     $distributor_area = DistributorArea::query()
//         ->whereHas("contract", function ($QQQ) use ($sales_order) {
//             return $QQQ->where("dealer_id", $sales_order->distributor_id);
//         })
//         ->first();

//     Address::factory()->create([
//         "type" => "dealer",
//         "parent_id" => $dealer->id,
//         "province_id" => $distributor_area->province_id,
//         "city_id" => $distributor_area->city_id,
//         "district_id" => $distributor_area->district_id,
//     ]);

//     $sales_order_detail = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $sales_order->id,
//         "product_id" => $product->id,
//         "quantity" => 100,
//     ]);

//     $distributor_pickup = SalesOrder::factory()->create([
//         "store_id" => $sales_order->distributor_id,
//         "personel_id" => $personel->id,
//         "type" => 1,
//         "status" => "confirmed",
//     ]);

//     $distributor_pickup_product = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "product_id" => $product->id,
//         "quantity" => 1000,
//     ]);

//     Invoice::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "payment_status" => "settle",
//         "created_at" => now()->subDay(2),
//     ]);

//     /* contest prepared */
//     $contest = Contest::factory()->create();

//     ContestPointReference::query()
//         ->where("product_id", $product->id)
//         ->delete();

//     ContestPointReference::factory()->create([
//         "contest_id" => $contest->id,
//         "product_id" => $product->id,
//         "minimum_quantity" => 1,
//         "product_point" => 1,
//         "periodic_status" => false,
//         "periodic_start_date" => $contest->period_date_start,
//         "periodic_end_date" => $contest->period_date_end,

//     ]);

//     $contest_prize = ContestPrize::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     /* distributor contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $personel->id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->distributor_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     /* retailer contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $sales_order->personel_id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->store_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
//         "status" => "confirmed",
//     ]);

//     $contest_point_origin = ContestPointOrigin::query()
//         ->where("sales_order_details_id", $sales_order_detail->id)
//         ->first();

//     $response->assertStatus(200);
//     expect($contest_point_origin->point)->toEqual(100);
// });

// test("contest point after proofing, contest point from office order", function () {
//     $personel = Personel::factory()->create();
//     $grading = Grading::query()
//         ->where("default", true)
//         ->first();

//     $dealer = Dealer::factory()->create([
//         "grading_id" => $grading->id,
//     ]);

//     $product = Product::query()
//         ->first();

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $dealer->id,
//         "model" => "1",
//         "personel_id" => $personel->id,
//         "type" => 2,
//         "date" => now(),
//         "status" => "submited",
//         "is_office" => false,
//         "grading_id" => $grading->id,
//         "is_office" => true,
//     ]);

//     $distributor_area = DistributorArea::query()
//         ->whereHas("contract", function ($QQQ) use ($sales_order) {
//             return $QQQ->where("dealer_id", $sales_order->distributor_id);
//         })
//         ->first();

//     Address::factory()->create([
//         "type" => "dealer",
//         "parent_id" => $dealer->id,
//         "province_id" => $distributor_area->province_id,
//         "city_id" => $distributor_area->city_id,
//         "district_id" => $distributor_area->district_id,
//     ]);

//     $sales_order_detail = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $sales_order->id,
//         "product_id" => $product->id,
//         "quantity" => 100,
//     ]);

//     $distributor_pickup = SalesOrder::factory()->create([
//         "store_id" => $sales_order->distributor_id,
//         "personel_id" => $personel->id,
//         "type" => 1,
//         "status" => "confirmed",
//     ]);

//     $distributor_pickup_product = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "product_id" => $product->id,
//         "quantity" => 1000,
//     ]);

//     Invoice::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "payment_status" => "settle",
//         "created_at" => now()->subDay(2),
//     ]);

//     /* contest prepared */
//     $contest = Contest::factory()->create();

//     /* default contest grading ist default grading */
//     $contest_grading = ContestDealerGrading::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     ContestPointReference::query()
//         ->where("product_id", $product->id)
//         ->delete();

//     ContestPointReference::factory()->create([
//         "contest_id" => $contest->id,
//         "product_id" => $product->id,
//         "minimum_quantity" => 1,
//         "product_point" => 1,
//         "periodic_status" => false,
//         "periodic_start_date" => $contest->period_date_start,
//         "periodic_end_date" => $contest->period_date_end,

//     ]);

//     $contest_prize = ContestPrize::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     /* distributor contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $personel->id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->distributor_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     /* retailer contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $sales_order->personel_id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->store_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
//         "status" => "confirmed",
//     ]);

//     $contest_point_origin = ContestPointOrigin::query()
//         ->where("sales_order_details_id", $sales_order_detail->id)
//         ->first();

//     $response->assertStatus(200);
//     expect($contest_point_origin->point)->toEqual(0);
// });

// test("contest point after proofing, contest point according affected retrun", function () {
//     $personel = Personel::factory()->create();
//     $grading = Grading::query()
//         ->where("default", true)
//         ->first();

//     $dealer = Dealer::factory()->create([
//         "grading_id" => $grading->id,
//     ]);

//     $product = Product::query()
//         ->first();

//     $return_order = SalesOrder::factory()->create([
//         "store_id" => $dealer->id,
//         "model" => "1",
//         "personel_id" => $personel->id,
//         "type" => 2,
//         "date" => now()->subDays(3),
//         "status" => "returned",
//         "is_office" => false,
//         "grading_id" => $grading->id,
//     ]);

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $dealer->id,
//         "model" => "1",
//         "personel_id" => $personel->id,
//         "type" => 2,
//         "date" => now(),
//         "status" => "submited",
//         "is_office" => false,
//         "grading_id" => $grading->id,
//         "afftected_by_return" => $return_order->id,
//     ]);

//     $distributor_area = DistributorArea::query()
//         ->whereHas("contract", function ($QQQ) use ($sales_order) {
//             return $QQQ->where("dealer_id", $sales_order->distributor_id);
//         })
//         ->first();

//     Address::factory()->create([
//         "type" => "dealer",
//         "parent_id" => $dealer->id,
//         "province_id" => $distributor_area->province_id,
//         "city_id" => $distributor_area->city_id,
//         "district_id" => $distributor_area->district_id,
//     ]);

//     $sales_order_detail = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $sales_order->id,
//         "product_id" => $product->id,
//         "quantity" => 100,
//     ]);

//     $distributor_pickup = SalesOrder::factory()->create([
//         "store_id" => $sales_order->distributor_id,
//         "personel_id" => $personel->id,
//         "type" => 1,
//         "status" => "confirmed",
//     ]);

//     $distributor_pickup_product = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "product_id" => $product->id,
//         "quantity" => 1000,
//     ]);

//     Invoice::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "payment_status" => "settle",
//         "created_at" => now()->subDay(2),
//     ]);

//     /* contest prepared */
//     $contest = Contest::factory()->create();

//     /* default contest grading ist default grading */
//     $contest_grading = ContestDealerGrading::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     ContestPointReference::query()
//         ->where("product_id", $product->id)
//         ->delete();

//     ContestPointReference::factory()->create([
//         "contest_id" => $contest->id,
//         "product_id" => $product->id,
//         "minimum_quantity" => 1,
//         "product_point" => 1,
//         "periodic_status" => false,
//         "periodic_start_date" => $contest->period_date_start,
//         "periodic_end_date" => $contest->period_date_end,

//     ]);

//     $contest_prize = ContestPrize::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     /* distributor contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $personel->id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->distributor_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     /* retailer contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $sales_order->personel_id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->store_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
//         "status" => "confirmed",
//     ]);

//     $contest_point_origin = ContestPointOrigin::query()
//         ->where("sales_order_details_id", $sales_order_detail->id)
//         ->first();

//     $response->assertStatus(200);
//     expect($contest_point_origin->point)->toEqual(0);
// });

// test("contest point after proofing, contest point according distributor", function () {
//     $personel = Personel::factory()->create();
//     $grading = Grading::query()
//         ->where("default", true)
//         ->first();

//     $dealer = Dealer::factory()->create([
//         "grading_id" => $grading->id,
//     ]);

//     $product = Product::query()
//         ->first();

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $dealer->id,
//         "model" => "1",
//         "personel_id" => $personel->id,
//         "type" => 2,
//         "date" => now(),
//         "status" => "submited",
//         "is_office" => false,
//         "grading_id" => $grading->id,
//     ]);

//     $distributor_area = DistributorArea::query()
//         ->whereHas("contract", function ($QQQ) use ($sales_order) {
//             return $QQQ->where("dealer_id", $sales_order->distributor_id);
//         })
//         ->first();

//     Address::factory()->create([
//         "type" => "dealer",
//         "parent_id" => $dealer->id,
//         "province_id" => $distributor_area->province_id,
//         "city_id" => $distributor_area->city_id,
//         "district_id" => $distributor_area->district_id,
//     ]);

//     $sales_order_detail = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $sales_order->id,
//         "product_id" => $product->id,
//         "quantity" => 100,
//     ]);

//     $distributor_pickup = SalesOrder::factory()->create([
//         "store_id" => $sales_order->distributor_id,
//         "personel_id" => $personel->id,
//         "type" => 1,
//         "status" => "confirmed",
//     ]);

//     $distributor_pickup_product = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "product_id" => $product->id,
//         "quantity" => 1000,
//     ]);

//     Invoice::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "payment_status" => "settle",
//         "created_at" => now()->subDay(2),
//     ]);

//     /* contest prepared */
//     $contest = Contest::factory()->create();

//     /* default contest grading ist default grading */
//     $contest_grading = ContestDealerGrading::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     ContestPointReference::query()
//         ->where("product_id", $product->id)
//         ->delete();

//     ContestPointReference::factory()->create([
//         "contest_id" => $contest->id,
//         "product_id" => $product->id,
//         "minimum_quantity" => 1,
//         "product_point" => 1,
//         "periodic_status" => false,
//         "periodic_start_date" => $contest->period_date_start,
//         "periodic_end_date" => $contest->period_date_end,

//     ]);

//     $contest_prize = ContestPrize::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     /* retailer contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $sales_order->personel_id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->store_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
//         "status" => "confirmed",
//     ]);

//     $contest_point_origin = ContestPointOrigin::query()
//         ->where("sales_order_details_id", $sales_order_detail->id)
//         ->first();

//     $response->assertStatus(200);
//     expect($contest_point_origin->point)->toEqual(0);
// });

// test("contest point after proofing, contest point according grade of order", function () {
//     $personel = Personel::factory()->create();
//     $grading = Grading::query()
//         ->where("default", false)
//         ->first();

//     $dealer = Dealer::factory()->create([
//         "grading_id" => $grading->id,
//     ]);

//     $product = Product::query()
//         ->first();

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $dealer->id,
//         "model" => "1",
//         "personel_id" => $personel->id,
//         "type" => 2,
//         "date" => now(),
//         "status" => "submited",
//         "is_office" => false,
//         "grading_id" => $grading->id,
//     ]);

//     $distributor_area = DistributorArea::query()
//         ->whereHas("contract", function ($QQQ) use ($sales_order) {
//             return $QQQ->where("dealer_id", $sales_order->distributor_id);
//         })
//         ->first();

//     Address::factory()->create([
//         "type" => "dealer",
//         "parent_id" => $dealer->id,
//         "province_id" => $distributor_area->province_id,
//         "city_id" => $distributor_area->city_id,
//         "district_id" => $distributor_area->district_id,
//     ]);

//     $sales_order_detail = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $sales_order->id,
//         "product_id" => $product->id,
//         "quantity" => 100,
//     ]);

//     $distributor_pickup = SalesOrder::factory()->create([
//         "store_id" => $sales_order->distributor_id,
//         "personel_id" => $personel->id,
//         "type" => 1,
//         "status" => "confirmed",
//     ]);

//     $distributor_pickup_product = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "product_id" => $product->id,
//         "quantity" => 1000,
//     ]);

//     Invoice::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "payment_status" => "settle",
//         "created_at" => now()->subDay(2),
//     ]);

//     /* contest prepared */
//     $contest = Contest::factory()->create();

//     /* default contest grading ist default grading */
//     $contest_grading = ContestDealerGrading::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     ContestPointReference::query()
//         ->where("product_id", $product->id)
//         ->delete();

//     ContestPointReference::factory()->create([
//         "contest_id" => $contest->id,
//         "product_id" => $product->id,
//         "minimum_quantity" => 1,
//         "product_point" => 1,
//         "periodic_status" => false,
//         "periodic_start_date" => $contest->period_date_start,
//         "periodic_end_date" => $contest->period_date_end,

//     ]);

//     $contest_prize = ContestPrize::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     /* distributor contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $personel->id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->distributor_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     /* retailer contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $sales_order->personel_id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->store_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
//         "status" => "confirmed",
//     ]);

//     $contest_point_origin = ContestPointOrigin::query()
//         ->where("sales_order_details_id", $sales_order_detail->id)
//         ->first();

//     $response->assertStatus(200);
//     expect($contest_point_origin->point)->toEqual(0);
// });

// test("contest point after proofing, contest point according blcked grade", function () {
//     $personel = Personel::factory()->create();
//     $grading = Grading::query()
//         ->where("default", true)
//         ->first();

//     $dealer = Dealer::factory()->create([
//         "grading_id" => $grading->id,
//     ]);

//     GradingBlock::factory()->create([
//         "grading_id" => $grading->id,
//     ]);

//     $product = Product::query()
//         ->first();

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $dealer->id,
//         "model" => "1",
//         "personel_id" => $personel->id,
//         "type" => 2,
//         "date" => now(),
//         "status" => "submited",
//         "is_office" => false,
//         "grading_id" => $grading->id,
//     ]);

//     $distributor_area = DistributorArea::query()
//         ->whereHas("contract", function ($QQQ) use ($sales_order) {
//             return $QQQ->where("dealer_id", $sales_order->distributor_id);
//         })
//         ->first();

//     Address::factory()->create([
//         "type" => "dealer",
//         "parent_id" => $dealer->id,
//         "province_id" => $distributor_area->province_id,
//         "city_id" => $distributor_area->city_id,
//         "district_id" => $distributor_area->district_id,
//     ]);

//     $sales_order_detail = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $sales_order->id,
//         "product_id" => $product->id,
//         "quantity" => 100,
//     ]);

//     $distributor_pickup = SalesOrder::factory()->create([
//         "store_id" => $sales_order->distributor_id,
//         "personel_id" => $personel->id,
//         "type" => 1,
//         "status" => "confirmed",
//     ]);

//     $distributor_pickup_product = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "product_id" => $product->id,
//         "quantity" => 1000,
//     ]);

//     Invoice::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "payment_status" => "settle",
//         "created_at" => now()->subDay(2),
//     ]);

//     /* contest prepared */
//     $contest = Contest::factory()->create();

//     /* default contest grading ist default grading */
//     $contest_grading = ContestDealerGrading::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     ContestPointReference::query()
//         ->where("product_id", $product->id)
//         ->delete();

//     ContestPointReference::factory()->create([
//         "contest_id" => $contest->id,
//         "product_id" => $product->id,
//         "minimum_quantity" => 1,
//         "product_point" => 1,
//         "periodic_status" => false,
//         "periodic_start_date" => $contest->period_date_start,
//         "periodic_end_date" => $contest->period_date_end,

//     ]);

//     $contest_prize = ContestPrize::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     /* distributor contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $personel->id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->distributor_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     /* retailer contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $sales_order->personel_id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->store_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
//         "status" => "confirmed",
//     ]);

//     $contest_point_origin = ContestPointOrigin::query()
//         ->where("sales_order_details_id", $sales_order_detail->id)
//         ->first();

//     $response->assertStatus(200);
//     expect($contest_point_origin->point)->toEqual(0);
// });

// test("contest point after proofing, minimum quantity had not match point reference", function () {
//     $personel = Personel::factory()->create();
//     $grading = Grading::query()
//         ->where("default", true)
//         ->first();

//     $dealer = Dealer::factory()->create([
//         "grading_id" => $grading->id,
//     ]);

//     $product = Product::query()
//         ->first();

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $dealer->id,
//         "model" => "1",
//         "personel_id" => $personel->id,
//         "type" => 2,
//         "date" => now(),
//         "status" => "submited",
//         "is_office" => false,
//         "grading_id" => $grading->id,
//     ]);

//     $distributor_area = DistributorArea::query()
//         ->whereHas("contract", function ($QQQ) use ($sales_order) {
//             return $QQQ->where("dealer_id", $sales_order->distributor_id);
//         })
//         ->first();

//     Address::factory()->create([
//         "type" => "dealer",
//         "parent_id" => $dealer->id,
//         "province_id" => $distributor_area->province_id,
//         "city_id" => $distributor_area->city_id,
//         "district_id" => $distributor_area->district_id,
//     ]);

//     $sales_order_detail = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $sales_order->id,
//         "product_id" => $product->id,
//         "quantity" => 10,
//     ]);

//     $distributor_pickup = SalesOrder::factory()->create([
//         "store_id" => $sales_order->distributor_id,
//         "personel_id" => $personel->id,
//         "type" => 1,
//         "status" => "confirmed",
//     ]);

//     $distributor_pickup_product = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "product_id" => $product->id,
//         "quantity" => 1000,
//     ]);

//     Invoice::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "payment_status" => "settle",
//         "created_at" => now()->subDay(2),
//     ]);

//     /* contest prepared */
//     $contest = Contest::factory()->create();

//     /* default contest grading ist default grading */
//     $contest_grading = ContestDealerGrading::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     ContestPointReference::query()
//         ->where("product_id", $product->id)
//         ->delete();

//     ContestPointReference::factory()->create([
//         "contest_id" => $contest->id,
//         "product_id" => $product->id,
//         "minimum_quantity" => 100,
//         "product_point" => 1,
//         "periodic_status" => false,
//         "periodic_start_date" => $contest->period_date_start,
//         "periodic_end_date" => $contest->period_date_end,

//     ]);

//     $contest_prize = ContestPrize::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     /* distributor contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $personel->id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->distributor_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     /* retailer contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $sales_order->personel_id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->store_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
//         "status" => "confirmed",
//     ]);

//     $contest_point_origin = ContestPointOrigin::query()
//         ->where("sales_order_details_id", $sales_order_detail->id)
//         ->first();

//     $log = LogContestPointOrigin::query()
//         ->where("sales_order_detail_id", $sales_order_detail->id)
//         ->first();

//     $response->assertStatus(200);
//     expect($contest_point_origin->point)->toEqual(0);
//     expect($log)->toBeTruthy();
// });

// /**
//  * CONTEST POINT ACTIVE
//  */
// test("contest point active after proofing, considered active", function () {
//     $personel = Personel::factory()->create();
//     $grading = Grading::query()
//         ->where("default", false)
//         ->first();

//     $dealer = Dealer::factory()->create([
//         "grading_id" => $grading->id,
//     ]);

//     $product = Product::query()
//         ->first();

//     $sales_order = SalesOrder::factory()->create([
//         "store_id" => $dealer->id,
//         "model" => "1",
//         "personel_id" => $personel->id,
//         "type" => 2,
//         "date" => now(),
//         "status" => "submited",
//         "is_office" => false,
//     ]);

//     $distributor_area = DistributorArea::query()
//         ->whereHas("contract", function ($QQQ) use ($sales_order) {
//             return $QQQ->where("dealer_id", $sales_order->distributor_id);
//         })
//         ->first();

//     Address::factory()->create([
//         "type" => "dealer",
//         "parent_id" => $dealer->id,
//         "province_id" => $distributor_area->province_id,
//         "city_id" => $distributor_area->city_id,
//         "district_id" => $distributor_area->district_id,
//     ]);

//     $sales_order_detail = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $sales_order->id,
//         "product_id" => $product->id,
//         "quantity" => 100,
//     ]);

//     $distributor_pickup = SalesOrder::factory()->create([
//         "store_id" => $sales_order->distributor_id,
//         "personel_id" => $personel->id,
//         "type" => 1,
//         "status" => "confirmed",
//     ]);

//     $distributor_pickup_product = SalesOrderDetail::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "product_id" => $product->id,
//         "quantity" => 1000,
//     ]);

//     Invoice::factory()->create([
//         "sales_order_id" => $distributor_pickup->id,
//         "payment_status" => "settle",
//         "created_at" => now()->subDay(2),
//     ]);

//     /* contest prepared */
//     $contest = Contest::factory()->create();

//     ContestPointReference::query()
//         ->where("product_id", $product->id)
//         ->delete();

//     ContestPointReference::factory()->create([
//         "contest_id" => $contest->id,
//         "product_id" => $product->id,
//         "minimum_quantity" => 1,
//         "product_point" => 1,
//         "periodic_status" => false,
//         "periodic_start_date" => $contest->period_date_start,
//         "periodic_end_date" => $contest->period_date_end,

//     ]);

//     $contest_prize = ContestPrize::factory()->create([
//         "contest_id" => $contest->id,
//     ]);

//     /* distributor contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $personel->id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->distributor_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     /* retailer contract contest */
//     ContestParticipant::factory()->create([
//         "personel_id" => $sales_order->personel_id,
//         "contest_id" => $contest->id,
//         "dealer_id" => $sales_order->store_id,
//         "contest_prize_id" => $contest_prize->id,
//     ]);

//     $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
//         "status" => "confirmed",
//     ]);

//     $contest_point_origin = ContestPointOrigin::query()
//         ->where("sales_order_details_id", $sales_order_detail->id)
//         ->first();

//     $response->assertStatus(200);
//     expect($contest_point_origin->point)->toEqual(100);
//     expect($contest_point_origin->active_point)->toBeTruthy();
// });
