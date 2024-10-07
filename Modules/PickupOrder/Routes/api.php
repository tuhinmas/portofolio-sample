<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::group([
    "prefix" => "v1",
    "middleware" => "auth",
], function () {
    Route::group([
        "prefix" => "pickup-order",
    ], function () {
        Orion::resource("pickup-order", "PickupOrderController");
        Route::get("detail-travel-document/{pickup_order_id}", "PickupOrderController@detailTravelDocument");
        Route::get("delivery-order", "PickupOrderController@deliveryOrderList");
        Orion::resource("delivery-pickup-order", "DeliveryPickupOrderController")->except(['batchStore']);
        Route::get("list-delivery-pickup-order", "DeliveryPickupOrderController@listDeliveryPickupOrder");
        Route::post("delivery-pickup-order/batch", "DeliveryPickupOrderController@batchStore");
        Orion::resource("pickup-order-detail", "PickupOrderDetailController");
        Orion::resource("pickup-order-detail-file", "PickupOrderDetailFileController");
        Orion::resource("pickup-order-file", "PickupOrderFileController");
        Route::get("{pickup_order_id}/detail-for-load", "PickupOrderByIdIncludeDispatchController");

        /* file uploader */
        Route::post("pickup-order-detail/{pickup_order_detail_id}/upload", "PickupOrderFileUploaderController@pickupDetailFileUpload");
        Route::post("pickup-order/{pickup_order_id}/upload", "PickupOrderFileUploaderController@pickupFileUpload");

        /* counter */
        Route::get("deliver-today-count", "PickupOrderCounterController");
        
        /* version */
        Orion::resource("mobile-version", "MobileWarehousingVersionController");
        
        /* print template */
        Route::get("{pickup_order_id}/print-template", "PickupPrintTemplateController");
    });

    Orion::resource("pickup-order-dispatch", "PickupOrderDispatchOrionController");

});

Route::group(["prefix" => "v2", "middleware" => "auth"], function () {
    Route::group(["prefix" => "pickup-order"], function () {
        Route::post("store", "PickupOrderV2Controller@store");
        Route::put("update/{pickup_order_id}", "PickupOrderV2Controller@update");
        Route::get("detail-dispatch-to-pickup", "PickupOrderV2Controller@detailDispatch");
        Route::get("detail/{pickup_order_id}", "PickupOrderV2Controller@detail");

        Route::group(["prefix" => "detail"], function () {
            Route::get("actual-check-load/{id_load}", "PickupOrderDetailController@actualCheckLoad");
            Route::post("store-actual-check-load/{pickup_order_id}", "PickupOrderDetailController@storeActualCheck");
        });

    });
    Route::group(["prefix" => "pickup-order-dispatch"], function () {
        Route::post("group-dispatch", "PickupOrderDispatchController@groupDispatch");
        Route::post("unload-dispatch/{pickup_order_dispatch_id}", "PickupOrderDispatchController@unloadDispatch");
    });
});
