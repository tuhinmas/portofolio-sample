<?php

use App\Traits\DistributorStock;
use Illuminate\Http\UploadedFile;
use Modules\DataAcuan\Entities\Price;
use Illuminate\Support\Facades\Storage;
use Modules\DataAcuan\Entities\Product;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\Distributor\Entities\DistributorContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class, DistributorStock::class);
ini_set('max_execution_time', 6000); // Set max_execution_time to 60 seconds

test("can create indirect order", function () {
    $dealer = Dealer::factory()->create();
    $distributor = Dealer::factory()->create();
    DistributorContract::factory()->create([
        "dealer_id" => $distributor->id,
    ]);

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "distributor_id" => $distributor->id,
        "type" => 2,
        "model" => 1,
        "status" => "draft",
    ]);

    $product = Product::factory()->create();
    $price = Price::factory()->create([
        "product_id" => $product->id,
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

    $response = actingAsSupport()->getJson("/api/v2/sales-order/sales-order/" . $response->getData()->data->id);
    $response->assertStatus(200);
});

test("can create indirect order, from null marketing on dealer", function () {
    $dealer = Dealer::factory()->create();
    $distributor = Dealer::factory()->create();
    DistributorContract::factory()->create([
        "dealer_id" => $distributor->id,
    ]);

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order", [
        "store_id" => $dealer->id,
        "distributor_id" => $distributor->id,
        "type" => 2,
        "model" => 1,
        "status" => "draft",
    ]);

    $product = Product::factory()->create();
    $price = Price::factory()->create([
        "product_id" => $product->id,
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

    $response = actingAsSupport()->getJson("/api/v2/sales-order/sales-order/" . $response->getData()->data->id);
    $response->assertStatus(200);
});

test("can show indirect order", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->getJson("/api/v2/sales-order/sales-order/" . $sales_order->id);
    $response->assertStatus(200);
});

test("can change status indirect order to reviewed", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "reviewed",
    ]);

    $response->assertStatus(200);
});

test("can change status indirect order to submited", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "submited",
    ]);

    $response->assertStatus(200);
});

test("can change status indirect order to confirmed", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "quantity" => 0,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "confirmed",
    ]);

    $response->assertStatus(200);
});

test("can update batch order item, batch update", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->patchJson("/api/v2/sales-order/sales-order-detail/batch", [
        "resources" => [
            $sales_order_detail->id => [
                "sales_order_id" => $sales_order_detail->sales_order_id,
                "product_id" => $sales_order_detail->product_id,
                "quantity" => 30,
                "only_unit" => false,
            ],
        ],
    ]);

    $response->assertStatus(200);
});

test("can update order item, single update", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order-detail/" . $sales_order_detail->id, [
        "sales_order_id" => $sales_order_detail->sales_order_id,
        "product_id" => $sales_order_detail->product_id,
        "quantity" => 30,
        "only_unit" => false,
    ]);

    $response->assertStatus(200);
});

test("maximum returned quantity validation, batch update", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->patchJson("/api/v2/sales-order/sales-order-detail/batch", [
        "resources" => [
            $sales_order_detail->id => [
                "product_id" => $sales_order_detail->product_id,
                "returned_quantity" => 300,
                "only_unit" => false,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("maximum returned quantity validation, single update", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order-detail/" . $sales_order_detail->id, [
        "product_id" => $sales_order_detail->product_id,
        "returned_quantity" => 300,
        "only_unit" => false,
    ]);

    $response->assertStatus(422);
});

test("reject duplicate product indirect sale order in single store", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    $sales_order_detail = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order-detail", [
        "sales_order_id" => $sales_order->id,
        "product_id" => $sales_order_detail->product_id,
        "quantity" => 300,
        "only_unit" => false,
    ]);

    $response->assertStatus(422);
});

test("reject duplicate product indirect sale order in batch store", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    $product = Product::factory()->create();
    $price = Price::factory()->create([
        "product_id" => $product->id,
    ]);
    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order-detail/batch", [
        "resources" => [
            [
                "sales_order_id" => $sales_order->id,
                "product_id" => $product->id,
                "quantity" => 300,
                "only_unit" => false,
            ],
            [
                "sales_order_id" => $sales_order->id,
                "product_id" => $product->id,
                "quantity" => 300,
                "only_unit" => false,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test("reject duplicate product indirect sale order in single update", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
    ]);

    $product = Product::factory()->create();
    $price = Price::factory()->create([
        "product_id" => $product->id,
    ]);

    $first_product = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product->id,
    ]);

    $product_2 = Product::factory()->create();

    $second_product = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product_2->id,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order-detail/" . $first_product->id, [
        "sales_order_id" => $sales_order->id,
        "product_id" => $second_product->product_id,
        "quantity" => 300,
        "only_unit" => false,
    ]);

    $response->assertStatus(422);
});

test("can not proof indirect if stock is insufficient", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
        "status" => "submited",
    ]);

    $product = Product::factory()->create();
    $price = Price::factory()->create([
        "product_id" => $product->id,
    ]);

    /* check current stock distributor */
    $current_stock = $this->distributorProductCurrentStockAdjusmentBased($sales_order->distributor_id, $product->id);

    $product = SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
        "product_id" => $product->id,
        "quantity" => $current_stock->current_stock + 100,
    ]);

    $response = actingAsSupport()->putJson("/api/v2/sales-order/sales-order/" . $sales_order->id, [
        "status" => "confirmed",
    ]);

    $response->assertStatus(422);

});

/* check */
test("upload nota: jpg", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);
    Storage::fake('s3');
    $file = UploadedFile::fake()->image('nota.jpg');

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order/" . $sales_order->id . "?_method=PUT", [
        "date" => now(),
        "reference_number" => "123456",
        "status" => "reviewed",
        "file" => UploadedFile::fake()->image('nota.jpg'),
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->link)->not->toBeNull();
});

/* check */
test("upload nota: jpeg", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);
    Storage::fake('s3');

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order/" . $sales_order->id . "?_method=PUT", [
        "date" => now(),
        "reference_number" => "123456",
        "status" => "reviewed",
        "file" => UploadedFile::fake()->image('nota.jpeg'),
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->link)->not->toBeNull();
});

/* check */
test("upload nota: png", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);
    Storage::fake('s3');

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order/" . $sales_order->id . "?_method=PUT", [
        "date" => now(),
        "reference_number" => "123456",
        "status" => "reviewed",
        "file" => UploadedFile::fake()->image('nota.png'),
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->link)->not->toBeNull();
});

test("upload nota: pdf", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);
    Storage::fake('s3');

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order/" . $sales_order->id . "?_method=PUT", [
        "date" => now(),
        "reference_number" => "123456",
        "status" => "reviewed",
        "file" => UploadedFile::fake()->image('nota.pdf'),
    ]);

    $response->assertStatus(422);
});

test("upload nota: heic", function () {
    $sales_order = SalesOrder::factory()->create([
        "type" => 2,
        "status" => "draft",
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $sales_order->id,
    ]);
    Storage::fake('s3');

    $response = actingAsSupport()->postJson("/api/v2/sales-order/sales-order/" . $sales_order->id . "?_method=PUT", [
        "date" => now(),
        "reference_number" => "123456",
        "status" => "reviewed",
        "file" => UploadedFile::fake()->image('nota.heic'),
    ]);

    $response->assertStatus(422);
});
