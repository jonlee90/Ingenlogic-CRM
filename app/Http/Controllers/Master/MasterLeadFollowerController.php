<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\MasterLeadController;

use App\User;
use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterLeadFollowerController extends MasterLeadController
{
  /**
  * custom variable
  */
  private $log_src = 'MasterLeadFollowerController';

  /**
  * ******************************************************* lead x followers *******************************************************
  *
  * output JSON for ingenOverlay: update follower(s) - list of master + provider-contacts
  *
  * @param lead_id: lead ID encoded
  */
  public function overlayMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayMod';
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
  * @param lead_id: lead ID encoded
  */
  public function ajaxUpdate (Request $request)
  {
    $log_src = $this->log_src.'@ajaxUpdate';
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
  public function ajaxMasterDelete (Request $request)
  {
    $log_src = $this->log_src.'@ajaxMasterDelete';
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
    log_write('Lead x Follower (Master Agent) removed from [Lead Management Page].', ['src'=> $log_src, 'lead-id'=> $lead_id, 'user-id'=> $user_id, 'manager_id'=> $manager_id]);
    return $this->jsonReload($lead_id, $manager_id);
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
  * ******************************************************* PRIVATE functions *******************************************************
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
}
