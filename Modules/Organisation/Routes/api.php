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
    'prefix' => 'v1/organisation',
    // 'middleware' => ['auth', 'role:administrator|super-admin|admin|Regional Marketing (RM)|marketing staff|Marketing Support|Regional Marketing Coordinator (RMC)'],
], function () {
    Route::resource('holding', 'HoldingController');
    Route::resource('holding-organisation', 'HoldingOrganisationController');
    Route::resource('organisation', 'OrganisationController');
    Route::get('all-organisation', 'OrganisationController@allOrganisation');
    Route::resource('organisation-business-sector', 'BussinessSectorController');
    Route::resource('organisation-contact', 'OrganisationContactController');
    Route::resource('organisation-address', 'OrganisationAddressController');
    Route::resource('organisation-category', 'OrganisationCategoryController');
});

Route::group([
    'prefix' => 'v1/organisation',
    'middleware' => ['auth'],
    ], function () {
        Route::resource('entity','EntityController');
});
