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
    'prefix' => 'v1/data-acuan',
    'middleware' => ['auth'],
], function () {
    Route::resource('business-sector', 'BussinessSectorController');
    Route::resource('business-sector-category', 'BussinessSectorCategoryController');
    Route::resource('country', 'CountryController');
    Route::resource('division', 'DivisionController');
    Route::resource('position', 'PositionController');
    Route::resource('religion', 'ReligionController');
    Route::resource('permission', 'PermissionController');
    Route::get('master-personal', 'MasterPersonalController@index');
});

Route::group([
    'prefix' => 'v1/data-acuan',
    'middleware' => ['auth'],
], function () {
    Route::get('provinces', 'AddressFinderController@province');
    Route::get('provinces-only', 'AddressFinderController@provinceOnly');
    Route::get('find-provinces', 'AddressFinderController@provinceFilterById');
    Route::get('find-cities', 'AddressFinderController@findCity');
    Route::get('find-districts', 'AddressFinderController@findDistrict');
    Route::get('find-districts-with-filter', 'AddressFinderController@findDistrictWithFilter');
    Route::get('find-multi-city', 'AddressFinderController@findMultiCity');
    Route::get('find-multi-district', 'AddressFinderController@findMultiDistrict');
    Route::get('district-list-not-in-area-district', 'AddressFinderController@districtExcludeAreaDistrict');
    Route::get('district-list-excluding-in-other', 'AddressFinderController@allDistrictExcludeDistrictInOtherSUbRegion');
    Route::get('district-list-excluding-distributor', 'AddressFinderController@districtExcludeDistributor');

    Route::get('region', 'MarketingAreaFinderController@region');
    Route::get('find-sub-regions', 'MarketingAreaFinderController@findSubRegion');
    Route::post('warehouse-porter', 'WarehousesController@attachPorter');
    Route::delete('warehouse-porter/{id}', 'WarehousesController@porterDestroy');
    
    Route::get('find-marketing-area-cities', 'MarketingAreaFinderController@findCities'); 

    Route::resource('payment-method', 'PaymentMethodController');
    Route::get('payment-method-export', 'PaymentMethodController@export');
    Route::get('payment-method-base-credit-limit', 'PaymentMethodController@dealerPaymentCreditLimitBased');
    Route::resource('bank', 'BankController');
    Route::get('bank-list', 'BankController@bank');
    Route::resource('entity', 'EntityController');
    Route::resource('organisation-category', 'OrganisationCategoryController');
    Route::resource('identity-card', 'IdentityCardController');
    Route::resource('blood', 'BloodController');
    Route::resource('rhesus', 'BloodRhesusController');
    Route::resource('capital-status', 'CapitalStatusController');
});

Route::group([
    'prefix' => 'v1/data-acuan',
    'middleware' => ['auth'],
], function () {
    Route::resource('product', 'ProductController');
    // Orion::resource('product-v2', 'ProductV2Controller');
    Route::get('product-only', 'ProductController@productPackageOnly');
    Route::get('product-agency-level-d1/{id}', 'ProductController@productAgencyLevelD1');
    Route::get('product-agency-level-shop-support/{id}', 'ProductController@productAgencyLevelD1Direct');

    Route::get('product-export', 'ProductController@export');
    Route::get('product-by-dealer', 'ProductController@getProductByDealer');
    Route::get('product-sales-by-dealer', 'ProductController@getProductSalesByDealer');
    Route::put('product-update-het/{id}', 'ProductController@updateHet');
    Route::resource('package', 'PackageController');
    Route::get('package-export', 'PackageController@export');
    Route::resource('product-category', 'ProductCategoryController');
    Route::resource('price', 'PriceController');
    Route::resource('agency-level', 'AgencyLevelController');
    Route::get('indonesian-area', 'IndonesianAreaController@index');
});

Route::group([
    'prefix' => 'v2/data-acuan',
    'middleware' => ['auth'],
], function () {
    Orion::resource('price', 'product\ProductPriceController');
});

Route::group([
    'prefix' => 'v1/data-acuan',
    'middleware' => ['auth'],
], function () {
    Orion::resource('dealer-grade-suggestions', 'DealerGradeSuggestionController')->withoutBatch();
});

Route::group([
    "prefix" => "v1",
    'middleware' => ['auth'],
], function () {
    Orion::resource('plant-category', 'PlanCategoryController');
    Route::get('store/duplicate-number-telephone', 'JavamasController@duplicateNumberTelephone');

});

