<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\ProductMandatoryPivot;
use Modules\Personel\Entities\PersonelStatusHistory;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("forecast achievement, default", function () {
    /* district 1 */
    $district_1 = MarketingAreaDistrict::factory()->marketingRM()->create();

    /* district 2 */
    $district_2 = MarketingAreaDistrict::factory()->marketingRMC()->create();

    $product_1 = ProductMandatoryPivot::factory()->create();
    $product_2 = ProductMandatoryPivot::factory()->create();

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_1->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_1->product_id,
        "quantity" => 100,
    ]);

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_2->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_2->product_id,
        "quantity" => 50,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v2/personnel/mandatory-product-achievement", [
        "sort" => [
            "field" => "marketing",
            "direction" => "asc",
        ],
        "marketing_id" => [
            $district_1->personel_id,
            $district_2->personel_id,
        ],
    ]);
    $response->assertStatus(200);
    $product_group_1 = DB::table('product_mandatories')
        ->where("id", $product_1->product_mandatory_id)
        ->first();

    $product_group_2 = DB::table('product_mandatories')
        ->where("id", $product_2->product_mandatory_id)
        ->first();

    expect($response->getData()->data->data[0])->toHaveKeys([
        "marketing_id",
        "marketing",
        "position",
        "sales_year",
        "group_rmc",
        "data",
    ]);

    expect($response->getData()->data->data[0]->data[0])->toHaveKeys([
        "period",
        "volume",
        "metric_unit",
        "product_group_id",
        "target_marketing",
        "mandatory_product",
        "persentage_marketing",
    ]);

    $marketing_1 = collect($response->getData()->data->data)->filter(fn($marketing) => $marketing->marketing_id == $district_1->personel_id)->first();
    $marketing_2 = collect($response->getData()->data->data)->filter(fn($marketing) => $marketing->marketing_id == $district_2->personel_id)->first();

    expect(collect($marketing_1->data)->filter(fn($achievement) => $achievement->product_group_id == $product_group_1->product_group_id)->first()->volume)->toEqual(100);
    expect(collect($marketing_2->data)->filter(fn($achievement) => $achievement->product_group_id == $product_group_2->product_group_id)->first()->volume)->toEqual(50);
    expect($response->getData()->data->data[0]->data)->toHaveCount(2);
    expect(collect($response->getData()->data->data)->filter(fn($personel) => $personel->marketing_id == $district_1->personel_id)->count())->toEqual(1);
    expect(collect($response->getData()->data->data)->filter(fn($personel) => $personel->marketing_id == $district_2->personel_id)->count())->toEqual(1);
});

test("forecast achievement, filter region", function () {
    /* district 1 */
    $district_1 = MarketingAreaDistrict::factory()->marketingRM()->create();

    /* district 2 */
    $district_2 = MarketingAreaDistrict::factory()->marketingRMC()->create();

    $product_1 = ProductMandatoryPivot::factory()->create();
    $product_2 = ProductMandatoryPivot::factory()->create();

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_1->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_1->product_id,
        "quantity" => 100,
    ]);

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_2->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_2->product_id,
        "quantity" => 50,
    ]);

    $sub_region = DB::table('marketing_area_sub_regions as ms')
        ->where("ms.id", $district_1->sub_region_id)
        ->first();

    $response = actingAsSupport()->json("GET", "/api/v2/personnel/mandatory-product-achievement", [
        "sort" => [
            "field" => "marketing",
            "direction" => "asc",
        ],
        "marketing_id" => [
            $district_1->personel_id,
            $district_2->personel_id,
        ],
        "region_id" => [
            $sub_region->region_id,
        ],
    ]);
    $response->assertStatus(200);
    $product_group_1 = DB::table('product_mandatories')
        ->where("id", $product_1->product_mandatory_id)
        ->first();

    $product_group_2 = DB::table('product_mandatories')
        ->where("id", $product_2->product_mandatory_id)
        ->first();

    expect($response->getData()->data->data[0])->toHaveKeys([
        "marketing_id",
        "marketing",
        "position",
        "sales_year",
        "group_rmc",
        "data",
    ]);

    expect($response->getData()->data->data[0]->data[0])->toHaveKeys([
        "period",
        "volume",
        "metric_unit",
        "product_group_id",
        "target_marketing",
        "mandatory_product",
        "persentage_marketing",
    ]);

    $marketing_1 = collect($response->getData()->data->data)->filter(fn($marketing) => $marketing->marketing_id == $district_1->personel_id)->first();

    expect(collect($marketing_1->data)->filter(fn($achievement) => $achievement->product_group_id == $product_group_1->product_group_id)->first()->volume)->toEqual(100);
    expect(collect($marketing_1->data)->filter(fn($achievement) => $achievement->product_group_id == $product_group_2->product_group_id)->first()->volume)->toEqual(0);
    expect($response->getData()->data->data[0]->data)->toHaveCount(2);
    expect($response->getData()->data->data)->toHaveCount(1);
    expect(collect($response->getData()->data->data)->filter(fn($personel) => $personel->marketing_id == $district_1->personel_id)->count())->toEqual(1);
});

