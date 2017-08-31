<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LeadController;

use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class LeadManageController extends LeadController
{
  // traits
  use GetLead;

  // custom variables
  private $log_src = 'LeadManageController';


  /**
  * ******************************************************* lead x log *******************************************************
  *
  * output JSON for ingenOverlay: new lead x log
  *
  * @param lead_id: lead ID encoded
  */
  public function overlayLogNew(Request $request)
  {
    $log_src = $this->log_src.'@overlayLogNew';
    $preapp = $request->get('preapp');
    
		$html_output =
      Form::open(['url'=> route('lead.ajax-log-add', ['lead_id'=> $request->lead_id]), 'class'=>'frm-log', 'method'=> 'PUT']).
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
  * @param log_id: log ID encoded
  */
  public function overlayLogMod(Request $request)
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
      Form::open(['url'=> route('lead.ajax-log-correct', ['log_id'=> $request->log_id]), 'class'=>'frm-log']).
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
  * output JSON for ingenOverlay: show all lead x logs
  *
  * @param lead_id: lead ID encoded
  */
  public function overlayLogHistory(Request $request)
  {
    $log_src = $this->log_src.'@overlayLogHistory';
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
  * @param lead_id: lead ID encoded
  */
  public function ajaxLogAdd (Request $request)
  {
    $log_src = $this->log_src.'@ajaxLogAdd';
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
  * @param $log_id: log ID encoded
  */
  public function ajaxLogCorrect (Request $request)
  {
    $log_src = $this->log_src.'@ajaxLogCorrect';
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
}
