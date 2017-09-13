<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user_id = Auth::user()->id;

        $alert_count = DB::select(
            " SELECT count(log_id) as count  FROM alerts  WHERE to_user_id =:user_id AND is_read = 0
          ", [$user_id]);

        $query = "SELECT a.log_id as log_id, a.id, a.alert_type, a.is_read, a.alert_type_id, a.date_added, a.alert_msg, CONCAT(u.fname, ' ', u.lname) as name
        FROM alerts as a  
        LEFT JOIN login_users as u ON u.id = a.by_user_id
        WHERE a.to_user_id =:user_id AND a.is_read = 0 
        ORDER BY a.date_added DESC
        LIMIT 0, 5
        ";
        $alerts = $this->getAlerts($query);

        return view('home')->with('preapp', $request->get('preapp'))
                           ->with('alert_count', $alert_count[0]->count)
                           ->with('alerts', $alerts);
    }
    public function ajaxAlertGet()
    {
        $query = "SELECT a.log_id as log_id, a.id, a.is_read, a.alert_type, a.alert_type_id, a.date_added, a.alert_msg, CONCAT(u.fname, ' ', u.lname) as name
        FROM alerts as a  
        LEFT JOIN login_users as u ON u.id = a.by_user_id
        WHERE a.to_user_id =:user_id 
        ORDER BY a.is_read, a.date_added DESC
       ";
        $alerts = $this->getAlerts($query);
       
        $html_output =
        view('alert.form-home')
          ->with('alerts', $alerts)
          ->render();
        return json_encode([
            'success'=>1, 'error'=>0,
            'html'=> $html_output
        ]);
    }
    public function getAlerts($query) 
    {
        $user_id = Auth::user()->id;
        $alerts = DB::select($query, [$user_id]);
        $alerts_count = count($alerts);
        for($i = 0; $i < $alerts_count; $i++) {
            $value = $alerts[$i]->alert_type_id;
            $alerts[$i]->alert_type_id = enc_id($value);

            $v_id = $alerts[$i]->id;
            $alerts[$i]->id = enc_id($v_id);
        }
        return $alerts;
    }
}
