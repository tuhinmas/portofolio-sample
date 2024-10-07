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
    "prefix" => "v1",
    "middleware" => "auth",
], function(){
    Orion::resource("invoice", "InvoiceController")->withSoftDeletes();
    Route::put("invoice-consider-to-done/{id}", "InvoiceController@consideerdToDone");
    Route::get("invoice-discount", "InvoiceController@discount");
    Orion::resource("payment", "PaymentController")->withSoftDeletes();
    Orion::resource("entrustment-payment", "EntrusmentPaymentController")->withSoftDeletes();
    Orion::resource("invoice-proforma", "InvoiceProformaController")->withSoftDeletes();
    Route::post("invoice-proforma-store", "InvoiceProformaController@storeInvoiceProforma");
    Route::put("invoice-proforma-update/{id}", "InvoiceProformaController@updateInvoiceProforma");   
    Route::get("grafik-proforma","InvoiceController@diagramPerforma");
    Route::get("grafik-five-year-proforma","InvoiceController@diagramProformaFiveYear");
    Route::post("invoice-payment-due", "InvoiceDueDateController");
    Route::get("grafik-five-year-list-proforma","InvoiceController@diagramListProformaFiveYear");
    Route::get("invoice-receiving-good/{invoice_id}", "InvoiceController@listReceivingGood");
    Route::put("invoice-update/{id}", "InvoiceController@updateCustom");
});

Route::group([
    "prefix" => "v2",
    "middleware" => "auth",
], function(){
    Orion::resource("invoicev2", "InvoiceV2Controller")->withSoftDeletes();
    Route::post("set-load", "V2\InvoiceController@list");
});


/**
 * -----------------------------------------
 * adjustment stock marketing              |
 * -----------------------------------------
 * 
 * there is different between adjusment stock from support
 * and from marketing, adjustment from marketing not
 * change stock distributor
 */
Route::group([
    "prefix" => "v1",
    "middleware" => "auth",
], function(){
    Orion::resource("adjustment-stock", "AdjustmentStockController")->withSoftDeletes(); 
    Orion::resource("adjustment-stock-marketing", "AdjustmentStockMarketingController")->withSoftDeletes(); 
});

Route::get('export-direct-order', "InvoiceController@export")->middleware("auth");

/**
 *--------------------------------------
 * CREDIT MEMO
 *----------------------------
 */
Route::group([
    "prefix" => "v1",
    "middleware" => "auth",
], function(){
    Orion::resource("credit-memo", "CreditMemoController")->withoutBatch();
    Route::put("credit-memo/{credit_memo_id}/cancel", "CreditMemoCancelController");
    Route::get("credit-memo-form-data", "CreditMemoFormdataController");
});
 