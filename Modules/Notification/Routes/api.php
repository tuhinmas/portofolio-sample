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
    'middleware' => ['auth'],
], function () {
    Route::group([
        'prefix' => 'v1/notification',
    ], function () {
        Route::get('read', 'NotificationController@index')->name('notification_read');
        Route::get('show/{id}', 'NotificationController@show')->name('notification_show');
        Route::get('read-marketing', 'NotificationController@readMarketing')->name('notification_read_marketing');
        Orion::resource('notification-group', 'NotificationGroupController');
        Orion::resource("notification-group-detail", "NotificationGroupDetailController");
        Orion::resource('notification-marketing-read', 'NotificationOrionController');
        Orion::resource('notification-marketing-detail', 'NotificationOrionDetailController');
        Route::get('notification-marketing-count', 'NotificationOrionController@countMarketingNotif');
        Route::get('notification-task-count', 'TaskListCounterController');
        
        Route::get('marketing-child-notif', 'NotificationController@personelChildNotif');
    });
});
