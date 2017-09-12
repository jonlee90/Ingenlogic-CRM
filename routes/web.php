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
  Route::group(['domain' => 'master.crm.app', 'middleware' => MasterPreapp::class], function () {
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

    Route::get('/projects', 'Master\MasterProjectController@list'); // project.list
    Route::get('/project/manage/{id}', 'Master\MasterProjectController@manage')->name('master.project.manage');

    /**
     * list of FORM (put/post/delete) action for DataTables.js
     */
    Route::get('/datatables/users', 'Master\MasterDataTablesController@users')->name('master.datatables.users');
    Route::get('/datatables/agencies', 'Master\MasterDataTablesController@agencies')->name('master.datatables.agencies');
    Route::get('/datatables/providers', 'Master\MasterDataTablesController@providers')->name('master.datatables.providers');
    Route::get('/datatables/services', 'Master\MasterDataTablesController@services')->name('master.datatables.services');
    Route::get('/datatables/leads', 'Master\MasterDataTablesController@leads')->name('master.datatables.leads');
    Route::get('/datatables/projects/signed', 'Master\MasterDataTablesController@projectsSigned'); // datatables.projects-sign
    Route::get('/datatables/projects/keep', 'Master\MasterDataTablesController@projectsKeep'); // datatables.projects-keep
    Route::get('/datatables/projects/cancel', 'Master\MasterDataTablesController@projectsCancel'); // datatables.projects-cancel

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
    Route::get('/lead/json/follower/mod/{lead_id}', 'Master\MasterLeadController@overlayFollowerMod')->name('master.lead.overlay-follower-mod');
    Route::get('/lead/json/commission/{lead_id}', 'Master\MasterLeadController@overlayCommissionMod')->name('master.lead.overlay-commission');
    Route::get('/lead/json/log/new/{lead_id}', 'Master\MasterLeadController@overlayLogNew')->name('master.lead.overlay-log-new');
    Route::get('/lead/json/log/mod/{log_id}', 'Master\MasterLeadController@overlayLogMod')->name('master.lead.overlay-log-mod');
    Route::get('/lead/json/log/history/{lead_id}', 'Master\MasterLeadController@overlayLogHistory')->name('master.lead.overlay-log-history');

    Route::get('/lead/json/location/new/{lead_id}', 'Master\MasterLeadLocationController@overlayLocationNew')->name('master.lead.overlay-loc-new');
    Route::get('/lead/json/location/mod/{loc_id}', 'Master\MasterLeadLocationController@overlayLocationMod')->name('master.lead.overlay-loc-mod');
    Route::get('/lead/json/location/file/{loc_id}', 'Master\MasterLeadLocationController@overlayLocationFiles'); // lead.overlay-loc-file
    Route::get('/lead/json/account/new/{loc_id}', 'Master\MasterLeadLocationController@overlayAccountNew')->name('master.lead.overlay-accnt-new');
    Route::get('/lead/json/account/mod/{accnt_id}', 'Master\MasterLeadLocationController@overlayAccountMod')->name('master.lead.overlay-accnt-mod');
    Route::get('/lead/json/account/services/{quote_id}', 'Master\MasterLeadLocationController@overlayAccountServices')->name('master.lead.overlay-accnt-svc');
    Route::get('/lead/json/quote/new/{loc_id}', 'Master\MasterLeadLocationController@overlayQuoteNew')->name('master.lead.overlay-quote-new');
    Route::get('/lead/json/quote/mod/{quote_id}', 'Master\MasterLeadLocationController@overlayQuoteMod')->name('master.lead.overlay-quote-mod');

    Route::get('/project/json/customer/mod/{lead_id}', 'Master\MasterProjectController@overlayCustomerMod')->name('master.project.overlay-customer-mod');
    Route::get('/project/json/agency/assign/{lead_id}', 'Master\MasterProjectController@overlayAgencyAssign')->name('master.project.overlay-agency-assign');
    Route::get('/project/json/manager/assign/{lead_id}', 'Master\MasterProjectController@overlayManagerAssign')->name('master.project.overlay-manager-assign');
    Route::get('/project/json/follower/mod/{lead_id}', 'Master\MasterProjectController@overlayFollowerMod')->name('master.project.overlay-follower-mod');
    Route::get('/project/json/commission/{lead_id}', 'Master\MasterProjectController@overlayCommissionMod')->name('master.project.overlay-commission');
    Route::get('/project/json/log/new/{lead_id}', 'Master\MasterProjectController@overlayLogNew')->name('master.project.overlay-log-new');
    Route::get('/project/json/log/mod/{log_id}', 'Master\MasterProjectController@overlayLogMod')->name('master.project.overlay-log-mod');
    Route::get('/project/json/log/history/{lead_id}', 'Master\MasterProjectController@overlayLogHistory')->name('project.overlay-log-history');

    Route::get('/project/json/location/file/{loc_id}', 'Master\MasterProjectController@overlayLocationFiles'); // project.overlay-loc-file
    Route::get('/project/json/keep/product/{accnt_id}', 'Master\MasterProjectController@overlayProductMod')->name('master.project.overlay-keep-prod');
    Route::get('/project/json/cancel/date/{accnt_id}', 'Master\MasterProjectController@overlayCancelDates')->name('master.project.overlay-cancel-date');
    Route::get('/project/json/signed/date/{quote_id}', 'Master\MasterProjectController@overlaySignedDates')->name('master.project.overlay-sign-date');

    /**
     * list of AJAX functions
     */
    Route::post('/lead/json/follower/update/{lead_id}', 'Master\MasterLeadController@ajaxFollowerUpdate')->name('master.lead.ajax-follower-update');
    Route::delete('/lead/json/follower/master/delete/{lead_id}/{user_id}', 'Master\MasterLeadController@ajaxFollowerMasterDelete')
      ->name('master.lead.ajax-follower-master-delete');
    Route::delete('/lead/json/follower/provider/delete/{lead_id}/{order_no}', 'Master\MasterLeadController@ajaxFollowerProviderDelete')
      ->name('master.lead.ajax-follower-provider-delete');
    Route::post('/lead/json/customer/update/{lead_id}', 'Master\MasterLeadController@ajaxCustomerUpdate')->name('master.lead.ajax-customer-update');
    Route::put('/lead/json/log/add/{lead_id}', 'Master\MasterLeadController@ajaxLogAdd')->name('master.lead.ajax-log-add');
    Route::post('/lead/json/log/correct/{log_id}', 'Master\MasterLeadController@ajaxLogCorrect')->name('master.lead.ajax-log-correct');

    Route::put('/lead/json/location/add/{lead_id}', 'Master\MasterLeadLocationController@ajaxLocationAdd')->name('master.lead.ajax-loc-add');
    Route::post('/lead/json/location/update/{loc_id}', 'Master\MasterLeadLocationController@ajaxLocationUpdate')->name('master.lead.ajax-loc-update');
    Route::delete('/lead/json/location/delete/{loc_id}', 'Master\MasterLeadLocationController@ajaxLocationDelete')->name('master.lead.ajax-loc-delete');
    Route::delete('/lead/json/location/file/delete/{file_id}', 'Master\MasterLeadController@ajaxLocationFileDelete'); // lead.ajax-loc-file-del
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

    Route::post('/project/json/customer/update/{lead_id}', 'Master\MasterProjectController@ajaxCustomerUpdate')->name('master.project.ajax-customer-update');
    Route::post('/project/json/follower/update/{lead_id}', 'Master\MasterProjectController@ajaxFollowerUpdate')->name('master.project.ajax-follower-update');
    Route::delete('/project/json/follower/master/delete/{lead_id}/{order_no}', 'Master\MasterProjectController@ajaxFollowerMasterDelete')
      ->name('master.project.ajax-follower-master-delete');
    Route::delete('/project/json/follower/provider/delete/{lead_id}/{order_no}', 'Master\MasterProjectController@ajaxFollowerProviderDelete')
      ->name('master.project.ajax-follower-provider-delete');
    Route::put('/project/json/log/add/{lead_id}', 'Master\MasterProjectController@ajaxLogAdd')->name('master.project.ajax-log-add');
    Route::post('/project/json/log/correct/{log_id}', 'Master\MasterProjectController@ajaxLogCorrect')->name('master.project.ajax-log-correct');
    
    Route::delete('/project/json/location/file/delete/{file_id}', 'Master\MasterProjectController@ajaxLocationFileDelete'); // project.ajax-loc-file-del
    Route::post('/project/json/keep/product/update/{accnt_id}', 'Master\MasterProjectController@ajaxProductUpdate'); // project.ajax-keep-update
    Route::post('/project/json/cancel/date/update/{accnt_id}', 'Master\MasterProjectController@ajaxCancelDateUpdate')->name('master.project.ajax-cancel-update');
    Route::post('/project/json/signed/date/update/{quote_id}', 'Master\MasterProjectController@ajaxSignedDateUpdate')->name('master.project.ajax-sign-update');

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
    Route::post('/lead/commission/update/{lead_id}', 'Master\MasterLeadController@commissionUpdate')->name('master.lead.commission-update');
    Route::post('/lead/location/file/attach/{loc_id}', 'Master\MasterLeadController@locationFileAttach'); // lead.loc-file-attach
    Route::post('/lead/account/proceed/{accnt_id}', 'Master\MasterLeadLocationController@accountProceed')->name('master.lead.accnt-proceed');
    Route::post('/lead/quote/sign/{quote_id}', 'Master\MasterLeadLocationController@quoteSign')->name('master.lead.quote-sign');

    Route::post('/project/manager/assign/{lead_id}', 'Master\MasterProjectController@managerAssign')->name('master.project.manager-assign');
    Route::post('/project/manager/primary/{lead_id}/{manager_id}', 'Master\MasterProjectController@managerSetPrimary')->name('master.project.manager-primary');
    Route::post('/project/manager/remove/{lead_id}/{manager_id}', 'Master\MasterProjectController@managerRemove')->name('master.project.manager-remove');
    Route::post('/project/commission/update/{lead_id}', 'Master\MasterProjectController@commissionUpdate')->name('master.project.commission-update');
    Route::post('/project/location/file/attach/{loc_id}', 'Master\MasterProjectController@locationFileAttach'); // project.loc-file-attach

    Route::post('/project/account/complete/{accnt_id}', 'Master\MasterProjectController@accountComplete'); // project.accnt-complete
    Route::post('/project/account/complete/undo/{accnt_id}', 'Master\MasterProjectController@accountCompleteUndo'); // project.accnt-complete-undo
    Route::delete('/project/account/revert/{accnt_id}', 'Master\MasterProjectController@accountRevert'); // project.accnt-revert
    Route::post('/project/signed/complete/{quote_id}', 'Master\MasterProjectController@signedComplete'); // project.sign-complete
    Route::post('/project/signed/complete/undo/{quote_id}', 'Master\MasterProjectController@signedCompleteUndo'); // project.sign-complete-undo
    Route::delete('/project/signed/revert/{quote_id}', 'Master\MasterProjectController@signedRevert'); // project.sign-revert
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
    Route::get('/lead/manage/{id}/{alert?}', 'LeadController@manage')->name('lead.manage');
    Route::get('/lead/report/current/{id}', 'LeadReportController@current')->name('lead.rpt.current');
    Route::get('/lead/report/quote/{id}', 'LeadReportController@quote')->name('lead.rpt.quote');

    Route::get('/projects', 'ProjectController@list')->name('project.list');
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
    Route::get('/datatables/projects/signed', 'DataTablesController@projectsSigned')->name('datatables.projects-sign');
    Route::get('/datatables/projects/keep', 'DataTablesController@projecstKeep')->name('datatables.projects-keep');
    Route::get('/datatables/projects/cancel', 'DataTablesController@projectsCancel')->name('datatables.projects-cancel');

    /**
     * list of overlay for ingenOverlay.js
     */
    /*
    Route::post('/customer/overlay/contact/new/{id}', 'CustomerController@overlayNewContact')->name('customer.overlay-contact-new');
    Route::post('/customer/overlay/contact/mod/{id}', 'CustomerController@overlayModContact')->name('customer.overlay-contact-mod');

    Route::post('/salesperson/overlay/new', 'SalespersonController@overlayNew')->name('salesperson.overlay-new');
    Route::post('/salesperson/overlay/mod/{id}', 'SalespersonController@overlayMod')->name('salesperson.overlay-mod');
    Route::get('/lead/json/customers', 'LeadController@overlayCustomerList')->name('lead.overlay-customer-list');
    Route::get('/lead/json/customer/new', 'LeadController@overlayCustomerNew')->name('lead.overlay-customer-new'); // query: lead_id? 
    */
    Route::get('/lead/json/customer/mod/{lead_id}', 'LeadController@overlayCustomerMod')->name('lead.overlay-customer-mod');
    /*
    Route::get('/lead/json/salespersons', 'LeadController@overlaySalespersonList')->name('lead.overlay-salesperson-list');
    Route::get('/lead/json/salesperson/new', 'LeadController@overlaySalespersonNew')->name('lead.overlay-salesperson-new'); // query: lead_id? 
    Route::get('/lead/json/salesperson/mod/{lead_id}', 'LeadController@overlaySalespersonMod')->name('lead.overlay-salesperson-mod');
    */
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

    Route::get('/project/json/customer/mod/{lead_id}', 'ProjectController@overlayCustomerMod')->name('project.overlay-customer-mod');
    Route::get('/project/json/follower/mod/{lead_id}', 'ProjectController@overlayFollowerMod')->name('project.overlay-follower-mod');
    Route::get('/project/json/log/new/{lead_id}', 'ProjectController@overlayLogNew')->name('project.overlay-log-new');
    Route::get('/project/json/log/mod/{log_id}', 'ProjectController@overlayLogMod')->name('project.overlay-log-mod');
    Route::get('/project/json/log/history/{lead_id}', 'ProjectController@overlayLogHistory')->name('project.overlay-log-history');
    Route::get('/project/json/location/file/{loc_id}', 'ProjectController@overlayLocationFiles')->name('project.overlay-loc-file');
    Route::get('/project/json/keep/product/{accnt_id}', 'ProjectController@overlayProductMod')->name('project.overlay-keep-prod');
    Route::get('/project/json/cancel/date/{accnt_id}', 'ProjectController@overlayCancelDates')->name('project.overlay-cancel-date');
    Route::get('/project/json/signed/date/{quote_id}', 'ProjectController@overlaySignedDates')->name('project.overlay-sign-date');

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
    Route::post('/lead/json/follower/update/{lead_id}', 'LeadController@ajaxFollowerUpdate')->name('lead.ajax-follower-update');
    Route::delete('/lead/json/follower/agent/delete/{lead_id}/{order_no}', 'LeadController@ajaxFollowerAgentDelete')->name('lead.ajax-follower-agent-delete');
    Route::delete('/lead/json/follower/provider/delete/{lead_id}/{order_no}', 'LeadController@ajaxFollowerProviderDelete')->name('lead.ajax-follower-provider-delete');
    Route::put('/lead/json/log/add/{lead_id}', 'LeadController@ajaxLogAdd')->name('lead.ajax-log-add');
    Route::post('/lead/json/log/correct/{log_id}', 'LeadController@ajaxLogCorrect')->name('lead.ajax-log-correct');

    // ALERT 
    Route::post('/lead/ajaxAlertSend/{id}', 'LeadController@ajaxAlertSend')->name('lead.ajax-alert-send');
    Route::get('/home/json/alert/mod', 'HomeController@ajaxAlertGet')->name('home.overlay-alert-mod');   
    Route::get('/alert/manage/{id}/{type}/{alert?}', 'AlertController@manage')->name('alert.manage');
    Route::get('/lead/json/alert/mod/{id}', 'LeadController@overlayAlertMod')->name('lead.overlay-alert-mod');


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

    Route::post('/project/json/customer/update/{lead_id}', 'ProjectController@ajaxCustomerUpdate')->name('project.ajax-customer-update');
    Route::post('/project/json/follower/update/{lead_id}', 'ProjectController@ajaxFollowerUpdate')->name('project.ajax-follower-update');
    Route::delete('/project/json/follower/agent/delete/{lead_id}/{order_no}', 'ProjectController@ajaxFollowerAgentDelete')->name('project.ajax-follower-agent-delete');
    Route::delete('/project/json/follower/provider/delete/{lead_id}/{order_no}', 'ProjectController@ajaxFollowerProviderDelete')->name('project.ajax-follower-provider-delete');
    Route::put('/project/json/log/add/{lead_id}', 'ProjectController@ajaxLogAdd')->name('project.ajax-log-add');
    Route::post('/project/json/log/correct/{log_id}', 'ProjectController@ajaxLogCorrect')->name('project.ajax-log-correct');
    
    Route::delete('/project/json/location/file/delete/{file_id}', 'ProjectController@ajaxLocationFileDelete')->name('project.ajax-loc-file-del');
    Route::post('/project/json/keep/product/update/{accnt_id}', 'ProjectController@ajaxProductUpdate')->name('project.ajax-keep-update');
    Route::post('/project/json/cancel/date/update/{accnt_id}', 'ProjectController@ajaxCancelDateUpdate')->name('project.ajax-cancel-update');
    Route::post('/project/json/signed/date/update/{quote_id}', 'ProjectController@ajaxSignedDateUpdate')->name('project.ajax-sign-update');

    /**
    * list of FORM (put/post/delete) action
    **/
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
    Route::post('/lead/location/file/attach/{loc_id}', 'LeadLocationController@locationFileAttach')->name('lead.loc-file-attach');
    Route::post('/lead/account/proceed/{accnt_id}', 'LeadLocationController@accountProceed')->name('lead.accnt-proceed');
    Route::post('/lead/quote/sign/{quote_id}', 'LeadLocationController@quoteSign')->name('lead.quote-sign');

    Route::post('/project/location/file/attach/{loc_id}', 'ProjectController@locationFileAttach')->name('project.loc-file-attach');
    Route::post('/project/account/complete/{accnt_id}', 'ProjectController@accountComplete')->name('project.accnt-complete');
    Route::post('/project/account/complete/undo/{accnt_id}', 'ProjectController@accountCompleteUndo')->name('project.accnt-complete-undo');
    Route::delete('/project/account/revert/{accnt_id}', 'ProjectController@accountRevert')->name('project.accnt-revert');
    Route::post('/project/signed/complete/{quote_id}', 'ProjectController@signedComplete')->name('project.sign-complete');
    Route::post('/project/signed/complete/undo/{quote_id}', 'ProjectController@signedCompleteUndo')->name('project.sign-complete-undo');
    Route::delete('/project/signed/revert/{quote_id}', 'ProjectController@signedRevert')->name('project.sign-revert');
    /*
    Route::post('/lead/update/{id}', 'LeadController@update')->name('lead.update');
    Route::delete('/lead/delete/{id}', 'LeadController@delete')->name('lead.delete');
    */
  });
});
