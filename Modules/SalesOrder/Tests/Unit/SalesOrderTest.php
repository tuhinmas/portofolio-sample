<?php

use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Price;
use Modules\DataAcuan\Entities\Product;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\Distributor\Entities\DistributorContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can create draft direct order", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => "0",
    ]);

    $dealer = Dealer::factory()->create();
    $response = actingAsSupport()->postJson("/api/v1/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
        "payment_method_id" => $payment_method->id,
    ]);
    $product = Product::factory()->create();
    $price = Price::factory()->create([
        "product_id" => $product->id
    ]);

    $items_of_order = actingAsSupport()->postJson("/api/v2/sales-order/sales-order-detail", [
        "sales_order_id" => $response->getData()->data->id,
        "product_id" => $product->id,
        "quantity" => 100,
        "quantity_order" => 100,
        "unit_price" => $price->price,
        "total" => 100 * $price->price,
        "agency_level_id" => $price->agency_level_id,
        "only_unit" => true,
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->grading_id)->toEqual($dealer->grading_id);
});

test("can create draft direct order, from null marketing", function () {
    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => "0",
    ]);

    $dealer = Dealer::factory()->create([
        "personel_id" => null
    ]);

    $response = actingAsSupport()->postJson("/api/v1/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
        "payment_method_id" => $payment_method->id,
    ]);

    $product = Product::factory()->create();
    $price = Price::factory()->create([
        "product_id" => $product->id
    ]);

    $items_of_order = actingAsSupport()->postJson("/api/v2/sales-order/sales-order-detail", [
        "sales_order_id" => $response->getData()->data->id,
        "product_id" => $product->id,
        "quantity" => 100,
        "quantity_order" => 100,
        "unit_price" => $price->price,
        "total" => 100 * $price->price,
        "agency_level_id" => $price->agency_level_id,
        "only_unit" => true,
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->grading_id)->toEqual($dealer->grading_id);
});


test("can create draft direct order, v2", function () {

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => "0",
    ]);

    $dealer = Dealer::factory()->create();

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
        "payment_method_id" => $payment_method->id,
    ]);

    $product = Product::factory()->create();
    $price = Price::factory()->create([
        "product_id" => $product->id
    ]);

    $items_of_order = actingAsSupport()->postJson("/api/v2/sales-order/sales-order-detail", [
        "sales_order_id" => $response->getData()->data->id,
        "product_id" => $product->id,
        "quantity" => 100,
        "quantity_order" => 100,
        "unit_price" => $price->price,
        "total" => 100 * $price->price,
        "agency_level_id" => $price->agency_level_id,
        "only_unit" => true,
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data->grading_id)->toEqual($dealer->grading_id);
});

test("can create draft direct order, v2, from null marketing", function () {

    $payment_method = PaymentMethod::factory()->create([
        "is_for_marketing" => "0",
    ]);

    $dealer = Dealer::factory()->create([
        "personel_id" => null
    ]);

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "type" => 1,
        "model" => 1,
        "status" => "draft",
        "payment_method_id" => $payment_method->id,
    ]);

    $product = Product::factory()->create();
    $price = Price::factory()->create([
        "product_id" => $product->id
    ]);

    $items_of_order = actingAsSupport()->postJson("/api/v2/sales-order/sales-order-detail", [
        "sales_order_id" => $response->getData()->data->id,
        "product_id" => $product->id,
        "quantity" => 100,
        "quantity_order" => 100,
        "unit_price" => $price->price,
        "total" => 100 * $price->price,
        "agency_level_id" => $price->agency_level_id,
        "only_unit" => true,
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data->grading_id)->toEqual($dealer->grading_id);
});

test("can submit direct order", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
    ]);

    $response->assertStatus(200);
});

test("can confirm direct order", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v1/sales-order/sales-order/" . $sales_order->id, [
        "status" => "confirmed",
        // "latitude" => -7.454109250282195,
        // "longitude" => 110.43979715041247
    ]);

    $response->assertStatus(200);
});

test("can show direct order", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 1,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->getJson("/api/v2/sales-order/sales-order/" . $sales_order->id);
    $response->assertStatus(200);
});

/**
 * Direct can not
 */
test("can proof indirect sales from null marketing inside order", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
        "status" => "submited",
        "personel_id" => null,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "confirmed",
    ]);

    $status_history = DB::table('sales_order_history_change_statuses')
        ->where("sales_order_id", $sales_order->id)
        ->orderBy("created_at", "desc")
        ->get()
        ->pluck("status")
        ->toArray();

    $response->assertStatus(200);
    expect(in_array("confirmed", $status_history))->toBeTruthy();
});

test("direct sales, marketing change on store, V1", function () {

    $dealer = Dealer::factory()->create();

    $response = actingAsSupport()->postJson("/api/v1/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->toEqual($dealer->personel_id);
});

test("direct sales, marketing change on confirm, V1", function () {

    $dealer = Dealer::factory()->create();

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "personel_id" => $dealer->personel_id,
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->where("id", "!=", $dealer->personel_id)
        ->first();

    $dealer->personel_id = $personel->id;
    $dealer->save();

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "confirmed",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->toEqual($dealer->personel_id);
});

test("direct sales, marketing change on store, V2", function () {

    $dealer = Dealer::factory()->create();

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data->personel_id)->toEqual($dealer->personel_id);
});

test("direct sales, marketing change on confirm, V2", function () {

    $dealer = Dealer::factory()->create();

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
        "personel_id" => $dealer->personel_id,
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->where("id", "!=", $dealer->personel_id)
        ->first();

    $dealer->personel_id = $personel->id;
    $dealer->save();

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "confirmed",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->toEqual($dealer->personel_id);
});

test("indirect sales, marketing change on store, V2", function () {

    $dealer = Dealer::factory()->create();

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "1",
    ]);

    $response->assertStatus(201);
    expect($response->getData()->data->personel_id)->toEqual($dealer->personel_id);
});

test("indirect sales, marketing  should not change on confirm, V2", function () {

    $dealer = Dealer::factory()->create();

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "model" => "1",
        "type" => "2",
        "personel_id" => $dealer->personel_id,
    ]);

    $personel = Personel::query()
        ->whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })
        ->where("id", "!=", $dealer->personel_id)
        ->first();

    $dealer->personel_id = $personel->id;
    $dealer->save();

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "confirmed",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->personel_id)->toEqual($sales_order->personel_id);
});
