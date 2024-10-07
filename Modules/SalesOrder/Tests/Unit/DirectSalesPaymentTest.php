<?php

use Modules\Invoice\Entities\Invoice;
use Modules\DataAcuan\Entities\Grading;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\KiosDealer\Entities\DealerGrading;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\DataAcuan\Entities\DealerPaymentMethod;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * create
 */
test("marketing, can not create draft direct order with non marketing payment method", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => "0",
    ]);
    $dealer = Dealer::factory()->create();

    $response = actingAsMarketing()->postJson("/api/v1/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

test("marketing, can not create draft direct order with non marketing payment method, v2", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $dealer = Dealer::factory()->create();
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => "0",
    ]);

    $response = actingAsMarketing()->postJson("/api/v2/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->payment_method_id[0])->toEqual("metode pembayaran untuk masrketing hanya bisa diisi saat submit order");
});

/* available payment method according grade and dealer payment */
test("marketing, can not create draft direct order with non available payment method", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
    ]);

    $grade = Grading::factory()->create([
        "maximum_payment_days" => 10,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $response = actingAsMarketing()->postJson("/api/v1/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/* available payment method according grade and dealer payment */
test("marketing, can not create draft direct order with non available payment method, V2", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
    ]);

    $grade = Grading::factory()->create([
        "maximum_payment_days" => 10,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $response = actingAsMarketing()->postJson("/api/v2/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/**
 * can not update
 */
test("marketing, can not update direct order with non marketing payment method", function () {

    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "draft",
    ]);

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => "0",
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "payment_method_id" => $payment_method->id,
        "status" => "submited",
    ]);

    $response->assertStatus(422);
});

test("marketing, can not update direct order with non marketing payment method, V2", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "draft",
    ]);

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => "0",
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "payment_method_id" => $payment_method->id,
        "status" => "submited",
    ]);

    $response->assertStatus(422);
});

/* available payment method according grade and dealer payment */
test("marketing, can not update direct order with non available payment method, V1", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
    ]);

    $grade = Grading::factory()->create([
        "maximum_payment_days" => 10,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/* available payment method according grade and dealer payment */
test("marketing, can not update direct order with non available payment method, V2", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
    ]);

    $grade = Grading::factory()->create([
        "maximum_payment_days" => 10,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/* available payment method according proforma count */
test("marketing, can not update direct depend on proforma count, V1", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 1000000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 0,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/* available payment method according proforma count */
test("marketing, can not update direct depend on proforma count, V2", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 1000000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 0,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/* available payment method according credit limit */
test("marketing, can not update direct depend on credit limit, V1", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 100000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 0,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order_first = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 100000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order_first->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 50000,
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/* available payment method according custom credit limit */
test("marketing, can not update direct depend on custom credit limit, V1", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 10,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 100000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 0,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
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

    DealerGrading::factory()->create([
        "dealer_id" => $dealer->id,
        "grading_id" => $dealer->grading_id,
        "custom_credit_limit" => 50000,
    ]);

    $sales_order_first = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order_first->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 20000,
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/* available payment method according credit limit */
test("marketing, can not update direct depend on credit limit, V2", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 100000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 0,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order_first = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 100000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order_first->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 50000,
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/* available payment method according custom credit limit */
test("marketing, can not update direct depend on custom credit limit, V2", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 10,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 100000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 0,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
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

    DealerGrading::factory()->create([
        "dealer_id" => $dealer->id,
        "grading_id" => $dealer->grading_id,
        "custom_credit_limit" => 50000,
    ]);

    $sales_order_first = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order_first->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 20000,
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/**==================================================================================================================== */
/**
 * CAN UPDATE
 */
/* available payment method according grade and dealer payment */
test("marketing, can update direct order with available payment method, V1", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 10,
    ]);

    $grade = Grading::factory()->create([
        "maximum_payment_days" => 10,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

/* available payment method according grade and dealer payment */
test("marketing, can update direct order with non available payment method, V2", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 10,
    ]);

    $grade = Grading::factory()->create([
        "maximum_payment_days" => 50,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
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
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

/* available payment method according proforma count */
test("marketing, can update direct depend on proforma count, V1", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 10,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 1000000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 2,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
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

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

/* available payment method according proforma count */
test("marketing, can update direct depend on proforma count, V2", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 1000000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 0,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
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

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(422);
});

/* available payment method according credit limit */
test("marketing, can update direct depend on credit limit, V1", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 10,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 100000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 3,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order_first = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order_first->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 50000,
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

/* available payment method according custom credit limit */
test("marketing, can update direct depend on custom credit limit, V1", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 10,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 100000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 2,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
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

    DealerGrading::factory()->create([
        "dealer_id" => $dealer->id,
        "grading_id" => $dealer->grading_id,
        "custom_credit_limit" => 70000,
    ]);

    $sales_order_first = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order_first->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 20000,
    ]);

    $response = actingAsMarketing()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

/* available payment method according credit limit */
test("marketing, can update direct depend on credit limit, V2", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 10,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 100000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 2,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order_first = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order_first->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 40000,
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

/* available payment method according custom credit limit */
test("marketing, can update direct depend on custom credit limit, V2", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 10,
        "name" => "credit",
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 100000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 2,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
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

    DealerGrading::factory()->create([
        "dealer_id" => $dealer->id,
        "grading_id" => $dealer->grading_id,
        "custom_credit_limit" => 70000,
    ]);

    $sales_order_first = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
        "total" => 50000,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order_first->id,
        "payment_status" => "unpaid",
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 20000,
    ]);

    $response = actingAsMarketing()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

/*
|-------------------------------------------------
| Payment method for support
|-----------------------------------
 */
test("support can create draft direct order with non marketing payment method", function () {

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => false,
    ]);

    $dealer = Dealer::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

test("support can create draft direct order with non marketing payment method, V2", function () {

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => false,
    ]);

    $dealer = Dealer::factory()->create();

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(201);
});

/* available payment method according grade and dealer payment */
test("support can update direct order with non available payment method for marketing", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
    ]);

    $grade = Grading::factory()->create([
        "maximum_payment_days" => 10,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

/* available payment method according grade and dealer payment */
test("support can update direct order with non available payment method for marketing, V2", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
    ]);

    $grade = Grading::factory()->create([
        "maximum_payment_days" => 10,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

test("support can update direct order, proforma count match", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 100000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 0,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});

test("support can update direct order, credit limit match match", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => true,
        "days" => 50,
    ]);

    $grade = Grading::factory()->create([
        "credit_limit" => 100000,
        "maximum_payment_days" => 10,
        "max_unsettle_proformas" => 0,
    ]);

    $dealer = Dealer::factory()->create([
        "grading_id" => $grade->id,
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "total" => 500000
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
        "payment_method_id" => $payment_method->id,
    ]);

    $response->assertStatus(200);
});
