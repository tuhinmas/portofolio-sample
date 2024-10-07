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
        'prefix' => 'v1/sales-order',
    ], function () {
        Route::resource('sales-order', 'SalesOrderController');
        Route::get('total-price', 'SalesOrderController@totalPrice')->name('totalPrice');
        Route::get('sales-order-group-month/{store?}', 'SalesOrderController@salesOrderGroupByStoreYearly')->name('group.sales.by.year.store');
        Route::get('sales-order-group-month-direct-indirect/{store?}', 'SalesOrderController@salesOrderGroupByStoreYearlyDirectAndIndirect')->name('group.sales.by.year.store.directindirect');
        Route::get('detail-five-year/{personel?}', 'SalesOrderController@detailRekapPerFiveYear');

        Route::get('sales-order-by-date/{id}', 'SalesOrderController@salesOrderAllGroupByStoreYearlyConfirmedWithStoreId')->name('sales.by.date.store');
        Route::get('sales-order-group-month-all', 'SalesOrderController@salesOrderAllGroupByStoreYearly')->name('group.sales.by.year');
        Route::get('sales-order-group-month-confirmed', 'SalesOrderController@salesOrderAllGroupByStoreYearlyConfirmed')->name('group.sales.by.year.confirmed');
        Route::get('product-sale-by-store/{id}', 'SalesOrderController@productSalesByStore')->name('product.sales.by.date.store');
        Route::get('product-sale-by-product/{id}', 'SalesOrderController@productSalesByProduct')->name('product.sales.by.product');

        Route::get('statistic-product-mandatory/{id?}', 'SalesOrderController@productMandatory')->name('product.mandatory');

        Route::get('statistic-distributor', 'SalesOrderController@distributorStatistic');
        Route::get('statistic-sales-order-last-four-month', 'SalesOrderController@saleorOrderLast4Month');
        Route::get('statistic-sales-order-last-one-year', 'SalesOrderController@saleorOrderLast1Year');
        Route::get('statistic-sales-order-by-year', 'SalesOrderController@saleorOrderByYear');
        Route::get('statistic-product-per-customer', 'SalesOrderController@productPerCustomer');
        Route::post('sales-order-push-notif', 'SalesOrderController@pushNotification');
        // Route::get('statistic-sales-order-last-one-year', 'SalesOrderController@saleorOrderLast1Year');

        Route::get('diagram-direct-sales', 'SalesOrderController@saleorOrderDiagramDirectSales');
        Route::get('statistic-sales-order-marketing-last-one-year', 'SalesOrderController@saleorOrderMarketingLast1Year');

        Route::get('diagram-payment-time', 'SalesOrderController@saleorOrderDiagramPaymentTime');

        Route::get('diagram-payment-time-not-settle', 'SalesOrderController@saleorOrderDiagramPaymentTimeNotSettle');

        Route::get('total-proforma-base-status', 'SalesOrderController@proformaTotalBaseStatus');
        Route::get('nominal-proforma-base-status', 'SalesOrderController@performNominalBaseStatus');
        Route::get('diagram-payment-time-v2', 'SalesOrderController@performPaymentTime');
        Route::get('point-origin', 'SalesOrderController@pointOrigin');
        Route::get('point-origin-total', 'SalesOrderController@pointOriginTotal');
        Route::get('list-performa-by-region-peryear', 'SalesOrderController@listProformaByRegionPerYear');
        Route::get('list-performa-by-region-three-peryear', 'SalesOrderController@listChartProformaByRegionThreeYear');
        
        /* move to modules analysis */
        // Route::get('analisys-group-by-subregion', 'SalesOrderController@analisysGroupLast4Month');
        // Route::get('analisys-group-by-subregion', 'SalesOrderController@analisysGroupLast4MonthFix');
        // Route::get('analisys-group-by-and-store', 'SalesOrderController@analisysGroupStore5year');
        Route::get('analisys-group-by-storeV2', 'SalesOrderController@analisysGroupStore5yearVer2');

        Route::get('analisys-group-by-years-and-month', 'SalesOrderController@analisysGroupStore5YearDetail');
        Route::get('analisys-group-by-marketing-years-and-month', 'SalesOrderController@analisysGroupSubRegionMarketing1Year');
        Route::get('analisys-group-by-product-five-years', 'SalesOrderController@analisysGroupSubRegionProductFiveYear');
        Route::get('analisys-group-by-product-and-dealer-five-years', 'SalesOrderController@analisysGroupSubRegionProductDealerFiveYear');
        Route::get('diagram-payment-time-v3', 'SalesOrderController@saleorOrderDiagramPaymentTimeV3');
        
        Route::post("history-direct-sales", "HistoryDirectSalesController");
        
    });

    Route::group([
        'middleware' => ['auth'],
        'prefix' => 'v1/sales-order',
    ], function () {
        Route::get("discount", "SalesOrderMoneyCalculationController@discount")->name("getDiscount");
        Route::get("indirect-sale-sub-recap-on-five-years/{id}", "SubDealerDetailController@salesOrderGroupByStoreYearly");
        Route::get("indirect-sale-sub-list/{id}", "SubDealerDetailController@indirectSaleListOnSubDelaerDetail");
        Route::post("sales-order-direct-draft", "SalesOrderDirectDraftController");
        Route::post("sales-order-direct-history-canceled", "SalesOrderDirectHistoryCanceledController");
    });

    Route::group([
        'prefix' => 'v1/sales-order-detail',
    ], function () {
        Route::resource('sales-order-detail', 'SalesOrderDetailController');
        Route::get('product-list', 'SalesOrderDetailController@product_list')->name('product_List');
        Route::get('product-price', 'SalesOrderDetailController@product_price')->name('product_price');
    });

    Route::group([
        'prefix' => 'v1',
    ], function () {
        Route::get('fee-product-item-order/sales-orders/{sales_order_id}/personnel/{personel_id}', 'FeePerProductOrderController');
    });

    Route::group([
        'prefix' => 'v2/sales-order-detail',
    ], function () {
        Route::get('product-list', 'SalesOrderDetailController@getSalesOrderDetailByDealer')->name('v2.product_List');
    });

    Route::group([
        'prefix' => 'v1/export',
    ], function () {
        
        Route::get('confirmed-sales-export', 'ExportConfirmedSaleController@exportShopTransaction');
        
        Orion::resource('confirmed-sale', 'ExportConfirmedSaleController')->withoutBatch();
        Orion::resource('confirmed-sale-detail', 'ExportConfirmedSaleDetailController')->withoutBatch();
        Orion::resource('indirect-sale', 'SalesOrderIndirectExportController')->withoutBatch();
        Orion::resource('direct-sale', 'SalesOrderDirectExportController')->withoutBatch();
        Route::get('direct-sale-distributor-export', 'ExportDirectDistributorController');
        Route::get('direct-sale-retailer-export', 'ExportDirectRetailerController');
        Route::get('indirect-sales-export', 'SalesOrderIndirectExportController@indirectSalesExport');
    });

    
    Route::group([
        'prefix' => 'v1/direct-sales',
        'namespace' => 'DirectSales'
    ], function () {
        Route::get('canceled-order/list', 'CanceledOrderController');
    });
});
