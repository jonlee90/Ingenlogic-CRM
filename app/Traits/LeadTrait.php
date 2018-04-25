<?php

namespace App\Traits;

use App\Http\Controllers\Controller;

use App\User;
use App\Agency;
use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

trait LeadTrait
{
  /**
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
  * ******************************************************* lead x customer *******************************************************
  *
  * output JSON for ingenOverlay: update customer of the lead
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded -> update lead
  * @param $form_url: URL to submit form
  */
  public function traitCustomerMod(Request $request, $form_url)
  {
    $log_src = $this->log_src.'@traitCustomerMod';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->lead_id); // $request->lead_id is encoded
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=>$log_src, 'agency-id'=> $agency_id, 'lead-id'=>$lead_id]);
      
    $row_states = get_state_list();
    
		$html_output =
      Form::open(['url'=> $form_url, 'class'=>'frm-update']).
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
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  */
  public function traitCustomerUpdate (Request $request)
  {
    $log_src = $this->log_src.'@traitCustomerUpdate';
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


    // validated: update lead with customer info
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
    log_write('Customer Information Updated.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'log-id'=> $log_id, ]);
    return $this->jsonReload($lead_id, $agency_id);
  }



  /**
  * ******************************************************* lead x log *******************************************************
  *
  * output JSON for ingenOverlay: new lead x log
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $form_url: URL to submit form
  */
  public function traitLogNew(Request $request, $form_url)
  {
    $log_src = $this->log_src.'@overlayLogNew';
    $preapp = $request->get('preapp');
    
    $html_output =
      Form::open(['url'=> $form_url, 'class'=>'frm-log', 'method'=> 'PUT']).
        view('leads.form-log')
          ->with('me', Auth::user())
          ->with('msg', '')
          ->render().'
            
        <div class="btn-group">
          '.Form::submit('Add New').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>

      '.Form::close().'
      <script>aoLogAdd()</script>
    ';
    return json_encode([
      'success'=>1, 'error'=>0,
      'html'=> $html_output
    ]);
  }
  /**
  * output JSON for ingenOverlay: update lead x log message (= mark the log "corrected" and create new log)
  *
  * @param $request
  * @param $request->log_id: log ID encoded
  * @param $form_url: URL to submit form
  */
  public function traitLogMod(Request $request, $form_url)
  {
    $log_src = $this->log_src.'@overlayLogMod';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    $log_id = dec_id($request->log_id);
    $lead_log = DB::table('lead_logs')->find($log_id);
    if (!$lead_log)
      return log_ajax_err('Log Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'log-id'=> $log_id]);
    if ($lead_log->mod_id != $me->id)
      return log_ajax_err('You have No Access to the Log.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'log-id'=> $log_id]);
    
    $html_output =
      Form::open(['url'=> $form_url, 'class'=>'frm-log']).
        view('leads.form-log')
          ->with('me', $me)
          ->with('msg', $lead_log->log_msg)
          ->render().'
            
        <div class="btn-group">
          '.Form::submit('Make Correction').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>

      '.Form::close().'
      <script>aoLogAdd()</script>
    ';
    return json_encode([
      'success'=>1, 'error'=>0,
      'html'=> $html_output
    ]);
  }
  /**
  *
  * output JSON for ingenOverlay: show all lead x logs
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  */
  public function traitLogHistory(Request $request)
  {
    $log_src = $this->log_src.'@traitLogHistory';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->lead_id);
    $row_logs = DB::table('lead_logs')
      ->whereRaw(' lead_id =:id ', [$lead_id])
      ->orderBy('date_log','desc')->orderBy('id','desc')
      ->get();
    

		$html_output =
      '<div>
        <div class="lead-log-history">'.
          view('leads.sub-log')
            ->with('show_detail', 1)
            ->with('logs', $row_logs)
            ->render().'
        </div>

        <div class="btn-group">
          '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
        </div>
      </div>
      <script>aoLogHistory()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }

  /**
  * Action: add new log -> output data in JSON.
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  */
  public function traitLogAdd (Request $request)
  {
    $log_src = $this->log_src.'@traitLogAdd';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=>$lead_id]);
      
    
    // input validation
    $v = Validator::make($request->all(), [
      'msg' => 'required|max:500',
    ], [
      'msg.*'=> 'Message cannot exceed 500 characters.',
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

    // validation passed -> add new log
    $log_id = log_lead((object) ['id' => $lead_id, 'msg' => $request->msg, 'auto' => 0, ]);
    
    // action SUCCESS: leave a log and output JSON
    log_write('Lead x Log left manually.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'log-id'=> $log_id ]);
    return $this->jsonReload($lead_id, $agency_id);
  }
  /**
  * Action: correct existing log -> mark log as "corrected", and new log -> output data in JSON.
  *
  * @param $request
  * @param $request->log_id: log ID encoded
  */
  public function traitLogCorrect (Request $request)
  {
    $log_src = $this->log_src.'@traitLogCorrect';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    $log_id = dec_id($request->log_id);
    $lead_log = DB::table('lead_logs')->find($log_id);
    if (!$lead_log)
      return log_ajax_err('Log Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'log-id'=> $log_id]);
    if ($lead_log->mod_id != $me->id)
      return log_ajax_err('You have No Access to the Log.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'log-id'=> $log_id]);
      
    $lead_id = $lead_log->lead_id;
      
    
    // input validation
    $v = Validator::make($request->all(), [
      'msg' => 'required|max:500',
    ], [
      'msg.*'=> 'Message cannot exceed 500 characters.',
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


    // validation passed -> mark "corrected" and add new log (Query Builder)
    DB::update(" UPDATE lead_logs SET is_corrected =1 WHERE id =:log_id AND lead_id =:lead_id ", [$log_id, $lead_id]);
    $new_log_id = log_lead((object) ['id' => $lead_id, 'msg' => $request->msg, 'auto' => 0, ]);
    
    // action SUCCESS: leave a log and output JSON
    log_write('Lead x Log has been corrected.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'log-id'=> $log_id, 'new-log-id'=> $new_log_id ]);
    return $this->jsonReload($lead_id, $agency_id);
  }


  

  /**
  * ******************************************************* lead x followers *******************************************************
  *
  * output JSON for ingenOverlay: update follower(s) - list of agents + provider-contacts
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $frm_url: URL to submit form
  */
  public function traitFollowerMod(Request $request, $frm_url)
  {
    $log_src = $this->log_src.'@traitFollowerMod';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);
    

    // get list of users from same 'Agency'
    $agents = DB::select(
      " SELECT u.id, u.fname, u.lname, u.title, u.tel, u.email
          FROM login_users u LEFT JOIN relation_user_agency ua ON u.id =ua.user_id
          WHERE u.active =1 AND ua.agency_id =:agency_id
          ORDER BY u.fname, u.lname, u.id DESC
    ", [$agency_id]);

    // get list of contacts from providers in the Lead
    $prov_contacts = DB::select(
      " SELECT p.id AS prov_id, p.name AS prov_name,  c.id AS contact_id, c.fname, c.lname, c.title, c.email, c.tel
          FROM (
            SELECT q.provider_id  FROM lead_locations l LEFT JOIN lead_quotes q ON l.id =q.location_id
              WHERE l.lead_id =:lead_id AND q.provider_id >0
              GROUP BY q.provider_id
          ) t
            LEFT JOIN providers p ON t.provider_id =p.id
              LEFT JOIN provider_contacts c ON p.id =c.provider_id
          WHERE c.id >0
    ", [$lead_id]);

    // get list of currently saved followers (agent)
    $agent_followers = DB::select(
      " SELECT f.user_id, IF(u.id >0 AND a.id >0, 1,0) AS valid,
            IF(u.id >0 AND a.id >0, a.name, f.agency) AS agency,
            IF(u.id >0 AND a.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), f.name) AS f_name,
            IF(u.id >0 AND a.id >0, u.title, f.title) AS title,
            IF(u.id >0 AND a.id >0, u.tel, f.tel) AS tel,
            IF(u.id >0 AND a.id >0, u.email, f.email) AS email
          FROM lead_follower_agents f LEFT JOIN relation_user_agency ua ON f.user_id =ua.user_id AND f.agency_id =ua.agency_id
            LEFT JOIN login_users u ON ua.user_id =u.id AND u.active >0
            LEFT JOIN agencies a ON ua.agency_id =a.id AND a.active >0
          WHERE f.lead_id =:lead_id AND f.agency_id =:agency_id
          ORDER BY valid, f_name, f.order_no
    ", [$lead_id, $agency_id, ]);

    // get list of currently saved followers (provider-contact)
    $prov_followers = DB::select(
      " SELECT f.provider_id, f.contact_id, IF(c.id >0, 1,0) AS valid,
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


		$html_output =
        view('leads.form-follower')
          ->with('lead_id', $lead_id)
          ->with('frm_url', $frm_url)
          ->with('agents', $agents)
          ->with('prov_contacts', $prov_contacts)
          ->with('agent_followers', $agent_followers)
          ->with('prov_followers', $prov_followers)
          ->render().'
          
      <script>aoLeadFollowers()</script>
		';    
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  
  
  /**
  * Action: update lead x followers (agent and/or provider-contacts) => output data in JSON.
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  */
  public function traitFollowerUpdate (Request $request)
  {
    $log_src = $this->log_src.'@traitFollowerUpdate';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $agency = Agency::find($agency_id);

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);
      

    // all POST arrays should have same count
    $n_agents = count($request->user_id);
    $n_provs = count($request->prov_id);
    if ($n_provs != count($request->contact_id))
      return log_ajax_err('Form Input is misconfigured. Please contact the adminstrator.', [ 'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id ]);

    // duplicates are skipped
    $row_agents = $row_provs = $row_contacts = [];
    for ($i =0; $i < $n_agents; $i++) {
      $r_user_id = dec_id($request->user_id[$i]);
      if (in_array($r_user_id, $row_agents))
        continue;
      $row_agents[] = $r_user_id;
    }
    for ($i =0; $i < $n_provs; $i++) {
      $r_prov_id = dec_id($request->prov_id[$i]);
      $r_contact_id = dec_id($request->contact_id[$i]);
      
      if (in_array($r_prov_id, $row_provs))
        continue;
      $row_provs[] = $r_prov_id;
      $row_contacts[] = $r_contact_id;
    }
    $n_agents = count($row_agents);
    $n_provs = count($row_provs);

    
    // loop through agents and provider-contacts (check if followers are valid users/contacts) -> also create logging detail object -> leave lead x log
    $old_agents = DB::select(
      " SELECT user_id, agency, name  FROM lead_follower_agents  WHERE lead_id =:lead_id AND agency_id =:agency_id  ORDER BY order_no
    ", [$lead_id, $agency_id]);
    $old_provs = DB::select(
      " SELECT contact_id, prov_name AS prov, name  FROM lead_follower_providers  WHERE lead_id =:lead_id  ORDER BY order_no
    ", [$lead_id]);

    $new_agents = $new_provs = [];
    $db_insert_agents = $db_insert_contacts = [];

    // list of agents (users)
    for ($i =0; $i < $n_agents; $i++) {
      $r_user_id = $row_agents[$i];

      $r_agent = User::whereRaw(" id =:user_id AND active =1 ", [$r_user_id])->first();
      if (!$r_agent)
        return log_ajax_err('Invalid input has been entered.', [ 'src'=> $log_src, 'msg'=> 'user NOT found.', 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'user-id'=>$user_id ]);

      $r_name = trim($r_agent->fname.' '.$r_agent->lname);
      $db_insert_agents[] = [
        'lead_id'=> $lead_id,
        'order_no'=> $i,
        'agency_id'=> $agency_id,
        'user_id'=> $r_user_id,
        'agency'=> $agency->name,
        'name'=> $r_name,
        'title'=> $r_agent->title,
        'tel'=> $r_agent->tel,
        'email'=> $r_agent->email,
      ];
      $new_agents[] = (object)['user_id' => $r_user_id, 'agency' => $agency->name, 'name' => $r_name, ];
    }

    // list of provider-contacts
    for ($i =0; $i < $n_provs; $i++) {
      $r_prov_id = $row_provs[$i];
      $r_contact_id = $row_contacts[$i];

      $r_prov_id = dec_id($request->prov_id[$i]);
      $r_contact_id = dec_id($request->contact_id[$i]);

      $db_rows = DB::select(
      " SELECT p.name AS prov_name,  c.fname, c.lname, c.title, c.email, c.tel
          FROM provider_contacts c LEFT JOIN providers p ON c.provider_id =p.id
          WHERE c.id =:contact_id AND p.id =:prov_id
          LIMIT 1
      ", [$r_contact_id, $r_prov_id ]);
      if (!$db_rows)
        return log_ajax_err('Invalid input has been entered.', [
          'src'=> $log_src, 'msg'=> 'provider x contact NOT found.', 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'provider-id'=>$r_prov_id, 'provider-contact-id'=>$r_contact_id
        ]);
      $r_contact = $db_rows[0];

      $r_name = trim($r_contact->fname.' '.$r_contact->lname);
      $db_insert_contacts[] = [
        'lead_id'=> $lead_id,
        'order_no'=> $i,
        'provider_id'=> $r_prov_id,
        'contact_id'=> $r_contact_id,
        'prov_name'=> $r_contact->prov_name,
        'name'=> $r_name,
        'title'=> $r_contact->title,
        'tel'=> $r_contact->tel,
        'email'=> $r_contact->email,
      ];
      $new_provs[] = (object)['contact_id'=> $r_contact_id, 'prov' => $r_contact->prov_name, 'name' => $r_name, ];
    }

    
    // validation passed -> clear any corrupted agent-followers (user x agency mistmatch) -> reset lead x followers (= delete existing and add)
    DB::delete(
      " DELETE f FROM
          lead_follower_agents f LEFT JOIN relation_user_agency ua ON f.user_id =ua.user_id AND f.agency_id =ua.agency_id
            LEFT JOIN login_users u ON ua.user_id =u.id AND u.active >0
            LEFT JOIN agencies a ON ua.agency_id =a.id AND a.active >0
          WHERE f.lead_id =:lead_id AND (u.id IS NULL OR a.id IS NULL)
      ", [$lead_id]);
    DB::delete(" DELETE FROM lead_follower_agents  WHERE lead_id =:lead_id AND agency_id =:agency_id ", [$lead_id, $agency_id,]);
    DB::delete(" DELETE FROM lead_follower_providers  WHERE lead_id =:lead_id ", [$lead_id]);
    DB::table('lead_follower_agents')->insert($db_insert_agents);
    DB::table('lead_follower_providers')->insert($db_insert_contacts);
    
    // action SUCCESS: leave a log and output JSON
    $log_id = $this->log_lead_followers((object) [
      'id' => $lead_id, 
      'msg' => '<p>Follower(s) have been updated.</p>',
      'old_agents' => $old_agents, 'new_agents' => $new_agents,
      'old_provs' => $old_provs, 'new_provs' => $new_provs,
    ]);
    log_write('Lead x Followers Updated.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, ]);
    return $this->jsonReload($lead_id, $agency_id);
  }
  /**
  * AJAX Action: delete lead x followers (agent) => output data in JSON.
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $request->order_no: follower order-no encoded (follower has primary key of lead ID x order no)
  */
  public function traitFollowerAgentDelete (Request $request)
  {
    $log_src = $this->log_src.'@traitFollowerAgentDelete';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->lead_id);
    $order_no = dec_id($request->order_no);

    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'order-no'=> $order_no]);

    $follower = DB::table('lead_follower_agents')->whereRaw(" lead_id =:lead_id AND order_no =:order_no ", [$lead_id, $order_no]);
    if (!$follower->count())
      return log_ajax_err('Follower Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'order-no'=> $order_no]);

    $follower_agent = $follower->first();


    // delete the follower (Query Builder)
    $follower->delete();
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Follower has been removed.</p><p>[Agent] '.$follower_agent->name.'</p>',
    ]);
    log_write('Lead x Follower (Agent) removed.', ['src'=> $log_src, 'agent-id'=>$agency_id, 'lead-id'=> $lead_id, 'order-no'=> $order_no]);
    return $this->jsonReload($lead_id, $agency_id);
  }
  /**
  * AJAX Action: delete lead x followers (provider contact) => output data in JSON.
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $request->order_no: follower order-no encoded (follower has primary key of lead ID x order no)
  */
  public function traitFollowerProviderDelete (Request $request)
  {
    $log_src = $this->log_src.'@traitFollowerProviderDelete';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->lead_id);
    $order_no = dec_id($request->order_no);

    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'order-no'=> $order_no]);

    $follower = DB::table('lead_follower_providers')->whereRaw(" lead_id =:lead_id AND order_no =:order_no ", [$lead_id, $order_no]);
    if (!$follower->count())
      return log_ajax_err('Follower Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'order-no'=> $order_no]);

    $follower_prov = $follower->first();


    // delete the follower (Query Builder)
    $follower->delete();
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Follower has been removed.</p><p>[Provider: '.$follower_prov->prov_name.'] '.$follower_prov->name.'</p>',
    ]);
    log_write('Lead x Follower (Provider Contact) removed from.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'order-no'=> $order_no,
    ]);
    return $this->jsonReload($lead_id, $agency_id);
  }


  /**
  * ******************************************************* lead x location *******************************************************
  *
  * output JSON for ingenOverlay: open file attachements
  *
  * @param $request
  * @param $request->loc_id: location ID encoded
  * @param $is_project: if function call is from lead-management or project-management
  */
  public function traitOverlayLocationFiles (Request $request, $is_project)
  {
    $log_src = $this->log_src.'@traitOverlayLocationFiles';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, ]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, ]);
    

    // list of attached files. if file not exists, remove from DB
    $files = [];
    $db_rows = DB::table('lead_location_files')->whereRaw(" location_id =:loc_id AND lead_id =:lead_id ", [$loc_id, $lead_id])->get();
    if ($db_rows) {
      foreach ($db_rows as $f) {
        $f_path = public_path().'/upload/loc/'.$f->id.'/'.$f->url;
        if (file_exists($f_path)) {
          $f->size = filesize($f_path);
          $files[] = $f;
        } else
          DB::table('lead_location_files')->where('id', $f->id)->delete();
      }
    }
    
		$html_output =
      view('leads.form-loc-files')
        ->with('loc_id', $loc_id)
        ->with('files', $files)
        ->with('is_project', $is_project)
        ->render().'
      
      <script>moLocationFiles()</script>
      ';

		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }

  /**
  * Action: upload location x file attachements
  *
  * @param $request
  * @param $request->loc_id: location ID encoded
  */
  public function traitLocationFileAttach (Request $request)
  {
    $log_src = $this->log_src.'@traitLocationFileAttach';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, ]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, ]);


    //  get already uploaded files -> apply file-size toward size-limit
    $total_f_size = 0;
    $current_files = DB::table('lead_location_files')->whereRaw(" location_id =:loc_id AND lead_id =:lead_id ", [$loc_id, $lead_id])->get();
    if ($current_files) {
      foreach ($current_files as $f) {
        $f_path = public_path().'/upload/loc/'.$f->id.'/'.$f->url;
        if (file_exists($f_path)) {
          $files[] = $f;
          $total_f_size += filesize($f_path);
        } else
          DB::table('lead_location_files')->where('id', $f->id)->delete();
      }
    }

    // input validation
    $files = $request->file('attachments');
    if (!$files)
      return log_redirect('Please upload at least one file.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, ]);

    $log_detail = '<ul class="lead-log-files">';
    foreach ($files as $f) {
      $f_name = $f->getClientOriginalName();
      $f_size = $f->getClientSize();

      if ($f_size <= 0)
        return log_redirect('One of the Uploaded File has a file size of 0 byte.', [
          'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'file'=> $f_name, 'size'=> $f_size, ]);
      $total_f_size += $f_size;
      
      $log_detail .= '<li>'.$f_name.'</li>';
    }
    $log_detail .= '</ul>';

    if ($total_f_size > LIMIT_LOCATION_FILE_SIZE_MB * 1048576)
      return log_redirect('Total File size of All uploaded files cannot exceed '.LIMIT_LOCATION_FILE_SIZE_MB.' MB.', [
        'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id,
      ]);

    
    // validate success: save file info om DB, move all uploaded files
    foreach ($files as $f) {
      $f_name = substr($f->getClientOriginalName(), 0,50);
      $f_name_enc = enc_id($f_name);
      $file_id = DB::table('lead_location_files')->insertGetId([
        'location_id'=> $loc_id, 'lead_id'=> $lead_id, 'f_desc'=> $f_name, 'url'=> $f_name_enc
      ]);
      $f_path = $f->move(public_path()."/upload/loc/$file_id/", $f_name_enc);
    }

    
    // action SUCCESS: leave a log and redirect
    $log_id = log_lead((object) [
      'id' => $lead_id, 
      'msg' => '<p>'.count($files).' File(s) attached to the Location.</p><p>[Location] '.$loc->name.'</p>',
      'auto'=> 1,
      'detail'=> $log_detail,
    ]);
    log_write('Location x Files attached.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, '# file'=> count($files) ]);
    return msg_redirect('File(s) attached to the Location.');
  }

  /**
  * AJAX Action: delete attached file
  *  use MasterLeadTrait->traitLocationFileDelete()
  *
  * @param $request
  * @param $request->file_id: file ID encoded
  */
  public function traitLocationFileDelete (Request $request)
  {
    $log_src = $this->log_src.'@traitLocationFileDelete';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $file_id = dec_id($request->file_id);
    $file = DB::table('lead_location_files')->find($file_id);
    if (!$file)
      return log_redirect('Attached File Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'file-id'=> $file_id, ]);

    $loc_id = $file->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'file-id'=> $file_id, ]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'file-id'=> $file_id, ]);


  
    DB::table('lead_location_files')->where('id',$file_id)->delete();

    
    // action SUCCESS: leave a log and redirect
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Attached File has been removed from the Location.</p><p>[Location] '.$loc->name.', [File] '.$file->f_desc.'</p>',
    ]);
    log_write('Location x Files deleted.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'file-id'=> $file_id, 'file'=> $file->f_desc,
    ]);
    return $this->jsonReload($lead_id, $agency_id);
  }



  /**
  * ******************************************************* lead x log helper functions *******************************************************
  * extended log_lead function for account x products
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
  /**
  * extended log_lead function for followers
  *
  * @param $obj: object [
  *		id: lead ID
  *		msg: log message
  *		old_agents: array of agent-followers before change
  *		new_agents: array of agent-followers after change
  *		old_provs: array of provider-followers before change
  *		new_provs: array of provider-followers after change
  * @return log_lead (): log ID (= last insert id)
  **/
  private function log_lead_followers ($obj) {
    $lead_id = $obj->id;
    $msg = $obj->msg;
    
    $del_agents = [];
    $del_provs = [];

    $new_agents = $obj->new_agents;
    $n_old = count($obj->old_agents);
    for ($i =0; $i < $n_old; $i++) {
      $found = FALSE;

      $n_new = count($new_agents);
      for ($j =0; !$found && $j < $n_new; $j++) {
        if ($obj->old_agents[$i]->user_id == $new_agents[$j]->user_id) {
          unset($new_agents[$j]);
          $new_agents = array_values($new_agents);
          $found = TRUE;
        }
      }
      if (!$found)
        $del_agents[] = $obj->old_agents[$i];
    }
    
    $new_provs = $obj->new_provs;
    $n_old = count($obj->old_provs);
    for ($i =0; $i < $n_old; $i++) {
      $found = FALSE;

      $n_new = count($new_provs);
      for ($j =0; !$found && $j < $n_new; $j++) {
        if ($obj->old_provs[$i]->contact_id == $new_provs[$j]->contact_id) {
          unset($new_provs[$i]);
          $new_provs = array_values($new_provs);
          $found = TRUE;
        }
      }
      if (!$found)
        $del_provs[] = $obj->old_provs[$j];
    }
    
    $detail = '';
    if ($del_agents) {
      foreach ($del_agents as $follower)
        $detail .= '<p>[Agency: '.$follower->agency.' x Agent: '.$follower->name.'] has been removed.</p>';
    }
    if ($new_agents) {
      foreach ($new_agents as $follower)
        $detail .= '<p>[Agency: '.$follower->agency.' x Agent: '.$follower->name.'] has been added.</p>';
    }
    if ($del_provs) {
      foreach ($del_provs as $follower)
        $detail .= '<p>[Provider: '.$follower->prov.' x Contact: '.$follower->name.'] has been removed.</p>';
    }
    if ($new_provs) {
      foreach ($new_provs as $follower)
        $detail .= '<p>[Provider: '.$follower->prov.' x Contact: '.$follower->name.'] has been added.</p>';
    }
    if (!$detail)
      $detail = '<p>* No Change</p>';

    return log_lead((object)[
      'id' => $obj->id, 'msg' => $obj->msg, 'auto' => 1, 'detail' => $detail,
    ]);
  }

}
