<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Traits\AlertTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class AlertController extends Controller
{
  use AlertTrait;
  private $log_src = 'AlertController';

  // When user clicks on the alert link, redirect the user to the appropriate route
  public function manage (Request $request, $alert = null)
  {
    $log_src = $this->log_src.'@manage';
    
    $alert_type = $request->type;

    $route_name;
    switch($alert_type) {
        case 1:
            $route_name = 'lead.manage';
        default:
            ;
    }

    if($request->alert) {
        $alert_id = dec_id($request->alert);
        $alert_update = DB::update('UPDATE alerts SET is_read = 1 WHERE id =:alert_id', [$alert_id]);
    }

    return redirect()->route($route_name, [$request->id]);
  }
 
  

}
