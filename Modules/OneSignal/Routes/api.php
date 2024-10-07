<?php

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
    "prefix" => "v1/one-signal",
], function () {
    Route::post("push-notification", "PushNotificationController");
});


Route::group([
    "middleware" => "auth",
    "prefix" => "v1/one-signal/test/",
], function () {
    Route::get("push", "PushNotificationController@testPush");
});
