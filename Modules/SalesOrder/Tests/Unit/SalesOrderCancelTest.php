<?php

use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\Invoice;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Invoice\Entities\InvoiceProforma;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\SalesOrderV2\Entities\SalesOrderHistoryChangeStatus;

uses(Tests\TestCase::class, DatabaseTransactions::class);
ini_set('max_execution_time', 6000); // Set max_execution_time to 60 seconds

test("can not cancel direct order if there invoice proforma", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $proforma = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $invoice = InvoiceProforma::factory()->create([
        "invoice_id" => $proforma->id,
    ]);

    $response = actingAsSupport("api")->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "canceled",
    ]);

    $response->assertStatus(422);
});

test("can not cancel direct order if there active dispatch order", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $proforma = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $dispatch = DispatchOrder::factory()->create([
        "invoice_id" => $proforma->id,
        "is_active" => true
    ]);

    $response = actingAsSupport("api")->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "canceled",
    ]);

    $response->assertStatus(422);
});

test("can not cancel direct order if there delivery order that date delivery less or equal today", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $proforma = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $dispatch = DispatchOrder::factory()->create([
        "invoice_id" => $proforma->id,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch->id,
        "date_delivery" => now()->format("Y-m-d"),
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "canceled",
    ]);

    $response->assertStatus(422);
});

test("can not cancel direct order if there receiving good", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $proforma = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $dispatch = DispatchOrder::factory()->create([
        "invoice_id" => $proforma->id,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch->id,
        "date_delivery" => now()->addDays(3)->format("Y-m-d"),
    ]);

    ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "canceled",
    ]);
    $response->assertStatus(422);
});

test("can cancel direct order if there no invoice proforma or active dispatch order", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $proforma = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $dispatch = DispatchOrder::factory()->create([
        "invoice_id" => $proforma->id,
        "is_active" => false
    ]);

    $response = actingAsSupport("api")->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "canceled",
    ]);

    $response->assertStatus(200);
});

test("can cancel direct order if there has no receiving good or invpoce proforma or delivery has not sent, V2", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $proforma = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "canceled",
    ]);

    $status_history = DB::table('sales_order_history_change_statuses')
        ->where("sales_order_id", $sales_order->id)
        ->where("status", "canceled")
        ->first();

    expect($status_history)->toBeTruthy();
    $response->assertStatus(200);
    expect($status_history->status)->toEqual("canceled");
    expect($response->getData()->data->status)->toEqual("canceled");
});

test("can cancel direct order if there has no receiving good or invoce proforma or delivery has not sent, V1", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)

    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "confirmed",
    ]);

    $proforma = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "canceled",
    ]);

    $status_history = SalesOrderHistoryChangeStatus::query()
        ->where("sales_order_id", $sales_order->id)
        ->where("status", "canceled")
        ->first();

    $response->assertStatus(200);
    expect($status_history)->toBeTruthy();
    expect($status_history->status)->toEqual("canceled");
});
