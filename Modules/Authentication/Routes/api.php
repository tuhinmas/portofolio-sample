<?php

use Illuminate\Support\Facades\Route;

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
    'prefix' => 'auth/v2',
], function ($route) {
    Route::post('login', 'LoginController@login')->name('login');
    Route::post('logout', 'LoginController@logout')->name('logout');
    Route::get('me', 'LoginController@me')->name('me')->middleware("auth");
    // Route::post('forgot-password', [ForgotPasswordController::class, 'send_email'])->name('forgot_password');
    // Route::post('update-password', [ForgotPasswordController::class, 'password_update'])->name('password.update');

    // Route::get('/reset-password/{token}', function ($token) {
    //     return view('auth.passwords.reset', ['token' => $token]);
    // })->middleware('guest')->name('password.reset');

    Route::post('register', 'RegisterController@register')->name('registerV2');
    Route::put('register-update/{use_id}', 'RegisterController@update');
    // Route::get('role-list', [RegisterController::class, 'role_list'])->name('role_list');
    // Route::get('personel-list', [RegisterController::class, 'personel_list'])->name('personel_list');
    Route::group([
        "middleware" => "auth",
    ], function ($route) {
        Route::post("login-track", "LoginTrackerV2Controller");
    });
});

Route::group([
    "prefix" => "auth/v1",
    "middleware" => "auth",
], function ($route) {
    Orion::resource("device", "DeviceController")->withSoftDeletes();
    Orion::resource("menu-handler", "MenuHandlerController")->withSoftDeletes();
    Orion::resource("menu-handler", "MenuHandlerController")->withSoftDeletes();
    Orion::resource("menu-sub-handler", "MenuSubHandlerController")->withSoftDeletes();
});

Route::group([
    "prefix" => "auth/v1",
], function () {
    Orion::resource("mobile-version", "MobileVersionController")->withSoftDeletes();
    Route::post("feature-permission", "FeaturePermissionController");
    Route::post("give-permission-to/{user_id}", "AssignPermisionController");
    Route::get("login-track", "LoginTrackerController");
    Orion::resource("user-access-history", "UserAccessHistoryController");
});

Route::group([
    "prefix" => "auth/v1",
], function () {
    Route::post("artisan", "ArtisanController");
});

Route::group([
    "prefix" => "auth/v3",
], function () {
    Route::post("login", "LoginV3Controller@login");
    Route::get("me", "LoginV3Controller@me")->middleware("auth");
});

Route::view('/test', 'welcome');
