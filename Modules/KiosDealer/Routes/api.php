<?php

use Modules\KiosDealer\Http\Controllers\DealerFileController;

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
    'middleware' => [
        'auth',
        // 'role:administrator|super-admin|Regional Marketing (RM)|marketing staff|Marketing Support|Regional Marketing Coordinator (RMC)|Assistant MDM|Marketing District Manager (MDM)|Marketing Manager (MM)|Sales Counter (SC)|Operational Manager|Support Bagian Distributor|Support Distributor|Support Bagian Kegiatan|Support Kegiatan|Support Supervisor|User Jember|Distribution Channel (DC)'
    ],
], function () {
    Route::group([
        'prefix' => 'v1/store',
    ], function () {
        Route::resource('store', 'StoreController');
        Route::get('store-by-telephone', 'StoreController@checkExistingData');
        Route::put('store-update-dealerid/{id}', 'StoreController@updateDealerId');
        Route::resource('core-farmer', 'CoreFarmerController');
        Route::post('core-farmer-exist-check', 'CoreFarmerController@searchCoreFarmerByTelephone');
        Route::get('all-stores', 'StoreController@getAllStores');
        Orion::resource('store-temp', 'StoreTempController')->withoutBatch();
        Route::get('store-temp/duplication-telp-number/{id}', 'StoreTempController@dupicationNoTelp');
        Route::get('store-temp/duplication-telp-number-core-farmer/{id}', 'StoreTempController@dupicationNoTelpCoreFarmer');
        Route::resource('core-farmer-temp', 'CoreFarmerTempV2Controller');
        Route::get('core-farmer-temp-telp-number-check/{store_temp_id}', 'CoreFarmerTempV2Controller@checkCoreFarmerTempByTelephone');
        Route::resource('core-farmer-temp', 'CoreFarmerTempV2Controller');
        Route::post('core-farmer-temp/search', 'CoreFarmerTempV2Controller@index');
        Route::post('core-farmer-temp/batch', 'CoreFarmerTempV2Controller@store');
        Route::get('store-by-telephone', 'StoreController@checkExistingData');
        Route::get('check-request-of-change/{store_id}', "StoreController@checkRequestOfChange");

    });

    Route::group([
        'prefix' => 'v1/dealer',
    ], function () {
        Route::resource('dealer', 'DealerController');
        Route::get('dealer/{dealer_id}/detail', 'DealerController@detail');
        Route::post('dealer-exist-check', 'DealerController@inactiveCheck');
        Route::post('dealer-exist-dealer-sub-dealer-check', 'DealerController@existCheckDealerSubDealer');
        Route::post('dealer-exist-dealer-sub-dealer-check-other', 'DealerController@existCheckDealerSubDealerOther');
        Route::post('new-dealer-check-telp-number-exist', 'DealerController@existCheckNoTelpDealer');
        Route::post('new-sub-dealer-check-telp-number-exist', 'DealerController@existCheckNoTelpSubDealer');

        Route::post('dealer-temp-draft-exist', 'DealerController@existDraftDealerTemp');

        // existCheckDealerSubDealerOther
        Route::put('dealer-update-status/{id}', 'DealerController@updateDealerStatus');
        Route::put('dealer-update-agnecy-level/{id}', 'DealerController@updateDealerAgencyLevel');
        Route::put('dealer-blocked/{id}', 'DealerController@blockDealer');
        Route::put('sub-dealer-blocked/{id}', 'SubDealerController@blockSubDealer');
        Route::put('sub-dealer-closed/{id}', 'SubDealerController@closedSubDealer');
        Route::post('dealer-payment', 'DealerController@dealerPayment');
        Route::put('dealer-custom-credit-limit/{id}', 'DealerController@updateCustomCreditLimit');

        Route::resource('dealer-file', 'DealerFileController');
        Route::delete('delete-dealer-file-by-dealerid/{id}', 'DealerFileController@deleteDealerFileById');
        Route::get('all-dealers', 'DealerController@getAllDealers');
        Route::put('dealer-grading/{dealer}', 'DealerController@updateGrading');
        Orion::resource('dealer-temp', "DealerTempController")->withoutBatch();
        Route::post('dealer-temp-store', "DealerTempController@dealerTempStore");
        Orion::resource('dealer-file-temp', "DealerTempFileController");
        Orion::resource('dealer-temp-note', "DealerTempNoteController");
        Orion::resource('sub-dealer-temp-note', "SubDealerTempNoteController");
        Route::resource('dealer-file-temp-backup', "DealerTempFileTestController");
        Route::post('dealer-file-temp-backup/batch', "DealerTempFileTestController@store");

        Route::get('dealer-temp/duplication-telp-number/{id}', 'DealerTempController@dupicationNoTelp');

        Route::get('sub-dealer-temp/duplication-telp-number/{id}', 'SubDealerTempController@dupicationNoTelp');

        Orion::resource('sub-dealer', "SubDealerController");
        Route::get('sub-dealer/check-request-of-change/{sub_dealer_id}', "SubDealerController@checkRequestOfChange");
        Route::get('indirect-sale-history', "SubDealerController@indirectSaleHitory");
        Orion::resource('sub-dealer-inactive', "SubDealerInactiveController");
        Route::put('sub-dealer-update-status/{id}', "SubDealerController@updateStatus");
        Orion::resource('sub-dealer-temp', "SubDealerTempController")->withoutBatch();
        Route::get('sub-dealer-all', 'SubDealerController@allSubDealers');
        Orion::resource('dealer-has-grading', "DealerGradingController");

        Route::get('statistic-grading-dealer', 'DealerGradingController@gradingGrafikFilter');

        Route::get('statistic-grading-dealer-active-or-not-active', 'DealerGradingController@gradingGrafikFilterActiveNotActive');

        Route::get("dealer-sub-dealer-list", "DealerController@dealerSubDealer");
        Route::get("dealer-temp-count/{id}", "DealerTempController@countDealerTempNote");
        Route::get("distributor-sync-stock/{id}", "DealerController@syncStockDistributor");
        Route::post("dealer-change-history", "DealerChangeHistoryController@index");
        Route::get("dealer-change-history/{id}", "DealerChangeHistoryController@show");

        Route::post("sub-dealer-change-history", "SubDealerChangeHistoryController@index");
        Route::get("sub-dealer-change-history/{id}", "SubDealerChangeHistoryController@show");

    }
    );
}
);
Route::group([
    'prefix' => 'v1/dealer',
    'middleware' => [
        'auth',
    ],
], function () {
    Route::resource('dealer-confirmation', 'DealerConfirmationController');
    Orion::resource('dealer-delivery-address', 'DealerDeliveryAddressController');
    Route::post('dealer-delivery-address/import', 'DealerDeliveryAddressController@import');
});