test("forecast achievement, filter sub region", function () {
    /* district 1 */
    $district_1 = MarketingAreaDistrict::factory()->marketingRM()->create();

    /* district 2 */
    $district_2 = MarketingAreaDistrict::factory()->marketingRMC()->create();

    $product_1 = ProductMandatoryPivot::factory()->create();
    $product_2 = ProductMandatoryPivot::factory()->create();

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_1->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_1->product_id,
        "quantity" => 100,
    ]);

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_2->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_2->product_id,
        "quantity" => 50,
    ]);

    $sub_region = DB::table('marketing_area_sub_regions as ms')
        ->where("ms.id", $district_2->sub_region_id)
        ->first();

    $response = actingAsSupport()->json("GET", "/api/v2/personnel/mandatory-product-achievement", [
        "sort" => [
            "field" => "marketing",
            "direction" => "asc",
        ],
        "marketing_id" => [
            $district_1->personel_id,
            $district_2->personel_id,
        ],
        "region_id" => [
            $sub_region->region_id,
        ],
    ]);
    $response->assertStatus(200);
    $product_group_1 = DB::table('product_mandatories')
        ->where("id", $product_1->product_mandatory_id)
        ->first();

    $product_group_2 = DB::table('product_mandatories')
        ->where("id", $product_2->product_mandatory_id)
        ->first();

    expect($response->getData()->data->data[0])->toHaveKeys([
        "marketing_id",
        "marketing",
        "position",
        "sales_year",
        "group_rmc",
        "data",
    ]);

    expect($response->getData()->data->data[0]->data[0])->toHaveKeys([
        "period",
        "volume",
        "metric_unit",
        "product_group_id",
        "target_marketing",
        "mandatory_product",
        "persentage_marketing",
    ]);

    $marketing_1 = collect($response->getData()->data->data)->filter(fn($marketing) => $marketing->marketing_id == $district_2->personel_id)->first();

    expect(collect($marketing_1->data)->filter(fn($achievement) => $achievement->product_group_id == $product_group_1->product_group_id)->first()->volume)->toEqual(0);
    expect(collect($marketing_1->data)->filter(fn($achievement) => $achievement->product_group_id == $product_group_2->product_group_id)->first()->volume)->toEqual(50);
    expect($response->getData()->data->data[0]->data)->toHaveCount(2);
    expect($response->getData()->data->data)->toHaveCount(1);
    expect(collect($response->getData()->data->data)->filter(fn($personel) => $personel->marketing_id == $district_2->personel_id)->count())->toEqual(1);
});

test("forecast achievement, filter product_group", function () {
    /* district 1 */
    $district_1 = MarketingAreaDistrict::factory()->marketingRM()->create();

    /* district 2 */
    $district_2 = MarketingAreaDistrict::factory()->marketingRMC()->create();

    $product_1 = ProductMandatoryPivot::factory()->create();
    $product_2 = ProductMandatoryPivot::factory()->create();

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_1->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_1->product_id,
        "quantity" => 100,
    ]);

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_2->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_2->product_id,
        "quantity" => 50,
    ]);

    $sub_region = DB::table('marketing_area_sub_regions as ms')
        ->where("ms.id", $district_2->sub_region_id)
        ->first();

    $product_group_1 = DB::table('product_mandatories')
        ->where("id", $product_1->product_mandatory_id)
        ->first();

    $product_group_2 = DB::table('product_mandatories')
        ->where("id", $product_2->product_mandatory_id)
        ->first();

    $response = actingAsSupport()->json("GET", "/api/v2/personnel/mandatory-product-achievement", [
        "sort" => [
            "field" => "marketing",
            "direction" => "asc",
        ],
        "marketing_id" => [
            $district_1->personel_id,
            $district_2->personel_id,
        ],
        "region_id" => [
            $sub_region->region_id,
        ],
        "product_group_id" => [
            $product_group_2->product_group_id,
        ],
    ]);
    $response->assertStatus(200);

    expect($response->getData()->data->data[0])->toHaveKeys([
        "marketing_id",
        "marketing",
        "position",
        "sales_year",
        "group_rmc",
        "data",
    ]);

    expect($response->getData()->data->data[0]->data[0])->toHaveKeys([
        "period",
        "volume",
        "metric_unit",
        "product_group_id",
        "target_marketing",
        "mandatory_product",
        "persentage_marketing",
    ]);

    $marketing_1 = collect($response->getData()->data->data)->filter(fn($marketing) => $marketing->marketing_id == $district_2->personel_id)->first();

    expect(collect($marketing_1->data)->filter(fn($achievement) => $achievement->product_group_id == $product_group_1->product_group_id)->count())->toEqual(0);
    expect(collect($marketing_1->data)->filter(fn($achievement) => $achievement->product_group_id == $product_group_2->product_group_id)->first()->volume)->toEqual(50);
    expect($response->getData()->data->data[0]->data)->toHaveCount(1);
    expect($response->getData()->data->data)->toHaveCount(1);
    expect(collect($response->getData()->data->data)->filter(fn($personel) => $personel->marketing_id == $district_2->personel_id)->count())->toEqual(1);
});

