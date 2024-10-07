<?php

use Illuminate\Http\Request;
// use Illuminate\Routing\Route;

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
    "prefix" => "v1/distribution-channel",
    'middleware' => [
        'auth',
        // 'role:administrator|super-admin|Regional Marketing (RM)|marketing staff|Marketing Support|Regional Marketing Coordinator (RMC)|Assistant MDM|Marketing District Manager (MDM)|Marketing Manager (MM)|Sales Counter (SC)|Operational Manager|Support Bagian Distributor|Support Distributor|Support Bagian Kegiatan|Support Kegiatan|Support Supervisor|User Jember|Distribution Channel (DC)'
    ],
], function () {
    Orion::resource('dispatch-order', 'DispatchOrderController')->withSoftDeletes();
    Orion::resource('dispatch-order-detail', 'DispatchOrderDetailController')->withSoftDeletes();
    Route::get('dispatch-order-detail-with-received-detail', 'DispatchOrderDetailController@dispatchOrderDetailWithQuantityReceived');
    Route::get('delivery-order-number-only', 'DispatchOrderController@deliveryOrderNumberOnly');
    Route::get('detail-dispatch-order/{id}', 'ListDispatchOrderController@dispatchOrder')->name('dispatchOrder');
    Orion::resource('delivery-order', 'DeliveryOrderController')->withSoftDeletes();
    Orion::resource('delivery-order-file', 'DeliveryOrderFileController')->withSoftDeletes();
    Orion::resource('dispatch-order-file', 'DispatchOrderFileController')->withSoftDeletes();
    Route::get('delivery-time', 'DeliveryOrderController@deliveryTime')->name('deliverytime');
    Route::get('delivery-driver-type-groupby-month-days', 'DeliveryOrderController@deliveryDriver')->name('deliverytime');
    Route::get('delivery-driver-type', 'DeliveryOrderController@deliveryTypeDriver')->name('deliverytime');
});

Route::group(["prefix" => "v2", "middleware" => "auth"], function(){
    Route::group(["prefix" => "dispatch",], function(){
        Route::get('detail/{dispatch_id}', 'DispatchV2Controller@detailDispatch');
        Route::put('update-dispatch/{dispatch_id}', 'DispatchV2Controller@updateDispatch');
        Route::get('shipping-list', 'DispatchV2Controller@shippingList');
        Route::get('shipping-list-dispatch', 'DispatchV2Controller@shippingListDispatch');
        Route::get('detail/{dispatch_id}', 'DispatchV2Controller@detailDispatch');
        Route::get('check-dispatch-edit/{dispatch_id}', 'DispatchV2Controller@checkEditDispatch');
    });    

    Route::get('delivery-order/list', 'V2\DeliveryOrderController');
});