Route::group([
    "prefix" => "v1",
    "middleware" => "auth",
], function () {
    Orion::resource("shop", "ShopController");
    Orion::resource("shop-simple", "ShopSimpleController");
});

Route::group([
    'prefix' => 'v1/dealer',
    'middleware' => [
        'auth',
    ],
], function () {
    Route::resource('dealer-inactive', 'DealerInactiveController');
});

Route::group([
    'prefix' => 'v2/dealer',
    // 'middleware' => [
    //     'auth',
    // ],
], function () {
    Route::get('dealer-inactive', 'Inactive\DealerInactiveController');
    Route::put('dealer-temp/{dealer_temp_id}/approve', 'Approve\DealerApprovalController');
    Route::put('sub-dealer-temp/{sub_dealer_temp_id}/approve', 'Approve\SubDealerApprovalController');
});

Route::group([
    'prefix' => 'v1/store',
    'middleware' => [
        'auth',
        // 'role:administrator|super-admin|Regional Marketing (RM)|marketing staff|Marketing Support|Regional Marketing Coordinator (RMC)|Assistant MDM|Marketing District Manager (MDM)|Marketing Manager (MM)|Sales Counter (SC)|Operational Manager|Support Bagian Distributor|Support Distributor|Support Bagian Kegiatan|Support Kegiatan|Support Supervisor|User Jember|Distribution Channel (DC)'
    ],
], function () {
    Route::resource('store-confirmation', 'StoreConfirmationController');
    Route::put('confirm-store/{store_temp_id}', 'StoreConfirmationController@confirmStore');
    Route::get("export-kios-3core-farmer", "StoreController@exportThreeFarmer");
}
);

Route::group([
    'prefix' => 'v1/seed',
], function () {
    Route::post('seeder', 'BatchInputCsvController@Seeder');
    Route::post('dealer-attachment', 'BatchInputCsvController@dealerAttachment');
    Route::post('sub-dealer-attachment', 'BatchInputCsvController@subDealerAttachment');
});

/*
|-----------------------
| IMPORT
|-----------------
 */
Route::group([
    'prefix' => 'v2/import',
    'middleware' => 'auth',
], function () {
    Route::post('store', 'ImportStoreController');
});

Route::get("export-dealer", "DealerController@export")->middleware("auth");

Route::get("export-dealer-v2", "DealerController@exportv2")->middleware("auth");

Route::get("export-three-farmer", "DealerController@exportThreeFarmer")->middleware("auth");

Route::get("export-kios-per-marketing", "DealerController@exportStorePerMarketing")->middleware("auth");

Route::get("export-shop-per-year", "DealerController@exportDealerSubDealerperYear")->middleware("auth");

Route::get('export-subdealer', "SubDealerController@export")->middleware("auth");

Route::get('export-subdealer-v2', "SubDealerController@exportv2")->middleware("auth");

Route::get("export-kios", "StoreController@export")->middleware("auth");