Route::group([
    "prefix" => "v2/data-acuan",
    'middleware' => ['auth'],
], function () {
    Route::post('pricev2', 'PriceController@storeBatchPriceHistory');
    Orion::resource('price', 'PriceV2Controller')->withSoftDeletes();

    Route::get('price-history-segmen', 'PriceV2Controller@priceHistoryBySegmenV2');
    Orion::resource('price-history', 'PriceHistoryController')->withSoftDeletes();
});

Route::group([
    "prefix" => "v1/data-acuan",
    'middleware' => ["auth"],
], function () {
    Orion::resource('plant', 'PlantController');
    Orion::resource('marketing-area-region', 'MarketingAreaRegionController')->withSoftDeletes();
    Route::put('marketing-area-region-update/{id}', 'MarketingAreaRegionController@updateRegion');
    Orion::resource('marketing-area-subregion', 'MarketingAreaSubRegionController')->withSoftDeletes();
    Orion::resource('marketing-area-city', 'MarketingAreaCityController')->withSoftDeletes();
    Orion::resource('marketing-area-district', 'MarketingAreaDistrictController')->withSoftDeletes();
    Route::put('marketing-area-district-sync-marketing', 'MarketingAreaDistrictController@syncMarketingOnAllStores');
    Route::put('marketing-area-district-sync-dealer-d1', 'MarketingAreaDistrictController@syncMarketingDistributorD1');
    Route::put('marketing-area-district-sync-dealer-d2', 'MarketingAreaDistrictController@syncMarketingDistributorD2');

    Orion::resource('grading', 'GradingController')->withSoftDeletes();
    Route::post('simple-grading', 'GradingController@simpleGrading');
    Orion::resource('dealer-payment', 'DealerPaymentMethodController')->withSoftDeletes();
    Orion::resource('dealer-benefit', 'DealerBenefitController')->withSoftDeletes();
    Route::get('dealer-benefit-active-benefit', 'DealerBenefitController@discount');

    /**
     * FEE REFERENCES
     */
    Orion::resource('fee', 'FeeController')->withSoftDeletes();

    Orion::resource('fee-position', 'Fee\FeePositionController')->withSoftDeletes();
    Orion::resource('fee-position-history', 'Fee\FeePositionHistoryController')->withSoftDeletes();
    Route::get('active-fee-position', 'Fee\ActiveFeePositionController');

    Orion::resource('fee-handover-status', 'Fee\StatusFeeController')->withSoftDeletes();
    Orion::resource('fee-handover-status-history', 'Fee\StatusFeeHistoryController')->withSoftDeletes();
    Route::get('active-fee-handover-status', 'Fee\ActiveStatusFeeController');
    
    Orion::resource('fee-follow-up', 'Fee\FeeFollowUpController')->withSoftDeletes();
    Orion::resource('fee-follow-up-history', 'Fee\FeeFolowUpHistoryController')->withSoftDeletes();
    Route::get('active-fee-follow-up', 'Fee\ActiveFeeFollowUpController');


    Route::get('fee-follow-up-days', 'Fee\FeeFollowUpController@followUpDays');
    Route::delete('fee-follow-up-delete/{id}', 'Fee\FeeFollowUpController@deleteFeeFollowUp');
    Orion::resource('point-product', 'PointProductController');
    Route::post("sub-region-sync-city", "MarketingAreaSubRegionController@syncCity");
    Route::post('marketing-area-district-sync', 'MarketingAreaDistrictController@syncDistrict');
    Route::delete('marketing-area-subregion-delete/{id}', 'MarketingAreaSubRegionController@marketingAreaSubRegionDelete');
    Orion::resource("ppn", "PpnController")->withSoftDeletes();
    Orion::resource("product-mandatory", "ProductMandatoryController")->withSoftDeletes();
    Route::post("product-mandatory-sync", "ProductMandatoryController@productMandatoriesSync");

    Orion::resource("warehouse", "WarehousesController")->withSoftDeletes();
    Orion::resource("marketing-poin", "MarketingPoinController")->withSoftDeletes();
    Route::get("active-ppn", "PpnController@activePpn");
    Orion::resource("driver", "DriverController")->withSoftDeletes();
    Route::get("driver-capacity-detail", "DriverController@armadaCapacityDetail");

    Orion::resource("proforma-receipt", "ProformaReceiptController")->withSoftDeletes();
    Orion::resource("budget", "BudgetController")->withSoftDeletes();

    Orion::resource("dealer-grading-block", "GradingBlockController")->withSoftDeletes();

    // Orion::resource("check-product-exist","ProductController@checkProductExict")->withSoftDeletes();
    // Route::resource('budget-area', 'BudgetAreaController');
    // Orion::resource('budget-rule', 'BudgetRuleController');
    // Route::get('province-area-budget/index', 'BudgetAreaController@provinceArea');
    Orion::resource('prize-marketing', 'PrizeMarketingController')->withSoftDeletes();
    Orion::resource('maximum-settle-day', 'MaximumSettleDayController')->withSoftDeletes()->withoutBatch();
    Route::post('maximum-settle-day-sync', 'MaximumSettleDayController@syncMaxSettleDays');
    Orion::resource('maximum-days-reference', 'MaxDaysReferenceController');

    Route::get('fee-product-template-import', 'FeeController@templateImport')->name('fee.template-import');
    Route::post('import-fee-product', 'FeeController@importFeeProduct');

    Orion::resource("payment-day-color", "PaymentDayColorController")->withSoftDeletes();
    Orion::resource("proforma-receipt", "ProformaReceiptController")->withSoftDeletes();
    Orion::resource("budget", "BudgetController")->withSoftDeletes();

    Orion::resource("dealer-grading-block", "GradingBlockController")->withSoftDeletes();

    // Orion::resource("check-product-exist","ProductController@checkProductExict")->withSoftDeletes();
    // Route::resource('budget-area', 'BudgetAreaController');
    // Orion::resource('budget-rule', 'BudgetRuleController');
    // Route::get('province-area-budget/index', 'BudgetAreaController@provinceArea');
    Orion::resource('prize-marketing', 'PrizeMarketingController')->withSoftDeletes();
    Orion::resource('maximum-settle-day', 'MaximumSettleDayController')->withSoftDeletes()->withoutBatch();
    Route::post('maximum-settle-day-sync', 'MaximumSettleDayController@syncMaxSettleDays');
    Orion::resource('maximum-days-reference', 'MaxDaysReferenceController');

    Route::get('fee-product-template-import', 'FeeController@templateImport')->name('fee.template-import');
    Route::post('import-fee-product', 'FeeController@importFeeProduct');
});

