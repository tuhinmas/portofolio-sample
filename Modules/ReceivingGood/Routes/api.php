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
    Orion::resource("receiving-good", "ReceivingGoodController")->withSoftDeletes();
    Route::get("receiving-good-detail-receiving", "ReceivingGoodController@recevingGoodDetailOnReceiving");
    Orion::resource("receiving-good-detail", "ReceivingGoodDetailController")->withSoftDeletes();
    Orion::resource("receiving-good-file", "ReceivingGoodFileController")->withSoftDeletes();
    Orion::resource("receiving-good-indirect-file", "ReceivingGoodFileIndirectSalesController")->withSoftDeletes();
});

Route::group([
    "prefix" => "v1",
    "middleware" => "auth",
], function () {
    Orion::resource("receiving-good-indirect", "ReceivingGoodIndirectSalesController")->withSoftDeletes();
    Orion::resource("receiving-good-detail-indirect", "ReceivingGoodDetailIndirectSaleController")->withSoftDeletes();
});

Route::group([
    "prefix" => "v1",
    "middleware" => "auth",
], function () {
    Route::get("considered-receiving-good-form-data", "ReceivingGoodConsideringFormDataController");
    Route::post("considered-receiving-good", "ReceivingGoodConsideringController");
});
