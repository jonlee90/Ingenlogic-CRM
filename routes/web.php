<?php

use App\Http\Middleware\MasterPreapp;
use App\Http\Middleware\Preapp; 
use App\Http\Middleware\ClearCache; 
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/**
 * clear cache before loading any pages: prevent session not clearing on browsers' Back/Forward buttons 
 */
Route::group(['middleware' => ClearCache::class], function () {

  Auth::routes();

  Route::get('/logout', 'Auth\LoginController@logout')->name('logout');


  /**
   * ******************************************************* agency page *******************************************************
   * pages that require Preapp middleware
   */
  Route::group(['middleware' => Preapp::class], function () {
    
    Route::get('/providers', 'ProviderController@list')->name('provider.list');
    Route::get('/provider/view/{id}', 'ProviderController@view')->name('provider.view');

    Route::get('/leads', 'LeadController@list')->name('lead.list');
    Route::get('/lead/new', 'LeadController@new')->name('lead.new');
    Route::get('/lead/report/current/{id}', 'LeadReportController@current')->name('lead.rpt.current');
    Route::get('/lead/report/quote/{id}', 'LeadReportController@quote')->name('lead.rpt.quote');

    /**
     * list of overlay for ingenOverlay.js
     */
    Route::get('/lead/json/customer/mod/{lead_id}', 'LeadController@overlayCustomerMod')->name('lead.overlay-customer-mod');

    Route::get('/lead/json/follower/mod/{lead_id}', 'LeadController@overlayFollowerMod')->name('lead.overlay-follower-mod');
    Route::get('/lead/json/log/new/{lead_id}', 'LeadController@overlayLogNew')->name('lead.overlay-log-new');
    Route::get('/lead/json/log/mod/{log_id}', 'LeadController@overlayLogMod')->name('lead.overlay-log-mod');
    Route::get('/lead/json/log/history/{lead_id}', 'LeadController@overlayLogHistory')->name('lead.overlay-log-history');

    Route::get('/lead/json/location/new/{lead_id}', 'LeadLocationController@overlayLocationNew')->name('lead.overlay-loc-new');
    Route::get('/lead/json/location/mod/{loc_id}', 'LeadLocationController@overlayLocationMod')->name('lead.overlay-loc-mod');
    Route::get('/lead/json/location/file/{loc_id}', 'LeadController@overlayLocationFiles')->name('lead.overlay-loc-file');
    Route::get('/lead/json/account/new/{loc_id}', 'LeadLocationController@overlayAccountNew')->name('lead.overlay-accnt-new');
    Route::get('/lead/json/account/mod/{accnt_id}', 'LeadLocationController@overlayAccountMod')->name('lead.overlay-accnt-mod');
    Route::get('/lead/json/account/services/{quote_id}', 'LeadLocationController@overlayAccountServices')->name('lead.overlay-accnt-svc');
    Route::get('/lead/json/quote/new/{loc_id}', 'LeadLocationController@overlayQuoteNew')->name('lead.overlay-quote-new');
    Route::get('/lead/json/quote/mod/{quote_id}', 'LeadLocationController@overlayQuoteMod')->name('lead.overlay-quote-mod');

    /**
     * list of AJAX functions
     */
    Route::post('/lead/json/customer/update/{lead_id}', 'LeadController@ajaxCustomerUpdate')->name('lead.ajax-customer-update');
    Route::post('/lead/json/follower/update/{lead_id}', 'LeadController@ajaxFollowerUpdate')->name('lead.ajax-follower-update');
    Route::delete('/lead/json/follower/agent/delete/{lead_id}/{order_no}', 'LeadController@ajaxFollowerAgentDelete')->name('lead.ajax-follower-agent-delete');
    Route::delete('/lead/json/follower/provider/delete/{lead_id}/{order_no}', 'LeadController@ajaxFollowerProviderDelete')->name('lead.ajax-follower-provider-delete');
    Route::put('/lead/json/log/add/{lead_id}', 'LeadController@ajaxLogAdd')->name('lead.ajax-log-add');
    Route::post('/lead/json/log/correct/{log_id}', 'LeadController@ajaxLogCorrect')->name('lead.ajax-log-correct');

    Route::put('/lead/json/location/add/{lead_id}', 'LeadLocationController@ajaxLocationAdd')->name('lead.ajax-loc-add');
    Route::post('/lead/json/location/update/{loc_id}', 'LeadLocationController@ajaxLocationUpdate')->name('lead.ajax-loc-update');
    Route::delete('/lead/json/location/delete/{loc_id}', 'LeadLocationController@ajaxLocationDelete')->name('lead.ajax-loc-delete');
    Route::delete('/lead/json/location/file/delete/{file_id}', 'LeadController@ajaxLocationFileDelete')->name('lead.ajax-loc-file-del');
    Route::put('/lead/json/account/add/{loc_id}', 'LeadLocationController@ajaxAccountAdd')->name('lead.ajax-accnt-add');
    Route::post('/lead/json/account/toggle/{accnt_id}', 'LeadLocationController@ajaxAccountToggle')->name('lead.ajax-accnt-toggle');
    Route::post('/lead/json/account/update/{accnt_id}', 'LeadLocationController@ajaxAccountUpdate')->name('lead.ajax-accnt-update');
    Route::delete('/lead/json/account/delete/{accnt_id}', 'LeadLocationController@ajaxAccountDelete')->name('lead.ajax-accnt-delete');
    Route::post('/lead/json/account/mrc/update/{accnt_id}', 'LeadLocationController@ajaxAccountMRC')->name('lead.ajax-accnt-mrc');
    Route::put('/lead/json/quote/add/{loc_id}', 'LeadLocationController@ajaxQuoteAdd')->name('lead.ajax-quote-add');
    Route::post('/lead/json/quote/toggle/{quote_id}', 'LeadLocationController@ajaxQuoteToggle')->name('lead.ajax-quote-toggle');
    Route::post('/lead/json/quote/update/{quote_id}', 'LeadLocationController@ajaxQuoteUpdate')->name('lead.ajax-quote-update');
    Route::delete('/lead/json/quote/delete/{quote_id}', 'LeadLocationController@ajaxQuoteDelete')->name('lead.ajax-quote-delete');
    Route::post('/lead/json/quote/mrc/update/{quote_id}', 'LeadLocationController@ajaxQuoteMRC')->name('lead.ajax-quote-mrc');
    Route::post('/lead/json/quote/nrc/update/{quote_id}', 'LeadLocationController@ajaxQuoteNRC')->name('lead.ajax-quote-nrc');


    /**
    * list of FORM (put/post/delete) action
    **/
    Route::put('/customer/create', 'CustomerController@create')->name('customer.create');
    Route::post('/customer/update/{id}', 'CustomerController@update')->name('customer.update');
    Route::delete('/customer/delete/{id}', 'CustomerController@delete')->name('customer.delete');
    Route::put('/customer/contact/create/{id}', 'CustomerController@createContact')->name('customer.create-contact');
    Route::post('/customer/contact/update/{id}', 'CustomerController@updateContact')->name('customer.update-contact');
    Route::delete('/customer/contact/delete/{id}', 'CustomerController@deleteContact')->name('customer.delete-contact');

    Route::put('/lead/create', 'LeadController@create')->name('lead.create');
    Route::post('/lead/request/{id}', 'LeadController@requestQuote')->name('lead.request-quote');
    Route::post('/lead/location/file/attach/{loc_id}', 'LeadLocationController@locationFileAttach')->name('lead.loc-file-attach');
    Route::post('/lead/account/proceed/{accnt_id}', 'LeadLocationController@accountProceed')->name('lead.accnt-proceed');
    Route::post('/lead/quote/sign/{quote_id}', 'LeadLocationController@quoteSign')->name('lead.quote-sign');
    
    // ALERT 
    Route::post('/lead/ajaxAlertSend/{id}', 'LeadController@ajaxAlertSend')->name('lead.ajax-alert-send');
    Route::get('/home/json/alert/mod', 'HomeController@ajaxAlertGet')->name('home.overlay-alert-mod');   
    Route::get('/alert/manage/{id}/{type}/{alert?}', 'AlertController@manage')->name('alert.manage');
    Route::get('/lead/json/alert/mod/{id}', 'LeadController@overlayAlertMod')->name('lead.overlay-alert-mod');

  });
});
