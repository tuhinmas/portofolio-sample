<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PM2Installer;
use App\Http\Controllers\TestController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\ExporterController;
use App\Http\Controllers\AuthAPI\LoginController;
use App\Http\Controllers\AuthAPI\RegisterController;
use App\Http\Controllers\API\UserDeviceAPIController;
use App\Http\Controllers\authAPI\ForgotPasswordController;

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
    'prefix' => 'auth',
], function ($route) {
    Route::post('login', [LoginController::class, 'login'])->name('loginAPI');
    Route::post('logout', [LoginController::class, 'logout'])->name('logoutAPI');
    // Route::post('forgot-password', [ForgotPasswordController::class, 'send_email'])->name('forgot_password');
    // Route::post('update-password', [ForgotPasswordController::class, 'password_update'])->name('password.update');

    Route::get('/reset-password/{token}', function ($token) {
        return view('auth.passwords.reset', ['token' => $token]);
    })->middleware('guest')->name('password.reset');

    Route::post('register', [RegisterController::class, 'register'])->name('registerAPI');
    Route::get('role-list', [RegisterController::class, 'role_list'])->name('role_list');
    Route::get('personel-list', [RegisterController::class, 'personel_list'])->name('personel_list');
});

Route::post("delete-file-on-bucket", IndexController::class)->middleware("auth");

Route::group([
    "prefix" => "v1/export",
    "middleware" => ["auth", "role:super-admin"],
    ], function () {
        Route::get("sub-region", [ExporterController::class,'subRegionExport']);
});

Route::get("test-product",  [TestController::class, "index"])->middleware("auth");
Route::delete("test-db-raw/{id}", [TestController::class, "destroy"])->middleware("auth");

/*
|----------------------------------------
| ONE SIGNAL API
|------------------------
*/
Route::group([
    "prefix" => "v1/one-signal",
    "middleware" => ["auth"],
    ], function () {
        Route::post('user-device/register', [UserDeviceAPIController::class, 'registerDevice']);
        Route::get('user-device/{playerId}/update-status', [UserDeviceAPIController::class, 'updateNotificationStatus']);
});

/*
|----------------------------------------
| PM2 INSTALLER
|------------------------
*/
Route::group([
    "prefix" => "v1",
    "middleware" => ["auth"],
    ], function () {
        Route::post('pm2-installer', PM2Installer::class);
});

Route::view('/test', 'welcome');
