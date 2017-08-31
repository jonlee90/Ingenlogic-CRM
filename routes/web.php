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
  /*
  * automatically redirects the user to get(/login) with view('auth/login') if NOT logged in
  *
  * get(/password/reset, Auth\ForgotPasswordController@showLinkRequestForm)->name(password.request) => view(auth.passwords.email)
  * post(/password/email, Auth\ForgotPasswordController@SendResetLinkEmil)->name(password.email) => action
  * get(/password/reset/{token}, Auth\ResetPasswordController@showResetForm)->name(password.reset) => view(auth.passwords.reset)
  * post(/password/reset, Auth\ResetPasswordController@reset) => action
  */
  Auth::routes();

  Route::get('/logout', 'Auth\LoginController@logout')->name('logout');

  /**
   * ******************************************************* master (admin) page *******************************************************
   */
  Route::group(['domain' => 'master.demo.app', 'middleware' => MasterPreapp::class], function () {
    Route::get('/', 'Master\MasterHomeController@index')->name('master.index');
    Route::get('/home', 'Master\MasterHomeController@index')->name('master.home');

    Route::get('/users', 'Master\MasterUserController@list')->name('master.user.list');
    Route::get('/user/new', 'Master\MasterUserController@new')->name('master.user.new');
    Route::get('/user/view/{id}', 'Master\MasterUserController@view')->name('master.user.view');
    Route::get('/user/mod/{id}', 'Master\MasterUserController@mod')->name('master.user.mod');

    Route::get('/agencies', 'Master\MasterAgencyController@list')->name('master.agency.list');
    Route::get('/agency/new', 'Master\MasterAgencyController@new')->name('master.agency.new');
    Route::get('/agency/view/{id}', 'Master\MasterAgencyController@view')->name('master.agency.view');
    Route::get('/agency/mod/{id}', 'Master\MasterAgencyController@mod')->name('master.agency.mod');
    
    Route::get('/providers', 'Master\MasterProviderController@list')->name('master.provider.list');
    Route::get('/provider/new', 'Master\MasterProviderController@new')->name('master.provider.new');
    Route::get('/provider/view/{id}', 'Master\MasterProviderController@view')->name('master.provider.view');
    Route::get('/provider/mod/{id}', 'Master\MasterProviderController@mod')->name('master.provider.mod');

    Route::get('/services', 'Master\MasterServiceController@list')->name('master.service.list');
    
    Route::get('/leads', 'Master\MasterLeadController@list')->name('master.lead.list');
    Route::get('/lead/new', 'Master\MasterLeadController@new')->name('master.lead.new');
    Route::get('/lead/manage/{id}', 'Master\MasterLeadController@manage')->name('master.lead.manage');

    /**
     * list of FORM (put/post/delete) action for DataTables.js
     */
    Route::get('/datatables/users', 'Master\MasterDataTablesController@users')->name('master.datatables.users');
    Route::get('/datatables/agencies', 'Master\MasterDataTablesController@agencies')->name('master.datatables.agencies');
    Route::get('/datatables/providers', 'Master\MasterDataTablesController@providers')->name('master.datatables.providers');
    Route::get('/datatables/services', 'Master\MasterDataTablesController@services')->name('master.datatables.services');
    Route::get('/datatables/leads', 'Master\MasterDataTablesController@leads')->name('master.datatables.leads');

    /**
     * list of overlay for ingenOverlay.js
     */
    Route::get('/user/json/agency/assign/{id}', 'Master\MasterUserController@overlayAssignAgency')->name('master.user.overlay-agency-assign');

    Route::get('/provider/json/contact/new/{id}', 'Master\MasterProviderController@overlayContactNew')->name('master.provider.overlay-contact-new');
    Route::get('/provider/json/contact/mod/{id}', 'Master\MasterProviderController@overlayContactMod')->name('master.provider.overlay-contact-mod');
    Route::get('/provider/json/product/new/{id}', 'Master\MasterProviderController@overlayProductNew')->name('master.provider.overlay-prod-new');
    Route::get('/provider/json/product/mod/{prod_id}', 'Master\MasterProviderController@overlayProductMod')->name('master.provider.overlay-prod-mod');

    Route::get('/service/json/new', 'Master\MasterServiceController@overlayNew')->name('master.service.overlay-new');
    Route::get('/service/json/mod/{id}', 'Master\MasterServiceController@overlayMod')->name('master.service.overlay-mod');

    Route::get('/lead/json/customer/mod/{lead_id}', 'Master\MasterLeadController@overlayCustomerMod')->name('master.lead.overlay-customer-mod');
    Route::get('/lead/json/agency/assign/{lead_id}', 'Master\MasterLeadController@overlayAgencyAssign')->name('master.lead.overlay-agency-assign');
    Route::get('/lead/json/manager/assign/{lead_id}', 'Master\MasterLeadController@overlayManagerAssign')->name('master.lead.overlay-manager-assign');
    Route::get('/lead/json/follower/mod/{lead_id}', 'Master\MasterLeadFollowerController@overlayMod')->name('master.lead.overlay-follower-mod');
    Route::get('/lead/json/commission/{lead_id}', 'Master\MasterLeadManageController@overlayCommissionMod')->name('master.lead.overlay-commission');
    Route::get('/lead/json/log/new/{lead_id}', 'Master\MasterLeadManageController@overlayLogNew')->name('master.lead.overlay-log-new');
    Route::get('/lead/json/log/mod/{log_id}', 'Master\MasterLeadManageController@overlayLogMod')->name('master.lead.overlay-log-mod');
    Route::get('/lead/json/log/history/{lead_id}', 'Master\MasterLeadManageController@overlayLogHistory')->name('master.lead.overlay-log-history');

    Route::get('/lead/json/location/new/{lead_id}', 'Master\MasterLeadLocationController@overlayLocationNew')->name('master.lead.overlay-loc-new');
    Route::get('/lead/json/location/mod/{loc_id}', 'Master\MasterLeadLocationController@overlayLocationMod')->name('master.lead.overlay-loc-mod');
    Route::get('/lead/json/account/new/{loc_id}', 'Master\MasterLeadLocationController@overlayAccountNew')->name('master.lead.overlay-accnt-new');
    Route::get('/lead/json/account/mod/{accnt_id}', 'Master\MasterLeadLocationController@overlayAccountMod')->name('master.lead.overlay-accnt-mod');
    Route::get('/lead/json/account/services/{quote_id}', 'Master\MasterLeadLocationController@overlayAccountServices')->name('master.lead.overlay-accnt-svc');
    Route::get('/lead/json/quote/new/{loc_id}', 'Master\MasterLeadLocationController@overlayQuoteNew')->name('master.lead.overlay-quote-new');
    Route::get('/lead/json/quote/mod/{quote_id}', 'Master\MasterLeadLocationController@overlayQuoteMod')->name('master.lead.overlay-quote-mod');

    /**
     * list of AJAX functions
     */
    Route::post('/lead/json/follower/update/{lead_id}', 'Master\MasterLeadFollowerController@ajaxUpdate')->name('master.lead.ajax-follower-update');
    Route::delete('/lead/json/follower/master/delete/{lead_id}/{user_id}', 'Master\MasterLeadFollowerController@ajaxMasterDelete')->name('master.lead.ajax-follower-master-delete');
    Route::delete('/lead/json/follower/provider/delete/{lead_id}/{order_no}', 'Master\MasterLeadFollowerController@ajaxProviderDelete')->name('master.lead.ajax-follower-provider-delete');
    Route::post('/lead/json/customer/update/{lead_id}', 'Master\MasterLeadController@ajaxCustomerUpdate')->name('master.lead.ajax-customer-update');
    Route::put('/lead/json/log/add/{lead_id}', 'Master\MasterLeadManageController@ajaxLogAdd')->name('master.lead.ajax-log-add');
    Route::post('/lead/json/log/correct/{log_id}', 'Master\MasterLeadManageController@ajaxLogCorrect')->name('master.lead.ajax-log-correct');

    Route::put('/lead/json/location/add/{lead_id}', 'Master\MasterLeadLocationController@ajaxLocationAdd')->name('master.lead.ajax-loc-add');
    Route::post('/lead/json/location/update/{loc_id}', 'Master\MasterLeadLocationController@ajaxLocationUpdate')->name('master.lead.ajax-loc-update');
    Route::delete('/lead/json/location/delete/{loc_id}', 'Master\MasterLeadLocationController@ajaxLocationDelete')->name('master.lead.ajax-loc-delete');
    Route::put('/lead/json/account/add/{loc_id}', 'Master\MasterLeadLocationController@ajaxAccountAdd')->name('master.lead.ajax-accnt-add');
    Route::post('/lead/json/account/toggle/{accnt_id}', 'Master\MasterLeadLocationController@ajaxAccountToggle')->name('master.lead.ajax-accnt-toggle');
    Route::post('/lead/json/account/update/{accnt_id}', 'Master\MasterLeadLocationController@ajaxAccountUpdate')->name('master.lead.ajax-accnt-update');
    Route::delete('/lead/json/account/delete/{accnt_id}', 'Master\MasterLeadLocationController@ajaxAccountDelete')->name('master.lead.ajax-accnt-delete');
    Route::post('/lead/json/account/mrc/update/{accnt_id}', 'Master\MasterLeadLocationController@ajaxAccountMRC')->name('master.lead.ajax-accnt-mrc');
    Route::put('/lead/json/quote/add/{loc_id}', 'Master\MasterLeadLocationController@ajaxQuoteAdd')->name('master.lead.ajax-quote-add');
    Route::post('/lead/json/quote/toggle/{quote_id}', 'Master\MasterLeadLocationController@ajaxQuoteToggle')->name('master.lead.ajax-quote-toggle');
    Route::post('/lead/json/quote/update/{quote_id}', 'Master\MasterLeadLocationController@ajaxQuoteUpdate')->name('master.lead.ajax-quote-update');
    Route::delete('/lead/json/quote/delete/{quote_id}', 'Master\MasterLeadLocationController@ajaxQuoteDelete')->name('master.ead.ajax-quote-delete');
    Route::post('/lead/json/quote/mrc/update/{quote_id}', 'Master\MasterLeadLocationController@ajaxQuoteMRC')->name('master.lead.ajax-quote-mrc');
    Route::post('/lead/json/quote/nrc/update/{quote_id}', 'Master\MasterLeadLocationController@ajaxQuoteNRC')->name('master.lead.ajax-quote-nrc');

    /**
     * list of FORM (put/post/delete) action
     */
    Route::put('/user/create', 'Master\MasterUserController@create')->name('master.user.create');
    Route::post('/user/update/{id}', 'Master\MasterUserController@update')->name('master.user.update');
    Route::post('/user/update-pw/{id}', 'Master\MasterUserController@updatePassword')->name('master.user.update-pw');
    Route::delete('/user/delete/{id}', 'Master\MasterUserController@delete')->name('master.user.delete');
    Route::post('/user/commission/update/{id}', 'Master\MasterUserController@channelManagerUpdateCommission')->name('master.user.commission-update');
    Route::post('/user/manager/update/{id}', 'Master\MasterUserController@channelManagerUpdate')->name('master.user.manager-update');
    Route::post('/user/agency/update/{id}', 'Master\MasterUserController@updateAgency')->name('master.user.update-agency');
    Route::post('/user/agency/assign/{id}', 'Master\MasterUserController@assignAgency')->name('master.user.agency-assign');
    Route::delete('/user/agency/unassign/{user_id}/{agency_id}', 'Master\MasterUserController@unassignAgency')->name('master.user.agency-unassign');

    Route::put('/agency/create', 'Master\MasterAgencyController@create')->name('master.agency.create');
    Route::post('/agency/update/{id}', 'Master\MasterAgencyController@update')->name('master.agency.update');
    Route::delete('/agency/delete/{id}', 'Master\MasterAgencyController@delete')->name('master.agency.delete');
    Route::post('/agency/manager/update/{id}', 'Master\MasterAgencyController@channelManagerUpdate')->name('master.agency.manager-update');

    Route::put('/provider/create', 'Master\MasterProviderController@create')->name('master.provider.create');
    Route::post('/provider/update/{id}', 'Master\MasterProviderController@update')->name('master.provider.update');
    Route::delete('/provider/delete/{id}', 'Master\MasterProviderController@delete')->name('master.provider.delete');
    Route::put('/provider/contact/create/{id}', 'Master\MasterProviderController@contactCreate')->name('master.provider.create-contact');
    Route::post('/provider/contact/update/{id}', 'Master\MasterProviderController@contactUpdate')->name('master.provider.update-contact');
    Route::delete('/provider/contact/delete/{id}', 'Master\MasterProviderController@contactDelete')->name('master.provider.delete-contact');
    Route::put('/provider/product/create/{id}', 'Master\MasterProviderController@productCreate')->name('master.provider.prod-create');
    Route::post('/provider/product/reset/{id}', 'Master\MasterProviderController@productResetRate')->name('master.provider.prod-reset');
    Route::post('/provider/product/update/{prod_id}', 'Master\MasterProviderController@productUpdate')->name('master.provider.prod-update');
    Route::delete('/provider/product/delete/{prod_id}', 'Master\MasterProviderController@productDelete')->name('master.provider.prod-delete');

    Route::put('/service/create', 'Master\MasterServiceController@create')->name('master.service.create');
    Route::post('/service/update/{id}', 'Master\MasterServiceController@update')->name('master.service.update');
    Route::delete('/service/delete/{id}', 'Master\MasterServiceController@delete')->name('master.service.delete');

    Route::put('/lead/create', 'Master\MasterLeadController@create')->name('master.lead.create');
    Route::post('/lead/agency/assign/{lead_id}', 'Master\MasterLeadController@agencyAssign')->name('master.lead.agency-assign');
    Route::post('/lead/agency/remove/{lead_id}/{agency_id}', 'Master\MasterLeadController@agencyRemove')->name('master.lead.agency-remove');
    Route::post('/lead/manager/assign/{lead_id}', 'Master\MasterLeadController@managerAssign')->name('master.lead.manager-assign');
    Route::post('/lead/manager/primary/{lead_id}/{manager_id}', 'Master\MasterLeadController@managerSetPrimary')->name('master.lead.manager-primary');
    Route::post('/lead/manager/remove/{lead_id}/{manager_id}', 'Master\MasterLeadController@managerRemove')->name('master.lead.manager-remove');
    Route::post('/lead/commission/update/{lead_id}', 'Master\MasterLeadManageController@overlayCommissionUpdate')->name('master.lead.commission-update');
  });

  /**
   * ******************************************************* agency page *******************************************************
   * pages that require Preapp middleware
   */
  Route::group(['middleware' => Preapp::class], function () {
    Route::get('/', 'HomeController@index')->name('index');
    Route::get('/home', 'HomeController@index')->name('home');
    
    Route::get('/users', 'UserController@list')->name('user.list');
    // Route::get('/user/new', 'UserController@new')->name('user.new');
    Route::get('/user/view/{id}', 'UserController@view')->name('user.view');
    Route::get('/user/mod/{id}', 'UserController@mod')->name('user.mod');
    Route::get('/user/agency', 'UserController@agency')->name('user.agency');

    Route::get('/providers', 'ProviderController@list')->name('provider.list');
    // Route::get('/provider/new', 'ProviderController@new')->name('provider.new');
    Route::get('/provider/view/{id}', 'ProviderController@view')->name('provider.view');
    /*
    Route::get('/customers', 'CustomerController@list')->name('customer.list');
    // Route::get('/customer/new', 'CustomerController@new')->name('customer.new');
    Route::get('/customer/view/{id}', 'CustomerController@view')->name('customer.view');
    Route::get('/customer/mod/{id}', 'CustomerController@mod')->name('customer.mod');
    
    Route::get('/salespersons', 'SalespersonController@list')->name('salesperson.list');
    */
    Route::get('/leads', 'LeadController@list')->name('lead.list');
    Route::get('/lead/new', 'LeadController@new')->name('lead.new');
    Route::get('/lead/manage/{id}', 'LeadController@manage')->name('lead.manage');
    Route::get('/lead/report/current/{id}', 'LeadReportController@current')->name('lead.rpt.current');
    Route::get('/lead/report/quote/{id}', 'LeadReportController@quote')->name('lead.rpt.quote');

    Route::get('/project/manage/{id}', 'ProjectController@manage')->name('project.manage');

    /**
     * list of FORM (put/post/delete) action for DataTables.js
     */
    Route::get('/datatables/users', 'DataTablesController@users')->name('datatables.users');
    Route::get('/datatables/providers', 'DataTablesController@providers')->name('datatables.providers');
    Route::get('/datatables/services', 'DataTablesController@services')->name('datatables.services');
    Route::get('/datatables/customers', 'DataTablesController@customers')->name('datatables.customers');
    Route::get('/datatables/salespersons', 'DataTablesController@salesperson')->name('datatables.salesperson');
    Route::get('/datatables/leads', 'DataTablesController@leads')->name('datatables.leads');

    /**
     * list of overlay for ingenOverlay.js
     */
    Route::post('/customer/overlay/contact/new/{id}', 'CustomerController@overlayNewContact')->name('customer.overlay-contact-new');
    Route::post('/customer/overlay/contact/mod/{id}', 'CustomerController@overlayModContact')->name('customer.overlay-contact-mod');

    Route::post('/salesperson/overlay/new', 'SalespersonController@overlayNew')->name('salesperson.overlay-new');
    Route::post('/salesperson/overlay/mod/{id}', 'SalespersonController@overlayMod')->name('salesperson.overlay-mod');
    /*
    Route::get('/lead/json/customers', 'LeadController@overlayCustomerList')->name('lead.overlay-customer-list');
    Route::get('/lead/json/customer/new', 'LeadController@overlayCustomerNew')->name('lead.overlay-customer-new'); // query: lead_id? 
    */
    Route::get('/lead/json/customer/mod/{lead_id}', 'LeadController@overlayCustomerMod')->name('lead.overlay-customer-mod');
    /*
    Route::get('/lead/json/salespersons', 'LeadController@overlaySalespersonList')->name('lead.overlay-salesperson-list');
    Route::get('/lead/json/salesperson/new', 'LeadController@overlaySalespersonNew')->name('lead.overlay-salesperson-new'); // query: lead_id? 
    Route::get('/lead/json/salesperson/mod/{lead_id}', 'LeadController@overlaySalespersonMod')->name('lead.overlay-salesperson-mod');
    */
    Route::get('/lead/json/follower/mod/{lead_id}', 'LeadFollowerController@overlayMod')->name('lead.overlay-follower-mod');
    Route::get('/lead/json/log/new/{lead_id}', 'LeadManageController@overlayLogNew')->name('lead.overlay-log-new');
    Route::get('/lead/json/log/mod/{log_id}', 'LeadManageController@overlayLogMod')->name('lead.overlay-log-mod');
    Route::get('/lead/json/log/history/{lead_id}', 'LeadManageController@overlayLogHistory')->name('lead.overlay-log-history');

    Route::get('/lead/json/location/new/{lead_id}', 'LeadLocationController@overlayLocationNew')->name('lead.overlay-loc-new');
    Route::get('/lead/json/location/mod/{loc_id}', 'LeadLocationController@overlayLocationMod')->name('lead.overlay-loc-mod');
    Route::get('/lead/json/account/new/{loc_id}', 'LeadLocationController@overlayAccountNew')->name('lead.overlay-accnt-new');
    Route::get('/lead/json/account/mod/{accnt_id}', 'LeadLocationController@overlayAccountMod')->name('lead.overlay-accnt-mod');
    Route::get('/lead/json/account/services/{quote_id}', 'LeadLocationController@overlayAccountServices')->name('lead.overlay-accnt-svc');
    Route::get('/lead/json/quote/new/{loc_id}', 'LeadLocationController@overlayQuoteNew')->name('lead.overlay-quote-new');
    Route::get('/lead/json/quote/mod/{quote_id}', 'LeadLocationController@overlayQuoteMod')->name('lead.overlay-quote-mod');

    /**
     * list of AJAX functions
     */
    Route::post('/lead/json/customer/update/{lead_id}', 'LeadController@ajaxCustomerUpdate')->name('lead.ajax-customer-update');
    /*
    Route::post('/lead/json/salesperson/select/{id}', 'LeadController@ajaxSalespersonSelect')->name('lead.ajax-salesperson-select');
    Route::put('/lead/json/salesperson/create', 'LeadController@ajaxSalespersonCreate')->name('lead.ajax-salesperson-create'); // query: lead_id?
    Route::post('/lead/json/salesperson/update/{lead_id}', 'LeadController@ajaxSalespersonUpdate')->name('lead.ajax-salesperson-update');
    Route::delete('/lead/json/salesperson/remove/{lead_id}', 'LeadController@ajaxSalespersonRemove')->name('lead.ajax-salesperson-remove');
*/
    Route::post('/lead/json/follower/update/{lead_id}', 'LeadFollowerController@ajaxUpdate')->name('lead.ajax-follower-update');
    Route::delete('/lead/json/follower/agent/delete/{lead_id}/{order_no}', 'LeadFollowerController@ajaxAgentDelete')->name('lead.ajax-follower-agent-delete');
    Route::delete('/lead/json/follower/provider/delete/{lead_id}/{order_no}', 'LeadFollowerController@ajaxProviderDelete')->name('lead.ajax-follower-provider-delete');
    Route::put('/lead/json/log/add/{lead_id}', 'LeadManageController@ajaxLogAdd')->name('lead.ajax-log-add');
    Route::post('/lead/json/log/correct/{log_id}', 'LeadManageController@ajaxLogCorrect')->name('lead.ajax-log-correct');

    Route::put('/lead/json/location/add/{lead_id}', 'LeadLocationController@ajaxLocationAdd')->name('lead.ajax-loc-add');
    Route::post('/lead/json/location/update/{loc_id}', 'LeadLocationController@ajaxLocationUpdate')->name('lead.ajax-loc-update');
    Route::delete('/lead/json/location/delete/{loc_id}', 'LeadLocationController@ajaxLocationDelete')->name('lead.ajax-loc-delete');
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
    // Route::put('/user/create', 'UserController@create')->name('user.create');
    Route::post('/user/update/{id}', 'UserController@update')->name('user.update');
    Route::post('/user/update-pw/{id}', 'UserController@updatePassword')->name('user.update-pw');
    Route::post('/user/update-agency', 'UserController@updateAgency')->name('user.update-agency');
    Route::delete('/user/delete/{id}', 'UserController@delete')->name('user.delete');

    Route::put('/customer/create', 'CustomerController@create')->name('customer.create');
    Route::post('/customer/update/{id}', 'CustomerController@update')->name('customer.update');
    Route::delete('/customer/delete/{id}', 'CustomerController@delete')->name('customer.delete');
    Route::put('/customer/contact/create/{id}', 'CustomerController@createContact')->name('customer.create-contact');
    Route::post('/customer/contact/update/{id}', 'CustomerController@updateContact')->name('customer.update-contact');
    Route::delete('/customer/contact/delete/{id}', 'CustomerController@deleteContact')->name('customer.delete-contact');

    Route::put('/salesperson/create', 'SalespersonController@create')->name('salesperson.create');
    Route::post('/salesperson/update/{id}', 'SalespersonController@update')->name('salesperson.update');
    Route::delete('/salesperson/delete/{id}', 'SalespersonController@delete')->name('salesperson.delete');

    Route::put('/lead/create', 'LeadController@create')->name('lead.create');
    Route::post('/lead/request/{id}', 'LeadController@requestQuote')->name('lead.request-quote');
    Route::post('/lead/account/proceed/{accnt_id}', 'LeadLocationController@accountProceed')->name('lead.accnt-proceed');
    Route::post('/lead/quote/sign/{quote_id}', 'LeadLocationController@quoteSign')->name('lead.quote-sign');
    /*
    Route::post('/lead/update/{id}', 'LeadController@update')->name('lead.update');
    */
    Route::delete('/lead/delete/{id}', 'LeadController@delete')->name('lead.delete');
  });
});
