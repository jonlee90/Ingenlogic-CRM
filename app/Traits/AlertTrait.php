<?php

namespace App\Traits;

use App\Http\Controllers\Controller;

use App\User;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

trait AlertTrait
{
    public function traitAlertMod(Request $request, $users, $view)
    {
        $log_src = $this->log_src.'@traitAlertMod';
        $log_id = $request['log'];
        $alert_type = $request['alertType'];

        $ajax = $this->getAjaxScript($alert_type);

        $type_id = dec_id($request->id);
        $follower_count = count($users);
        if($follower_count > 0) {
            $followers = DB::table('login_users')
                                ->select('id', 'fname', 'lname', 'title', 'tel', 'email', 'access_lv')
                                ->whereIn('id', $users)->get();

            $alerted_followers = DB::select(
                    " SELECT a.to_user_id as user_id,  
                    a.is_read,
                    TRIM(CONCAT(u.fname,' ',u.lname)) AS f_name,
                    u.title AS title,
                    u.tel AS tel,
                    u.email AS email
                    FROM alerts as a
                    LEFT JOIN login_users as u ON u.id = a.to_user_id
                    WHERE a.alert_type_id =:type_id AND a.log_id =:log_id
                    GROUP BY a.to_user_id
                ", [$type_id, $log_id]);
            
            $alerted_followers_count = count($alerted_followers);
            for($i = 0; $i < $follower_count; $i++) {
                $flag = true;
                $currentFollower = $followers[$i];
                $currentFollower->access_lv = config_pos_name($currentFollower->access_lv);
                for($j = 0; $j < $alerted_followers_count; $j++) {
                    if($alerted_followers[$j]->user_id == $currentFollower->id) {
                        // mark current follower as read if the user already received an alert
                        $currentFollower->is_read = 1;
                        $flag = false;
                    }
                }
                if($flag) {
                    $currentFollower->is_read = 0;
                }
            }
        }else {
            $followers = null;
        }
        $html_output =
        view($view)
        ->with('type_id', $type_id)
        ->with('alert_type', $alert_type)
        ->with('followers', $followers)
        ->with('log_id', $log_id)
        ->render()."
            
        <script>$ajax->script</script>
          ";    
        return json_encode([
            'success'=>1, 'error'=>0,
            'html'=> $html_output
        ]);
    }
    public function traitAlertSend (Request $request, $route)
    {
        $log_src = $this->log_src.'@traitAlertSend';
        $log_id = $request['log'];
        $alert_type = $request['alert_type'];
        $current_user = Auth::user()->fname . ' ' . Auth::user()->lname;
        $current_user_id = Auth::user()->id;

        $type_id = dec_id($request->id);
        $selected_users = $request['is_read'];
        $user_count = count($selected_users);
 
        if ($user_count < 1) {
            return log_redirect('Must select atleast one user', [] ,'err', $route);
        }

        $ajax = $this->getAjaxScript($alert_type);

        $alerted_followers = DB::select(
            " SELECT to_user_id, is_read, id  FROM alerts  WHERE log_id =:log_id
        ", [$log_id]);
    
        $current_log = DB::select("SELECT log_msg, mod_user FROM $ajax->type WHERE id=:log_id", [$log_id]);
    
        // Delete if the user already read and received the alert. Skip if the user already received the alert but didn't read. 
        $alerted_count = count($alerted_followers);
        $insert_datas = [];
        for ($i =0; $i < $user_count; $i++) {
            $same = false;
            $r_user_id = dec_id($selected_users[$i]);
            for($j = 0; $j < $alerted_count; $j++) {
                if ($r_user_id == $alerted_followers[$j]->to_user_id && $alerted_followers[$j]->is_read == 1) {
                    DB::table('alerts')->where('id', '=', $alerted_followers[$j]->id)->delete();
                    break;
                }else if ($r_user_id == $alerted_followers[$j]->to_user_id) {
                    $same = true;
                    break;
                }
            }
            if(!$same) {
                $insert_datas[] = $r_user_id;
            }
            continue;
        }
        $user_count = count($insert_datas);
        $db_insert_users = [];
        for ($i =0; $i < $user_count; $i++) {
            $r_user_id = $insert_datas[$i];
    
            $db_insert_users[] = [
                'log_id'=> $log_id,
                'by_user_id' => $current_user_id,
                'to_user_id'=> $r_user_id,
                'is_read'=> 0,
                'alert_type_id'=> $type_id,
                'alert_type' => $alert_type,
                'alert_msg' => '<p>Page: ' . $ajax->name . '</p>' . '<div>Message: ' . $current_log[0]->log_msg . '</div><p>-By: ' . $current_log[0]->mod_user . '</p>'
            ];
        }
    
        DB::table('alerts')->insert($db_insert_users);
        
        log_write("$ajax->name x Alert Sent.", ['Type Id'=> $type_id, 'Log Id'=> $log_id, 'How many sent' => $user_count, 'Sent by' => $current_user ]);
        return msg_redirect('Alert Sent', $route);
    }
    public function getFollowersId($id)
    {
        $followers = DB::select(
            " SELECT user_id
            FROM lead_follower_agents 
            WHERE lead_id =$id
            UNION
            SELECT user_id
            FROM lead_follower_masters 
            WHERE lead_id =$id;
        ");
        $follower_count = count($followers);
        if($follower_count > 0) {
            for($i = 0; $i < $follower_count; $i++) 
            {
                $followers_id[] = $followers[$i]->user_id;
            }
        }else {
            $followers_id = null;
        }
        return $followers_id;
    }
    private function getAjaxScript($alert_type) 
    {   
        switch($alert_type) {
            case 1:
                $a_type = 'lead_logs';
                $a_type_name = 'Leads';
                $a_type_script = 'aoLeadAlert()';
            default:
                $a_type = 'lead_logs';
                $a_type_name = 'Leads';
                $a_type_script = 'aoLeadAlert()';
        }
        return (object)array('type' => $a_type, 'name' => $a_type_name, 'script' => $a_type_script);
    }
}
