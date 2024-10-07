<?php

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Queue;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Modules\Authentication\Entities\User;
use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Contest\Jobs\ContestPointCalculationByOrderJob;
use Modules\Invoice\Jobs\CalculateMarketingFeeOnPaymentJob;
use Modules\Invoice\Jobs\GenerateFeeTargetNomimalSharingJob;
use Modules\SalesOrderV2\Jobs\MarketingPointCalculationByOrderJob;
use Modules\SalesOrderV2\Jobs\CalculateMarketingFeeTargetByOrderJob;

uses(Tests\TestCase::class, DatabaseTransactions::class);

it("can create payment", function () {
    $faker = Faker::create('id_ID');

    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $user = User::where("name", "support")->first();

    $response = actingAsSupport()->postJson("/api/v1/payment", [
        "invoice_id" => $invoice->id,
        "payment_date" => now()->format("Y-m-d"),
        "nominal" => 100000,
        "remaining_payment" => 1000,
        "user_id" => $user->id,
        "reference_number" => $faker->word . "-" . $faker->numberBetween($min = 1000, $max = 9000),
    ]);

    $response->assertStatus(201);
});

test("can create payment as settle", function () {
    $faker = Faker::create('id_ID');

    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order,
    ]);

    $user = User::where("name", "support")->first();

    Bus::fake();

    $response = actingAsSupport()->postJson("/api/v1/payment", [
        "invoice_id" => $invoice->id,
        "payment_date" => now()->format("Y-m-d"),
        "nominal" => 100000,
        "remaining_payment" => 0,
        "user_id" => $user->id,
        "reference_number" => $faker->word . "-" . $faker->numberBetween($min = 1000, $max = 9000),
    ]);

    Bus::assertChained([
        CalculateMarketingFeeOnPaymentJob::class,
        GenerateFeeTargetNomimalSharingJob::class,
        CalculateMarketingFeeTargetByOrderJob::class,
        MarketingPointCalculationByOrderJob::class,
        ContestPointCalculationByOrderJob::class
    ]);

    $response->assertStatus(201);
});

/**
 * can not delete
 */
it("can not delete if from credit memo payment", function () {
    $faker = Faker::create('id_ID');

    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $payment = Payment::factory()->create([
        "invoice_id" => $invoice->id,
        "is_credit_memo" => true,
        "memo_status" => "accepted"
    ]);

    $user = User::where("name", "support")->first();

    $response = actingAsSupport()->deleteJson("/api/v1/payment/".$payment->id);
    $response->assertStatus(422);
});

it("can not delete if after that is credit memo and not zero value", function () {
    $faker = Faker::create('id_ID');

    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $payment_1 = Payment::factory()->create([
        "invoice_id" => $invoice->id,
        "is_credit_memo" => false,
        "nominal" => 1000,
        "created_at" => now()->subdays(2)
    ]);

    $payment_2 = Payment::factory()->create([
        "invoice_id" => $invoice->id,
        "is_credit_memo" => true,
        "memo_status" => "accepted",
        "nominal" => 1000,
        "created_at" => now()
    ]);

    $user = User::where("name", "support")->first();

    $response = actingAsSupport()->deleteJson("/api/v1/payment/".$payment_1->id);
    $response->assertStatus(422);
});

/**
 * can delete
 */
it("can delete if after that if credit memo and zero value", function () {
    $faker = Faker::create('id_ID');

    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $payment_1 = Payment::factory()->create([
        "invoice_id" => $invoice->id,
        "is_credit_memo" => false,
        "nominal" => 1000,
        "created_at" => now()->subdays(2)
    ]);

    $payment_2 = Payment::factory()->create([
        "invoice_id" => $invoice->id,
        "is_credit_memo" => true,
        "memo_status" => "accepted",
        "nominal" => 0,
        "created_at" => now()
    ]);

    $user = User::where("name", "support")->first();
    $response = actingAsSupport()->deleteJson("/api/v1/payment/".$payment_1->id);
    $response->assertStatus(200);
});
