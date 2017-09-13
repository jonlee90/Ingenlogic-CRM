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
use Illuminate\Support\Facades\Storage;
use Validator;

trait MasterLeadTrait
{
  /**
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
    $log_src = $this->log_src.'@overlayCustomerMod';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $lead_id = dec_id($request->lead_id);
    
    $lead = $this->getLead($lead_id, $preapp->manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, ]);
      
    $row_states = get_state_list();
    
		$html_output =
      Form::open(['url'=> $form_url, 'class'=>'frm-update']).
        view('master.leads.form-customer')
          ->with('data', (object)[
              'row_states'=> $row_states,
            ])
          ->with('lead', $lead)
          ->render().'
                  
        <div class="btn-group">
          '.Form::submit('Update Customer').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
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
  * Action: update currently selected customer (NOT available in new lead page) => output customer data in JSON.
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  */
  public function traitCustomerUpdate (Request $request)
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
    log_write('Customer Information Updated.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'log-id'=> $log_id, ]);
    return $this->jsonReload($lead_id, $manager_id);
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
    $log_src = $this->log_src.'@traitLogNew';
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
    $log_src = $this->log_src.'@traitLogMod';
    $preapp = $request->get('preapp');
    $me = Auth::user();

    $log_id = dec_id($request->log_id);
    $lead_log = DB::table('lead_logs')->find($log_id);
    if (!$lead_log)
      return log_ajax_err('Log Not found.', ['src'=> $log_src, 'log-id'=> $log_id]);
    if ($lead_log->mod_id != $me->id)
      return log_ajax_err('You have No Access to the Log.', ['src'=> $log_src, 'log-id'=> $log_id]);
    
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
    $manager_id = $preapp->manager_id;

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=>$log_src, 'lead-id'=>$lead_id, ]);
      
    
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
    log_write('Lead x Log left manually.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'log-id'=> $log_id ]);
    return $this->jsonReload($lead_id, $manager_id);
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
    $manager_id = $preapp->manager_id;

    $log_id = dec_id($request->log_id);
    $lead_log = DB::table('lead_logs')->find($log_id);
    if (!$lead_log)
      return log_ajax_err('Log Not found.', ['src'=> $log_src, 'log-id'=> $log_id]);
    if ($lead_log->mod_id != $me->id)
      return log_ajax_err('You have No Access to the Log.', ['src'=> $log_src, 'log-id'=> $log_id]);
      
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
    log_write('Lead x Log has been corrected.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'log-id'=> $log_id, 'new-log-id'=> $new_log_id ]);
    return $this->jsonReload($lead_id, $manager_id);
  }


  

  /**
  * ******************************************************* lead x followers *******************************************************
  *
  * output JSON for ingenOverlay: update follower(s) - list of master-agents + provider-contacts
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $frm_url: URL to submit form
  */
  public function traitFollowerMod(Request $request, $frm_url)
  {
    $log_src = $this->log_src.'@traitFollowerMod';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $manager_id = $preapp->manager_id;

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getlead($lead_id, $manager_id);
    

    // get list of 'Master Agent', 'Channal Manager', and 'Channel assistants' (associated with the manager).
    $masters = DB::select(
      " SELECT u.id, u.fname, u.lname, u.title, u.tel, u.email, u.access_lv,
            IF(u.access_lv >= :master_user_lv1, 1,0) is_master
          FROM login_users u
            LEFT JOIN relation_assistant_manager am ON u.id =am.assistant_id
          WHERE u.active =1 AND :master_admin_lv >= u.access_lv AND u.access_lv >= :ch_user_lv
              AND (:auth_lv >= :master_manager_lv OR u.access_lv >= :master_user_lv2 OR u.id =:manager_id1 OR am.user_id =:manager_id2)
            GROUP BY u.id
          ORDER BY is_master, u.access_lv DESC, u.fname, u.lname, u.id DESC
    ", [POS_LV_MASTER_USER, POS_LV_MASTER_ADMIN, POS_LV_CH_USER, $me->access_lv, POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER, $manager_id, $manager_id]);

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

    // get list of currently saved followers (master): exclude followers that auth-user has no access
    $master_followers = DB::select(
      " SELECT f.user_id, IF(u.id >0, 1,0) AS valid,
            IF(u.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), f.name) AS f_name,
            IF(u.id >0, u.title, f.title) AS title,
            IF(u.id >0, u.tel, f.tel) AS tel,
            IF(u.id >0, u.email, f.email) AS email
          FROM lead_follower_masters f
            LEFT JOIN login_users u ON f.user_id =u.id AND :master_admin_lv >= u.access_lv AND u.access_lv >= :ch_user_lv AND u.active >0
              LEFT JOIN relation_assistant_manager am ON u.id =am.assistant_id
          WHERE f.lead_id =:lead_id AND (:auth_lv >= :master_manager_lv OR u.access_lv >= :master_user_lv OR u.id =:auth_id OR am.user_id =:manager_id)
          ORDER BY valid, f_name, f.user_id DESC
    ", [ POS_LV_MASTER_ADMIN, POS_LV_CH_USER, $lead_id, $me->access_lv, POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER, $me->id, $manager_id, ]);

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
        view('master.leads.form-follower')
          ->with('lead_id', $lead_id)
          ->with('frm_url', $frm_url)
          ->with('masters', $masters)
          ->with('prov_contacts', $prov_contacts)
          ->with('master_followers', $master_followers)
          ->with('prov_followers', $prov_followers)
          ->render().'
          
      <script>moFollower()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  
  
  /**
  * Action: update lead x followers (master and/or provider-contacts) => output data in JSON.
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  */
  public function traitFollowerUpdate (Request $request)
  {
    $log_src = $this->log_src.'@traitFollowerUpdate';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $manager_id = $preapp->manager_id;

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id ]);
    

    // all POST arrays should have same count
    $n_masters = count($request->user_id);
    $n_provs = count($request->prov_id);
    if ($n_provs != count($request->contact_id))
      return log_ajax_err('Form Input is misconfigured. Please contact the adminstrator.', [ 'src'=> $log_src, 'lead-id'=> $lead_id ]);

    // duplicates are skipped
    $row_masters = $row_provs = $row_contacts = [];
    for ($i =0; $i < $n_masters; $i++) {
      $r_user_id = dec_id($request->user_id[$i]);
      if (in_array($r_user_id, $row_masters))
        continue;
      $row_masters[] = $r_user_id;
    }
    for ($i =0; $i < $n_provs; $i++) {
      $r_prov_id = dec_id($request->prov_id[$i]);
      $r_contact_id = dec_id($request->contact_id[$i]);
      
      if (in_array($r_prov_id, $row_provs))
        continue;
      $row_provs[] = $r_prov_id;
      $row_contacts[] = $r_contact_id;
    }
    $n_masters = count($row_masters);
    $n_provs = count($row_provs);

    
    // loop through master-agents and provider-contacts (check if followers are valid users/contacts) -> also create logging detail object -> leave lead x log
    $old_masters = DB::select(
      " SELECT name, user_id  FROM lead_follower_masters  WHERE lead_id =:lead_id  ORDER BY name, user_id DESC
    ", [$lead_id]);
    $old_provs = DB::select(
      " SELECT contact_id, prov_name AS prov, name  FROM lead_follower_providers  WHERE lead_id =:lead_id  ORDER BY order_no
    ", [$lead_id]);

    $db_insert_masters = $db_insert_contacts = [];

    // list of masters (users)
    for ($i =0; $i < $n_masters; $i++) {
      $r_user_id = $row_masters[$i];

      $r_user = User::whereRaw(" id =:user_id AND active =1 ", [$r_user_id])->first();
      if (!$r_user)
        return log_ajax_err('Invalid input has been entered.', [ 'src'=> $log_src, 'msg'=> 'user NOT found.', 'lead-id'=> $lead_id, 'user-id'=> $user_id ]);

      $r_name = trim($r_user->fname.' '.$r_user->lname);
      $db_insert_masters[] = [
        'lead_id'=> $lead_id,
        'user_id'=> $r_user_id,
        'name'=> $r_name,
        'title'=> $r_user->title,
        'tel'=> $r_user->tel,
        'email'=> $r_user->email,
      ];
    }

    // list of provider-contacts
    for ($i =0; $i < $n_provs; $i++) {
      $r_prov_id = $row_provs[$i];
      $r_contact_id = $row_contacts[$i];
      
      $db_rows = DB::select(
      " SELECT p.name AS prov_name,  c.fname, c.lname, c.title, c.email, c.tel
          FROM provider_contacts c LEFT JOIN providers p ON c.provider_id =p.id
          WHERE c.id =:contact_id AND p.id =:prov_id
          LIMIT 1
      ", [$r_contact_id, $r_prov_id]);
      if (!$db_rows)
        return log_ajax_err('Invalid input has been entered.', [
          'src'=> $log_src, 'msg'=> 'provider x contact NOT found.', 'lead-id'=> $lead_id, 'provider-id'=> $r_prov_id, 'provider-contact-id'=> $r_contact_id
        ]);
      $r_contact = $db_rows[0];

      $r_name = trim($r_contact->fname.' '.$r_contact->lname);
      $db_insert_contacts[] = [
        'lead_id'=> $lead_id,
        'order_no'=> $i,
        'provider_id'=> $r_prov_id,
        'contact_id'=> $r_contact_id,
        'prov_name'=> $r_contact->prov_name,
        'name'=> trim($r_contact->fname.' '.$r_contact->lname),
        'title'=> $r_contact->title,
        'tel'=> $r_contact->tel,
        'email'=> $r_contact->email,
      ];
    }
    
    // validation passed -> reset lead x followers (= delete existing and add)
    DB::delete(
      " DELETE f 
          FROM lead_follower_masters f
            LEFT JOIN login_users u ON f.user_id =u.id
            LEFT JOIN relation_assistant_manager am ON f.user_id =am.assistant_id
          WHERE f.lead_id =:lead_id
            AND (:auth_lv >= :master_manager_lv OR u.id IS NULL OR u.access_lv >= :master_user_lv OR f.user_id =:manager_id1 OR am.user_id =:manager_id2)
    ", [$lead_id, $me->access_lv, POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER, $manager_id, $manager_id]);
    DB::delete(" DELETE FROM lead_follower_providers  WHERE lead_id =:lead_id ", [$lead_id]);
    DB::table('lead_follower_masters')->insert($db_insert_masters);
    DB::table('lead_follower_providers')->insert($db_insert_contacts);


    // get follower-masters + follower-providers after the change
    $new_masters = DB::select(
      " SELECT name, user_id  FROM lead_follower_masters  WHERE lead_id =:lead_id  ORDER BY name, user_id DESC
    ", [$lead_id]);
    $new_provs = DB::select(
      " SELECT contact_id, prov_name AS prov, name  FROM lead_follower_providers  WHERE lead_id =:lead_id  ORDER BY order_no
    ", [$lead_id]);
    
    // action SUCCESS: leave a log and output JSON
    $log_id = $this->log_lead_followers((object) [
      'id' => $lead_id, 
      'msg' => '<p>Follower(s) have been updated.</p>',
      'old_masters' => $old_masters, 'new_masters' => $new_masters,
      'old_provs' => $old_provs, 'new_provs' => $new_provs,
    ]);
    log_write('Lead x Followers Updated.', ['src'=> $log_src, 'lead-id'=> $lead_id, ]);
    return $this->jsonReload($lead_id, $manager_id);
  }
  /**
  * AJAX Action: delete lead x followers (master) => output data in JSON.
  *
  * @param lead_id: lead ID encoded
  * @param order_no: follower order-no encoded (follower has primary key of lead ID x order no)
  */
  public function traitFollowerMasterDelete (Request $request)
  {
    $log_src = $this->log_src.'@traitFollowerMasterDelete';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $manager_id = $preapp->manager_id;

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=>$lead_id, 'user-id'=> $user_id, 'manager_id'=> $manager_id]);
    
    $user_id = dec_id($request->user_id);
    $follower = DB::table('lead_follower_masters')->whereRaw(" lead_id =:lead_id AND user_id =:user_id ", [$lead_id, $user_id]);
    if (!$follower->count())
      return log_ajax_err('Follower Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'user-id'=> $user_id, 'manager_id'=> $manager_id]);
      
    $follower_master = $follower->first();


    // delete the follower (Query Builder)
    $follower->delete();
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Follower has been removed.</p><p>[Agent] '.$follower_master->name.'</p>',
    ]);
    log_write('Lead x Follower (Master Agent) removed.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'user-id'=> $user_id, 'manager_id'=> $manager_id]);
    return $this->jsonReload($lead_id, $manager_id);
  }
  /**
  * AJAX Action: delete lead x followers (agent) => output data in JSON.
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $request->order_no: follower order-no encoded (follower has primary key of lead ID x order no)
  */
  /*
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
    return $this->jsonReload($lead_id, $manager_id);
  }
  */
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
    $me = Auth::user();
    $manager_id = $preapp->manager_id;
    
    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=>$lead_id, 'order-no'=> $order_no, 'manager_id'=> $manager_id]);
    
    $order_no = dec_id($request->order_no);
    $follower = DB::table('lead_follower_providers')->whereRaw(" lead_id =:lead_id AND order_no =:order_no ", [$lead_id, $order_no]);
    if (!$follower->count())
      return log_ajax_err('Follower Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'order-no'=> $order_no, 'manager_id'=> $manager_id]);

    $follower_prov = $follower->first();


    // delete the follower (Query Builder)
    $follower->delete();
    
    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Follower has been removed.</p><p>[Provider: '.$follower_prov->prov_name.'] '.$follower_prov->name.'</p>',
    ]);
    log_write('Lead x Follower (Provider Contact) removed from [Lead Management Page].', ['src'=> $log_src, 'lead-id'=> $lead_id, 'order-no'=> $order_no, 'manager_id'=> $manager_id]);
    return $this->jsonReload($lead_id, $manager_id);
  }


  /**
  * ******************************************************* lead x commission *******************************************************
  *
  * output JSON for ingenOverlay: set commission share for all agency, managers assigned to the lead
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $frm_url: URL to submit form
  */
  public function traitCommissionMod(Request $request, $frm_url)
  {
    $log_src = $this->log_src.'@traitCommissionMod';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $manager_id = $preapp->manager_id;

    if ($me->access_lv < POS_LV_MASTER_USER)
      return no_access_ajax(['src'=> $log_src, 'lead-id'=> $lead_id, ]);

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, ]);
      

    // get assigned agencies, managers and their default spiff/residual rates
    $agencies = DB::select(
      " SELECT a.id, a.name, a.spiff, a.residual
          FROM lead_relation_agency la
            LEFT JOIN agencies a ON la.agency_id =a.id
          WHERE la.lead_id =:lead_id AND a.id >0
            GROUP BY a.id
          ORDER BY a.name, a.id DESC
    ", [$lead_id]);

    $managers = DB::select(
      " SELECT u.id, u.fname, u.lname, IF(mc.user_id >0, mc.spiff,0) spiff, IF(mc.user_id >0, mc.residual, 0) residual
          FROM lead_relation_manager lm
            LEFT JOIN login_users u ON lm.user_id =u.id
            LEFT JOIN relation_manager_commission mc ON lm.user_id =mc.user_id
          WHERE lm.lead_id =:lead_id AND u.id >0
          ORDER BY u.fname, u.lname, u.id DESC
    ", [$lead_id,]);

    if (!$agencies && !$managers)
      return log_ajax_err('The lead is Not associated with any Agencies or Channel Managers.', ['src'=> $log_src, 'lead-id'=> $lead_id, ]);


    $locations = DB::table('lead_locations')->whereRaw(" lead_id =:lead_id ", [$lead_id])->orderBy('id')->get();
    if (!$locations)
      return log_ajax_err('At least 1 Quote is required before you can set Commission Rates.', ['src'=> $log_src, 'lead-id'=> $lead_id, ]);

    $row_locations = [];
    foreach ($locations as $loc) {
      $db_rows = DB::select(
        " SELECT q.id, IF(p.id >0, p.name, q.provider) prov_name
            FROM lead_quotes q
              LEFT JOIN providers p ON q.provider_id =p.id
            WHERE q.location_id =:loc_id
            ORDER BY q.id
      ", [$loc->id]);
      if (!$db_rows)
        continue;
      
      $row_quotes = [];
      foreach ($db_rows as $q) {
        $rate_agencies = $rate_managers = [];
        if ($agencies) {
          foreach ($agencies as $a) {
            $rec = DB::table('lead_quote_rate_agency')->whereRaw(" quote_id =:quote_id AND agency_id =:agency_id AND lead_id =:lead_id ", [
              $q->id, $a->id, $lead_id
            ])->first();
            if (!$rec)
              $rec = (object)['quote_id'=> $q->id, 'agency_id'=> $a->id, 'lead_id'=> $lead_id, 'spiff'=> 0, 'residual'=> 0, ];
            $rate_agencies[] = $rec;
          }
        }
        if ($managers) {
          foreach ($managers as $m) {
            $rec = DB::table('lead_quote_rate_manager')->whereRaw(" quote_id =:quote_id AND user_id =:manager_id AND lead_id =:lead_id ", [
              $q->id, $m->id, $lead_id
            ])->first();
            if (!$rec)
              $rec = (object)['quote_id'=> $q->id, 'user_id'=> $m->id, 'lead_id'=> $lead_id, 'spiff'=> 0, 'residual'=> 0, ];
            $rate_managers[] = $rec;
          }
        }
        $q->rate_agencies = $rate_agencies;
        $q->rate_managers = $rate_managers;
        $row_quotes[] = $q;
      }
      $loc->quotes = $row_quotes;
      $row_locations[] = $loc;
    }
    
    
		$html_output =
      Form::open(['url'=> $frm_url, 'class'=>'frm-commission']).
        view('master.leads.form-commission')
          ->with('agencies', $agencies)
          ->with('managers', $managers)
          ->with('locations', $row_locations)
          ->render().'
            
        <div class="btn-group">
          '.Form::submit('Save Rates').'
          '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
          '.Form::button('Reset', ['type'=> 'reset']).'
        </div>

      '.Form::close().'
      <script>moCommissionUpdate()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  
  
  /**
  * Action: update commission share for all agency, manager assigned to the lead
  *
  * @param lead_id: lead ID encoded
  */
  public function traitCommissionUpdate(Request $request)
  {
    $log_src = $this->log_src.'@traitCommissionUpdate';
    $preapp = $request->get('preapp');
    $me = Auth::user();

    
    // input validation
    $v = Validator::make($request->all(), [
      '*.*.*.*' => 'bail|required|numeric',
    ], [
      '*'=> 'Please enter a valid Commission Rate',
    ]);
    if ($v->fails()) {
      $errs = $v->errors()->all();
      $errs = [];
      // filter out duplicate error message(s) - since all fields are array, same error can occur multiple times
      foreach ($errs_tmp as $r) {
        if (!in_array($r, $errs))
          $errs[] = $r;
      }
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';
      return err_redirect($msg);
    }
      
    
    // get assigned agencies, managers, quotes -> check if matching with input
    $lead_id = dec_id($request->lead_id);

    $agencies = DB::select(
      " SELECT a.id
          FROM lead_relation_agency la
            LEFT JOIN agencies a ON la.agency_id =a.id
          WHERE la.lead_id =:lead_id AND a.id >0
    ", [$lead_id]);

    $managers = DB::select(
      " SELECT u.id
          FROM lead_relation_manager lm
            LEFT JOIN login_users u ON lm.user_id =u.id
          WHERE lm.lead_id =:lead_id AND u.id >0
    ", [$lead_id,]);

    if (!$agencies && !$managers)
      return log_redirect('The lead is Not associated with any Agencies or Channel Managers.', ['src'=> $log_src, 'lead-id'=> $lead_id, ]);
      
    $quotes = DB::select(
      " SELECT q.id
          FROM lead_quotes q LEFT JOIN lead_locations l ON q.location_id =l.id
          WHERE l.lead_id =:lead_id
    ", [$lead_id]);
    if (!$quotes)
      return log_redirect('The lead has No Quotes available.', ['src'=> $log_src, 'lead-id'=> $lead_id, ]);

    $n_quotes = count($quotes);
    $db_insert_param_agencies = $db_insert_param_managers = [];
    
    // validate: if all lead x agency and input matches -> create array of insert-params
    $txt_mismatch = 'Form Input is misconfigured. Please contact the adminstrator.';

    if ($agencies) {
      $n_agency = count($agencies);
      $n_rate = count($request->agency_rate);
      if ($n_agency != $n_rate)
        return log_redirect($txt_mismatch, [
          'src'=> $log_src, 'lead-id'=> $lead_id, 'msg'=> "agency count mismatch: agency #$n_agency, rates #$n_rate",
      ]);
      foreach ($agencies as $a) {
        $enc_aid = enc_id($a->id);
        $n_rate = count($request->agency_rate[$enc_aid]);
        if ($n_quotes != $n_rate)
          return log_redirect($txt_mismatch, [
            'src'=> $log_src, 'lead-id'=> $lead_id, 'agency-id'=> $a->id, 'msg'=> "quote count mismatch: quote #$n_quotes, rates #$n_rate",
        ]);
        foreach ($quotes as $q) {
          $enc_qid = enc_id($q->id);
          $rate = $request->agency_rate[$enc_aid][enc_id($q->id)];
          if (!$rate)
            return log_redirect($txt_mismatch, [
              'src'=> $log_src, 'lead-id'=> $lead_id, 'agency-id'=> $a->id, 'quote-id'=> $q->id,
              'msg'=> 'quote is not found from "agency_rate" input',
            ]);
          
          $r_spiff = ($rate['spiff'] >0)?  $rate['spiff'] : 0;
          $r_resid = ($rate['resid'] >0)?  $rate['resid'] : 0;
          $db_insert_param_agencies[] = ['quote_id'=> $q->id, 'agency_id'=> $a->id, 'lead_id'=> $lead_id, 'spiff'=> $r_spiff, 'residual'=> $r_resid, ];
        }
      }
    } // END if: agency is assigned
    if ($managers) {
      $n_manager = count($managers);
      $n_rate = count($request->manager_rate);
      if ($n_manager != $n_rate)
        return log_redirect($txt_mismatch, [
          'src'=> $log_src, 'lead-id'=> $lead_id, 'msg'=> "agency count mismatch: manager #$n_manager, rates #$n_rate",
      ]);
      foreach ($managers as $m) {
        $enc_mid = enc_id($m->id);
        $n_rate = count($request->manager_rate[$enc_mid]);
        if ($n_quotes != $n_rate)
          return log_redirect($txt_mismatch, [
            'src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $m->id, 'msg'=> "quote count mismatch: quote #$n_quotes, rates #$n_rate",
        ]);
        foreach ($quotes as $q) {
          $rate = $request->manager_rate[$enc_mid][enc_id($q->id)];
          if (!$rate)
            return log_redirect($txt_mismatch, [
              'src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $m->id, 'quote-id'=> $q->id,
              'msg'=> 'quote is not found from "manager_rate" input',
            ]);
          $r_spiff = ($rate['spiff'] >0)?  $rate['spiff'] : 0;
          $r_resid = ($rate['resid'] >0)?  $rate['resid'] : 0;
          $db_insert_param_managers[] = ['quote_id'=> $q->id, 'user_id'=> $m->id, 'lead_id'=> $lead_id, 'spiff'=> $r_spiff, 'residual'=> $r_resid, ];
        }
      }
    } // END if: manager is assigned
    
    
    // validate passed: reset rates -> insert (or update if exist) rates
    DB::table('lead_quote_rate_agency')->whereRaw(" lead_id =:lead_id ", [$lead_id])->delete();
    DB::table('lead_quote_rate_manager')->whereRaw(" lead_id =:lead_id ", [$lead_id])->delete();
    if ($db_insert_param_agencies)
      DB::table('lead_quote_rate_agency')->insert($db_insert_param_agencies);
    if ($db_insert_param_managers)
      DB::table('lead_quote_rate_manager')->insert($db_insert_param_managers);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead((object) [
      'id' => $lead_id, 
      'msg' => '<p>Commission Share has been updated for '.$n_quotes.' Quote(s).</p>', 'auto' => 1,
    ]);
    log_write('Commission Share updated.', ['src'=> $log_src, 'lead-id'=> $lead_id, '# quotes'=> $n_quotes]);
    return msg_redirect('The Commission Rates have been created.');
  }


  /**
  * ******************************************************* lead x agency *******************************************************
  *
  * output JSON for ingenOverlay: assign new agency (amongst agency the auth-master-agent has access to)
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $frm_url: URL to submit form
  */
  public function traitOverlayAgencyAssign (Request $request, $frm_url)
  {
    $log_src = $this->log_src.'@traitOverlayAgencyAssign';
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
        ->with('frm_url', $frm_url)
        ->render();

		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  
  /**
  * Action: assign new agency to the lead (cannot exceed # set in config = MAX_AGENCY_PER_LEAD)
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $request->agency_id: agency ID encoded
  */
  public function traitAgencyAssign (Request $request)
  {
    $log_src = $this->log_src.'@traitAgencyAssign';
    $preapp = $request->get('preapp');
    $manager_id = $preapp->manager_id;

    $lead_id = dec_id($request->lead_id);
    $agency_id = dec_id($request->agency_id);

    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id]);

    // validate: # of agencies assigned is limited
    $assigned_agencies = DB::table('lead_relation_agency')->where('lead_id', $lead_id);
    if ($assigned_agencies->count() >= MAX_AGENCY_PER_LEAD)
      return log_redirect("Only upto ".MAX_AGENCY_PER_LEAD." Agencies can be assigned.", ['src'=> $log_src, 'lead-id'=> $lead_id]);

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
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $request->agency_id: agency ID encoded
  */
  public function traitAgencyRemove (Request $request)
  {
    $log_src = $this->log_src.'@traitAgencyRemove';
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
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $frm_url: URL to submit form
  */
  public function traitOverlayManagerAssign (Request $request, $frm_url)
  {
    $log_src = $this->log_src.'@traitOverlayManagerAssign';
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
        ->with('frm_url', $frm_url)
        ->render();

		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  
  /**
  * Action: assign new channel manager to the lead (cannot exceed # set in config = MAX_MANAGER_PER_LEAD)
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $request->manager_id: user ID encoded
  */
  public function traitManagerAssign (Request $request)
  {
    $log_src = $this->log_src.'@traitManagerAssign';
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
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $request->manager_id: user ID encoded
  */
  public function traitManagerSetPrimary (Request $request)
  {
    $log_src = $this->log_src.'@traitManagerSetPrimary';
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
    log_write('Primary Manager has been changed.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id, ]);
    return msg_redirect('Primary Manager of the Lead has been changed.');
  }
  /**
  * Action: remove currently assigned channel-manager
  *
  * @param $request
  * @param $request->lead_id: lead ID encoded
  * @param $request->manager_id: user ID encoded
  */
  public function traitManagerRemove (Request $request)
  {
    $log_src = $this->log_src.'@traitManagerRemove';
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

    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, ]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $preapp->manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, ]);
    

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

    $loc_id = dec_id($request->loc_id);
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, ]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $preapp->manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, ]);


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
      return log_redirect('Please upload at least one file.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, ]);

    $log_detail = '<ul class="lead-log-files">';
    foreach ($files as $f) {
      $f_name = $f->getClientOriginalName();
      $f_size = $f->getClientSize();

      if ($f_size <= 0)
        return log_redirect('One of the Uploaded File has a file size of 0 byte.', [
          'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'file'=> $f_name, 'size'=> $f_size, ]);
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
    log_write('Location x Files attached.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, '# file'=> count($files) ]);
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
    $manager_id = $preapp->manager_id;

    $file_id = dec_id($request->file_id);
    $file = DB::table('lead_location_files')->find($file_id);
    if (!$file)
      return log_redirect('Attached File Not found.', ['src'=> $log_src, 'file-id'=> $file_id, ]);

    $loc_id = $file->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'file-id'=> $file_id, ]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $preapp->manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'file-id'=> $file_id, ]);


  
    DB::table('lead_location_files')->where('id',$file_id)->delete();

    
    // action SUCCESS: leave a log and redirect
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Attached File has been removed from the Location.</p><p>[Location] '.$loc->name.', [File] '.$file->f_desc.'</p>',
    ]);
    log_write('Location x Files deleted.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'file-id'=> $file_id, 'file'=> $file->f_desc, ]);
    return $this->jsonReload($lead_id, $manager_id);
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
  *		old_masters: array of master-followers before change
  *		new_masters: array of master-followers after change
  *		old_provs: array of provider-followers before change
  *		new_provs: array of provider-followers after change
  * @return log_lead (): log ID (= last insert id)
  **/
  private function log_lead_followers ($obj) {
    $lead_id = $obj->id;
    $msg = $obj->msg;
    
    $del_masters = [];
    $del_provs = [];

    $new_masters = $obj->new_masters;
    $n_old = count($obj->old_masters);
    for ($i =0; $i < $n_old; $i++) {
      $found = FALSE;

      $n_new = count($new_masters);
      for ($j =0; !$found && $j < $n_new; $j++) {
        if ($obj->old_masters[$i]->user_id == $new_masters[$j]->user_id) {
          unset($new_masters[$j]);
          $new_masters = array_values($new_masters);
          $found = TRUE;
        }
      }
      if (!$found)
        $del_masters[] = $obj->old_masters[$i];
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
    if ($del_masters) {
      foreach ($del_masters as $follower)
        $detail .= '<p>[Master: '.$follower->name.'] has been removed.</p>';
    }
    if ($new_masters) {
      foreach ($new_masters as $follower)
        $detail .= '<p>[Master: '.$follower->name.'] has been added.</p>';
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

  /*
  public function getLead ($lead_id, $agency_id) {
    return parent::getLead($lead_id, $agency_id);
  }
  */
}
