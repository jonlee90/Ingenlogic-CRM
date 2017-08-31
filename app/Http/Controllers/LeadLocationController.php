<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LeadController;

use App\Agency;
use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class LeadLocationController extends LeadController
{
  // traits
  use GetLead;

  // custom variables
  private $log_src = 'LeadLocationController';


  /**
  * ******************************************************* lead x location *******************************************************
  *
  * output JSON for ingenOverlay: new location
  *
  * @param lead_id: lead ID encoded
  */
  public function overlayLocationNew(Request $request)
  {
    $log_src = $this->log_src.'@overlayLocationNew';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);
    $lead_id = dec_id($request->lead_id);

    $row_states = get_state_list();
    
		$html_output =
      Form::open(['url'=> route('lead.ajax-loc-add', ['lead_id'=> $request->lead_id]), 'class'=>'frm-add', 'method'=> 'PUT']).
        view('leads.form-loc')
          ->with('data', (object)['row_states'=> $row_states,])
          ->with('loc', (object)[
              'name'=>'', 'addr'=>'', 'addr2'=>'', 'city'=>'', 'state_id'=>'', 'zip'=>'',
            ])
          ->render().'
            
        <div class="btn-group">
          '.Form::submit('Add New').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>

      '.Form::close().'
      <script>aoLocationAdd()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  /**
  * output JSON for ingenOverlay: update location (name/address)
  *
  * @param loc_id: location ID encoded
  */
  public function overlayLocationMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayLocationMod';

    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id]);

    $row_states = get_state_list();
    

		$html_output =
      Form::open(['url'=> route('lead.ajax-loc-update', ['loc_id'=> $request->loc_id]), 'class'=>'frm-update']).
        view('leads.form-loc')
          ->with('data', (object)['row_states'=> $row_states,])
          ->with('loc', (object)[
              'name'=> $loc->name, 'addr'=> $loc->addr, 'addr2'=> $loc->addr2, 'city'=> $loc->city, 'state_id'=> $loc->state_id, 'zip'=> $loc->zip,
            ])
          ->render().'
            
        <div class="btn-group">
          '.Form::submit('Update Information').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>

      '.Form::close().'
      <script>oAlL2()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }

  /**
  * Action: create new location => link with lead => output location data in JSON.
  *
  * @param lead_id: lead ID encoded
  */
  public function ajaxLocationAdd (Request $request)
  {
    $log_src = $this->log_src.'@ajaxLocationAdd';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);
      
    
    // input validation
    $v = Validator::make($request->all(), [
      'l_name' => 'required',
      'state_id' => 'nullable|numeric',
      'zip' => ['nullable', 'max:10', 'regex:/^\d{5}(-\d{4})?$/'],
    ], [
      'l_name.*'=> 'Location Name is required.',
      'state_id.*'=> 'Invalid State ID entered.',
      'zip.*'=> 'Please use a valid US Zip code.',
    ]);
    if ($v->fails()) {
      $errs = $v->errors()->all();
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';

		  return json_encode([
        'success'=>0, 'error'=>1,
        'msg'=> $msg,
      ]);
    }

    $p_addr = ($request->addr)?  $request->addr : '';
    $p_addr2 = ($request->addr2)?  $request->addr2 : '';
    $p_city = ($request->city)?  $request->city : '';
    if ($request->state_id) {
      $p_state_id = $request->state_id;

      $state = DB::table('states')->find($p_state_id);
      $state_code = $state->code;
    } else {
      $p_state_id = 0;
      $state_code = '';
    }
    $p_zip = ($request->zip)?  $request->zip : '';


    // validation passed -> create new location (Query Builder)
    $loc_id = DB::table('lead_locations')->insertGetId([
      'lead_id'=> $lead_id,
      'name'=> $request->l_name,
      'addr' => $p_addr,
      'addr2' => $p_addr2,
      'city' => $p_city,
      'state_id' => $p_state_id,
      'zip' => $p_zip,
    ]);
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>New Location has been added.</p><p>[Location] '.$request->l_name.'</p>'
    ]);
    log_write('Lead x Location Created.', ['src'=> $log_src, 'agency-id'=>$agency_id, 'lead-id'=> $lead_id, 'new-location-id'=> $loc_id, 'log-id'=> $log_id, ]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> enc_id($loc_id),
    ]);
  }
  /**
  * Action: update location info => output location data in JSON.
  *
  * @param loc_id: location ID encoded
  */
  public function ajaxLocationUpdate (Request $request)
  {
    $log_src = $this->log_src.'@ajaxLocationUpdate';

    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);
    
    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id]);
      
    
    // input validation
    $v = Validator::make($request->all(), [
      'l_name' => 'required',
      'state_id' => 'nullable|numeric',
      'zip' => ['nullable', 'max:10', 'regex:/^\d{5}(-\d{4})?$/'],
    ], [
      'l_name.*'=> 'Location Name is required.',
      'state_id.*'=> 'Invalid State ID entered.',
      'zip.*'=> 'Please use a valid US Zip code.',
    ]);
    if ($v->fails()) {
      $errs = $v->errors()->all();
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';

		  return json_encode([
        'success'=>0, 'error'=>1,
        'msg'=> $msg,
      ]);
    }

    $p_addr = ($request->addr)?  $request->addr : '';
    $p_addr2 = ($request->addr2)?  $request->addr2 : '';
    $p_city = ($request->city)?  $request->city : '';
    $p_state_id = ($request->state_id)?  $request->state_id : 0;
    $p_zip = ($request->zip)?  $request->zip : '';


    // create logging detail object -> leave lead x log
    $state = DB::table('states')->find($loc->state_id);
    $state_code = ($state)?  $state->code : '';
    $city_state = $loc->city;
    $city_state .= ($city_state && $state_code)?  ', '.$state_code : $state_code;
    $old_addr = '<p>'.$loc->addr.'</p><p>'.$loc->addr2.'</p><p>'.trim($city_state.' '.$loc->zip).'</p>';

    $state = DB::table('states')->find($p_state_id);
    $state_code = ($state)?  $state->code : '';
    $city_state = $p_city;
    $city_state .= ($city_state && $state_code)?  ', '.$state_code : $state_code;
    $new_addr = '<p>'.$p_addr.'</p><p>'.$p_addr2.'</p><p>'.trim($city_state.' '.$p_zip).'</p>';

    $log_detail_values = [
      (object)['field'=> 'Name', 'old'=> $loc->name, 'new'=> $request->l_name],
      (object)['field'=> 'Address', 'old'=> $old_addr, 'new'=> $new_addr],
    ];


    // validation passed -> update location (Query Builder)
    DB::update(" UPDATE lead_locations SET name =?, addr =?, addr2 =?, city =?, state_id =?, zip =? WHERE id =? ", [
      $request->l_name, $p_addr, $p_addr2, $p_city, $p_state_id, $p_zip,  $loc_id
    ]);
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Location has been updated.</p><p>[Location] '.$request->l_name.'</p>',
      'detail' => $log_detail_values,
    ]);
    log_write('Lead x Location Updated.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'log-id'=> $log_id, ]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> $request->loc_id,
    ]);
  }
  /**
  * Action: delete location info
  *
  * @param loc_id: location ID encoded
  */
  public function ajaxLocationDelete (Request $request)
  {
    $log_src = $this->log_src.'@ajaxLocationDelete';

    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);
    
    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id]);
      

    // validation passed -> delete location (Query Builder)
    DB::update(" DELETE FROM lead_locations WHERE id =? ", [$loc_id]);
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Location has been removed.</p><p>[Location] '.$loc->name.'</p>',
    ]);
    log_write('Lead x Location Deleted.', ['src'=> $log_src, 'agency-id'=>$agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'log-id'=> $log_id, ]);
    return $this->jsonReload($lead_id, $agency_id);
  }


  /**
  * ******************************************************* lead x location x account *******************************************************
  *
  * output JSON for ingenOverlay: new account
  *
  * @param loc_id: location ID encoded
  */
  public function overlayAccountNew(Request $request)
  {
    $log_src = $this->log_src.'@overlayAccountNew';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);
    
    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id]);
    

    // get names of provider
    $providers = DB::select(" SELECT name FROM providers  WHERE active =1 ");

		$html_output =
      Form::open(['url'=> route('lead.ajax-accnt-add', ['loc_id'=> $request->loc_id]), 'class'=>'frm-add', 'method'=> 'PUT']).
        view('leads.form-accnt')
          ->with('providers', $providers)
          ->with('accnt', (object)[
              'name'=>'', 'accnt_no'=>'', 'passcode'=>'', 'term'=> 1, 'date_contract_end'=>'', 'etf'=> 0, 'memo'=> '',
            ])
          ->render().'
            
        <div class="btn-group">
          '.Form::submit('Add New').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>

      '.Form::close().'
      <script>aoCurrentNew()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  /**
  * output JSON for ingenOverlay: update account info -> provider + MRC (monthly)
  *
  * @param accnt_id: account ID encoded
  */
  public function overlayAccountMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayAccountMod';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    // currently saved account x products
    $accnt->products = DB::select(" SELECT svc_name, prod_name, memo, price, qty  FROM lead_current_products  WHERE account_id =:accnt_id  ORDER BY order_no", [$accnt_id]);


    // get names of provider
    $providers = DB::select(" SELECT name FROM providers  WHERE active =1 ");

    // get names of existing services: child services only
    $services = DB::select(" SELECT name FROM services WHERE id <> parent_id ");


		$html_output =
      Form::open(['url'=> route('lead.ajax-accnt-update', ['accnt_id'=> $request->accnt_id]), 'class'=>'frm-accnt']).
        view('leads.form-accnt')
          ->with('providers', $providers)
          ->with('accnt', (object)[
              'name'=> $accnt->provider_name, 'accnt_no'=> $accnt->accnt_no, 'passcode'=> $accnt->passcode,
              'term'=> $accnt->term, 'date_contract_end'=> $accnt->date_contract_end, 'etf'=> $accnt->etf, 'memo'=> $accnt->memo,
            ])
          ->render().'
            
        <div class="btn-group">
          '.Form::submit('Update Information').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>
      '.Form::close().

      view('leads.form-accnt-prod')
        ->with('services', $services)
        ->with('accnt', $accnt)
        ->render().'

      <script>aoCurrentUpdate()</script>
		';
    
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }

  /**
  * Action: create new account => link with lead x location => output data in JSON.
  *
  * @param loc_id: location ID encoded
  */
  public function ajaxAccountAdd (Request $request)
  {
    $log_src = $this->log_src.'@ajaxAccountAdd';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);
    
    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id]);
      

    // input validation
    $v = Validator::make($request->all(), [
      'p_name' => 'required',
      'accnt_no' => 'required',
      'term' => ['nullable', 'min:1', 'regex:/^\d+$/'],
      'date_end' => 'nullable|date_format:Y-m-d',
      'etf' => 'nullable|numeric|min:0',
      'memo' => 'nullable|max:500',
    ], [
      'p_name.*'=> 'Service Provider Name is required.',
      'accnt_no.*'=> 'Account Number is required.',
      'term.*'=> 'Please enter the term in months. Use 0 for "Month to Month".',
      'date_end.*'=> 'Invalid Date format entered.',
      'etf.*'=> 'Early Termination Fee should be decimal of 0 or greater.',
      'memo.*'=> 'Memo cannot exceed 500 characters',
    ]);
    if ($v->fails()) {
      $errs = $v->errors()->all();
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';

		  return json_encode([
        'success'=>0, 'error'=>1,
        'msg'=> $msg,
      ]);
    }
    

    $p_pass = ($request->passcode)?  $request->passcode : '';
    $p_term = ($request->term)?  $request->term : 1;
    $p_end = ($request->date_end)?  $request->date_end : NULL;
    $p_etf = ($request->etf)?  $request->etf : 0;
    $p_memo = ($request->memo)?  $request->memo : '';

    // validation passed -> create new location (Query Builder)
    $accnt_id = DB::table('lead_current_accounts')->insertGetId([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
      'location_id'=> $loc_id,
      'provider_name'=> $request->p_name,
      'accnt_no'=> $request->accnt_no, 'passcode'=> $p_pass,
      'term'=> $p_term,
      'date_contract_end'=> $p_end,
      'etf' => $p_etf,
      'memo' => $p_memo,
    ]);

      
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>New Current Account has been added.</p><p>[Location] '.$loc->name.' x [Account] '.$request->p_name.'</p>'
    ]);
    log_write('Lead x Location x Account Created.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
    ]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> $request->loc_id, 'accntId'=> enc_id($accnt_id),
    ]);
  }
  /**
  * Action: toggle account to selected/not-selected
  *
  * @param accnt_id: account ID encoded
  */
  public function ajaxAccountToggle (Request $request)
  {
    $log_src = $this->log_src.'@ajaxAccountToggle';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
      

    $p_select = ($accnt->is_selected)?  0 : 1;
    $log_select_txt = ($p_select)?  'Added to the Report' : 'Removed from the Report';

    // validation passed -> update account (Query Builder)
    DB::update(" UPDATE lead_current_accounts SET mod_id =?, mod_user =?,  is_selected =? WHERE id =? ", [
      $me->id, trim($me->fname.' '.$me->lname),  $p_select,  $accnt_id
    ]);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Current Account has been '.$log_select_txt.'.</p><p>[Location] '.$loc->name.' x [Account] '.$accnt->provider_name.'</p>'
    ]);
    log_write('Lead x Location x Account toggled.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'toggle'=> $p_select, 'log-id'=> $log_id,
    ]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> enc_id($loc_id),
    ]);
  }
  /**
  * Action: update account => output data in JSON.
  *
  * @param accnt_id: account ID encoded
  */
  public function ajaxAccountUpdate (Request $request)
  {
    $log_src = $this->log_src.'@ajaxAccountUpdate';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
      

    // input validation
    $validate_vars = [
      'p_name' => $request->p_name,
      'accnt_no' => $request->accnt_no,
      'term' => $request->term,
      'date_end' => $request->date_end,
      'etf' => $request->etf,
      'memo' => str_replace("\r\n", "\n", $request->memo), // replace \r\n to \n: match string length as in javascript
    ];

    $v = Validator::make($validate_vars, [
      'p_name' => 'required',
      'accnt_no' => 'required',
      'term' => ['nullable', 'min:1', 'regex:/^\d+$/'],
      'date_end' => 'nullable|date_format:Y-m-d',
      'etf' => 'nullable|numeric|min:0',
      'memo' => 'nullable|max:500',
    ], [
      'p_name.*'=> 'Service Provider Name is required.',
      'accnt_no.*'=> 'Account Number is required.',
      'term.*'=> 'Please enter the term in months. Use 0 for "Month to Month".',
      'date_end.*'=> 'Invalid Date format entered.',
      'etf.*'=> 'Early Termination Fee should be decimal of 0 or greater.',
      'memo.*'=> 'Memo cannot exceed 500 characters',
    ]);
    if ($v->fails()) {
      $errs = $v->errors()->all();
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';

		  return json_encode([
        'success'=>0, 'error'=>1,
        'msg'=> $msg,
      ]);
    }

    $p_pass = ($request->passcode)?  $request->passcode : '';
    $p_term = ($request->term)?  $request->term : 1;
    $p_end = ($request->date_end)?  $request->date_end : NULL;
    $p_etf = ($request->etf)?  $request->etf : 0;
    $p_memo = ($request->memo)?  $request->memo : '';

    // create logging detail object -> leave lead x log
    $log_detail_values = [
      (object)['field'=> 'Provider Name', 'old'=> $accnt->provider_name, 'new'=> $request->p_name],
      (object)['field'=> 'Account #', 'old'=> $accnt->accnt_no, 'new'=> $request->accnt_no],
      (object)['field'=> 'Passcode', 'old'=> $accnt->passcode, 'new'=> $p_pass],
      (object)['field'=> 'Terms', 'old'=> $accnt->term, 'new'=> $p_term],
      (object)['field'=> 'Contract End', 'old'=> $accnt->date_contract_end, 'new'=> $p_end],
      (object)['field'=> 'ETF', 'old'=> $accnt->etf, 'new'=> $p_etf],
      (object)['field'=> 'Memo', 'old'=> $accnt->memo, 'new'=> $p_memo],
    ];


    // validation passed -> update account (Query Builder)
    DB::update(
      " UPDATE lead_current_accounts SET
            mod_id =?, mod_user =?,
            provider_name =?, accnt_no =?, passcode =?, term =?, date_contract_end =?, etf =?, memo =?
          WHERE id =? 
    ", [
      $me->id, trim($me->fname.' '.$me->lname),
      $request->p_name, $request->accnt_no, $p_pass, $p_term, $p_end, $p_etf, $p_memo,
      $accnt_id
    ]);

    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Current Account has been updated.</p><p>[Location] '.$loc->name.' x [Account] '.$accnt->provider_name.'</p>',
      'detail' => $log_detail_values,
    ]);
    log_write('Lead x Location x Account Updated.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id' => $log_id,
    ]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> enc_id($loc_id), 'accntId'=> $request->accnt_id,
    ]);
  }
  /**
  * Action: update account x products (monthly rate) => output data in JSON.
  *
  * @param accnt_id: account ID encoded
  */
  public function ajaxAccountMRC (Request $request)
  {
    $log_src = $this->log_src.'@ajaxAccountMRC';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Current Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);


    // at least 1 product is required, all POST arrays should have same count
    $n_prods = count($request->prod);
    if (!($n_prods >0))
      return log_ajax_err('One or more Products are required.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id
      ]);
    if ($n_prods != count($request->svc) || $n_prods != count($request->memo) || $n_prods != count($request->price) || $n_prods != count($request->qty))
      return log_ajax_err('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id
      ]);

    // input validation
    $v = Validator::make($request->all(), [
      'svc.*' => 'required',
      'prod.*' => 'required',
      'price.*' => 'required|numeric',
      'qty.*' => 'required|integer',
    ], [
      'svc.*'=> 'Service Name is required for all rows.',
      'prod.*'=> 'Product Name is required for all rows.',
      'price.*'=> 'Please enter a valid price.',
      'qty.*'=> 'Please enter a valid quantity.',
    ]);
    if ($v->fails()) {
      $errs_tmp = $v->errors()->all();
      $errs = [];
      // filter out duplicate error message(s) - since all fields are array, same error can occur multiple times
      foreach ($errs_tmp as $r) {
        if (!in_array($r, $errs))
          $errs[] = $r;
      }
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';
        
		  return json_encode([
        'success'=>0, 'error'=>1,
        'msg'=> $msg,
      ]);
    }
    
    // validation passed -> reset account x products (= delete existing and add products), also create logging detail object -> leave lead x log
    $old_prods = DB::select(
      " SELECT svc_name, prod_name, memo, price, qty  FROM lead_current_products  WHERE account_id =:accnt_id  ORDER BY order_no
    ", [$accnt_id]);

    $db_insert_params = $new_prods = [];

    for ($i =0; $i < $n_prods; $i++) {
      $prod_memo = ($request->memo[$i])?  $request->memo[$i] : '';
      
      $db_insert_params[] = [
        'account_id'=> $accnt_id,
        'order_no'=> $i,
        'svc_name'=> $request->svc[$i],
        'prod_name'=> $request->prod[$i],
        'memo'=> $prod_memo,
        'price'=> $request->price[$i],
        'qty'=> $request->qty[$i],
      ];

      $new_prods[] = (object)[
        'svc_name'=> $request->svc[$i],
        'prod_name'=> $request->prod[$i],
        'memo'=> $prod_memo,
        'price'=> $request->price[$i],
        'qty'=> $request->qty[$i],
      ];
    }


    DB::table('lead_current_products')->whereRaw(" account_id =:accnt_id ", [$accnt_id])->delete();
    DB::table('lead_current_products')->insert($db_insert_params);
    
    // action SUCCESS: leave a log and output JSON
    $log_id = $this->log_lead_prods((object) [
      'id' => $lead_id, 
      'msg' => '<p>Current Account Product(s) have been updated.</p><p>[Location] '.$loc->name.' x [Account] '.$accnt->provider_name.'</p>',
      'old_prods' => $old_prods, 'new_prods' => $new_prods,
    ]);
    log_write('Lead x Location x Account x Products Updated.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
    ]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> enc_id($loc_id), 'accntId'=> $request->accnt_id,
    ]);
  }
  /**
  * Action: delete current account
  *
  * @param accnt_id: account ID encoded
  */
  public function ajaxAccountDelete (Request $request)
  {
    $log_src = $this->log_src.'@ajaxAccountDelete';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
      

    // validation passed -> delete account and products associated (Query Builder)
    DB::table('lead_current_accounts')->whereRaw(" id =:accnt_id ", [$accnt_id])->delete();
    DB::table('lead_current_products')->whereRaw(" account_id =:accnt_id ", [$accnt_id])->delete();
 
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Current Account has been deleted.</p><p>[Location] '.$loc->name.' x [Account] '.$accnt->provider_name.'</p>',
    ]);
    log_write('Lead x Location x Account Deleted.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> enc_id($loc_id),
    ]);
  }
  

  /**
  * Action: mark account as project -> redirect to project-management page
  *
  * @param accnt_id: account ID encoded
  */
  public function accountProceed (Request $request)
  {
    $log_src = $this->log_src.'@accountProceed';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
      

    // reset any updated products
    DB::table('lead_current_updated_products')->where('account_id',$accnt_id)->delete();
    
    // if proceeded account is to be-kept, reset and create updated-products
    if ($accnt->is_selected) {
      $products = DB::table('lead_current_products')->where('account_id', $accnt_id)->get();
      if ($products) {
        $n = count($products);
        $db_insert_params = [];
        for ($i =0; $i < $n; $i++) {
          $prod = $products[$i];
          $db_insert_params[] = [
            'account_id'=> $accnt_id, 'order_no'=> ($i +1),
            'svc_name' => $prod->svc_name,
            'prod_name' => $prod->prod_name,
            'price' => $prod->price, 'qty' => $prod->qty,
          ];
        }
        DB::table('lead_current_updated_products')->insert($db_insert_params);
      }
    }
    DB::table('lead_current_accounts')->where('id', $accnt_id)->update([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname), 'is_project'=> DB::raw(1),
    ]);
      
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Current Account has been added to Project Management.</p><p>[Location] '.$loc->name.' x [Account] '.$accnt->provider_name.'</p>'
    ]);
    log_write('Current Account added to Project Management.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id,
      'account-selected'=> $accnt->is_selected, 'log-id'=> $log_id,
    ]);
    return msg_redirect('Current Account has been added to Project Management.', route('project.manage', ['lead_id'=> enc_id($lead_id),]));
  }


  /**
  * ******************************************************* lead x location x quote *******************************************************
  *
  * output JSON for ingenOverlay: new quote
  *
  * @param loc_id: location ID encoded
  */
  public function overlayQuoteNew(Request $request)
  {
    $log_src = $this->log_src.'@overlayQuoteNew';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);
    
    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id]);
    
    
    $providers = DB::select(" SELECT id, name, default_spiff, default_residual FROM providers  WHERE active =1  ORDER BY name, id DESC ");
    if (!$providers)
      return log_ajax_err('There are No service providers to select from.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id ]);
    
    
		$html_output =
      Form::open(['url'=> route('lead.ajax-quote-add', ['loc_id'=> $request->loc_id]), 'class'=>'frm-add', 'method'=> 'PUT']).
        view('leads.form-quote')
          ->with('providers', $providers)
          ->with('data', (object)[
            'provider_id'=> 0, 'name'=>'', 
            'default_spiff' => 0, 'default_residual' => 0, 'agent_spiff'=> 0, 'agent_residual'=> 0,
          ])
          ->with('quote', (object)[ 'term'=> 1, 'date_contract_end'=>'', ])
          ->render().'
            
        <div class="btn-group">
          '.Form::submit('Add New').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>

      '.Form::close().'
      <script>aoQuoteCreate()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  /**
  * output JSON for ingenOverlay: update quote info -> provider + MRC (monthly) + NRC (one-time)
  *
  * @param quote_id: quote ID encoded
  */
  public function overlayQuoteMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayQuoteMod';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = $this->getQuote($quote_id, $agency_id);
    if (!$quote)
      return log_ajax_err('Quote Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $agency = Agency::find($agency_id);
    $quote->spiff_expect = $agency->spiff;
    $quote->resid_expect = $agency->residual;
    
    $quote->spiff_total = $quote->resid_total = 0;
    $rec = DB::table('lead_quote_mrc_products')
      ->select(DB::raw('SUM(price * qty * spiff_rate /100) spiff, SUM(price * qty * residual_rate /100) residual'))
      ->where('quote_id', [$quote_id])
      ->first();
    if ($rec) {
      $quote->spiff_total = $rec->spiff;
      $quote->resid_total = $rec->residual;
    }

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    // validate: check provider -> get commission rates (spiff and residual) and ratio between master and agency
    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'provider-id'=> $quote->provider_id
      ]);
    
    // currently saved quote x MRC products
    $quote->mrc_prods = DB::select(
      " SELECT p.product_id, p.memo, p.price, p.qty, p.spiff_rate, p.residual_rate,
            IF(pp.id >0 AND s.id >0 ,1,0) AS valid,
            IF(pp.id >0, pp.p_name, p.prod_name) AS prod_name,
            IF(s.id >0, s.name, p.svc_name) AS svc_name 
          FROM lead_quote_mrc_products p
            LEFT JOIN provider_products pp ON p.product_id =pp.id AND pp.provider_id =:provider_id
            LEFT JOIN services s ON pp.service_id =s.id
          WHERE p.quote_id =:quote_id
          ORDER BY valid, p.order_no
    ", [$quote->provider_id, $quote_id]);

    // currently saved quote x NRC products
    $quote->nrc_prods = DB::select(
      " SELECT p.product_id, p.memo, p.price, p.qty,
            IF(pp.id >0 AND s.id >0 ,1,0) AS valid,  IF(pp.id >0, pp.p_name, p.prod_name) AS prod_name,  IF(s.id >0, s.name, p.svc_name) AS svc_name 
          FROM lead_quote_nrc_products p
            LEFT JOIN provider_products pp ON p.product_id =pp.id AND pp.provider_id =:provider_id
            LEFT JOIN services s ON pp.service_id =s.id
          WHERE p.quote_id =:quote_id
          ORDER BY valid, p.order_no
    ", [$quote->provider_id, $quote_id]);


    // get product list (serviced by current provider)
    $products = DB::select(
      " SELECT p.id, p.p_name, p.price, p.rate_spiff, p.rate_residual,  s.name AS svc_name
          FROM provider_products p LEFT JOIN services s ON p.service_id =s.id
          WHERE p.provider_id =:prov_id
          ORDER BY svc_name, name, id DESC
    ", [$quote->provider_id]);
    

		$html_output =
      Form::open(['url'=> route('lead.ajax-quote-update', ['quote_id'=> $request->quote_id]), 'class'=>'frm-quote']).
        view('leads.form-quote')
          ->with('providers', [])
          ->with('data', (object)[
            'provider_id'=> $quote->provider_id, 'name'=> $prov->name,
            'default_spiff' => $prov->default_spiff, 'default_residual' => $prov->default_residual,
            // 'agent_spiff'=> $quote->spiff, 'agent_residual'=> $quote->residual,
          ])
          ->with('quote', $quote)
          ->render().'
            
        <div class="btn-group">
          '.Form::submit('Update Information').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>
      '.Form::close().

      view('leads.form-quote-prod')
        ->with('products', $products)
        ->with('quote', $quote)
        ->render().'

      <script>aoQuoteUpdate()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }

  /**
  * Action: create new quote => link with lead x location => output data in JSON.
  *
  * @param loc_id: location ID encoded
  */
  public function ajaxQuoteAdd (Request $request)
  {
    $log_src = $this->log_src.'@ajaxQuoteAdd';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);
    
    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id]);

    // get agency ID and user ID (test if user is valid channel-managers) assigned to the lead
    $lead_agencies = DB::table('lead_relation_agency')->whereRaw(" lead_id =:lead_id ", [$lead_id]);
    $lead_managers = DB::select(
      " SELECT u.*
          FROM lead_relation_manager lm LEFT JOIN login_users u ON lm.user_id =u.id
          WHERE lm.lead_id =:lead_id AND u.id >0 AND u.access_lv =:ch_manager_lv
          ORDER BY u.fname, u.lname, u.email, u.id DESC    
    ", [$lead_id, POS_LV_CH_MANAGER, ]);
    

    // input validation
    $v = Validator::make($request->all(), [
      'prov_id' => 'required',
      'term' => ['nullable', 'min:1', 'regex:/^\d+$/'],
    ], [
      'prov_id.*'=> 'Provider should be selected.',
      'term.*'=> 'Please enter the term in months. Use 0 for "Month to Month".',
    ]);
    if ($v->fails()) {
      $errs = $v->errors()->all();
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';

		  return json_encode([
        'success'=>0, 'error'=>1,
        'msg'=> $msg,
      ]);
    }

    // validate: check provider -> get commission rates (spiff and residual) and ratio between master(s) and agency(s)
    $prov_id = dec_id($request->prov_id);
    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'provider-id'=> $prov_id]);

    $p_term = ($request->term)?  $request->term : 1;
    $p_end = ($request->date_end)?  $request->date_end : NULL;


    // validation passed -> create new quote -> create new quote x commission rates (Query Builder)
    $quote_id = DB::table('lead_quotes')->insertGetId([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
      'location_id'=> $loc_id,
      'provider_id'=> $prov_id,
      'term'=> $p_term,
      'date_contract_end'=> $p_end,
    ]);
    
      
    // action SUCCESS: leave a log and output JSON (lead reload)
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>New Quote has been added.</p><p>[Location] '.$loc->name.' x [Quote Provider] '.$prov->name.'</p>'
    ]);
    log_write('Lead x Location x Quote Created.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> enc_id($loc_id), 'quoteId' => enc_id($quote_id),
    ]);
  }
  /**
  * Action: toggle quote to selected/not-selected
  *
  * @param quote_id: quote ID encoded
  */
  public function ajaxQuoteToggle (Request $request)
  {
    $log_src = $this->log_src.'@ajaxQuoteToggle';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Quote Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    // get provider name for lead-logging
    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

      
    $p_select = ($quote->is_selected)?  0 : 1;

    // validation passed -> update quote (Query Builder)
    DB::update(" UPDATE lead_quotes SET mod_id =?, mod_user =?,  is_selected =? WHERE id =? ", [
      $me->id, trim($me->fname.' '.$me->lname),  $p_select,  $quote_id
    ]);
    
    // action SUCCESS: leave a log and output JSON (lead reload)
    $log_select = ($p_select)?  'added to the Selected Quotes' : 'removed from the Selected Quotes';
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Quote has been '.$log_select.'</p><p>[Location] '.$loc->name.' x [Quote Provider] '.$prov->name.'</p>'
    ]);
    log_write('Lead x Location x Quote toggled.', ['src'=> $log_src, 'agency-id'=>$agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'toggle'=> $p_select]);
    return $this->jsonReload($lead_id, $agency_id, ['locId'=> enc_id($loc_id)]);
  }
  /**
  * Action: update quote => output data in JSON.
  *
  * @param quote_id: quote ID encoded
  */
  public function ajaxQuoteUpdate (Request $request)
  {
    $log_src = $this->log_src.'@ajaxQuoteUpdate';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Quote Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);
      
    // get provider name for lead-logging
    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);
      

    // input validation
    $v = Validator::make($request->all(), [
      'term' => ['nullable', 'min:1', 'regex:/^\d+$/'],
    ], [
      'term.*'=> 'Please enter the term in months. Use 0 for "Month to Month".',
    ]);
    if ($v->fails()) {
      $errs = $v->errors()->all();
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';

		  return json_encode([
        'success'=>0, 'error'=>1,
        'msg'=> $msg,
      ]);
    }

    $p_term = ($request->term)?  $request->term : 1;
    
    // validation passed -> update quote
    DB::update(" UPDATE lead_quotes  SET mod_id =?, mod_user =?, term =?  WHERE id =? ", [
      $me->id, trim($me->fname.' '.$me->lname),  $p_term,  $quote_id
    ]);

      
    // action SUCCESS: leave a log and output JSON (lead reload)
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Quote Information has been updated.</p><p>[Location] '.$loc->name.' x [Quote Provider] '.$prov->name.'</p>',
      'detail' => [ (object)['field'=> 'Terms', 'old'=> $quote->term, 'new'=> $p_term ] ],
    ]);
    log_write('Lead x Location x Quote Updated.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, ]);
    return $this->jsonReload($lead_id, $agency_id, ['locId'=> enc_id($loc_id)]);
  }
  /**
  * Action: update quote x products (MRC) => output data in JSON.
  *
  * @param quote_id: quote ID encoded
  */
  public function ajaxQuoteMRC (Request $request)
  {
    $log_src = $this->log_src.'@ajaxQuoteMRC';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);
    
    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Quote Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);


    // at least 1 product is required, all POST arrays should have same count
    $n_prods = count($request->prod_id);
    if (!($n_prods >0))
      return log_ajax_err('One or more Products are required.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id
      ]);
    if ($n_prods != count($request->memo) || $n_prods != count($request->price) || $n_prods != count($request->qty))
      return log_ajax_err('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id
      ]);

    // input validation
    $v = Validator::make($request->all(), [
      'prod_id.*' => 'required',
      'spiff.*' => 'required|numeric',
      'resid.*' => 'required|numeric',
      'price.*' => 'required|numeric',
      'qty.*' => 'required|integer',
    ], [
      'prod_id.*'=> 'Invalid Product has been selected.',
      'spiff.*'=> 'Please enter a valid Spiff Rate.',
      'resid.*'=> 'Please enter a valid Residual Rate.',
      'price.*'=> 'Please enter a valid price.',
      'qty.*'=> 'Please enter a valid quantity.',
    ]);
    if ($v->fails()) {
      $errs_tmp = $v->errors()->all();
      $errs = [];
      // filter out duplicate error message(s) - since all fields are array, same error can occur multiple times
      foreach ($errs_tmp as $r) {
        if (!in_array($r, $errs))
          $errs[] = $r;
      }
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';
        
		  return json_encode([
        'success'=>0, 'error'=>1, 'msg'=> $msg,
      ]);
    }

    // validate: check provider -> get default commission rates (spiff and residual) in case provider_products NOT found
    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    
    // validation passed -> reset quote x MRC products (= delete existing and add products), also create logging detail object -> leave lead x log
    $old_prods = DB::select(
      " SELECT svc_name, prod_name, memo, price, qty, spiff_rate AS spiff, residual_rate AS residual
          FROM lead_quote_mrc_products
          WHERE quote_id =:quote_id
          ORDER BY order_no
    ", [$quote_id]);

    $db_insert_params = $new_prods = [];
    
    for ($i =0; $i < $n_prods; $i++) {
      $r_prod_id = dec_id($request->prod_id[$i]);
      $svc_name = $prod_name = '';
      $prod_memo = ($request->memo[$i])?  $request->memo[$i] : '';

      $rec = DB::table('provider_products AS p')->select(DB::raw('p.p_name, s.name AS svc_name'))
        ->leftJoin('services AS s', 'p.service_id','=','s.id')
        ->where('p.id', $r_prod_id)
        ->first();
      
      if ($rec) {
        $svc_name = ($rec->svc_name)?  $rec->svc_name : '';
        $prod_name = $rec->p_name;
      }
      
      $db_insert_params[] = [
        'quote_id'=> $quote_id,
        'order_no'=> $i,
        'product_id'=> $r_prod_id,
        'svc_name'=> $svc_name,
        'prod_name'=> $prod_name,
        'memo'=> $prod_memo,
        'price'=> $request->price[$i],
        'qty'=> $request->qty[$i],
        'spiff_rate'=> $request->spiff[$i],
        'residual_rate'=> $request->resid[$i],
      ];
      $new_prods[] = (object)[
        'svc_name'=> $svc_name,
        'prod_name'=> $prod_name,
        'memo'=> $prod_memo,
        'price'=> $request->price[$i],
        'qty'=> $request->qty[$i],
        'spiff'=> $request->spiff[$i],
        'residual'=> $request->resid[$i],
      ];
    }
    DB::delete(" DELETE FROM lead_quote_mrc_products WHERE quote_id =:quote_id ", [$quote_id]);
    DB::table('lead_quote_mrc_products')->insert($db_insert_params);

    
    // action SUCCESS: leave a log and output JSON
    $log_id = $this->log_lead_prods((object) [
      'id' => $lead_id, 
      'msg' => '<p>Quote x Recurring Product(s) have been updated.</p><p>[Location] '.$loc->name.' x [Quote Provider] '.$prov->name.'</p>',
      'old_prods' => $old_prods, 'new_prods' => $new_prods,
    ]);
    log_write('Lead x Location x Quote x MRC Products Updated.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id' => $log_id,
    ]);
    return $this->jsonReload($lead_id, $agency_id, ['locId'=> enc_id($loc_id)]);
  }
  /**
  * Action: update quote x products (NRC) => output data in JSON.
  *
  * @param quote_id: quote ID encoded
  */
  public function ajaxQuoteNRC (Request $request)
  {
    $log_src = $this->log_src.'@ajaxQuoteNRC';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);
    
    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Quote Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    // get provider name for lead-logging
    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);


    // at least 1 product is required, all POST arrays should have same count
    $n_prods = count($request->prod_id);
    if ($n_prods != count($request->memo) || $n_prods != count($request->price) || $n_prods != count($request->qty))
      return log_ajax_err('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id
      ]);

    // input validation
    $v = Validator::make($request->all(), [
      'prod_id.*' => 'required',
      'price.*' => 'required|numeric',
      'qty.*' => 'required|integer',
    ], [
      'prod_id.*'=> 'Invalid Product has been selected.',
      'price.*'=> 'Please enter a valid price.',
      'qty.*'=> 'Please enter a valid quantity.',
    ]);
    if ($v->fails()) {
      $errs_tmp = $v->errors()->all();
      $errs = [];
      // filter out duplicate error message(s) - since all fields are array, same error can occur multiple times
      foreach ($errs_tmp as $r) {
        if (!in_array($r, $errs))
          $errs[] = $r;
      }
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';
        
		  return json_encode([
        'success'=>0, 'error'=>1,
        'msg'=> $msg,
      ]);
    }
    
    // validation passed -> reset quote x NRC products (= delete existing and add products), also create logging detail object -> leave lead x log
    $old_prods = DB::select(
      " SELECT svc_name, prod_name, memo, price, qty  FROM lead_quote_nrc_products  WHERE quote_id =:quote_id  ORDER BY order_no
    ", [$quote_id]);

    $db_insert_params = $new_prods = [];

    for ($i =0; $i < $n_prods; $i++) {
      $r_prod_id = dec_id($request->prod_id[$i]);
      $svc_name = $prod_name = '';
      $prod_memo = ($request->memo[$i])?  $request->memo[$i] : '';

      $rec = DB::table('provider_products AS p')->select(DB::raw('p.p_name, s.name AS svc_name'))
        ->leftJoin('services AS s', 'p.service_id','=','s.id')
        ->where('p.id', $r_prod_id)
        ->first();
      
      if ($rec) {
        $svc_name = ($rec->svc_name)?  $rec->svc_name : '';
        $prod_name = $rec->p_name;
      }
      
      $db_insert_params[] = [
        'quote_id'=> $quote_id,
        'order_no'=> $i,
        'product_id'=> $r_prod_id,
        'svc_name'=> $svc_name,
        'prod_name'=> $prod_name,
        'memo'=> $prod_memo,
        'price'=> $request->price[$i],
        'qty'=> $request->qty[$i],
      ];
      $new_prods[] = (object)[
        'svc_name'=> $svc_name,
        'prod_name'=> $prod_name,
        'memo'=> $prod_memo,
        'price'=> $request->price[$i],
        'qty'=> $request->qty[$i],
      ];
    }                  
    DB::table('lead_quote_nrc_products')->where('quote_id', $quote_id)->delete();
    DB::table('lead_quote_nrc_products')->insert($db_insert_params);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = $this->log_lead_prods((object) [
      'id' => $lead_id, 
      'msg' => '<p>Quote x Non-Recurring Product(s) have been updated.</p><p>[Location] '.$loc->name.' x [Quote Provider] '.$prov->name.'</p>',
      'old_prods' => $old_prods, 'new_prods' => $new_prods,
    ]);
    log_write('Lead x Location x Quote x NRC Products Updated.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id' => $log_id,
    ]);
    return $this->jsonReload($lead_id, $agency_id, ['locId'=> enc_id($loc_id)]);
  }
  /**
  * Action: delete quote
  *
  * @param quote_id: quote ID encoded
  */
  public function ajaxQuoteDelete (Request $request)
  {
    $log_src = $this->log_src.'@ajaxQuoteDelete';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Quote Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    // get provider name for lead-logging
    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);
      

    // validation passed -> delete quote, quote-commission, and products associated (Query Builder)
    DB::table('lead_quotes')->whereRaw(" id =:quote_id ", [$quote_id])->delete();
    DB::table('lead_quote_mrc_products')->whereRaw(" quote_id =:quote_id ", [$quote_id])->delete();
    DB::table('lead_quote_nrc_products')->whereRaw(" quote_id =:quote_id ", [$quote_id])->delete();
    DB::table('lead_quote_rate_agency')->whereRaw(" quote_id =:quote_id ", [$quote_id])->delete();
    DB::table('lead_quote_rate_manager')->whereRaw(" quote_id =:quote_id ", [$quote_id])->delete();
  
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Quote has been deleted.</p><p>[Location] '.$loc->name.' x [Quote Provider] '.$prov->name.'</p>',
    ]);
    log_write('Lead x Location x Quote Deleted.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id'=> $log_id,
    ]);
    return $this->jsonReload($lead_id, $agency_id, ['locId'=> enc_id($loc_id)]);
  }
  

  /**
  * Action: mark quote as signed (is_project) -> redirect to project-management page
  *
  * @param quote_id: quote ID encoded
  */
  public function quoteSign (Request $request)
  {
    $log_src = $this->log_src.'@quoteSign';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Quote Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    // get provider name for lead-logging
    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);
      

    DB::table('lead_quotes')->where('id', $quote_id)->update([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname), 'is_project'=> DB::raw(1), 'date_signed'=> date('Y-m-d'),
    ]);
      
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Quote has been signed and added to Project Management.</p><p>[Location] '.$loc->name.' x [Quote Provider] '.$prov->name.'</p>'
    ]);
    log_write('Quote signed and added to Project Management.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id'=> $log_id,
    ]);
    return msg_redirect('Quote has been signed and added to Project Management.', route('project.manage', ['lead_id'=> enc_id($lead_id),]));
  }


  /**
  * ******************************************************* PRIVATE function *******************************************************
  * get Lead x Quote object + spiff/residual rate for quote x agency
  *
  * @param $quote_id: quote ID
  * @param $agency_id: agency ID
  * @return Lead x Quote object: quote is NULL if not found, rates are 0 if not found
  */
  private function getQuote ($quote_id, $agency_id)
  {
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return NULL;
      
    $rate = DB::table('lead_quote_rate_agency')->whereRaw(" quote_id =:quote_id AND agency_id =:agency_id ", [$quote_id, $agency_id])->first();
    if ($rate) {
      $quote->spiff_share = $rate->spiff;
      $quote->resid_share = $rate->residual;
    } else
      $quote->spiff_share = $quote->resid_share = NULL;
    
    return $quote;
  }
  /**
  * extended log_lead function for account/quote products
  *
  * @param $obj: object [
  *		id: lead ID
  *		msg: log message
  *		old_prods: array of products before change [svc_name, prod_name, memo, price, qty]
  *		new_prods: array of products after change [svc_name, prod_name, memo, price, qty]
  * @return log_lead (): log ID (= last insert id)
  */ 
  private function log_lead_prods ($obj) {
    $lead_id = $obj->id;
    $msg = $obj->msg;
    
    $n_old = count($obj->old_prods);
    $n_new = count($obj->new_prods);
    $n = ($n_old > $n_new)?  $n_old : $n_new;
    $prod_changed = [];

    $old_html = $new_html = '';

    for ($i =0; $i < $n; $i++) {
      $prod_changed[$i] = ($n_old < 1 || $i >= $n_old || $i >= $n_new ||
        $obj->new_prods[$i]->svc_name != $obj->old_prods[$i]->svc_name || $obj->new_prods[$i]->prod_name != $obj->old_prods[$i]->prod_name ||
        $obj->new_prods[$i]->memo != $obj->old_prods[$i]->memo ||
        $obj->new_prods[$i]->price != $obj->old_prods[$i]->price || $obj->new_prods[$i]->qty != $obj->old_prods[$i]->qty
      );
    }
    if ($n_old >0) {
      $old_html .= '
        <p>[Products Before]</p>
        <table>
          <thead> <tr> <th>Service</th> <th>Product</th> <th>Note</th> <th>Price</th> <th>Qty</th> </tr> </thead>
      ';
      for ($i =0; $i < $n_old; $i++) {
        $prod = $obj->old_prods[$i];
        $old_html .= ($prod_changed[$i])?  '<tr class="old">' : '<tr>';
        $old_html .= '<td>'.$prod->svc_name.'</td> <td>'.$prod->prod_name.'</td> <td>'.$prod->memo.'</td> <td>'.$prod->price.'</td> <td>'.$prod->qty.'</td> </tr>';
      }
      $old_html .= '
        </table>
      ';
    } else
      $old_html = '<p>[Products Before]</p><div class="err">* No products associated.</div>';
    
    if ($n_new >0) {
      $new_html .= '
        <p>[Products After]</p>
        <table>
          <thead> <tr> <th>Service</th> <th>Product</th> <th>Note</th> <th>Price</th> <th>Qty</th> </tr> </thead>
      ';
      for ($i =0; $i < $n_new; $i++) {
        $prod = $obj->new_prods[$i];
        $new_html .= ($prod_changed[$i])?  '<tr class="new">' : '<tr>';
        $new_html .= '<td>'.$prod->svc_name.'</td> <td>'.$prod->prod_name.'</td> <td>'.$prod->memo.'</td> <td>'.$prod->price.'</td> <td>'.$prod->qty.'</td> </tr>';
      }
      $new_html .= '
        </table>
      ';
    }
    return log_lead((object)[
      'id' => $obj->id, 'msg' => $obj->msg, 'auto' => 1, 'detail' => $old_html.$new_html,
    ]);
  }
}
