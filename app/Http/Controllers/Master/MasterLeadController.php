<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use App\Agency;
use App\Provider;
use App\User;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterLeadController extends Controller
{
  /*
   * custom variable
   */
  private $log_src = 'MasterLeadController';

  /*
  * View: list of service providers.
  *
  * @return \Illuminate\Http\Response
  */
  public function list (Request $request)
  {
    return view('master.leads.list')
      ->with('preapp', $request->get('preapp'));
  }
  /**
  * View: new lead
  *
  * @return \Illuminate\Http\Response
  */
  public function new (Request $request)
  {
    $data = (object)[
      'row_states'=> get_state_list(),
    ];
    return view('master.leads.new')
      ->with('data', $data)
      ->with('preapp', $request->get('preapp'));
  }
  /**
  * View: lead management page (continued from new lead page / update existing lead = overview + mod) 
  *
  * @param $id: lead ID encoded
  * @return \Illuminate\Http\Response
  */
  public function manage (Request $request)
  {
    $log_src = $this->log_src.'@manage';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $manager_id = $preapp->manager_id;
    
    $lead_id = dec_id($request->id);
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);


    $data = $this->leadReload($lead, $manager_id);
    return view('master.leads.manage')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('lead', $lead);
  }

  /**
  * Action: create new lead.
  */
  public function create (Request $request)
  {
    $log_src = $this->log_src.'@create';
    $preapp = $request->get('preapp');
    $me = Auth::user();

    
    // input validation
    $v = Validator::make($request->all(), [
      'c_name' => 'required',
      'tel' => ['required', 'max:10', 'regex:/^\d{10}$/'],
      'tax_id' => ['nullable', 'regex:/^\d{2}-\d{7}$/'],
      'email' => 'nullable|email',
      'state_id' => 'nullable|numeric',
      'zip' => ['nullable', 'max:10', 'regex:/^\d{5}(-\d{4})?$/'],
    ], [
      'c_name.*'=> 'Customer Name is required.',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
      'tax_id.*'=> 'Please enter a valid Tax ID number (12-3456789 format).',
      'email.*'=> 'Please enter a valid Email Address.',
      'state_id.*'=> 'Invalid State ID entered.',
      'zip.*'=> 'Please use a valid US Zip code.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }

    $p_tax = ($request->tax_id)?  $request->tax_id : '';
    $p_email = ($request->email)?  $request->email : '';
    $p_addr = ($request->addr)?  $request->addr : '';
    $p_addr2 = ($request->addr2)?  $request->addr2 : '';
    $p_city = ($request->city)?  $request->city : '';
    $p_state_id = ($request->state_id)?  $request->state_id : 0;
    $p_zip = ($request->zip)?  $request->zip : '';
    

    // validation passed -> create new lead (Query Builder) -> assign the auth-user 
    $lead_id = DB::table('leads')->insertGetId([
      'mod_id'=> $me->id,
      'mod_user'=> trim($me->fname.' '.$me->lname),
      'cust_name'=> $request->c_name,
      'tel' => $request->tel,
      'tax_id' => $p_tax,
      'email' => $p_email,
      'addr' => $p_addr,
      'addr2' => $p_addr2,
      'city' => $p_city,
      'state_id' => $p_state_id,
      'zip' => $p_zip,
      'quote_requested' => 1,
    ]);
    // if lead was created by channel-assistant/manager: assign the manager
    if (POS_LV_CH_USER <= $me->access_lv && $me->access_lv <= POS_LV_CH_MANAGER)
      DB::table('lead_relation_manager')->insert([
        'lead_id'=> $lead_id, 'user_id' => $preapp->manager_id, 'is_primary' => DB::raw(1), 
      ]);
    

    // action SUCCESS: leave a log and redirect to view
    log_write('New Lead Created.', ['src'=> $log_src, 'lead-id'=> $lead_id]);
    return msg_redirect('The Lead has been created. Please continue with location and services', route('master.lead.manage', ['id'=> enc_id($lead_id)]));
  }


  /**
  * ******************************************************* lead x agency *******************************************************
  *
  * output JSON for ingenOverlay: assign new agency (amongst agency the auth-master-agent has access to)
  *
  * @param lead_id: lead ID encoded
  */
  public function overlayAgencyAssign (Request $request)
  {
    $log_src = $this->log_src.'@overlayAgencyAssign';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $manager_id = $preapp->manager_id;

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id]);
    

    // list of available agencies: exclude agencies already assigned
    $agencies = DB::select(
      " SELECT a.id, a.name, a.spiff, a.residual
          FROM agencies a
            LEFT JOIN lead_relation_agency la ON a.id =la.agency_id AND la.lead_id =:lead_id
            LEFT JOIN relation_agency_manager am ON a.id =am.agency_id
          WHERE a.active >0 AND la.agency_id IS NULL AND (am.user_id =:manager_id OR :auth_lv >= :master_manager_lv)
            GROUP BY a.id
          ORDER BY a.name, a.id DESC
    ", [$lead_id, $manager_id, $me->access_lv, POS_LV_MASTER_MANAGER]);
    
		$html_output =
      view('master.leads.form-agency')
        ->with('agencies', $agencies)
        ->with('lead_id', $lead_id)
        ->render();

		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  
  /**
  * Action: assign new agency to the lead (cannot exceed # set in config = MAX_AGENCY_PER_LEAD)
  *
  * @param lead_id: lead ID encoded
  */
  public function agencyAssign (Request $request)
  {
    $log_src = $this->log_src.'@agencyAssign';
    $preapp = $request->get('preapp');
    $manager_id = $preapp->manager_id;

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id]);

    // validate: # of agencies assigned is limited
    $assigned_agencies = DB::table('lead_relation_agency')->where('lead_id', $lead_id);
    if ($assigned_agencies->count() >= MAX_AGENCY_PER_LEAD)
      return log_redirect("Only upto ".MAX_AGENCY_PER_LEAD." Agencies can be assigned.", ['src'=> $log_src, 'lead-id'=> $lead_id]);

    $agency_id = dec_id($request->agency_id);

    // validate: check if selected agency is valid agency, and is not already assigned
    if ($assigned_agencies->where('agency_id', $agency_id)->count() >0)
      return log_redirect('Selected Agency is already Assigned.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'agency-id'=> $agency_id, ]);

    $agency = Agency::find($agency_id);
    if (!$agency)
      return log_redirect('Selected Agency Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'agency-id'=> $agency_id, ]);


    // validation passed
    DB::insert(" INSERT INTO lead_relation_agency (lead_id, agency_id) VALUES (:lead_id, :agency_id) ", [$lead_id, $agency_id]);

    
    // action SUCCESS: leave a log and redirect
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>New Agency has been assigned.</p><p>[Agency] '.$agency->name.'</p>',
    ]);
    log_write('New Agency has been assigned.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'agency-id'=> $agency_id, ]);
    return msg_redirect('New Agency has been assigned to the Lead');
  }
  /**
  * Action: remove currently assigned agency
  *
  * @param lead_id: lead ID encoded
  * @param agency_id: agency ID encoded
  */
  public function agencyRemove (Request $request)
  {
    $log_src = $this->log_src.'@agencyRemove';
    $preapp = $request->get('preapp');

    $lead_id = dec_id($request->lead_id);
    $agency_id = dec_id($request->agency_id);

    $lead = $this->getLead($lead_id, $preapp->manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'agency-id'=> $agency_id, ]);

    $agency = Agency::find($agency_id);
    if (!$agency)
      return log_redirect('Selected Agency Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'agency-id'=> $agency_id, ]);


    // delete association: lead x agency, quote x agency-rates
    DB::table('lead_relation_agency')->whereRaw(" lead_id =:lead_id AND agency_id =:agency_id ", [$lead_id, $agency_id, ])->delete();
    DB::delete(
      " DELETE qr
          FROM lead_quote_rate_agency qr
            LEFT JOIN lead_quotes q ON qr.quote_id =q.id
            LEFT JOIN lead_locations l ON q.location_id =l.id
          WHERE qr.agency_id =:agency_id AND (qr.lead_id =:lead_id1 OR l.lead_id =:lead_id2)
    ", [$agency_id, $lead_id, $lead_id]);
    
    
    // action SUCCESS: leave a log and redirect
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Agency has been removed from the Lead.</p><p>[Agency] '.$agency->name.'</p>',
    ]);
    log_write('Agency has been Unassigned.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'agency-id'=> $agency_id, ]);
    return msg_redirect('Agency was removed from the Lead.');
  }


  /**
  * ******************************************************* lead x manager *******************************************************
  *
  * output JSON for ingenOverlay: assign new master-agent (amongst agency the auth-master-agent has access to)
  *
  * @param lead_id: lead ID encoded -> update lead page
  */
  public function overlayManagerAssign (Request $request)
  {
    $log_src = $this->log_src.'@overlayManagerAssign';
    $preapp = $request->get('preapp');

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $preapp->manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id]);
    

    // list of available managers: exclude managers already assigned
    $managers = DB::select(
      " SELECT u.id, u.fname, u.lname
          FROM login_users u
            LEFT JOIN lead_relation_manager lm ON u.id =lm.user_id AND lm.lead_id =:lead_id
          WHERE lm.lead_id IS NULL AND u.access_lv =:ch_manager_lv
            GROUP BY u.id
          ORDER BY u.fname, u.lname, u.id DESC
    ", [$lead_id, POS_LV_CH_MANAGER]);
    
		$html_output =
      view('master.leads.form-manager')
        ->with('managers', $managers)
        ->with('lead_id', $lead_id)
        ->render();

		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  
  /**
  * Action: assign new channel manager to the lead (cannot exceed # set in config = MAX_MANAGER_PER_LEAD)
  *
  * @param lead_id: lead ID encoded
  */
  public function managerAssign (Request $request)
  {
    $log_src = $this->log_src.'@managerAssign';
    $preapp = $request->get('preapp');

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $preapp->manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id]);

    // validate: # of managers assigned is limited
    $assigned_managers = DB::table('lead_relation_manager')->where('lead_id', $lead_id);
    if ($assigned_managers->count() >= MAX_MANAGER_PER_LEAD)
      return log_redirect("Only upto ".MAX_MANAGER_PER_LEAD." Managers can be assigned.", ['src'=> $log_src, 'lead-id'=> $lead_id]);

    $manager_id = dec_id($request->manager_id);

    // validate: check if selected manager is a valid manager, and is not already assigned
    if ($assigned_managers->where('user_id', $manager_id)->count() >0)
      return log_redirect('Selected Manager is already Assigned.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);

    $manager = DB::table('login_users')->whereRaw(" id =:id AND access_lv =:ch_manager_lv ", [$manager_id, POS_LV_CH_MANAGER, ])->first();
    if (!$manager)
      return log_redirect('Selected Manager Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);


    // validation passed
    DB::insert(" INSERT INTO lead_relation_manager (lead_id, user_id, ch_manager) VALUES (:lead_id, :manager_id, :name) ", [
      $lead_id, $manager_id, trim($manager->fname.' '.$manager->lname)
    ]);

    
    // action SUCCESS: leave a log and redirect
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>New Manager has been assigned.</p><p>[Manager] '.trim($manager->fname.' '.$manager->lname).'</p>',
    ]);
    log_write('New Manager has been assigned.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);
    return msg_redirect('New Manager has been assigned to the Lead');
  }
  /**
  * Action: set selected channel-manager as a primary manager of the lead
  *
  * @param lead_id: lead ID encoded
  * @param manager_id: user ID encoded
  */
  public function managerSetPrimary (Request $request)
  {
    $log_src = $this->log_src.'@managerSetPrimary';
    $preapp = $request->get('preapp');

    $lead_id = dec_id($request->lead_id);
    $manager_id = dec_id($request->manager_id);

    $lead = $this->getLead($lead_id, $preapp->manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);

    // get previous primary-manager
    $old_manager = DB::table('lead_relation_manager')->whereRaw(" lead_id =:lead_id AND is_primary =1 ", [$lead_id])->first();
    if ($old_manager) {
      $old_id = $old_manager->user_id;
      $old_user = User::find($old_id);
      $old_name = ($old_user)?  trim($old_user->fname.' '.$old_user->lname) : '(Invalid User)';
    
    } else {
      $old_id = 0;
      $old_name = '(None)';
    }
    
    // validate new primary-manager
    $db_query = DB::table('lead_relation_manager')->whereRaw(" lead_id =:lead_id AND user_id =:manager_id ", [$lead_id, $manager_id]);
    $new_manager = $db_query->first();
    $new_user = User::find($manager_id);
    if (!$new_manager || !$new_user)
      return log_redirect('Selected Manager Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);


    // delete association: lead x manager, quote x manager-rates
    DB::table('lead_relation_manager')->whereRaw(" lead_id =:lead_id ", [$lead_id])->update(['is_primary'=> DB::raw(0), ]);
    $db_query->update(['is_primary'=> DB::raw(1), ]);
    
    
    // action SUCCESS: leave a log and redirect
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Primary Manager has been changed.</p>',
      'detail'=> [(object)[
        'field'=> 'Primary Manager', 'old'=> $old_name, 'new'=> trim($new_user->fname.' '.$new_user->lname), 'old_val'=> $old_id, 'new_val'=> $manager_id, ],
      ],
    ]);
    log_write('Manager has been Unassigned.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);
    return msg_redirect('Manager was removed from the Lead.');
  }
  /**
  * Action: remove currently assigned channel-manager
  *
  * @param lead_id: lead ID encoded
  * @param manager_id: user ID encoded
  */
  public function managerRemove (Request $request)
  {
    $log_src = $this->log_src.'@managerRemove';
    $preapp = $request->get('preapp');

    $lead_id = dec_id($request->lead_id);
    $manager_id = dec_id($request->manager_id);

    $lead = $this->getLead($lead_id, $preapp->manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);

    $db_rows = DB::select(
      " SELECT IF(u.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), lm.ch_manager) ch_manager, lm.is_primary
          FROM lead_relation_manager lm LEFT JOIN login_users u ON lm.user_id =u.id
          WHERE lm.lead_id =:lead_id AND lm.user_id =:manager_id
    ", [$lead_id, $manager_id]);
    if (!$db_rows)
      return log_redirect('Selected Manager Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);

    $manager = $db_rows[0];
    if ($manager->is_primary)
    return log_redirect('Primary Manager cannot be Removed.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);


    // delete association: lead x manager, quote x manager-rates
    DB::table('lead_relation_manager')->whereRaw(" lead_id =:lead_id AND user_id =:manager_id ", [$lead_id, $manager_id])->delete();
    DB::delete(
      " DELETE qr
          FROM lead_quote_rate_manager qr
            LEFT JOIN lead_quotes q ON qr.quote_id =q.id
            LEFT JOIN lead_locations l ON q.location_id =l.id
          WHERE qr.user_id =:manager_id AND (qr.lead_id =:lead_id1 OR l.lead_id =:lead_id2)
    ", [$manager_id, $lead_id, $lead_id]);
    
    
    // action SUCCESS: leave a log and redirect
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Manager has been removed from the Lead.</p><p>[Manager] '.$manager->ch_manager.'</p>',
    ]);
    log_write('Manager has been Unassigned.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);
    return msg_redirect('Manager was removed from the Lead.');
  }


  /**
  * ******************************************************* lead x customer *******************************************************
  *
  * output JSON for ingenOverlay: update currently saved customer (of the lead, does NOT update agent's customer record)
  *
  * @param lead_id: lead ID encoded -> update lead page
  */
  public function overlayCustomerMod (Request $request)
  {
    $log_src = $this->log_src.'@overlayCustomerMod';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $lead_id = dec_id($request->lead_id);
    
    $lead = $this->getLead($lead_id, $preapp->manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, ]);
      
    $row_states = get_state_list();
    
		$html_output =
      Form::open(['url'=> route('master.lead.ajax-customer-update', ['id'=> $request->lead_id]), 'class'=>'frm-update']).
        view('master.leads.form-customer')
          ->with('data', (object)[
              'row_states'=> $row_states,
            ])
          ->with('lead', $lead)
          ->render().'
                  
        <div class="btn-group">
          '.Form::submit('Save Information').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>

      '.Form::close().'
      <script>moCustomerUpdate()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  
  /**
  * Action: update currently selected customer => output customer data in JSON.
  *
  * @param lead_id: lead ID encoded -> update lead page
  */
  public function ajaxCustomerUpdate (Request $request)
  {
    $log_src = $this->log_src.'@ajaxCustomerUpdate';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $manager_id = $preapp->manager_id;

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);
    
    
    // input validation
    $v = Validator::make($request->all(), [
      'c_name' => 'required',
      'tel' => ['required', 'max:10', 'regex:/^\d{10}$/'],
      'tax_id' => ['nullable', 'regex:/^\d{2}-\d{7}$/'],
      'email' => 'nullable|email',
      'state_id' => 'nullable|numeric',
      'zip' => ['nullable', 'max:10', 'regex:/^\d{5}(-\d{4})?$/'],
    ], [
      'c_name.*'=> 'Customer Name is required.',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
      'tax_id.*'=> 'Please enter a valid Tax ID number (12-3456789 format).',
      'email.*'=> 'Please enter a valid Email Address.',
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


    $p_tax = ($request->tax_id)?  $request->tax_id : '';
    $p_email = ($request->email)?  $request->email : '';
    $p_addr = ($request->addr)?  $request->addr : '';
    $p_addr2 = ($request->addr2)?  $request->addr2 : '';
    $p_city = ($request->city)?  $request->city : '';
    $p_state_id = ($request->state_id)?  $request->state_id : 0;
    $p_zip = ($request->zip)?  $request->zip : '';


    // create logging detail object -> leave lead x log
    $state = DB::table('states')->find($lead->state_id);
    $state_code = ($state)?  $state->code : '';
    $city_state = $lead->city;
    $city_state .= ($city_state && $state_code)?  ', '.$state_code : $state_code;
    $old_addr = '<p>'.$lead->addr.'</p><p>'.$lead->addr2.'</p><p>'.trim($city_state.' '.$lead->zip).'</p>';

    $state = DB::table('states')->find($p_state_id);
    $state_code = ($state)?  $state->code : '';
    $city_state = $p_city;
    $city_state .= ($city_state && $state_code)?  ', '.$state_code : $state_code;
    $new_addr = '<p>'.$p_addr.'</p><p>'.$p_addr2.'</p><p>'.trim($city_state.' '.$p_zip).'</p>';

    $log_detail_values = [
      (object)['field'=> 'Name', 'old'=> $lead->cust_name, 'new'=> $request->c_name],
      (object)['field'=> 'Phone', 'old'=> $lead->tel, 'new'=> $request->tel],
      (object)['field'=> 'Tax ID', 'old'=> $lead->tax_id, 'new'=> $p_tax],
      (object)['field'=> 'Email', 'old'=> $lead->email, 'new'=> $p_email],
      (object)['field'=> 'Address', 'old'=> $old_addr, 'new'=> $new_addr],
    ];


    DB::update(
      " UPDATE leads SET mod_id =?, mod_user =?,
          cust_name =?, addr =?, addr2 =?, city =?, state_id =?, zip =?,  tel =?, tax_id =?, email =?
          WHERE id =?
    ", [$me->id, trim($me->fname.' '.$me->lname),
      $request->c_name, $p_addr, $p_addr2, $p_city, $p_state_id, $p_zip,  $request->tel, $p_tax, $p_email,
      $lead_id
    ]);

    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Customer Information has been updated.</p>',
      'detail' => $log_detail_values,
    ]);
    log_write('Agency Customer Updated from [Lead Management Page].', ['src'=> $log_src, 'lead-id'=> $lead_id]);
    return $this->jsonReload($lead_id);
  }
  
  
  
  
  /**
  * ******************************************************* PRIVATE function *******************************************************
  * return lead-related information 
  *
  * @param $lead: (reference) lead object
  * @param $manager_id: manager ID encoded
  * @return object: [permissions, agencies, managers, followers, locations, logs]
  */
  private function leadReload (& $lead, $manager_id)
  {
    $log_src = $this->log_src.'@leadReload';
    $me = Auth::user();
    
    $lead_id = $lead->id;
    
    // get customer state-code
    $state = DB::table('states')->find($lead->state_id);
    $lead->state_code = ($state)?  $state->code : '';

    // get assigned agencies, managers
    $agencies = DB::select(
      " SELECT a.id, a.name,  IF(am.user_id =:manager_id OR :lv >= :master_manager_lv, 1,0) is_accessible
          FROM lead_relation_agency la LEFT JOIN agencies a ON la.agency_id =a.id
            LEFT JOIN relation_agency_manager am ON a.id =am.agency_id
          WHERE la.lead_id =:lead_id AND a.id >0
            GROUP BY a.id
          ORDER BY a.name
    ", [$manager_id, $me->access_lv, POS_LV_MASTER_MANAGER, $lead_id]);

    $managers = DB::select(
      " SELECT u.id, u.fname, u.lname, lm.is_primary
          FROM lead_relation_manager lm LEFT JOIN login_users u ON lm.user_id =u.id
          WHERE lm.lead_id =:lead_id AND u.id >0 AND u.access_lv =:ch_manager_lv
          ORDER BY u.fname, u.lname, u.id DESC
    ", [$lead_id, POS_LV_CH_MANAGER, ]);


    /* permission on leads (by default, viewable)
      mod: modify lead + agency
      manager = modify managers (= master OR primary-manager)
      commission = modify spiff/residual
    */
    $lead_perm_mod = $lead_perm_manager = $lead_perm_commission = 0;

    if ($me->access_lv >= POS_LV_MASTER_MANAGER)
      $lead_perm_mod = $lead_perm_manager = $lead_perm_commission = 1;
    elseif ($me->access_lv >= POS_LV_MASTER_USER)
      $lead_perm_commission = 1;
    elseif ($managers) {
      foreach ($managers as $manager) {
        if ($manager->id == $manager_id) {
          $lead_perm_mod = 1;
          $lead_perm_manager = $manager->is_primary;
        }
      }
    }
    
    // get lead x followers
    $follower_masters = DB::select(
      " SELECT f.user_id, IF(u.id >0, 1,0) AS valid,
            IF(u.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), f.name) AS f_name,
            IF(u.id >0, u.title, f.title) AS title,
            IF(u.id >0, u.tel, f.tel) AS tel,
            IF(u.id >0, u.email, f.email) AS email,
            IF(u.id >0 AND (:auth_lv >= :master_manager_lv OR u.access_lv >= :master_user_lv OR u.id =:manager_id1 OR am.user_id =:manager_id2), 1,0) is_accessible
          FROM lead_follower_masters f
            LEFT JOIN login_users u ON f.user_id =u.id AND :lv_max >= u.access_lv AND u.access_lv >= :lv_min AND u.active >0
              LEFT JOIN relation_assistant_manager am ON u.id =am.assistant_id
          WHERE f.lead_id =:lead_id
            GROUP BY u.id
          ORDER BY valid, f_name, f.user_id DESC
    ", [$me->access_lv, POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER, $manager_id, $manager_id,  POS_LV_MASTER_ADMIN, POS_LV_CH_USER, $lead_id]);
    $follower_agents = DB::select(
      " SELECT f.order_no, IF(u.id >0 AND a.id >0, 1,0) AS valid,
            IF(u.id >0 AND a.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), f.name) AS f_name,
            IF(u.id >0 AND a.id >0, u.title, f.title) AS title,
            IF(u.id >0 AND a.id >0, u.tel, f.tel) AS tel,
            IF(u.id >0 AND a.id >0, u.email, f.email) AS email,
            IF(u.id >0 AND a.id >0, a.name, f.agency) AS agency
          FROM lead_follower_agents f
            LEFT JOIN lead_relation_agency la ON f.lead_id =la.lead_id
            LEFT JOIN relation_user_agency ua ON f.user_id =ua.user_id AND la.agency_id =ua.agency_id
              LEFT JOIN agencies a ON ua.agency_id =a.id AND a.active >0
              LEFT JOIN login_users u ON ua.user_id =u.id AND u.active >0
          WHERE f.lead_id =:lead_id
            GROUP BY f.lead_id, f.order_no
          ORDER BY valid, la.agency_id, f_name, f.order_no
    ", [$lead_id]);
    $follower_provs = DB::select(
      " SELECT f.order_no, IF(c.id >0, 1,0) AS valid,
            IF(c.id >0, p.name, f.prov_name) AS prov,
            IF(c.id >0, TRIM(CONCAT(c.fname,' ',c.lname)), f.name) AS f_name,
            IF(c.id >0, c.title, f.title) AS title,
            IF(c.id >0, c.tel, f.tel) AS tel,
            IF(c.id >0, c.email, f.email) AS email
          FROM lead_follower_providers f LEFT JOIN providers p ON f.provider_id =p.id
            LEFT JOIN provider_contacts c ON p.id =c.provider_id
          WHERE f.lead_id =:lead_id
          ORDER BY valid, prov, f_name, f.order_no
    ", [$lead_id]);
    
    $followers = (object)[
      'masters'=> $follower_masters,
      'agents'=> $follower_agents,
      'prov_contacts'=> $follower_provs,
    ];

    // get lead x location -> current accounts and quotes
    $row_locations = [];
    $db_rows = DB::select(
      " SELECT l.id, l.name, l.addr, l.addr2, l.city, l.zip,  s.code AS state_code
          FROM lead_locations l LEFT JOIN states s ON l.state_id = s.id
          WHERE l.lead_id =:lead_id
          ORDER BY l.id
    ", [$lead_id]);
    if (count($db_rows) >0) {
      foreach ($db_rows as $row) {
        $r_curr = [];
        $accnts_tmp = DB::select(
          " SELECT id, account_id, is_selected, provider_name AS name, accnt_no, passcode, term, date_contract_end AS date_end, etf, memo
              FROM lead_current_accounts
              WHERE location_id =:loc_id
              ORDER BY id
        ", [$row->id]);
        if ($accnts_tmp) {
          foreach ($accnts_tmp as $accnt) {
            $accnt->products = DB::select(" SELECT svc_name, prod_name, memo, price, qty  FROM lead_current_products  WHERE account_id =:accnt_id   ORDER BY order_no ", [
              $accnt->id
            ]);
            $r_curr[] = $accnt;
          }
        }        
        $r_quotes = [];
        $quotes_tmp = DB::select(
          " SELECT q.id, q.provider_id, q.is_selected, q.term, q.date_contract_end AS date_end,
                p.name AS name
              FROM lead_quotes q LEFT JOIN providers p ON q.provider_id =p.id
                LEFT JOIN lead_locations l ON q.location_id =l.id
              WHERE l.lead_id =:lead_id AND l.id =:loc_id
              ORDER BY q.id
        ", [$lead_id, $row->id]);

        if ($quotes_tmp) {
          foreach ($quotes_tmp as $quote) {
            // MRC products
            $quote->mrc_prods = DB::select(
              " SELECT p.memo, p.price, p.qty, p.spiff_rate, p.residual_rate,
                    IF(pp.id >0 AND s.id >0 AND pp.provider_id =:prov_id ,1,0) AS valid,
                    IF(pp.id >0, pp.p_name, p.prod_name) AS prod_name,
                    IF(s.id >0, s.name, p.svc_name) AS svc_name 
                  FROM lead_quote_mrc_products p
                    LEFT JOIN provider_products pp ON p.product_id =pp.id
                    LEFT JOIN services s ON pp.service_id =s.id
                  WHERE p.quote_id =:quote_id
                  ORDER BY valid DESC, p.order_no
            ", [$quote->provider_id, $quote->id]);
            // NRC products
            $quote->nrc_prods = DB::select(
              " SELECT p.memo, p.price, p.qty,
                    IF(pp.id >0 AND s.id >0 AND pp.provider_id =:prov_id ,1,0) AS valid,
                    IF(pp.id >0, pp.p_name, p.prod_name) AS prod_name,
                    IF(s.id >0, s.name, p.svc_name) AS svc_name 
                  FROM lead_quote_nrc_products p
                    LEFT JOIN provider_products pp ON p.product_id =pp.id
                    LEFT JOIN services s ON pp.service_id =s.id
                  WHERE p.quote_id =:quote_id
                  ORDER BY valid DESC, p.order_no
            ", [$quote->provider_id, $quote->id]);
            $r_quotes[] = $quote;
          }
        }
        $r_addr = $row->addr;
        $r_addr .= ($r_addr && $row->addr2)?  ', '.$row->addr2 : $row->addr2;
        $r_addr .= ($r_addr && $row->city)?  ', '.$row->city : $row->city;
        $r_addr .= ($r_addr && $row->state_code)?  ', '.$row->state_code : $row->state_code;
        $r_addr .= ($r_addr && $row->zip)?  ' '.$row->zip : $row->zip;

        $row_locations[] = (object)['id'=> $row->id, 'name'=> $row->name, 'addr'=> $r_addr, 'curr_accounts'=> $r_curr, 'quotes'=> $r_quotes];
      }
    }

    // get lead x logs, only the latest 5
    $row_logs = DB::table('lead_logs')
      ->whereRaw(' lead_id =:id ', [$lead_id])
      ->orderBy('date_log','desc')->orderBy('id','desc')
      ->take(5)->get();
    
    return (object)[
      'permissions' => (object)['mod'=> $lead_perm_mod, 'manager'=> $lead_perm_manager, 'commission'=> $lead_perm_commission, ],
      'agencies'=> $agencies,
      'managers'=> $managers,
      'followers'=> $followers,
      'locations'=> $row_locations,
      'logs'=> $row_logs,
    ];
  }

  /**
  * ******************************************************* Base/TRAIT functions: also used in extending classes *******************************************************
  * get Lead object
  *
  * @param $lead_id: lead ID
  * @param $manager_id: manager ID (if auth-user is channel manager/assistant, 0 for master-agents)
  * @return Lead object
  */
  public function getLead ($lead_id, $manager_id)
  {
    $me = Auth::user();
    if (POS_LV_CH_USER <= $me->access_lv && $me->access_lv <= POS_LV_CH_MANAGER) {
      // channel user/manager has access to leads if: the manager is assigned OR assigned agency is associated with the manager
      $db_rows = DB::select(
        " SELECT l.*
            FROM leads l
            LEFT JOIN lead_relation_manager lm ON l.id =lm.lead_id
              LEFT JOIN login_users u1 ON lm.user_id =u1.id
            LEFT JOIN lead_relation_agency la ON l.id =la.lead_id
              LEFT JOIN agencies a ON la.agency_id =a.id
              LEFT JOIN relation_agency_manager am ON la.agency_id =am.agency_id
                LEFT JOIN login_users u2 ON am.user_id =u2.id
          WHERE l.id =:lead_id AND (u1.id =:manager_id1 OR (a.id >0 AND u2.id =:manager_id2))
          LIMIT 1
      ", [$lead_id, $manager_id, $manager_id]);
      if ($db_rows)
        return $db_rows[0];
      else
        return NULL;

    } else
      // master-agents have acces to any leads
      return DB::table('leads')->find($lead_id);
  }
  /**
  * output JSON to reload Lead page with updated contents
  *  list of locations, control panel: location navigation, lead summary, followers, customer
  *
  * @param $lead_id: lead ID
  * @param $manager_id: manager ID (if auth-user is channel manager/assistant, 0 for master-agents)
  * @param $vars (optional): array of additional output to include in JSON output (by default, empty)
  * @return JSON with HTML outputs
  **/
  public function jsonReload ($lead_id, $manager_id, $vars = [])
  {
    $log_src = $this->log_src.'@jsonReload';
    $me = Auth::user();

    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id,]);

    $data = $this->leadReload($lead, $manager_id);
    
    
    // create HTML output to render on reload
    $html_commission = view('master.leads.sub-commission')
      ->with('lead_id', $lead_id)
      ->with('permissions', $data->permissions)
      ->with('agencies', $data->agencies)
      ->with('managers', $data->managers)
      ->render();

    $html_customer = '
      <div class="input-group">
        <label>Name</label>
        <div class="output">'.$lead->cust_name.'</div>
      </div>
      <div class="input-group">
        <label>Phone Number</label>
        <div class="output">'.format_tel($lead->tel).'</div>
      </div>
      <div class="input-group">
        <label>Tax ID</label>
        <div class="output">'.$lead->tax_id.'</div>
      </div>
      <div class="input-group">
        <label>Email Address</label>
        <div class="output">'.$lead->email.'</div>
      </div>
      <div class="input-group">
        <label>Address</label>
        <div class="output">
          <p>'.$lead->addr.'</p>
          <p>'.$lead->addr2.'</p>
          <p>'.format_city_state_zip($lead->city, $lead->state_code, $lead->zip).'</p>
        </div>
      </div>
    ';
    $html_follower = view('master.leads.sub-follower')
      ->with('lead_id', $lead->id)
      ->with('followers', $data->followers)
      ->render();

    if ($data->locations) {
      $html_location_opts = '';
      foreach ($data->locations as $loc)
        $html_location_opts .= '<option value="'.enc_id($loc->id).'">'.$loc->name.'</option>';
    } else
      $html_location_opts = '<option>There is No Location</option>';

    $html_logs = view('leads.sub-log')
      ->with('show_detail', 0)
      ->with('logs', $data->logs)
      ->render();
      
    $html_location = view('master.leads.sub-location')
      ->with('locations', $data->locations)
      ->with('open_first', FALSE)
      ->with('quote_requested', $lead->quote_requested)
      ->render();

    // output in JSON format: also include any additional output included in $vars
    $arr_output = [
			'success'=>1, 'error'=>0,
      'commissionHTML'=> $html_commission, 'custHTML'=> $html_customer, 'followerHTML'=> $html_follower, 'logHTML'=> $html_logs,
      'locOptHTML'=> $html_location_opts, 'locHTML'=> $html_location,
    ];
    if (count($vars) >0) {
      foreach ($vars as $k=>$v)
        $arr_output[$k] = $v;
    }
		return json_encode($arr_output);
  }
}

trait GetLead {
  public function getLead ($lead_id, $manager_id) {
    return parent::getLead($lead_id, $manager_id);
  }
}
