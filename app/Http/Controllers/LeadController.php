<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Agency;
use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class LeadController extends Controller
{
  /*
  * custom variable
  */
  private $log_src = 'LeadController';

  /**
  * View: list of service providers.
  *
  * @return \Illuminate\Http\Response
  **/
  public function list (Request $request)
  {
    return view('leads.list')
      ->with('preapp', $request->get('preapp'));
  }
  /**
  * View: new lead
  *
  * @return \Illuminate\Http\Response
  **/
  public function new (Request $request)
  {
    $log_src = $this->log_src.'@new';

    $row_states = get_state_list();
    
    return view('leads.new')
      ->with('cust', (object)[
        'name' => '', 'tel' => '',
        'tax_id' => '',
        'email' => '',
        'addr' => '', 'addr2' => '',
        'city' => '', 'state_id' => '', 'zip' => '',
      ])
      ->with('data', (object)['row_states'=> $row_states])
      ->with('preapp', $request->get('preapp'));
  }
  /**
  * View: lead management page (continued from new lead page / update existing lead = overview + mod) 
  *
  * @param $id: lead ID encoded
  * @return \Illuminate\Http\Response
  **/
  public function manage (Request $request)
  {
    $log_src = $this->log_src.'@manage';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->id);
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);


    $data = $this->leadReload($lead, $agency_id);
    return view('leads.manage')
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
    $agency_id = dec_id($preapp->agency_id);

    $agency = Agency::find($agency_id);
    $db_rows = DB::select(
      " SELECT u.id, u.fname, u.lname
          FROM relation_agency_manager am LEFT JOIN login_users u ON am.user_id = u.id
          WHERE am.agency_id =:agency_id AND u.id >0
            LIMIT 1
    ", [$agency_id]);
    if (!$db_rows)
      return log_redirect('You do not have an associated Channel Manager.', ['src'=> $log_src, 'agency-id'=> $agency_id,]);
    $manager = $db_rows[0];
      

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
    

    // validation passed -> create new lead
    $lead_id = DB::table('leads')->insertGetId([
      'mod_id'=> $me->id,
      'mod_user'=> trim($me->fname.' '.$me->lname),
      'cust_name'=> $request->c_name,
      'tel' => $request->tel,
      'tax_id' => $p_tax,
      'email' => $p_email,
      'addr' => $p_addr, 'addr2' => $p_addr2,
      'city' => $p_city, 'state_id' => $p_state_id, 'zip' => $p_zip,
    ]);
    DB::table('lead_relation_agency')->insert([ 'lead_id'=> $lead_id, 'agency_id' => $agency_id, 'agency'=> $agency->name, ]);
    DB::table('lead_relation_manager')->insert([ 'lead_id'=> $lead_id, 'user_id' => $manager->id, 'ch_manager'=> trim($manager->fname.' '.$manager->lname), ]);

    
    // action SUCCESS: leave a log and redirect to view
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>New Lead has been created.</p><p>[Customer] '.$request->c_name.'</p>'
    ]);
    log_write('New Lead Created.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'manager-id'=> $manager->id, 'lead-id'=> $lead_id, 'log-id'=> $log_id, ]);
    return msg_redirect('The Lead has been created. Please continue with location and accounts.', route('lead.manage', ['id'=> enc_id($lead_id)]));
  }
  /**
  * Action: request master-agent for quotes (= flag the lead's quote-requested to 1 + assign ch-manager to the quote (primary manager))
  *
  * @param $id: lead ID encoded
  */
  public function requestQuote (Request $request)
  {
    $log_src = $this->log_src.'@requestQuote';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->id);
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);
    
    // validate: get channel manager of the agency
    $manager = DB::table('relation_agency_manager AS am')->select('u.*')
      ->leftJoin('login_users AS u', 'am.user_id','=','u.id')
      ->whereRaw(" am.agency_id =:agency_id AND u.id >0 ", [$agency_id])
      ->first();

    // validate: there should be at least one location to request for quotes
    $loc_count = DB::table('lead_locations')->whereRaw(" lead_id =:lead_id ", [$lead_id])->count();
    if (!$loc_count)
      return log_redirect('At least one location is required before Quote can be requested.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);


    // validate success: assign channel-manager to the lead, and flag the lead as 'Quote Requested'
    DB::insert(
      " INSERT INTO lead_relation_manager (lead_id, user_id, is_primary) VALUES (:lead_id, :user_id, 1) 
          ON DUPLICATE KEY UPDATE is_primary =1 
    ", [$lead_id, $manager->id, ]);
    DB::update(
      " UPDATE leads l LEFT JOIN lead_relation_agency la ON l.id =la.lead_id
          SET l.quote_requested =1
          WHERE l.id =:id AND la.agency_id =:agency_id
    ", [$lead_id, $agency_id]);
    

    // action SUCCESS: leave a log and redirect
    $log_id = log_lead_values((object) [
      'id' => $lead_id,
      'msg' => '<p>Quote has been requested for the Lead.</p><p>['.trim($manager->fname.' '.$manager->lname).'] has been assigned to the Lead as a Primary manager</p>',
    ]);
    log_write('Quote Requested for the Lead.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);
    return msg_redirect('The Quote has been requested. The lead is now available for adding New Quotes.');
  }

  /**
  * output JSON for ingenOverlay: reload page
  *  list of locations, control panel: location navigation, lead summary, followers, customer
  *
  * @param $id: lead ID encoded
  * @return JSON with HTML outputs
  **/
  public function ajaxReload (Request $request)
  {
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->id);
    return $this->jsonReload($lead_id, $agency_id);
  }


  /**
  * ******************************************************* lead x customer *******************************************************
  *
  * output JSON for ingenOverlay: update customer of the lead
  *
  * @param lead_id: lead ID encoded -> update lead
  */
  public function overlayCustomerMod (Request $request)
  {
    $log_src = $this->log_src.'@overlayCustomerMod';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->lead_id); // $request->lead_id is encoded
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=>$log_src, 'agency-id'=> $agency_id, 'lead-id'=>$lead_id]);
      
    $row_states = get_state_list();
    
		$html_output =
      Form::open(['url'=> route('lead.ajax-customer-update', ['id'=> $request->lead_id]), 'class'=>'frm-update']).
        view('customers.form')
          ->with('cust', (object)[
            'name' => $lead->cust_name, 'tel' => $lead->tel,
            'tax_id' => $lead->tax_id,
            'email' => $lead->email,
            'addr' => $lead->addr, 'addr2' => $lead->addr2,
            'city' => $lead->city, 'state_id' => $lead->state_id, 'zip' => $lead->zip,
          ])
          ->with('data', (object)[
              'row_states'=> $row_states,
            ])
          ->render().'
                  
        <div class="btn-group">
          '.Form::submit('Update Customer').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>

      '.Form::close().'
      <script>aoCustomerUpdate()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  /**
   * Action: update currently selected customer (NOT available in new lead page) => output customer data in JSON.
   */
  public function ajaxCustomerUpdate (Request $request)
  {
    $log_src = $this->log_src.'@ajaxCustomerUpdate';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);
    $me = Auth::user();

    $lead_id = dec_id($request->lead_id); // $request->lead_id is encoded
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);
    

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

    $state = DB::table('states')->find($request->state_id);
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

    // update both customer AND lead
    /*
    DB::update(
      " UPDATE customers SET mod_id =?, mod_user =?,
          name =?, addr =?, addr2 =?, city =?, state_id =?, zip =?,  tel =?, tax_id =?, email =?
          WHERE id =?
    ", [$me->id, trim($me->fname.' '.$me->lname),
      $request->c_name, $p_addr, $p_addr2, $p_city, $p_state_id, $p_zip,  $request->tel, $p_tax, $p_email,
      $cust_id
    ]);
    */
    DB::update(
      " UPDATE leads SET mod_id =?, mod_user =?,
          cust_name =?, addr =?, addr2 =?, city =?, state_id =?, zip =?,  tel =?, tax_id =?, email =?
          WHERE id =?
    ", [$me->id, trim($me->fname.' '.$me->lname),
      $request->c_name, $p_addr, $p_addr2, $p_city, $p_state_id, $p_zip,  $request->tel, $p_tax, $p_email,
      $lead_id
    ]);
    // get customer with updated info
    // $cust = DB::table('customers')->find($cust_id);
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Customer Information has been updated.</p>',
      'detail' => $log_detail_values,
    ]);
    log_write('Customer Information Updated.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'log-id'=> $log_id, ]);
    return $this->jsonReload($lead_id, $agency_id);
  }
  
  
  
  
  /**
  * ******************************************************* PRIVATE function *******************************************************
  * return lead-related information 
  *
  * @param $lead: (reference) lead object
  * @param $agency_id: agency ID encoded
  * @return object: [permissions, agencies, managers, followers, locations, logs]
  */
  private function leadReload (& $lead, $agency_id)
  {
    $log_src = $this->log_src.'@leadReload';
    $me = Auth::user();
    
    $agency = Agency::find($agency_id);
    $lead_id = $lead->id;
    
    // get customer state-code
    $state = DB::table('states')->find($lead->state_id);
    $lead->state_code = ($state)?  $state->code : '';
    
    $follower_masters = DB::select(
      " SELECT f.user_id, IF(u.id >0, 1,0) AS valid,
            IF(u.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), f.name) AS f_name,
            IF(u.id >0, u.title, f.title) AS title,
            IF(u.id >0, u.tel, f.tel) AS tel,
            IF(u.id >0, u.email, f.email) AS email
          FROM lead_follower_masters f LEFT JOIN login_users u ON f.user_id =u.id AND :lv_max >= u.access_lv AND u.access_lv >= :lv_min AND u.active >0
          WHERE f.lead_id =:lead_id
          ORDER BY valid, f_name, f.user_id DESC
    ", [POS_LV_MASTER_ADMIN, POS_LV_CH_USER, $lead_id]);
    $follower_agents = DB::select(
      " SELECT f.order_no, f.agency_id, IF(u.id >0 AND a.id >0, 1,0) AS valid,
            IF(u.id >0 AND a.id >0, a.name, f.agency) AS agency,
            IF(u.id >0 AND a.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), f.name) AS f_name,
            IF(u.id >0 AND a.id >0, u.title, f.title) AS title,
            IF(u.id >0 AND a.id >0, u.tel, f.tel) AS tel,
            IF(u.id >0 AND a.id >0, u.email, f.email) AS email
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
                p.name AS name,  qr.spiff AS spiff_share, qr.residual AS resid_share
              FROM lead_quotes q LEFT JOIN providers p ON q.provider_id =p.id
                LEFT JOIN lead_quote_rate_agency qr ON q.id =qr.quote_id AND qr.lead_id =:lead_id AND qr.agency_id =:agency_id
              WHERE q.location_id =:loc_id
              ORDER BY q.id
        ", [$lead_id, $agency_id, $row->id]);
        if ($quotes_tmp) {
          foreach ($quotes_tmp as $quote) {
            $quote->spiff_expect = $agency->spiff;
            $quote->resid_expect = $agency->residual;
            
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
        $r_city_state_zip = format_city_state_zip($row->city, $row->state_code, $row->zip);
        $r_addr .= ($r_addr && $r_city_state_zip)?  ', '.$r_city_state_zip : $r_city_state_zip;
        
        $row_locations[] = (object)['id'=> $row->id, 'name'=> $row->name, 'addr'=> $r_addr, 'curr_accounts'=> $r_curr, 'quotes'=> $r_quotes];
      }
    }

    // get lead-logs, only the latest 5
    $row_logs = DB::table('lead_logs')
      ->whereRaw(' lead_id =:id ', [$lead_id])
      ->orderBy('date_log','desc')->orderBy('id','desc')
      ->take(5)->get();
    
    return (object)[
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
  * @param $agency_id: agency ID
  * @return Lead object
  */
  public function getLead ($lead_id, $agency_id)
  {
    return DB::table('leads AS l')->leftJoin('lead_relation_agency AS la', 'l.id','=','la.lead_id')
      ->whereRaw(" l.id =? AND la.agency_id =? ", [$lead_id, $agency_id])->first();
  }
  /**
  * output JSON to reload Lead page with updated contents
  *  list of locations, control panel: location navigation, lead summary, followers, customer
  *
  * @param $lead_id: lead ID
  * @param $agency_id: agency ID
  * @param $vars (optional): array of additional output to include in JSON output (by default, empty)
  * @return JSON with HTML outputs
  **/
  public function jsonReload ($lead_id, $agency_id, $vars = [])
  {
    $log_src = $this->log_src.'@jsonReload';

    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);

    $data = $this->leadReload($lead, $agency_id);

    
    // create HTML output to render on reload
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
    $html_follower = view('leads.sub-follower')
      ->with('lead_id', $lead->id)
      ->with('followers', $data->followers)
      ->with('agency_id', $agency_id)
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
      
    $html_location = view('leads.sub-location')
      ->with('locations', $data->locations)
      ->with('open_first', FALSE)
      ->with('quote_requested', $lead->quote_requested)
      ->render();

    // output in JSON format: also include any additional output included in $vars
    $arr_output = [
			'success'=>1, 'error'=>0,
      'custHTML'=> $html_customer, 'followerHTML'=> $html_follower, 'logHTML'=> $html_logs, 'locOptHTML'=> $html_location_opts,
      'locHTML'=> $html_location,
    ];
    if (count($vars) >0) {
      foreach ($vars as $k=>$v)
        $arr_output[$k] = $v;
    }
		return json_encode($arr_output);
  }
}

trait GetLead {
  public function getLead ($lead_id, $agency_id) {
    return parent::getLead($lead_id, $agency_id);
  }
}
