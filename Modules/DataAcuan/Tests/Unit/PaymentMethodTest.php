<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\DealerPaymentMethod;
use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("payment method list, default", function () {
    $response = actingAsSupport()->getJson("/api/v1/data-acuan/payment-method");
    $response->assertStatus(200);
});

test("payment method list, marketing filter", function () {
    PaymentMethod::query()
        ->update([
            "is_for_marketing" => true,
        ]);

    $response = actingAsMarketing()->getJson("/api/v1/data-acuan/payment-method", [
        "is_for_marketing" => true,
    ]);
    $payment_methods = PaymentMethod::query()
        ->where("is_for_marketing", true)
        ->count();

    $response->assertStatus(200);
    expect(collect($response->getData()->data)->count())->toEqual($payment_methods);
});

/**
 * ONLY CASH ON max unsettel proforma
 */
test("only cash if dealer has unsettle payment according max_unsettle_proformas in dealer grading", function () {
    $grading = Grading::factory()->create([
        "max_unsettle_proformas" => 0,
        "maximum_payment_days" => 30
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
    ]);

    $sales_order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "payment_status" => "unpaid",
    ]);

    $response = actingAsMarketing()->json("GET", "/api/v1/data-acuan/payment-method", [
        "is_for_marketing" => true,
        "dealer_id" => $dealer->id,
        "sales_order_id" => $sales_order_2->id,
    ]);

    $response->assertStatus(200);
    expect(count($response->getData()->data))->toEqual(1);

});

test("only cash if order out of credit limit 1", function () {
    $grading = Grading::factory()->create([
        "max_unsettle_proformas" => 3,
        "credit_limit" => 100000,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 100000,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 100000,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order_2->id,
        "total" => 100000,
    ]);

    $response = actingAsMarketing()->json("GET", "/api/v1/data-acuan/payment-method", [
        "is_for_marketing" => true,
        "dealer_id" => $dealer->id,
        "sales_order_id" => $sales_order_2->id,
    ]);

    $response->assertStatus(200);
    expect(count($response->getData()->data))->toEqual(1);
});

test("only cash if order out of credit limit 2", function () {
    $grading = Grading::factory()->create([
        "max_unsettle_proformas" => 3,
        "credit_limit" => 100000,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 70000,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 70000,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order_2->id,
        "total" => 50000,
    ]);

    $response = actingAsMarketing()->json("GET", "/api/v1/data-acuan/payment-method", [
        "is_for_marketing" => true,
        "dealer_id" => $dealer->id,
        "sales_order_id" => $sales_order_2->id,
    ]);

    $response->assertStatus(200);
    expect(count($response->getData()->data))->toEqual(1);
});

/**
 * can credit depend max proforma
 */
test("have credit, but max proforma is limitless, still can credit again 1", function () {
    $grading = Grading::factory()->create([
        "credit_limit" => 1000000,
        "maximum_payment_days" => 20,
        "max_unsettle_proformas" => null,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order_2->id,
        "total" => 50000,
    ]);

    $response = actingAsMarketing()->json("GET", "/api/v1/data-acuan/payment-method", [
        "is_for_marketing" => true,
        "dealer_id" => $dealer->id,
        "sales_order_id" => $sales_order_2->id,
    ]);

    $response->assertStatus(200);
    $payment_methods = PaymentMethod::query()
        ->where("days", "<=", 20)
        ->where("is_for_marketing", true)
        ->count();

    expect(count($response->getData()->data))->toEqual($payment_methods);

});

test("have credit, but max proforma is limitless, still can credit again 2", function () {
    $grading = Grading::factory()->create([
        "credit_limit" => 1000000,
        "maximum_payment_days" => 20,
        "max_unsettle_proformas" => null,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $sales_order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order_2->id,
        "total" => 50000,
    ]);

    $response = actingAsMarketing()->json("GET", "/api/v1/data-acuan/payment-method", [
        "is_for_marketing" => true,
        "dealer_id" => $dealer->id,
        "sales_order_id" => $sales_order_2->id,
    ]);

    $response->assertStatus(200);
    $payment_methods = PaymentMethod::query()
        ->where("days", "<=", 20)
        ->where("is_for_marketing", true)
        ->count();

    expect(count($response->getData()->data))->toEqual($payment_methods);

});

/**
 * can credit depend max payment days
 */
test("have credit, but maximum_payment_days is limitless, still can credit again 1", function () {
    $grading = Grading::factory()->create([
        "credit_limit" => 1000000,
        "maximum_payment_days" => null,
        "max_unsettle_proformas" => 3,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order_2->id,
        "total" => 50000,
    ]);

    $response = actingAsMarketing()->json("GET", "/api/v1/data-acuan/payment-method", [
        "is_for_marketing" => true,
        "dealer_id" => $dealer->id,
        "sales_order_id" => $sales_order_2->id,
    ]);

    $response->assertStatus(200);
    $payment_methods = PaymentMethod::query()
        ->where("is_for_marketing", true)
        ->count();

    expect(count($response->getData()->data))->toEqual($payment_methods);
});

test("have credit, but maximum_payment_days is match to payment limit, still can credit again", function () {
    $grading = Grading::factory()->create([
        "credit_limit" => 1000000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 3,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order_2->id,
        "total" => 50000,
    ]);

    $response = actingAsMarketing()->json("GET", "/api/v1/data-acuan/payment-method", [
        "is_for_marketing" => true,
        "dealer_id" => $dealer->id,
        "sales_order_id" => $sales_order_2->id,
    ]);

    $response->assertStatus(200);
    $payment_methods = PaymentMethod::query()
        ->where("is_for_marketing", true)
        ->where("days", "<=", "10")
        ->count();

    expect(count($response->getData()->data))->toEqual($payment_methods);

});

/**
 * can credit depend dealer payment
 */
test("can credit if match to dealer payment 1", function () {
    $grading = Grading::factory()->create([
        "credit_limit" => 1000000,
        "maximum_payment_days" => 100,
        "max_unsettle_proformas" => 3,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grading->id,
    ]);

    PaymentMethod::query()
        ->where("is_for_marketing", true)
        ->where("days", "<=", "10")
        ->get()
        ->each(function ($payment) use ($dealer) {
            DealerPaymentMethod::factory()->create([
                "dealer_id" => $dealer->id,
                "payment_method_id" => $payment->id,
            ]);
        });

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order_2->id,
        "total" => 50000,
    ]);

    $response = actingAsMarketing()->json("GET", "/api/v1/data-acuan/payment-method", [
        "is_for_marketing" => true,
        "dealer_id" => $dealer->id,
        "sales_order_id" => $sales_order_2->id,
    ]);

    $response->assertStatus(200);
    $payment_methods = PaymentMethod::query()
        ->where("is_for_marketing", true)
        ->where("days", "<=", "10")
        ->count();

    expect(count($response->getData()->data))->toEqual($payment_methods);
});

test("can credit if match to dealer payment 2", function () {
    $grading = Grading::factory()->create([
        "credit_limit" => 1000000,
        "maximum_payment_days" => 100,
        "max_unsettle_proformas" => 3,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grading->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order_2->id,
        "total" => 50000,
    ]);

    $response = actingAsMarketing()->json("GET", "/api/v1/data-acuan/payment-method", [
        "is_for_marketing" => true,
        "dealer_id" => $dealer->id,
        "sales_order_id" => $sales_order_2->id,
    ]);

    $response->assertStatus(200);
    $payment_methods = PaymentMethod::query()
        ->where("is_for_marketing", true)
        ->where("days", "<=", "100")
        ->count();

    expect(count($response->getData()->data))->toEqual($payment_methods);
});
