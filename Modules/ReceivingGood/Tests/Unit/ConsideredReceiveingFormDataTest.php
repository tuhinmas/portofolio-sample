<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\Product;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("considered receiving: form data", function () {
    $delivery_order = DeliveryOrder::factory()->create();

    $invoice = DB::table('invoices as i')
        ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
        ->join("delivery_orders as deo", "deo.dispatch_order_id", "dis.id")
        ->where("deo.id", $delivery_order->id)
        ->select("i.*", "dis.id as dispatch_order_id")
        ->first();

    $product_1 = Product::factory()->create([
        "name" => "produk test 1",
    ]);
    $product_2 = Product::factory()->create([
        "name" => "produk test 2",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $invoice->sales_order_id,
        "product_id" => $product_1->id,
    ]);

    SalesOrderDetail::factory()->create([
        "product_id" => $product_2->id,
        "sales_order_id" => $invoice->sales_order_id,
    ]);

    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $invoice->dispatch_order_id,
        "id_product" => $product_1->id,
    ]);
    DispatchOrderDetail::factory()->create([
        "id_dispatch_order" => $invoice->dispatch_order_id,
        "id_product" => $product_2->id,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v1/considered-receiving-good-form-data", [
        "delivery_order_id" => $delivery_order->id,
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data[0])->toHaveKeys([
        "product_id",
        "product_name",
        "product_unit",
        "product_size",
        "product_type",
        "sent_unit_quantity",
        "sent_package_quantity",
        "sent_package_name",
    ]);

    expect($response->getData()->data)->toHaveCount(2);
});

test("considered receiving: form data dispatch promotion", function () {
    $delivery_order = DeliveryOrder::factory()->dispatchPromotion()->create();

    $response = actingAsSupport()->json("GET", "/api/v1/considered-receiving-good-form-data", [
        "delivery_order_id" => $delivery_order->id,
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data[0])->toHaveKeys([
        "product_id",
        "product_name",
        "product_unit",
        "product_size",
        "product_type",
        "sent_unit_quantity",
        "sent_package_quantity",
        "sent_package_name",
        "promotion_good_id",
    ]);
    expect($response->getData()->data[0]->product_id)->not->toBeNull();
    expect($response->getData()->data)->toHaveCount(1);
});

test("considered receiving: form data ispatch promotion non product", function () {
    $delivery_order = DeliveryOrder::factory()->dispatchPromotionNonProduct()->create();
    $response = actingAsSupport()->json("GET", "/api/v1/considered-receiving-good-form-data", [
        "delivery_order_id" => $delivery_order->id,
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data[0])->toHaveKeys([
        "product_id",
        "product_name",
        "product_unit",
        "product_size",
        "product_type",
        "sent_unit_quantity",
        "sent_package_quantity",
        "sent_package_name",
        "promotion_good_id",
    ]);

    expect($response->getData()->data[0]->product_id)->toBeNull();

    expect($response->getData()->data)->toHaveCount(1);
});
