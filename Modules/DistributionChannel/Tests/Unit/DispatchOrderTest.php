<?php

use Modules\Invoice\Entities\Invoice;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DeliveryOrderNumber;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);
ini_set('max_execution_time', 6000); // Set max_execution_time to 60 seconds

/* create */
test("dispatch order, can create with valid data", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/dispatch-order",
        collect($dispatch_order)
            ->except([
                "id",
                "is_received",
                "is_has_delivery_order",
            ])
            ->toArray()
    );

    $response->assertStatus(201);
});

test("dispatch order, can not create with proforma was done", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    Invoice::query()
        ->where("id", $dispatch_order->invoice_id)
        ->update([
            "delivery_status" => "1",
        ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/dispatch-order",
        collect($dispatch_order)
            ->except([
                "id",
                "is_received",
                "is_has_delivery_order",
            ])
            ->toArray()
    );
    $response->assertStatus(422);
});

test("dispatch order, can not create with proforma was consider done", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    Invoice::query()
        ->where("id", $dispatch_order->invoice_id)
        ->update([
            "delivery_status" => "3",
        ]);

    $response = actingAsSupport()->postJson("/api/v1/distribution-channel/dispatch-order",
        collect($dispatch_order)
            ->except([
                "id",
                "is_received",
                "is_has_delivery_order",
            ])
            ->toArray()
    );

    $response->assertStatus(422);
});

/* update */
test("dispatch order, can update with valid data", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "invoice_id" => $dispatch_order->invoice_id,
        "is_active" => true,
    ]);

    $response->assertStatus(200);
});

test("dispatch order, can no update invoice_id already done", function () {
    $invoice = Invoice::factory()->create([
        "delivery_status" => "1",
    ]);
    $dispatch_order = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "invoice_id" => $dispatch_order->invoice_id,
        "is_active" => true,
    ]);

    $response->assertStatus(422);
});

test("dispatch order, can no update invoice_id already considered", function () {
    $invoice = Invoice::factory()->create([
        "delivery_status" => "1",
    ]);
    $dispatch_order = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
    ]);
    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "invoice_id" => $dispatch_order->invoice_id,
        "is_active" => true,
    ]);

    $response->assertStatus(422);
});

test("dispatch order, can no update and change invoice_id already done", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $invoice = Invoice::factory()->create([
        "delivery_status" => "1",
    ]);
    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    $response->assertStatus(422);
});

test("dispatch order, can no update and change invoice_id already considered", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    $invoice = Invoice::factory()->create([
        "delivery_status" => "1",
    ]);
    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    $response->assertStatus(422);
});

test("dispatch order, can not update if on pickup order", function () {
    $dispatch_order = DispatchOrder::factory()->create();
    PickupOrderDispatch::factory()->create([
        "dispatch_id" => $dispatch_order->id,
    ]);
    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "invoice_id" => $dispatch_order->invoice_id,
    ]);
    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toEqual("tidak bisa merubah dispatch, karena dispatch sudah dipickup");
});

/* deactivate dispatch order */
test("can not deactivate dispatch order if have active delivery order 1", function () {
    $sales_order = SalesOrder::factory()->create([
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
    ]);

    $sales_order_detail = SalesOrderDetail::factory([
        "sales_order_id" => $sales_order->id,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $dispatch_order = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "is_active" => false,
    ]);

    $response->assertStatus(422);
});

test("can not deactivate dispatch order if last deleivery order is send", function () {

    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $delivery_order_1 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now()->subDay(),
        "status" => "send",
    ]);

    $delivery_order_2 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now(),
        "status" => "send",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "is_active" => false,
    ]);

    $response->assertStatus(422);
});