test("forecast achievement, marketing has active in this year", function () {
    /* district 1 */
    $district_1 = MarketingAreaDistrict::factory()->marketingRM()->create();

    /* district 2 */
    $district_2 = MarketingAreaDistrict::factory()->marketingRMC()->create();

    $product_1 = ProductMandatoryPivot::factory()->create();
    $product_2 = ProductMandatoryPivot::factory()->create();

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_1->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_1->product_id,
        "quantity" => 100,
    ]);

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_2->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_2->product_id,
        "quantity" => 50,
    ]);

    $sub_region = DB::table('marketing_area_sub_regions as ms')
        ->where("ms.id", $district_2->sub_region_id)
        ->first();

    $product_group_1 = DB::table('product_mandatories')
        ->where("id", $product_1->product_mandatory_id)
        ->first();

    $product_group_2 = DB::table('product_mandatories')
        ->where("id", $product_2->product_mandatory_id)
        ->first();

    PersonelStatusHistory::factory()->create([
        "personel_id" => $district_1->personel_id,
        "start_date" => now()->startOfYear()->startOfDay(),
        "end_date" =>  now()->startOfYear()->addDays(30),
    ]);
    $response = actingAsSupport()->json("GET", "/api/v2/personnel/mandatory-product-achievement", [
        "sort" => [
            "field" => "marketing",
            "direction" => "asc",
        ],
        "marketing_id" => [
            $district_1->personel_id,
            $district_2->personel_id,
        ],
        "region_id" => [
            $sub_region->region_id,
        ],
        "product_group_id" => [
            $product_group_2->product_group_id,
        ],
    ]);
    $response->assertStatus(200);

    expect($response->getData()->data->data[0])->toHaveKeys([
        "marketing_id",
        "marketing",
        "position",
        "sales_year",
        "group_rmc",
        "data",
    ]);

    expect($response->getData()->data->data[0]->data[0])->toHaveKeys([
        "period",
        "volume",
        "metric_unit",
        "product_group_id",
        "target_marketing",
        "mandatory_product",
        "persentage_marketing",
    ]);

    $marketing_1 = collect($response->getData()->data->data)->filter(fn($marketing) => $marketing->marketing_id == $district_2->personel_id)->first();

    expect(collect($marketing_1->data)->filter(fn($achievement) => $achievement->product_group_id == $product_group_1->product_group_id)->count())->toEqual(0);
    expect(collect($marketing_1->data)->filter(fn($achievement) => $achievement->product_group_id == $product_group_2->product_group_id)->first()->volume)->toEqual(50);
    expect($response->getData()->data->data[0]->data)->toHaveCount(1);
    expect($response->getData()->data->data)->toHaveCount(1);
    expect(collect($response->getData()->data->data)->filter(fn($personel) => $personel->marketing_id == $district_2->personel_id)->count())->toEqual(1);
});

test("forecast achievement, marketing doesn't active in this year but active in previous year", function () {
    /* district 1 */
    $district_1 = MarketingAreaDistrict::factory()->marketingRM()->create();

    /* district 2 */
    $district_2 = MarketingAreaDistrict::factory()->marketingRMC()->create();

    $product_1 = ProductMandatoryPivot::factory()->create();
    $product_2 = ProductMandatoryPivot::factory()->create();

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_1->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_1->product_id,
        "quantity" => 100,
    ]);

    $order = SalesOrder::factory()->create([
        "type" => "2",
        "date" => now(),
        "status" => "confirmed",
        "personel_id" => $district_2->personel_id,
    ]);

    SalesOrderDetail::factory()->create([
        "sales_order_id" => $order->id,
        "product_id" => $product_2->product_id,
        "quantity" => 50,
    ]);

    $sub_region = DB::table('marketing_area_sub_regions as ms')
        ->where("ms.id", $district_2->sub_region_id)
        ->first();

    $product_group_1 = DB::table('product_mandatories')
        ->where("id", $product_1->product_mandatory_id)
        ->first();

    $product_group_2 = DB::table('product_mandatories')
        ->where("id", $product_2->product_mandatory_id)
        ->first();

    PersonelStatusHistory::factory()->create([
        "personel_id" => $district_2->personel_id,
        "start_date" => now()->subYear()->startOfYear()->startOfDay(),
        "end_date" =>  now()->subYear()->startOfYear()->addDays(30),
    ]);
    $response = actingAsSupport()->json("GET", "/api/v2/personnel/mandatory-product-achievement", [
        "sort" => [
            "field" => "marketing",
            "direction" => "asc",
        ],
        "marketing_id" => [
            $district_2->personel_id,
        ],
        "region_id" => [
            $sub_region->region_id,
        ],
        "product_group_id" => [
            $product_group_2->product_group_id,
        ],
    ]);

    $response->assertStatus(200);
    expect(collect($response->getData()->data->data)->count())->toEqual(0);
});
