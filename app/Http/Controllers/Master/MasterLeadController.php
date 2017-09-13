<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use App\Agency;
use App\Provider;
use App\User;
use App\Traits\MasterLeadTrait;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterLeadController extends Controller
{
  use MasterLeadTrait;

  /**
  * custom variable
  */
  private $log_src = 'MasterLeadController';

  /**
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
      'addr' => 'nullable|required_if:create_loc,1',
      'city' => 'nullable|required_if:create_loc,1',
      'state_id' => 'nullable|numeric|required_if:create_loc,1',
      'zip' => ['nullable', 'max:10', 'required_if:create_loc,1', 'regex:/^\d{5}(-\d{4})?$/'],
    ], [
      'c_name.*'=> 'Customer Name is required.',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
      'tax_id.*'=> 'Please enter a valid Tax ID number (12-3456789 format).',
      'addr.*' => 'Address is required if Create Location is checked',
      'city.*' => 'City is required if Create Location is checked',
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

    $log_msg = '<p>New Lead has been created.</p><p>[Customer] '.$request->c_name.'</p>';
    $log_vars = ['src'=> $log_src, 'manager-id'=> $preapp->manager_id, 'lead-id'=> $lead_id, ];

    // if create-location checker is checked: create new location with the same address
    if ($request->create_loc) {
      $loc_id = DB::table('lead_locations')->insertGetId([
        'lead_id'=> $lead_id,
        'name'=> 'New Location',
        'addr' => $p_addr, 'addr2' => $p_addr2,
        'city' => $p_city, 'state_id' => $p_state_id, 'zip' => $p_zip,
      ]);
      
      $log_msg = '<p>New Lead has been created with Location.</p><p>[Customer] '.$request->c_name.'</p>';
      $log_vars['location-id'] = $loc_id;
    }

    
    // action SUCCESS: leave a log and redirect to view
    $log_id = log_lead_values((object) ['id' => $lead_id, 'msg' => $log_msg, ]);
    $log_vars['log_id'] = $log_id;
    log_write('New Lead Created.', $log_vars);
    return msg_redirect('The Lead has been created. Please continue with location and services', route('master.lead.manage', ['id'=> enc_id($lead_id)]));
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
    $lead->project_open = FALSE;
    $row_locations = [];

    $db_rows = DB::select(
      " SELECT l.id, l.name, l.addr, l.addr2, l.city, l.zip,  s.code AS state_code
          FROM lead_locations l LEFT JOIN states s ON l.state_id = s.id
          WHERE l.lead_id =:lead_id
          ORDER BY l.id
    ", [$lead_id]);
    if (count($db_rows) >0) {
      foreach ($db_rows as $row) {
        $r_file_count = DB::table('lead_location_files')->whereRaw(" location_id =:loc_id AND lead_id =:lead_id ", [$row->id, $lead_id])->count();

        $r_curr = [];
        $accnts_tmp = DB::select(
          " SELECT id, account_id, is_selected, is_project, provider_name AS name, accnt_no, passcode, term, date_contract_end AS date_end, etf, memo
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

            // if account is set for project -> lead status is "project opened"
            if (!$lead->project_open && $accnt->is_project)
              $lead->project_open = TRUE;
          }
        }        
        $r_quotes = [];
        $quotes_tmp = DB::select(
          " SELECT q.id, q.provider_id, q.is_selected, q.is_project, q.term, q.date_contract_end AS date_end,
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
            
            // if account is set for project -> lead status is "project opened"
            if (!$lead->project_open && $quote->is_project)
              $lead->project_open = TRUE;
          }
        }
        $r_addr = $row->addr;
        $r_addr .= ($r_addr && $row->addr2)?  ', '.$row->addr2 : $row->addr2;
        $r_addr .= ($r_addr && $row->city)?  ', '.$row->city : $row->city;
        $r_addr .= ($r_addr && $row->state_code)?  ', '.$row->state_code : $row->state_code;
        $r_addr .= ($r_addr && $row->zip)?  ' '.$row->zip : $row->zip;

        $row_locations[] = (object)['id'=> $row->id, 'name'=> $row->name, 'addr'=> $r_addr, 'file_count'=> $r_file_count, 'curr_accounts'=> $r_curr, 'quotes'=> $r_quotes];
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
  * output JSON to reload Lead page with updated contents
  *  list of locations, control panel: location navigation, lead summary, followers, customer
  *
  * @param $lead_id: lead ID
  * @param $manager_id: manager ID (if auth-user is channel manager/assistant, 0 for master-agents)
  * @param $vars (optional): array of additional output to include in JSON output (by default, empty)
  * @return JSON with HTML outputs
  */
  protected function jsonReload ($lead_id, $manager_id, $vars = [])
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
      ->with('route_name_master_del', 'master.lead.ajax-follower-master-delete')
      ->with('route_name_prov_del', 'master.lead.ajax-follower-provider-delete')
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




  
  /**
  * ******************************************************* SHARED functions using trait *******************************************************
  * **********     lead x customer     **********
  *
  * output JSON for ingenOverlay: update customer of the lead
  *  use MasterLeadTrait->traitCustomerMod()
  */
  public function overlayCustomerMod (Request $request)
  {
    return $this->traitCustomerMod($request, route('master.lead.ajax-customer-update', ['id'=> $request->lead_id]));
  }
  /**
  * Action: update currently selected customer (NOT available in new lead page) => output customer data in JSON.
  *  use MasterLeadTrait->traitCustomerUpdate()
  */
  public function ajaxCustomerUpdate (Request $request)
  {
    return $this->traitCustomerUpdate($request);
  }

  /**
  * **********     lead x log     **********
  *
  * output JSON for ingenOverlay: new lead x log
  *  use MasterLeadTrait->traitLogNew()
  */
  public function overlayLogNew(Request $request)
  {
    return $this->traitLogNew($request, route('master.lead.ajax-log-add', ['lead_id'=> $request->lead_id]));
  }
  /**
  * output JSON for ingenOverlay: update lead x log message (= mark the log "corrected" and create new log)
  *  use MasterLeadTrait->traitLogMod()
  */
  public function overlayLogMod(Request $request)
  {
    return $this->traitLogMod($request, route('master.lead.ajax-log-correct', ['log_id'=> $request->log_id]));
  }
  /**
  * output JSON for ingenOverlay: show all lead x logs
  *  use MasterLeadTrait->traitLogHistory()
  */
  public function overlayLogHistory(Request $request)
  {
    return $this->traitLogHistory($request);
  }
  /**
  * Action: add new log -> output data in JSON.
  *  use MasterLeadTrait->traitLogAdd()
  */
  public function ajaxLogAdd (Request $request)
  {
    return $this->traitLogAdd($request);
  }
  /**
  * Action: correct existing log -> mark log as "corrected", and new log -> output data in JSON.
  *  use MasterLeadTrait->traitLogCorrect()
  */
  public function ajaxLogCorrect (Request $request)
  {
    return $this->traitLogCorrect($request);
  }

  /**
  * **********     lead x follower     **********
  * output JSON for ingenOverlay: update follower(s) - list of agents + provider-contacts
  *  use MasterLeadTrait->traitFollowerMod()
  */
  public function overlayFollowerMod(Request $request)
  {
    return $this->traitFollowerMod($request, route('master.lead.ajax-follower-update', ['lead_id'=> $request->lead_id]));
  }
  /**
  * Action: update lead x followers (master and/or provider-contacts) => output data in JSON.
  *  use MasterLeadTrait->traitFollowerUpdate()
  */
  public function ajaxFollowerUpdate (Request $request)
  {
    return $this->traitFollowerUpdate($request);
  }
  /**
  * AJAX Action: delete lead x followers (master) => output data in JSON.
  *  use MasterLeadTrait->traitFollowerMasterDelete()
  */
  public function ajaxFollowerMasterDelete (Request $request)
  {
    return $this->traitFollowerMasterDelete($request);
  }
  /**
  * AJAX Action: delete lead x followers (agent) => output data in JSON.
  *  use MasterLeadTrait->traitFollowerUpdate()
  */
  /*
  public function ajaxFollowerAgentDelete (Request $request)
  {
    return $this->traitFollowerAgentDelete($request);
  }
  /**
  * output JSON for ingenOverlay: set commission share for all agency, managers assigned to the lead
  *  use MasterLeadTrait->traitFollowerUpdate()
  */
  public function ajaxFollowerProviderDelete (Request $request)
  {
    return $this->traitFollowerProviderDelete($request);
  }

  /**
  * **********     lead x commission     **********
  * output JSON for ingenOverlay: set commission share for all agency, managers assigned to the lead
  *  use MasterLeadTrait->traitFollowerMod()
  */
  public function overlayCommissionMod(Request $request)
  {
    return $this->traitCommissionMod($request, route('master.lead.commission-update', ['lead_id'=> $request->lead_id]));
  }
  /**
  * Action: update commission share for all agency, manager assigned to the lead
  *  use MasterLeadTrait->traitCommissionUpdate()
  */
  public function commissionUpdate(Request $request)
  {
    return $this->traitCommissionUpdate($request);
  }

  /**
  * **********     lead x agency     **********
  * output JSON for ingenOverlay: assign new agency (amongst agency the auth-master-agent has access to)
  *  use MasterLeadTrait->traitOverlayAgencyAssign()
  */
  public function overlayAgencyAssign(Request $request)
  {
    return $this->traitOverlayAgencyAssign($request, route('master.lead.agency-assign', ['lead_id'=> $request->lead_id]));
  }
  /**
  * Action: assign new agency to the lead (cannot exceed # set in config = MAX_AGENCY_PER_LEAD)
  *  use MasterLeadTrait->traitAgencyAssign()
  */
  public function agencyAssign(Request $request)
  {
    return $this->traitAgencyAssign($request);
  }
  /**
  * Action: remove currently assigned agency
  *  use MasterLeadTrait->traitAgencyRemove()
  */
  public function agencyRemove(Request $request)
  {
    return $this->traitAgencyRemove($request);
  }
  /**
  * **********     lead x manager     **********
  * output JSON for ingenOverlay: assign new master-agent (amongst agency the auth-master-agent has access to)
  *  use MasterLeadTrait->traitOverlayManagerAssign()
  */
  public function overlayManagerAssign(Request $request)
  {
    return $this->traitOverlayManagerAssign($request, route('master.lead.manager-assign', ['id'=> $request->lead_id]));
  }
  /**
  * Action: assign new channel manager to the lead (cannot exceed # set in config = MAX_MANAGER_PER_LEAD)
  *  use MasterLeadTrait->traitManagerAssign()
  */
  public function managerAssign(Request $request)
  {
    return $this->traitManagerAssign($request);
  }
  /**
  * Action: set selected channel-manager as a primary manager of the lead
  *  use MasterLeadTrait->traitManagerSetPrimary()
  */
  public function managerSetPrimary(Request $request)
  {
    return $this->traitManagerSetPrimary($request);
  }
  /**
  * Action: remove currently assigned channel-manager
  *  use MasterLeadTrait->traitManagerRemove()
  */
  public function managerRemove(Request $request)
  {
    return $this->traitManagerRemove($request);
  }

  /**
  * **********     lead x location     **********
  * output JSON for ingenOverlay: open file attachements
  *  use MasterLeadTrait->traitOverlayLocationFiles()
  */
  public function overlayLocationFiles(Request $request)
  {
    return $this->traitOverlayLocationFiles($request, FALSE);
  }
  /**
  * Action: attach uploaded file(s)
  *  use MasterLeadTrait->traitLocationFileAttach()
  */
  public function locationFileAttach(Request $request)
  {
    return $this->traitLocationFileAttach($request);
  }
  /**
  * AJAX Action: delete attached file
  *  use MasterLeadTrait->traitLocationFileDelete()
  */
  public function ajaxLocationFileDelete(Request $request)
  {
    return $this->traitLocationFileDelete($request);
  }
}
