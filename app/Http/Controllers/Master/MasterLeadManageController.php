<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\MasterLeadController;

use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterLeadManageController extends MasterLeadController
{
  // traits
  use GetLead;

  // custom variables
  private $log_src = 'MasterLeadManageController';


  /**
  * ******************************************************* lead x commission *******************************************************
  *
  * output JSON for ingenOverlay: set commission share for all agency, managers assigned to the lead
  *
  * @param lead_id: lead ID encoded
  */
  public function overlayCommissionMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayCommissionMod';
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
      Form::open(['url'=> route('master.lead.commission-update', ['lead_id'=> $request->lead_id]), 'class'=>'frm-commission']).
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
  * action: update commission share for all agency, manager assigned to the lead
  *
  * @param lead_id: lead ID encoded
  */
  public function overlayCommissionUpdate(Request $request)
  {
    $log_src = $this->log_src.'@overlayCommissionUpdate';
    $preapp = $request->get('preapp');
    $me = Auth::user();

    
    // input validation
    $v = Validator::make($request->all(), [
      '*.*.*.*' => 'bail|required|numeric',
    ], [
      '*'=> 'Please enter a valid Commission Rate',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
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
      Form::open(['url'=> route('master.lead.ajax-log-add', ['lead_id'=> $request->lead_id]), 'class'=>'frm-log', 'method'=> 'PUT']).
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

    $log_id = dec_id($request->log_id);
    $lead_log = DB::table('lead_logs')->find($log_id);
    if (!$lead_log)
      return log_ajax_err('Log Not found.', ['src'=> $log_src, 'log-id'=> $log_id]);
    if ($lead_log->mod_id != $me->id)
      return log_ajax_err('You have No Access to the Log.', ['src'=> $log_src, 'log-id'=> $log_id]);
    
		$html_output =
      Form::open(['url'=> route('master.lead.ajax-log-correct', ['log_id'=> $request->log_id]), 'class'=>'frm-log']).
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

    $lead_id = dec_id($request->lead_id);
    $lead = $this->getLead($lead_id, $preapp->manager_id);
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
    return $this->jsonReload($lead_id);
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
    return $this->jsonReload($lead_id);
  }
}