Route::group([
    "prefix" => "v1/data-acuan",
    'middleware' => ["auth"],
], function () {
    Orion::resource('promo', 'PromoController');
    Route::get('promo-product/', 'PromoController@promoByInProduct');
    Route::get('promo/stop-promo/{promo_id}', 'PromoController@stopPromo');
    Route::get('promo-product/list-product/{promo_id}', 'PromoProductController@list');
    Route::put('promo-product/{promo_id}/update/{product_promo_id}', 'PromoProductController@update');
    Route::post('promo-product/{promo_id}', 'PromoProductController@store');
    Route::delete('promo-product/{promo_id}/delete/{product_promo_id}', 'PromoProductController@delete');
    Route::put('promo-product/{promo_id}/save-attribute-products', 'PromoProductController@saveAttributeProduct');
});

Route::group([
    "prefix" => "v1/promo/",
    'middleware' => ["auth"],
], function () {
    Route::get('list-promo-by-product', 'PromoController@listPromoByProduct');
    Route::get('list-promo-by-order', 'PromoController@listPromoByOrder');
});

Route::group([
    "prefix" => "v1/",
    'middleware' => ["auth"],
], function () {
    Orion::resource('promo-order', 'PromoOrderController');
    Route::get('promo-order-simple', 'PromoOrderController@simple');
});

Route::group([
    "prefix" => "v1/data-acuan",
    'middleware' => ["auth"],
], function () {
    Route::post('payment-method-is-for-marketing', 'PaymentMethod\UpdatePaymentMethodForMarketingController');
});

Route::group([
    "prefix" => "v1/data-acuan/form-data",
    'middleware' => ["auth"],
], function () {
    Route::get('dealer-benefit-grade', 'DealerBenefit\DataFormRequirementController');
});

Route::group([
    "prefix" => "v2/data-acuan",
    'middleware' => ["auth"],
], function () {
    Route::get('master-personal', 'MasterPersonalController@index');
    Route::get('blood-rhesuses', 'MasterPersonalController@bloodRhesuses');
    Route::get('positions', 'MasterPersonalController@positions');
    Route::get('countries', 'MasterPersonalController@countries');
    Route::get('personels', 'MasterPersonalController@personels');
    Route::get('religions', 'MasterPersonalController@religions');
    Route::get('organisations', 'MasterPersonalController@organisations');
    Route::get('identity-cards', 'MasterPersonalController@identityCards');
    Route::get('banks', 'MasterPersonalController@banks');
    Route::get('bloods', 'MasterPersonalController@bloods');
    Route::get('regions', 'MasterPersonalController@regions');
    Route::get('sub-regions', 'MasterPersonalController@subRegions');
    Route::get('districts', 'MasterPersonalController@districts');
    Route::get('reset-master', 'MasterPersonalController@resetMaster');
    Orion::resource('marketing-area-district', 'MarketingAreaDistrictV2Controller')->withSoftDeletes();
});
