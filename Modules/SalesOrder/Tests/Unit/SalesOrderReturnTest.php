<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Distributor\Entities\DistributorContract;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);
ini_set('max_execution_time', 6000); // Set max_execution_time to 60 seconds

test("can return indirect order", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(2),
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "return" => now()->subDays(1),
        "returned_by" => $sales_order->personel_id,
    ]);

    $response->assertStatus(200);
});

test("can return indirect order, if nota date and retrun in the same distributor contract", function () {
    set_time_limit(600); // Set the max execution time to 600 seconds (10 minutes)
    $dealer = Dealer::factory()->create();
    $sub_dealer = SubDealer::factory()->create();

    $distributor_contract_1 = DistributorContract::factory()->create([
        "dealer_id" => $dealer->id,
        "contract_start" => now()->subMonths(5)->startOfDay(),
        "contract_end" => now()->subMonths(3)->endOfDay(),
    ]);

    $distributor_contract_2 = DistributorContract::factory()->create([
        "dealer_id" => $dealer->id,
        "contract_start" => now()->startOfMonth()->startOfDay(),
        "contract_end" => now()->endOfMonth()->endOfDay(),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $sub_dealer->id,
        "distributor_id" => $dealer->id,
        "model" => "2",
        "type" => "2",
        "date" => now()->subMonths(4),
        "status" => "confirmed",
    ]);

    $SalesOrderDetail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "quantity" => 10,
        "returned_quantity" => 5,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "return" => now()->subMonths(4),
        "returned_by" => $sales_order->personel_id,
    ]);

    $response->assertStatus(200);
});

test("can return indirect order using default return date", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(2),
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "returned_by" => $sales_order->personel_id,
    ]);

    expect($response->getData()->data->return)->toEqual($sales_order->date);
    $response->assertStatus(200);
});

test("can return direct order", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "status" => "confirmed",
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "created_at" => now()->subDays(2),
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "return" => now()->subDays(1),
        "returned_by" => $sales_order->personel_id,
        "total" => $sales_order->total - ($sales_order->total * 10 / 100)
    ]);

    $response->assertStatus(200);
    $total = $sales_order->total - ($sales_order->total * 10 / 100);
    $sales_order->refresh();
    expect($sales_order->total)->toEqual($total);
});

test("can return direct order using default return date", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "status" => "confirmed",
    ]);

    $invoice = Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "created_at" => now(),
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "returned_by" => $sales_order->personel_id,
    ]);

    expect(Carbon::parse($response->getData()->data->return)->format("Y-m-d hh:mm:ss"))->toEqual($invoice->created_at->format("Y-m-d hh:mm:ss"));
    $response->assertStatus(200);
});

/* can not return */
test("can not return indirect order, if return date less than nota date", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(1),
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "return" => now()->subDays(2),
        "returned_by" => $sales_order->personel_id,
    ]);

    $response->assertStatus(422);
});

test("can not return indirect order, if return date more than current date", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "2",
        "status" => "confirmed",
        "date" => now()->subDays(1),
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "return" => now()->addDays(2),
        "returned_by" => $sales_order->personel_id,
    ]);

    $response->assertStatus(422);
});

test("can not return direct order, if return date less than prforma date", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "status" => "confirmed",
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "created_at" => now()->subDays(1),
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "return" => now()->subDays(2),
        "returned_by" => $sales_order->personel_id,
    ]);

    $response->assertStatus(422);
});

test("can not return direct order, if return date more than current date", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => "1",
        "status" => "confirmed",
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $sales_order->id,
        "created_at" => now()->subDays(1),
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "return" => now()->addDays(2),
        "returned_by" => $sales_order->personel_id,
    ]);

    $response->assertStatus(422);
});

test("can not return indirect order, if nota date out off distributor contract", function () {
    $dealer = Dealer::factory()->create();
    $sub_dealer = SubDealer::factory()->create();

    $distributo_contract = DistributorContract::factory()->create([
        "dealer_id" => $dealer->id,
        "contract_start" => now()->startOfMonth()->startOfDay(),
        "contract_end" => now()->endOfMonth()->endOfDay(),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $sub_dealer->id,
        "distributor_id" => $dealer->id,
        "model" => "2",
        "type" => "2",
        "date" => now()->startOfYear(),
        "status" => "confirmed",
    ]);

    $SalesOrderDetail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "quantity" => 10,
        "returned_quantity" => 5,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "return" => now(),
        "returned_by" => $sales_order->personel_id,
    ]);

    $response->assertStatus(422);
});

test("can not return indirect order, if return date out off nota date distributor contract", function () {
    $dealer = Dealer::factory()->create();
    $sub_dealer = SubDealer::factory()->create();

    $distributor_contract_1 = DistributorContract::factory()->create([
        "dealer_id" => $dealer->id,
        "contract_start" => now()->subMonths(5)->startOfDay(),
        "contract_end" => now()->subMonths(3)->endOfDay(),
    ]);

    $distributor_contract_2 = DistributorContract::factory()->create([
        "dealer_id" => $dealer->id,
        "contract_start" => now()->startOfMonth()->startOfDay(),
        "contract_end" => now()->endOfMonth()->endOfDay(),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $sub_dealer->id,
        "distributor_id" => $dealer->id,
        "model" => "2",
        "type" => "2",
        "date" => now()->subMonths(4),
        "status" => "confirmed",
    ]);

    $SalesOrderDetail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "quantity" => 10,
        "returned_quantity" => 5,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "returned",
        "return" => now(),
        "returned_by" => $sales_order->personel_id,
    ]);

    $response->assertStatus(422);
});
