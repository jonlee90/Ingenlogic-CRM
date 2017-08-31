<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LeadController;

use App\User;
use App\Agency;
use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class LeadFollowerController extends LeadController
{
  // traits
  use GetLead;

  // custom variables
  private $log_src = 'LeadFollowerController';


  /**
  * ******************************************************* lead x followers *******************************************************
  *
  * output JSON for ingenOverlay: update follower(s) - list of agents + provider-contacts
  *
  * @param lead_id: lead ID encoded
  */
  public function overlayMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayMod';
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
  * @param lead_id: lead ID encoded
  */
  public function ajaxUpdate (Request $request)
  {
    $log_src = $this->log_src.'@ajaxUpdate';
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
  * @param lead_id: lead ID encoded
  * @param order_no: follower order-no encoded (follower has primary key of lead ID x order no)
  */
  public function ajaxAgentDelete (Request $request)
  {
    $log_src = $this->log_src.'@ajaxAgentDelete';
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
    log_write('Lead x Follower (Agent) removed from [Lead Management Page].', ['src'=> $log_src, 'agent-id'=>$agency_id, 'lead-id'=> $lead_id, 'order-no'=> $order_no]);
    return $this->jsonReload($lead_id, $agency_id);
  }
  /**
  * AJAX Action: delete lead x followers (provider contact) => output data in JSON.
  *
  * @param lead_id: lead ID encoded
  * @param order_no: follower order-no encoded (follower has primary key of lead ID x order no)
  */
  public function ajaxProviderDelete (Request $request)
  {
    $log_src = $this->log_src.'@ajaxProviderDelete';
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
    log_write('Lead x Follower (Provider Contact) removed from [Lead Management Page].', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'order-no'=> $order_no,
    ]);
    return $this->jsonReload($lead_id, $agency_id);
  }
    


  /**
  * ******************************************************* PRIVATE functions *******************************************************
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
