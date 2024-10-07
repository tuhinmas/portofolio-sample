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
    'prefix' => 'v1/personnel',
    // 'role:administrator|super-admin|Regional Marketing (RM)|marketing staff|Marketing Support|Regional Marketing Coordinator (RMC)|Assistant MDM|Marketing District Manager (MDM)|Marketing Manager (MM)|Sales Counter (SC)|Operational Manager|Support Bagian Distributor|Support Distributor|Support Bagian Kegiatan|Support Kegiatan|Support Supervisor|User Jember|Distribution Channel (DC)'
],
    function () {
        // Route::resource('personnel','PersonelController');
        Route::resource('personnel-address', 'AddressController');
        Route::resource('personnel-bank', 'BankController');
        Route::resource('personnel-contact', 'ContactController');
        Route::get('find-supervisor', 'PersonelFormInputFinderController@findSupervisor');
        Route::get('find-position', 'PersonelFormInputFinderController@findPosition');
        Route::get('find-citizenship', 'PersonelFormInputFinderController@findCitizenship');
        Route::post('mandatory-product-achievement', 'MandatoryProductAchievementController@index');
    });

Route::group([
    'prefix' => 'v1/personnel',
    'midlleware' => [
        'auth',
        // 'role:administrator|super-admin|Regional Marketing (RM)|marketing staff|Marketing Support|Regional Marketing Coordinator (RMC)|Assistant MDM|Marketing District Manager (MDM)|Marketing Manager (MM)|Sales Counter (SC)|Operational Manager|Support Bagian Distributor|Support Distributor|Support Bagian Kegiatan|Support Kegiatan|Support Supervisor|User Jember|Distribution Channel (DC)'
    ],
], function () {
    Route::resource('personnel', 'PersonelController');
    Route::get('personnel-check-disable/{personel_id}', 'PersonelController@personelCheckDisable');
    Route::get('personnel-export', 'PersonelController@exportPersonel');
    Route::post('personel-add-user', 'PersonelController@addPersonelAsUser');
    Route::get('all-personnel-minimalis-data', 'PersonelController@PersonelIndexMinimalis');
    Route::get('allpersonnel', 'PersonelController@allPersonel');
    Route::get("marketing-child-count-notif", "PersonelController@personelChildSupervisorCountNotif");
    
    Route::get("marketing-aplicator-count-notif", "PersonelController@personelChildAplicatorCountNotif");

    // Route::get("marketing-child-supervisor-count-notif", "PersonelController@personelChildSupervisorCountNotif");
    Route::get("marketing-child-total-count-notif", "PersonelController@personelTotalChildCountNotif");
    Route::get("marketing-child-notif", "PersonelController@personelChildNotif");
    Route::get('allpersonnelv2', 'PersonelController@allPersonelV2');
    Route::get('personnel-children', 'PersonelController@personelListBaseOnOthers');
    Route::get('supervisor-children', 'PersonelController@supervisorChild');
});

Route::group([
    'prefix' => 'v1/personnel',
    'middleware' => 'auth',
], function () {
    Route::resource('identity-card', 'IdentityCardController');
    Route::post('import-addresses', 'PersonelController@importAddress');
    Route::post('import-contact', 'PersonelController@importContact');
    Route::post('import-bank', 'PersonelController@importBank');
    Route::resource("marketing-list", "MarketingController");
    Route::get("marketing-sales-recap", "MarketingController@salesRecap");
    Route::get("marketing-recap-in-last-four-quarter/{personel_id}", "MarketingRecapSalesInLastFourQuarterController");
    Route::get("marketing-sales-recap-per-dealer-per-quartal/{dealer_id}", "MarketingController@salesRecapPerdealerPerQuartal");
    Route::get("marketing-sales-recap-indirect", "MarketingController@indirectSalesRecapBasedQuarter");
    Route::get("marketing-sales-recap-product-distribution", "MarketingController@productSalesByMarketing");
    Route::get("marketing-sales-recap-product-distribution-per-product", "MarketingController@productDistributionPerProductOnStore");
    Route::get("marketing-sales-recap-product-distribution-per-store", "MarketingController@productDistributionByStore");
    Route::get("marketing-sales-recap-five-years", "MarketingController@marketingSalesGrafikFiveYear");
    Route::get("marketing-sales-recap-per-quartal", "MarketingController@marketingSalesGrafikPerQuartal");
    Route::get("marketing-sales-recap-per-sub-region-per-marketing", "MarketingController@marketingSalesRecapPerSubRegionPerMarketing");
    Route::get("marketing-sales-recap-per-region-per-marketing", "MarketingController@marketingSalesRecapPerSubRegionPerMarketingFourMonth");
    Route::get("marketing-sales-recap-per-region-per-stores", "MarketingController@marketingSalesRecapPerSubRegionPerStoresFourMonth");
    Route::get("marketing-sales-recap-per-year-per-month", "MarketingController@marketingSalesRecapPerYearPerMonth");
    Route::get("marketing-sales/{personel_id}", "MarketingSalesController");
    Route::get("marketing-achievement-recap-per-year-per-quartal", "MarketingController@marketingAchievementRecapFiveYearsPerQuartal");
    Route::get("marketing-fee-recap-per-quartal", "MarketingController@marketingFeeRecapPerQuartal");
    Route::resource("marketing", "MarketingController");
    Orion::resource("personnel-note", "PersonelNoteController");
    Orion::resource("personnel-status-history", "HistoryPersonelStatusController");
    Route::get("marketing-fee-point-achievement", "FeePointController@feeAndPointRecap");
    Route::get("marketing-achievement-recap-per-region", "FeePointController@marketingAchievementTargetForGraphic");
    Orion::resource("marketing-fee", "MarketingFeeController")->withSoftDeletes();
    Route::post("marketing-fee-grouped", "MarketingFeeController@marketingFeeGrouped");
    Route::get("markting-dashboard", "MarketingDashboardController");

    /* fee origin */
    Route::get("marketing-fee-detail/{personel_id}", "MarketingFeeController@marketingFeeDetail");
    Route::get("marketing-fee-target-detail/{personel_id}", "FeeTargetOriginController");
    Route::get("marketing-fee-target-origin/{personel_id}", "FeeTargetOriginFromOrderController");

    Route::get("marketing-list-v2/{id}", "MarketingV2Controller@show");

    Route::get("export-area-marketing", "MarketingController@exportAreaMarketing");
    Route::get("export-export-calendar-crop", "MarketingController@exportCalendarCrop");

    Route::put("recalculate-point-marketing/{personel_id}", "RecalculatePointMarketingController");
    Route::put("recalculate-fee-marketing/{personel_id}", "RecalculateMarketingFeeController");
    Route::get("fee-sharing-to-fee-position/{sales_order_id}", "FeeSharingToFeePositionPopulationController");
    Route::get("marketing-product-distribution-per-year", "MarketingProductDistributionPerStoreController");

    Route::get("marketing-duplication-notelp-kios/{id}", "PersonelController@marketingDupliationNoTelp");
    Route::post("sync-fee-personnel", "PersonelController@syncFeePersonnel");

    Route::get("check-marketing-has-applicator/{id}", "PersonelController@checkMarketingHasApplicator");

    Route::get("last-data-history-personel", "HistoryPersonelStatusController@lastDataHistoryPersonel");
    Route::get("personnel-form-data/{personel_id}", "PersonelFormDataController");
});

