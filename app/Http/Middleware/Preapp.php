<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class Preapp
{
  /**
  * Handle an incoming request.
  *
  * @param  \Illuminate\Http\Request  $request
  * @param  \Closure  $next
  * @return mixed
  **/
  public function handle($request, Closure $next)
  {
    /**
    * begin Preapp setup
    **/
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
            IF(a.id >0, a.id, 0) AS agency_id,
            IF(up.action1 IS NULL, pos.action1, up.action1) AS perm_action1
          FROM rec_user_login r
            LEFT JOIN login_users u ON r.user_id =u.id
              LEFT JOIN user_positions pos ON u.access_lv =pos.pos_lv
              LEFT JOIN user_permissions up ON u.id =up.user_id
              LEFT JOIN relation_user_agency ua ON u.id =ua.user_id
                LEFT JOIN agencies a ON ua.agency_id =a.id AND a.active >0
          WHERE r.user_id =:auth_id AND u.active >0 AND pos.id >0
            LIMIT 1
    ", [$auth_id]);
    
    // if auth-user NOT found, logout
    if (!count($db_rows))
      return $this->logout(
        "Your login session was lost, and you were automatically logged out. Please login again.",
        "Unable to find login-record from given session (IP-address: ".$_SERVER['REMOTE_ADDR']."). "
      );

      
    $r = $db_rows[0];

    // if logged-in user has been turned "inactive"
    if ($r->active ==0)
      return $this->logout(
        "Your login credential has been modified, and you were automatically logged out. Please login again.",
        "User turned inactive by other user with higher access LV."
      );
    // if login_token <> csrf_token, user logged in other location
    if ($r->login_token != session('login_token'))
      return $this->logout(
        "You have logged-in from a different location. You have been automatically logged out. Please login again.",
        "The user has been logged in from different location. New login IP-address: ".$r->ip_addr
      );
    // if login-mod =1, someone with higher access LV modified pw. force logoff user to re-login
    if ($r->login_mod >0)
      return $this->logout(
        "Your login credential has been modified, and you were automatically logged out. Please login again.",
        "Login credential was updated by other user with higher access LV."
      );
    // check if user has valid key and ip-address (in case logged in 2 different locations)
    if ($r->ip_addr != $_SERVER['REMOTE_ADDR'])
      return $this->logout(
        "You have logged in a different location. You have been automatically logged out. Please login again.",
        "Duplicate Login. The user has been force-logged out from previous login. New login IP-address: ".$_SERVER['REMOTE_ADDR']
      );
    // check if user's agency ID exists (= active)
    if (!($r->agency_id >0))
      return $this->logout(
        "Login Failed. Your account has been deactivated.",
        "Agent is missing or not active (Agency ID: ".$r->agency_id.")"
      );

    // login record validate success - update last activity
    DB::update(" UPDATE rec_user_login SET date_act = NOW() WHERE user_id =:auth_id ", [$auth_id]);
    
    // save user's access level - if user is system-admin (or master agent with appropriate permission), impersonate to agency-admin
    $preapp->lv = ($r->pos_lv >= POS_LV_SYS_ADMIN)?  POS_LV_AGENT_ADMIN : $r->pos_lv;

    // save user's agency id - encode the ID, permissions to $preapp
    $preapp->agency_id = enc_id($r->agency_id);
    $preapp->perm_user_view = ($preapp->lv >= POS_LV_AGENT_MANAGER);
    $preapp->perm_agency_view = ($preapp->lv >= POS_LV_AGENT_MANAGER);
    $preapp->perm_agency_mod = ($preapp->lv >= POS_LV_AGENT_ADMIN);
    $preapp->perm_prov_view = ($preapp->lv >= POS_LV_AGENT_MANAGER);


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
    log_write($toast_msg, ['src'=>'Preapp@logout', 'msg'=> $log_msg, 'user-id'=> $auth_id], 'warn');
    session()->put('toast_msg', '<span class="err">'.$toast_msg.'</err>');
    session()->save();
    
    return redirect()->route('home');
  }
}