test("can not deactivate dispatch order if already on pickup order", function () {
    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    PickupOrderDispatch::factory()->create([
        "dispatch_id" => $dispatch_order->id
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "is_active" => false,
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->is_active[0])->toEqual("Dispatch order tidak bisa dibatalkan karena sudah dipickup");
});

/* can deactivate */
test("can deactivate dispatch order if last delivery order is canceled", function () {
    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $delivery_order_2 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now(),
        "status" => "canceled",
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "is_active" => false,
    ]);

    $response->assertStatus(200);

});

test("can deactivate dispatch order if does not have delivery order at all", function () {
    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "is_active" => false,
    ]);

    $response->assertStatus(200);
});

/**
 * cat not update
 */
test("can not update dispatch order if has valid delivery order", function () {
    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "status" => "send"
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "is_active" => false,
    ]);

    $response->assertStatus(422);
});

test("can not update dispatch order if has receiving good", function () {
    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "status" => "canceled"
    ]);

    ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order->id,
        "delivery_status" => "2"
    ]);

    $response = actingAsSupport()->putJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id, [
        "is_active" => false,
    ]);

    $response->assertStatus(422);
});

/*
|------------------------------
| DELETE
|---------------------------
 */

/* can not delete */
test("can not delete dispatch order if have active delivery order 1", function () {
    $sales_order = SalesOrder::factory()->create([
        "model" => "1",
        "type" => "1",
        "status" => "confirmed",
    ]);

    $sales_order_detail = SalesOrderDetail::factory([
        "sales_order_id" => $sales_order->id,
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $dispatch_order = DispatchOrder::factory()->create([
        "invoice_id" => $invoice->id,
        "is_active" => true,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $dispatch_order->id,
    ]);

    $delivery_order = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id);

    $response->assertStatus(422);
});

test("can not delete dispatch order if last deleivery order is send", function () {

    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $delivery_order_1 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now()->subDay(),
        "status" => "canceled",
    ]);

    $delivery_order_2 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now(),
        "status" => "send",
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id);

    $response->assertStatus(422);
});

test("can not delete dispatch order if already received", function () {

    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $delivery_order_1 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now()->subDay(),
        "status" => "canceled",
    ]);

    ReceivingGood::factory()->create([
        "delivery_order_id" => $delivery_order_1->id,
        "delivery_status" => "2",
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id);

    $response->assertStatus(422);
});

test("can not delete dispatch order if already on pickup order", function () {
    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);
    PickupOrderDispatch::factory()->create([
        "dispatch_id" => $dispatch_order->id
    ]);
    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id);
    $response->assertStatus(422);
    expect($response->getData()->data->message[0])->toEqual("Dispatch order tidak bisa dibatalkan karena sudah dipickup");
});

/* can delete */
test("can delete dispatch order if last delivery order is canceled", function () {
    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $delivery_order_2 = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "date_delivery" => now(),
        "status" => "canceled",
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id);
    $response->assertStatus(200);
});

test("can delete dispatch order if does not have delivery order at all", function () {
    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id);
    $response->assertStatus(200);
});

test("can delete dispatch order, deleted delivery on deleted dispatch order", function () {
    $dispatch_order = DispatchOrder::factory()->create([
        "is_active" => true,
    ]);

    $delivery = DeliveryOrder::factory()->create([
        "dispatch_order_id" => $dispatch_order->id,
        "status" => "canceled",
    ]);

    DeliveryOrderNumber::factory()->create([
        "dispatch_order_id" => $delivery->dispatch_order_id,
        "dispatch_promotion_id" => $delivery->dispatch_promotion_id,
        "delivery_order_id" => $delivery->id,
        "delivery_order_number" => $delivery->delivery_order_number,
    ]);

    $response = actingAsSupport()->deleteJson("/api/v1/distribution-channel/dispatch-order/" . $dispatch_order->id);
    $response->assertStatus(200);

    $delivery_order_number = DeliveryOrderNumber::query()
        ->where("dispatch_order_id", $delivery->dispatch_order_id)
        ->first();

    expect($delivery_order_number)->toBeNull();
});
