<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterPreapp
{
  /**
  * Handle an incoming request.
  *
  * @param  \Illuminate\Http\Request  $request
  * @param  \Closure  $next
  * @return mixed
  */
  public function handle($request, Closure $next)
  {
    /**
    * begin Preapp setup
    */
    // Report simple running errors
    // error_reporting(E_ERROR | E_WARNING | E_PARSE);
    if (!Auth::check()) {
      return redirect('/login');
    }
    $preapp = (object)[
      'toast_msg'=> ''
    ];
    $auth_id = Auth::id();
      

    $db_rows = DB::select(
      " SELECT r.ip_addr, r.login_token, r.login_mod,  u.active,  pos.pos_lv,
            IF(up.action1 IS NULL, pos.action1, up.action1) AS perm_action1
          FROM rec_user_login r
            LEFT JOIN login_users u ON r.user_id =u.id
              LEFT JOIN user_positions pos ON u.access_lv =pos.pos_lv
              LEFT JOIN user_permissions up ON u.id =up.user_id
          WHERE r.user_id =:auth_id AND u.active >0 AND pos.id >0
            LIMIT 1
    ", [$auth_id]);
    
    // if auth-user NOT found, logout
    if (!count($db_rows))
      return $this->logout(
        "Your login session was lost, and you were automatically logged out. Please login again.",
        "Unable to find login-record from given session (IP-address: ".$_SERVER['REMOTE_ADDR']."). "
      );
      
      
    $rec = $db_rows[0];

    // if logged-in user has been turned "inactive"
    if ($rec->active ==0)
      return $this->logout(
        "Your login credential has been modified, and you were automatically logged out. Please login again.",
        "User turned inactive/removed by other user with higher access LV."
      );
    // if login_token <> csrf_token, user logged in other location
    if ($rec->login_token != session('login_token'))
      return $this->logout(
        "You have logged-in from a different location. You have been automatically logged out. Please login again.",
        "The user has been logged in from different location. New login IP-address: ".$r->ip_addr
      );
    // if login-mod =1, someone with higher access LV modified pw. force logoff user to re-login
    if ($rec->login_mod >0)
      return $this->logout(
        "Your login credential has been modified, and you were automatically logged out. Please login again.",
        "Login credential was updated by other user with higher access LV."
      );
    // check if user has valid key and ip-address (in case logged in 2 different locations)
    if ($rec->ip_addr != $_SERVER['REMOTE_ADDR'])
      return $this->logout(
        "You have logged in a different location. You have been automatically logged out. Please login again.",
        "Duplicate Login. The user has been force-logged out from previous login. New login IP-address: ".$_SERVER['REMOTE_ADDR']
      );
    // check if user has access to master page
    if ($rec->pos_lv <= POS_LV_AGENT_ADMIN)
      return $this->logout(
        'Login Failed. Your login name and/or password was not matching.',
        "User has NO access to the Admin Page"
      );
      

    // login record validate success - update last activity
    DB::update(" UPDATE rec_user_login SET date_act = NOW() WHERE user_id =:auth_id ", [$auth_id]);

    // save user's access LV, manager ID (if channel-assistant/manager), permissions to $preapp
    $preapp->lv = $rec->pos_lv;
    if ($rec->pos_lv == POS_LV_CH_USER) {
      $db_rows = DB::select(
        " SELECT u.id
            FROM relation_assistant_manager am LEFT JOIN login_users u ON am.user_id =u.id 
            WHERE am.assistant_id =:auth_id AND u.id >0 AND u.active >0
      ", [$auth_id]);
      if (!$db_rows)
        return $this->logout(
          'Login Failed. Your login name and/or password was not matching.',
          "Logged-in User is a Channel Assistant, but the associated Channel Manager is NOT found."
        );
      $preapp->manager_id = $db_rows[0]->id;
    } elseif ($rec->pos_lv == POS_LV_CH_MANAGER)
      $preapp->manager_id = $auth_id;
    else
      $preapp->manager_id = 0; // master-agents have no manager-ID

    $preapp->perm_agency_rec =
    $preapp->perm_agency_mod = 
    $preapp->perm_agency_del = ($rec->pos_lv >= POS_LV_MASTER_MANAGER);
    $preapp->perm_prov_rec =
    $preapp->perm_prov_mod = 
    $preapp->perm_prov_del = ($rec->pos_lv >= POS_LV_MASTER_MANAGER);
    $preapp->perm_svc_rec =
    $preapp->perm_svc_mod = 
    $preapp->perm_svc_del = ($rec->pos_lv >= POS_LV_MASTER_MANAGER);
    $preapp->perm_lead_rec =
    $preapp->perm_lead_mod =
    $preapp->perm_lead_del = ($rec->pos_lv >= POS_LV_MASTER_MANAGER || (POS_LV_CH_USER <= $rec->pos_lv && $rec->pos_lv <= POS_LV_CH_MANAGER));
    // END if: logged-in user found from DB
    

    /**
     * pass Preapp variables
     */
    $request->attributes->add(['preapp'=> & $preapp]);
    return $next($request);
  }
  private function logout($toast_msg, $log_msg)
  {
    // get user-id, reset session, and logout
    $auth_id = Auth::id();
    session()->flush();
    Auth::logout();

    // leave a log and return home (=login page) with appropriate message
    log_write($toast_msg, ['src'=>'MasterPreapp@logout', 'msg' => $log_msg, 'auth-id' => $auth_id], 'warn');
    session()->put('toast_msg', '<span class="err">'.$toast_msg.'</err>');
    session()->save();
    
    return redirect()->route('home');
  }
}