Route::group([
    'prefix' => 'v2/personnel',
    'middleware' => 'auth',
], function () {
    Route::get("personel-fetch-simple", "PersonelController@fetchDataSimple");
    Route::get("marketing-sales-recap-per-region-per-marketing", "MarketingController@marketingSalesRecapPerSubRegionPerMarketingFourMonthV2");
    Orion::resource("personnel", "PersonelV2\PersonelV2Controller");
    Route::get("mandatory-product-achievement", "MandatoryProductAchievementV2Controller");
});

Route::group([
    'prefix' => 'v1/personnel',
    'middleware' => 'auth',
], function () {
    Route::GET("{personel_id}/applicators", "Marketing\GetApplicatorByMarketingController");
    Route::post('store-coverage/{personel_id}', "PersonelController@storeCoverage");
    Route::post('store-coverage-filter/{personel_id}', "PersonelController@storeCoverageFilter");
});

Route::group([
    'prefix' => 'v1/data-acuan/marketing-area-subregion',
    'middleware' => 'auth',
], function () {
    Route::GET("{personel_id}/applicators", "Marketing\GetApplicatorBySubRegionController");
});


/**
 * MARKETING FEE PAYMENT
 */
Route::group([
    'prefix' => 'v1/marketing',
    'middleware' => 'auth',
], function () {
    Route::post("fee-payment", "Marketing\StoreMarketingFeePaymentController");
    Route::get("fee-payment", "Marketing\GetMarketingFeePaymentController");
    Route::get("fee-payment/{payment_id}", "Marketing\GetMarketingFeePaymentByIdController");
    Route::get("fee-quarter-detail", "Marketing\GetMarketingFeeDetailController");
    Route::get("fee-quarter-achievement", "Fee\MarketingFeeAchievementPerQuarterController");
});

/*
|-----------------------------------
| FEE TRACKER
|------------------------
 */

Route::group([
    "middleware" => "auth",
    "prefix" => "v1/personnel",
], function () {
    Route::get("fee-tracker", "Fee\MarketingFeeTrackerController");
    Route::get("fee-target-tracker", "Fee\MarketingFeeTargetTrackerController");
});

Route::group([
    'prefix' => 'v1/personnel',
    'middleware' => 'auth',
], function () {
    Orion::resource('history-structure', 'HistoryStructurePersonnelController');
    Route::get("history-structure-export-marketing", "HistoryStructurePersonnelController@exportMarketing");
    Route::get("history-structure-export-history-marketing", "HistoryStructurePersonnelController@exportHistoryMarketing");
    Route::post("history-structure-import-marketing", "HistoryStructurePersonnelController@import");
});

/**
 * personel non marketing
 */
Route::group([
    'prefix' => 'v2/personnel',
    'middleware' => 'auth',
], function () {
    Route::get("{personel_id}", "PersonelNonMarketingByIdController");
});

Route::group([
    'prefix' => 'v2',
    'middleware' => 'auth',
], function () {
    Route::resource("marketing-list", "MarketingListController");
});


