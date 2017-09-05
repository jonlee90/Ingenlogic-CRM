<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Agency;
use App\Provider;
use App\Traits\LeadTrait;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class ProjectController extends Controller
{
  use LeadTrait;
  /*
  * custom variable
  */
  private $log_src = 'ProjectController';

  /**
  * View: list of service providers.
  *
  * @return \Illuminate\Http\Response
  */
  public function list (Request $request)
  {
    return view('leads.list')
      ->with('preapp', $request->get('preapp'));
  }
  /**
  * View: lead management page (continued from new lead page / update existing lead = overview + mod) 
  *
  * @param $id: lead ID encoded
  * @return \Illuminate\Http\Response
  */
  public function manage (Request $request)
  {
    $log_src = $this->log_src.'@manage';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->id);
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);

    $data = $this->projectReload($lead, $agency_id);
    if (!$lead->project_open)
      return log_redirect('There is No account in the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id],
        'err', route('lead.manage', ['lead_id'=> $request->id])
      );


    return view('projects.manage')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('lead', $lead);
  }
  
  
  /**
  * ******************************************************* project x account kept *******************************************************
  *
  * output JSON for ingenOverlay: update account products
  *
  * @param accnt_id: account ID encoded
  */
  public function overlayProductMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayProductMod';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    // currently saved account x products
    $accnt->products = DB::select(
      " SELECT svc_name, prod_name, memo, price, qty
          FROM lead_current_updated_products
          WHERE account_id =:accnt_id
          ORDER BY order_no
    ", [$accnt_id]);


    // get names of provider
    $providers = DB::select(" SELECT name FROM providers  WHERE active =1 ");

    // get names of existing services: child services only
    $services = DB::select(" SELECT name FROM services WHERE id <> parent_id ");


		$html_output =
      view('projects.form-keep-prod')
        ->with('services', $services)
        ->with('accnt', $accnt)
        ->render().'

      <script>aoKeepProdUpdate()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  /**
  * Action: update account x products => output data in JSON.
  *
  * @param accnt_id: account ID encoded
  */
  public function ajaxProductUpdate (Request $request)
  {
    $log_src = $this->log_src.'@ajaxProductUpdate';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);


    // at least 1 product is required, all POST arrays should have same count
    $n_prods = count($request->prod);
    if (!($n_prods >0))
      return log_ajax_err('One or more Products are required.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id
      ]);
    if ($n_prods != count($request->svc) || $n_prods != count($request->memo) || $n_prods != count($request->price) || $n_prods != count($request->qty))
      return log_ajax_err('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id
      ]);

    // input validation
    $v = Validator::make($request->all(), [
      'svc.*' => 'required',
      'prod.*' => 'required',
      'price.*' => 'required|numeric',
      'qty.*' => 'required|integer',
    ], [
      'svc.*'=> 'Service Name is required for all rows.',
      'prod.*'=> 'Product Name is required for all rows.',
      'price.*'=> 'Please enter a valid price.',
      'qty.*'=> 'Please enter a valid quantity.',
    ]);
    if ($v->fails()) {
      $errs_tmp = $v->errors()->all();
      $errs = [];
      // filter out duplicate error message(s) - since all fields are array, same error can occur multiple times
      foreach ($errs_tmp as $r) {
        if (!in_array($r, $errs))
          $errs[] = $r;
      }
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';
        
		  return json_encode([
        'success'=>0, 'error'=>1,
        'msg'=> $msg,
      ]);
    }
    
    // validation passed -> reset account x products (= delete existing and add products), also create logging detail object -> leave lead x log
    $db_query = DB::table('lead_current_updated_products')->where('account_id', $accnt_id)->orderBy('order_no');
    $old_prods = $db_query->get();

    $db_insert_params = $new_prods = [];

    for ($i =0; $i < $n_prods; $i++) {
      $prod_memo = ($request->memo[$i])?  $request->memo[$i] : '';
      
      $db_insert_params[] = [
        'account_id'=> $accnt_id,
        'order_no'=> $i,
        'svc_name'=> $request->svc[$i],
        'prod_name'=> $request->prod[$i],
        'memo'=> $prod_memo,
        'price'=> $request->price[$i],
        'qty'=> $request->qty[$i],
      ];

      $new_prods[] = (object)[
        'svc_name'=> $request->svc[$i],
        'prod_name'=> $request->prod[$i],
        'memo'=> $prod_memo,
        'price'=> $request->price[$i],
        'qty'=> $request->qty[$i],
      ];
    }


    $db_query->delete();
    DB::table('lead_current_updated_products')->insert($db_insert_params);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = $this->log_lead_prods((object) [
      'id' => $lead_id, 
      'msg' => '<p>Account Product(s) have been updated.</p><p>[Location] '.$loc->name.' x [Account] '.$accnt->provider_name.'</p>',
      'old_prods' => $old_prods, 'new_prods' => $new_prods,
    ]);
    log_write('Project x Account-Keep x Products Updated.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
    ]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> enc_id($loc_id), 'accntId'=> $request->accnt_id,
    ]);
  }
  
  
  /**
  * ******************************************************* project x account cancel *******************************************************
  *
  * output JSON for ingenOverlay: update account dates
  *
  * @param accnt_id: account ID encoded
  */
  public function overlayCancelDates(Request $request)
  {
    $log_src = $this->log_src.'@overlayCancelDates';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);


		$html_output =
      view('projects.form-cancel-date')
        ->with('accnt', $accnt)
        ->render().'

      <script>aoDateUpdate()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  /**
  * Action: update account x cancel dates => output data in JSON.
  *
  * @param accnt_id: account ID encoded
  */
  public function ajaxCancelDateUpdate (Request $request)
  {
    $log_src = $this->log_src.'@ajaxCancelDateUpdate';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
      

    // input validation
    $v = Validator::make($request->all(), [
      'port_date' => 'nullable|date_format:Y-m-d',
      'cancel_date' => 'nullable|date_format:Y-m-d',
    ], [
      'port_date.*'=> 'Please enter a valid inout for Port-Out Date.',
      'cancel_date.*'=> 'Please enter a valid inout for Cancelled Date',
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
    
    $log_detail_values = [
      (object)['field'=> 'Port-out Date', 'old'=> $accnt->date_portout, 'new'=> $request->port_date],
      (object)['field'=> 'Cancelled Date', 'old'=> $accnt->date_cancel, 'new'=> $request->cancel_date],
    ];
    

    // validation passed -> update account x dates
    $db_query = DB::table('lead_current_accounts')->where('id', $accnt_id)->update([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
      'date_portout'=> $request->port_date,
      'date_cancel'=> $request->cancel_date,
    ]);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Account Dates have been updated.</p><p>[Location] '.$loc->name.' x [Account] '.$accnt->provider_name.'</p>',
      'detail' => $log_detail_values,
    ]);
    log_write('Project x Account-Cancel x Dates Updated.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
    ]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> enc_id($loc_id), 'accntId'=> $request->accnt_id,
    ]);
  }
  
  
  /**
  * ******************************************************* project x account (kept or cancelled) *******************************************************
  *
  * Action: mark account as completed
  *
  * @param accnt_id: account ID encoded
  */
  public function accountComplete (Request $request)
  {
    $log_src = $this->log_src.'@accountComplete';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_redirect('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
      
    
    // validation passed -> update database
    $db_query = DB::table('lead_current_accounts')->where('id', $accnt_id)->update([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
      'is_complete'=> DB::raw(1),
    ]);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Account has been marked as Completed.</p><p>[Location] '.$loc->name.' x [Account] '.$accnt->provider_name.'</p>',
    ]);
    log_write('Project x Account marked as Complete.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
    ]);
    return msg_redirect('Account has been marked as Complete');
  }
  /**
  * Action: undo account-complete -> revert to not-complete status
  *
  * @param accnt_id: account ID encoded
  */
  public function accountCompleteUndo (Request $request)
  {
    $log_src = $this->log_src.'@accountCompleteUndo';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_redirect('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
      
    
    // validation passed -> update database
    $db_query = DB::table('lead_current_accounts')->where('id', $accnt_id)->update([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
      'is_complete'=> DB::raw(0),
    ]);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Account has been marked as Not Complete.</p><p>[Location] '.$loc->name.' x [Account] '.$accnt->provider_name.'</p>',
    ]);
    log_write('Project x Account marked as Not Complete.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
    ]);
    return msg_redirect('Account has been marked as Not Complete');
  }
  /**
  *
  * Action: revert account and remove from project management
  *
  * @param accnt_id: account ID encoded
  */
  public function accountRevert (Request $request)
  {
    $log_src = $this->log_src.'@accountRevert';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_redirect('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    
    // check if once the account is reverted, the project management still has account to work on
    $row_accounts = DB::select(
      " SELECT a.id
          FROM lead_current_accounts a
            LEFT JOIN lead_locations l ON a.location_id =l.id
          WHERE l.lead_id =:lead_id AND a.is_project >0
    ", [$lead_id]);
    $row_quotes = DB::select(
      " SELECT q.id
          FROM lead_quotes q
            LEFT JOIN lead_locations l ON q.location_id =l.id
          WHERE l.lead_id =:lead_id AND q.is_project >0
    ", [$lead_id]);
    $last_account = (count($row_accounts) + count($row_quotes) <= 1);
    
    // validation passed -> delete updated products, turn off 'is_project'
    $db_query = DB::table('lead_current_updated_products')->where('account_id', $accnt_id)->delete();
    $db_query = DB::table('lead_current_accounts')->where('id', $accnt_id)->update([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
      'is_project'=> DB::raw(0),
      'date_portout'=> NULL, 'date_cancel'=> NULL, 
    ]);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Account has been reverted and removed from Project Management.</p><p>[Location] '.$loc->name.' x [Account] '.$accnt->provider_name.'</p>',
    ]);
    log_write('Project x Account reverted.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
    ]);
    // if last account has been reverted, redirect back to lead-management
    if ($last_account)
      return msg_redirect('All accounts have been removed from the Project Management.', route('lead.manage', [enc_id($lead_id)]));
    else
      return msg_redirect('Account has been reverted');
  }
  
  
  /**
  * ******************************************************* project x account signed *******************************************************
  *
  * output JSON for ingenOverlay: update account dates (signed accounts are related to quotes)
  *
  * @param quote_id: quote ID encoded
  */
  public function overlaySignedDates(Request $request)
  {
    $log_src = $this->log_src.'@overlaySignedDates';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);
    
    if (!$quote->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);


		$html_output =
      view('projects.form-signed-date')
        ->with('accnt', $quote)
        ->render().'

      <script>aoDateUpdate()</script>
		';
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }
  /**
  * Action: update account x signed dates (sign, site-survey, installation ... etc) => output data in JSON.
  *
  * @param quote_id: quote ID encoded
  */
  public function ajaxSignedDateUpdate (Request $request)
  {
    $log_src = $this->log_src.'@ajaxCancelDateUpdate';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);
    
    if (!$quote->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'provider-id'=> $quote->provider_id
      ]);
      

    // input validation
    $v = Validator::make($request->all(), [
      'sign_date' => 'required|date_format:Y-m-d',
      'inspect_date' => 'nullable|date_format:Y-m-d',
      'construct_date' => 'nullable|date_format:Y-m-d',
      'install_date' => 'nullable|date_format:Y-m-d',
      'port_date' => 'nullable|date_format:Y-m-d',
      'inspect_done' => 'nullable|boolean',
      'construct_done' => 'nullable|boolean',
      'install_done' => 'nullable|boolean',
      'port_done' => 'nullable|boolean',
    ], [
      'sign_date.*' => 'Please enter a valid inout for Signed Date.',
      'inspect_date.*' => 'Please enter a valid inout for Site Survey Date.',
      'construct_date.*' => 'Please enter a valid inout for Construction Date.',
      'install_date.*' => 'Please enter a valid inout for Installation Date.',
      'port_date.*' => 'Please enter a valid inout for Port-In Date.',
      'inspect_done.*' => 'Please enter a valid inout for Site Survey Completion.',
      'construct_done.*' => 'Please enter a valid inout for Construction Completion.',
      'install_done.*' => 'Please enter a valid inout for Installation Completion.',
      'port_done.*' => 'Please enter a valid inout for Port-In Completion.',
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

    $p_done_inspect = ($request->inspect_done)?  1:0;
    $p_done_construct = ($request->construct_done)?  1:0;
    $p_done_install = ($request->install_done)?  1:0;
    $p_done_port = ($request->port_done)?  1:0;
    
    // create log-detail object
    $old_inspect_bool = ($quote->inspect_done)?  1:0;
    $old_construct_bool = ($quote->construct_done)?  1:0;
    $old_install_bool = ($quote->install_done)?  1:0;
    $old_port_bool = ($quote->portin_done)?  1:0;

    $old_inspect_txt = ($quote->inspect_done)?  ' (complete)':'';
    $old_construct_txt = ($quote->construct_done)?  ' (complete)':'';
    $old_install_txt = ($quote->install_done)?  ' (complete)':'';
    $old_port_txt = ($quote->portin_done)?  ' (complete)':'';
    $new_inspect_txt = ($request->inspect_done)?  ' (complete)':'';
    $new_construct_txt = ($request->construct_done)?  ' (complete)':'';
    $new_install_txt = ($request->install_done)?  ' (complete)':'';
    $new_port_txt = ($request->port_done)?  ' (complete)':'';

    $log_detail_values = [
      (object)['field'=> 'Signed Date', 'old'=> $quote->date_signed, 'new'=> $request->sign_date ],
      (object)['field'=> 'Site Survey Date', 'old_val'=> $old_inspect_bool.$quote->date_inspect, 'new_val'=> $p_done_inspect.$request->inspect_date,
        'old'=> $quote->date_inspect.$old_inspect_txt, 'new'=> $request->inspect_date.$new_inspect_txt ],
      (object)['field'=> 'Construction Date', 'old_val'=> $old_construct_bool.$quote->date_construct, 'new_val'=> $p_done_construct.$request->construct_date,
        'old'=> $quote->date_construct.$old_construct_txt, 'new'=> $request->construct_date.$new_construct_txt ],
      (object)['field'=> 'Installation Date', 'old_val'=> $old_install_bool.$quote->date_install, 'new_val'=> $p_done_install.$request->install_date,
        'old'=> $quote->date_install.$old_install_txt, 'new'=> $request->install_date.$new_install_txt ],
      (object)['field'=> 'Port-in Date', 'old_val'=> $old_port_bool.$quote->date_portin, 'new_val'=> $p_done_port.$request->port_date,
        'old'=> $quote->date_portin.$old_port_txt, 'new'=> $request->port_date.$new_port_txt ],
    ];
    

    // validation passed -> update account x dates
    $db_query = DB::table('lead_quotes')->where('id', $quote_id)->update([
      'date_signed'=> $request->sign_date,
      'date_inspect'=> $request->inspect_date, 'inspect_done'=> DB::raw($p_done_inspect),
      'date_construct'=> $request->construct_date, 'construct_done'=> DB::raw($p_done_construct),
      'date_install'=> $request->install_date, 'install_done'=> DB::raw($p_done_install),
      'date_portin'=> $request->port_date, 'portin_done'=> DB::raw($p_done_port),
    ]);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Account Dates have been updated.</p><p>[Location] '.$loc->name.' x [Account] '.$prov->name.'</p>',
      'detail' => $log_detail_values,
    ]);
    log_write('Project x Account-Signed x Dates Updated.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id'=> $log_id,
    ]);
    return $this->jsonReload($lead_id, $agency_id, [
      'locId'=> enc_id($loc_id), 'quoteId'=> $request->quote_id,
    ]);
  }
  /**
  * Action: mark signed-account (quote) as completed
  *
  * @param quote_id: quote ID encoded
  */
  public function signedComplete (Request $request)
  {
    $log_src = $this->log_src.'@signedComplete';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);
    
    if (!$quote->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);
    if (!$quote->date_signed)
      return log_redirect('The Account should have a Date Signed.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'provider-id'=> $quote->provider_id
      ]);
      
    
    // validation passed -> update database
    $db_query = DB::table('lead_quotes')->where('id', $quote_id)->update([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
      'is_complete'=> DB::raw(1),
    ]);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Signed Account has been marked as Completed.</p><p>[Location] '.$loc->name.' x [Account] '.$prov->name.'</p>',
    ]);
    log_write('Project x Signed Account marked as Complete.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id'=> $log_id,
    ]);
    return msg_redirect('Signed Account has been marked as Complete');
  }
  /**
  * Action: mark signed-account (quote) as not-complete: undo completion
  *
  * @param quote_id: quote ID encoded
  */
  public function signedCompleteUndo (Request $request)
  {
    $log_src = $this->log_src.'@signedCompleteUndo';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);
    
    if (!$quote->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'provider-id'=> $quote->provider_id
      ]);
      
    
    // validation passed -> update database
    $db_query = DB::table('lead_quotes')->where('id', $quote_id)->update([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
      'is_complete'=> DB::raw(0),
    ]);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Signed Account has been marked as Not Complete.</p><p>[Location] '.$loc->name.' x [Account] '.$prov->name.'</p>',
    ]);
    log_write('Project x Signed Account marked as Not Complete.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id'=> $log_id,
    ]);
    return msg_redirect('Signed Account has been marked as Not Complete');
  }
  /**
  * Action: revert account and remove from project management
  *
  * @param accnt_id: account ID encoded
  */
  public function signedRevert (Request $request)
  {
    $log_src = $this->log_src.'@signedRevert';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);
    
    if (!$quote->is_project)
      return log_redirect('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_redirect('Provider Not found.', [
        'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'provider-id'=> $quote->provider_id
      ]);

    
    // check if once the account is reverted, the project management still has account to work on
    $row_accounts = DB::select(
      " SELECT a.id
          FROM lead_current_accounts a
            LEFT JOIN lead_locations l ON a.location_id =l.id
          WHERE l.lead_id =:lead_id AND a.is_project >0
    ", [$lead_id]);
    $row_quotes = DB::select(
      " SELECT q.id
          FROM lead_quotes q
            LEFT JOIN lead_locations l ON q.location_id =l.id
          WHERE l.lead_id =:lead_id AND q.is_project >0
    ", [$lead_id]);
    $last_account = (count($row_accounts) + count($row_quotes) <= 1);
      
    
    // validation passed -> turn off 'is_project', reset dates
    $db_query = DB::table('lead_quotes')->where('id', $quote_id)->update([
      'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
      'is_project'=> DB::raw(0),
      'date_signed'=> NULL,
      'date_inspect'=> NULL, 'inspect_done'=> DB::raw(0),
      'date_construct'=> NULL, 'construct_done'=> DB::raw(0),
      'date_install'=> NULL, 'install_done'=> DB::raw(0),
      'date_portin'=> NULL, 'portin_done'=> DB::raw(0),
    ]);
    

    // action SUCCESS: leave a log and output JSON
    $log_id = log_lead_values((object) [
      'id' => $lead_id, 
      'msg' => '<p>Signed Account has been reverted and removed from Project Management.</p><p>[Location] '.$loc->name.' x [Account] '.$prov->name.'</p>',
    ]);
    log_write('Project x Signed Account reverted.', [
      'src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id'=> $log_id,
    ]);

    // if last account has been reverted, redirect back to lead-management
    if ($last_account)
      return msg_redirect('All accounts have been removed from the Project Management.', route('lead.manage', [enc_id($lead_id)]));
    else
      return msg_redirect('Account has been reverted');
  }
  
  
  
  
  /**
  * ******************************************************* PRIVATE function *******************************************************
  * return lead-related information 
  *
  * @param $lead: (reference) lead object
  * @param $agency_id: agency ID encoded
  * @return object: [permissions, agencies, managers, followers, locations, logs]
  */
  private function projectReload (& $lead, $agency_id)
  {
    $log_src = $this->log_src.'@projectReload';
    $me = Auth::user();
    
    $agency = Agency::find($agency_id);
    $lead_id = $lead->id;
    
    // get customer state-code
    $state = DB::table('states')->find($lead->state_id);
    $lead->state_code = ($state)?  $state->code : '';
    
    $follower_masters = DB::select(
      " SELECT f.user_id, IF(u.id >0, 1,0) AS valid,
            IF(u.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), f.name) AS f_name,
            IF(u.id >0, u.title, f.title) AS title,
            IF(u.id >0, u.tel, f.tel) AS tel,
            IF(u.id >0, u.email, f.email) AS email
          FROM lead_follower_masters f LEFT JOIN login_users u ON f.user_id =u.id AND :lv_max >= u.access_lv AND u.access_lv >= :lv_min AND u.active >0
          WHERE f.lead_id =:lead_id
          ORDER BY valid, f_name, f.user_id DESC
    ", [POS_LV_MASTER_ADMIN, POS_LV_CH_USER, $lead_id]);
    $follower_agents = DB::select(
      " SELECT f.order_no, f.agency_id, IF(u.id >0 AND a.id >0, 1,0) AS valid,
            IF(u.id >0 AND a.id >0, a.name, f.agency) AS agency,
            IF(u.id >0 AND a.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), f.name) AS f_name,
            IF(u.id >0 AND a.id >0, u.title, f.title) AS title,
            IF(u.id >0 AND a.id >0, u.tel, f.tel) AS tel,
            IF(u.id >0 AND a.id >0, u.email, f.email) AS email
          FROM lead_follower_agents f
            LEFT JOIN lead_relation_agency la ON f.lead_id =la.lead_id
            LEFT JOIN relation_user_agency ua ON f.user_id =ua.user_id AND la.agency_id =ua.agency_id
              LEFT JOIN agencies a ON ua.agency_id =a.id AND a.active >0
              LEFT JOIN login_users u ON ua.user_id =u.id AND u.active >0
          WHERE f.lead_id =:lead_id
            GROUP BY f.lead_id, f.order_no
          ORDER BY valid, la.agency_id, f_name, f.order_no
    ", [$lead_id]);
    $follower_provs = DB::select(
      " SELECT f.order_no, IF(c.id >0, 1,0) AS valid,
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
    
    $followers = (object)[
      'masters'=> $follower_masters,
      'agents'=> $follower_agents,
      'prov_contacts'=> $follower_provs,
    ];

    // get lead x location -> accounts kept, account to cancel, accounts signed
    $row_locations = [];
    $db_rows = DB::select(
      " SELECT l.id, l.name, l.addr, l.addr2, l.city, l.zip,  s.code AS state_code
          FROM lead_locations l LEFT JOIN states s ON l.state_id = s.id
          WHERE l.lead_id =:lead_id
          ORDER BY l.id
    ", [$lead_id]);
    if (count($db_rows) >0) {
      foreach ($db_rows as $row) {
        $r_file_count = DB::table('lead_location_files')->whereRaw(" location_id =:loc_id AND lead_id =:lead_id ", [$row->id, $lead_id])->count();

        $r_kept = [];
        $kept_accounts = DB::select(
          " SELECT id, account_id, is_selected, is_complete, provider_name AS name, accnt_no, passcode, term, date_contract_end AS date_end, etf, memo
              FROM lead_current_accounts
              WHERE location_id =:loc_id AND is_project >0 AND is_selected >0
              ORDER BY id
        ", [$row->id]);
        if ($kept_accounts) {
          foreach ($kept_accounts as $accnt) {
            $accnt->products = DB::select(
              " SELECT svc_name, prod_name, memo, price, qty
                  FROM lead_current_updated_products
                    WHERE account_id =:accnt_id
                  ORDER BY order_no
              ", [$accnt->id]);
            $r_kept[] = $accnt;
          }
        }
        $r_cancel = DB::select(
          " SELECT id, account_id, is_selected, is_complete, provider_name AS name, accnt_no, passcode, term, date_contract_end AS date_end, etf, memo,
                date_portout, date_cancel
              FROM lead_current_accounts
              WHERE location_id =:loc_id AND is_project >0 AND is_selected =0
              ORDER BY id
        ", [$row->id]);

        $r_signed = [];
        $signed_accounts = DB::select(
          " SELECT q.*, p.name AS name,  qr.spiff AS spiff_share, qr.residual AS resid_share
              FROM lead_quotes q LEFT JOIN providers p ON q.provider_id =p.id
                LEFT JOIN lead_quote_rate_agency qr ON q.id =qr.quote_id AND qr.lead_id =:lead_id AND qr.agency_id =:agency_id
              WHERE q.location_id =:loc_id AND is_project >0 AND is_selected >0
              ORDER BY q.id
        ", [$lead_id, $agency_id, $row->id]);
        if ($signed_accounts) {
          foreach ($signed_accounts as $quote) {
            $quote->spiff_expect = $agency->spiff;
            $quote->resid_expect = $agency->residual;
            $quote->total_spiff = $quote->total_resid = 0;
            
            // recurring products
            $quote->mrc_prods = DB::select(
              " SELECT p.product_id, p.memo, p.price, p.qty, p.spiff_rate, p.residual_rate,
                    IF(pp.id >0 AND s.id >0 AND pp.provider_id =:prov_id ,1,0) AS valid,
                    IF(pp.id >0, pp.p_name, p.prod_name) AS prod_name,
                    IF(s.id >0, s.name, p.svc_name) AS svc_name 
                  FROM lead_quote_mrc_products p
                  LEFT JOIN provider_products pp ON p.product_id =pp.id
                    LEFT JOIN services s ON pp.service_id =s.id
                  WHERE p.quote_id =:quote_id
                  ORDER BY valid, p.order_no
            ", [$quote->provider_id, $quote->id]);

            // non-recurring products
            $quote->nrc_prods = DB::select(
              " SELECT p.product_id, p.memo, p.price, p.qty,
                    IF(pp.id >0 AND s.id >0 AND pp.provider_id =:prov_id ,1,0) AS valid,
                    IF(pp.id >0, pp.p_name, p.prod_name) AS prod_name,
                    IF(s.id >0, s.name, p.svc_name) AS svc_name
                  FROM lead_quote_nrc_products p
                  LEFT JOIN provider_products pp ON p.product_id =pp.id
                    LEFT JOIN services s ON pp.service_id =s.id
                  WHERE p.quote_id =:quote_id
                  ORDER BY valid, p.order_no
            ", [$quote->provider_id, $quote->id]);

            if ($quote->mrc_prods) {
              foreach ($quote->mrc_prods as $prod) {
                $quote->total_spiff += $prod->spiff_rate * $prod->price * $prod->qty /100;
                $quote->total_resid += $prod->residual_rate * $prod->price * $prod->qty /100;
              }
            }
            $r_signed[] = $quote;
          }
        }
        // add location only if there is account (keep/cancel/signed) in the location
        if ($r_kept || $r_cancel || $r_signed) {
          $r_addr = $row->addr;
          $r_addr .= ($r_addr && $row->addr2)?  ', '.$row->addr2 : $row->addr2;
          $r_city_state_zip = format_city_state_zip($row->city, $row->state_code, $row->zip);
          $r_addr .= ($r_addr && $r_city_state_zip)?  ', '.$r_city_state_zip : $r_city_state_zip;
          
          $row_locations[] = (object)[
            'id'=> $row->id, 'name'=> $row->name, 'addr'=> $r_addr, 'file_count'=> $r_file_count, 
            'kept_accounts'=> $r_kept, 'cancel_accounts'=> $r_cancel, 'signed_accounts'=> $r_signed,
          ];
        }
      } // foreach: locations
    }
    $lead->project_open = (count($row_locations) >0);

    // get lead-logs, only the latest 5
    $row_logs = DB::table('lead_logs')
      ->whereRaw(' lead_id =:id ', [$lead_id])
      ->orderBy('date_log','desc')->orderBy('id','desc')
      ->take(5)->get();
    
    return (object)[
      'followers'=> $followers,
      'locations'=> $row_locations,
      'logs'=> $row_logs,
    ];
  }

  /**
  * ******************************************************* Base functions: used in extending classes/trait  *******************************************************
  * output JSON to reload Lead page with updated contents
  *  list of locations, control panel: location navigation, lead summary, followers, customer
  *
  * @param $lead_id: lead ID
  * @param $agency_id: agency ID
  * @param $vars (optional): array of additional output to include in JSON output (by default, empty)
  * @return JSON with HTML outputs
  **/
  public function jsonReload ($lead_id, $agency_id, $vars = [])
  {
    $log_src = $this->log_src.'@jsonReload';

    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);

    // if there is no location (= no account for project-management)
    $data = $this->projectReload($lead, $agency_id);
    if (!$data->locations)
      return json_encode([
        'success'=>1, 'error'=>0, 'noLocation'=> 1, 'leadManageUrl'=> route('lead.manage', ['lead_id'=> enc_id($lead_id)]),
      ]);

    
    $html_location_opts = '';
    foreach ($data->locations as $loc)
      $html_location_opts .= '<option value="'.enc_id($loc->id).'">'.$loc->name.'</option>';
    
    // create HTML output to render on reload
    $html_customer = '
      <div class="input-group">
        <label>Name</label>
        <div class="output">'.$lead->cust_name.'</div>
      </div>
      <div class="input-group">
        <label>Phone Number</label>
        <div class="output">'.format_tel($lead->tel).'</div>
      </div>
      <div class="input-group">
        <label>Tax ID</label>
        <div class="output">'.$lead->tax_id.'</div>
      </div>
      <div class="input-group">
        <label>Email Address</label>
        <div class="output">'.$lead->email.'</div>
      </div>
      <div class="input-group">
        <label>Address</label>
        <div class="output">
          <p>'.$lead->addr.'</p>
          <p>'.$lead->addr2.'</p>
          <p>'.format_city_state_zip($lead->city, $lead->state_code, $lead->zip).'</p>
        </div>
      </div>
    ';
    $html_follower = view('leads.sub-follower')
      ->with('lead_id', $lead->id)
      ->with('followers', $data->followers)
      ->with('agency_id', $agency_id)
      ->with('route_name_agent_del', 'project.ajax-follower-agent-delete')
      ->with('route_name_prov_del', 'project.ajax-follower-provider-delete')
      ->render();

    $html_logs = view('leads.sub-log')
      ->with('show_detail', 0)
      ->with('logs', $data->logs)
      ->render();
      
    $html_location = view('projects.sub-location')
      ->with('locations', $data->locations)
      ->with('open_first', FALSE)
      ->with('is_master', FALSE)
      ->render();

    // output in JSON format: also include any additional output included in $vars
    $arr_output = [
			'success'=>1, 'error'=>0,
      'custHTML'=> $html_customer, 'followerHTML'=> $html_follower, 'logHTML'=> $html_logs, 'locOptHTML'=> $html_location_opts,
      'locHTML'=> $html_location,
    ];
    if (count($vars) >0) {
      foreach ($vars as $k=>$v)
        $arr_output[$k] = $v;
    }
		return json_encode($arr_output);
  }




  
  /**
  * ******************************************************* SHARED functions using trait *******************************************************
  * **********     lead x customer     **********
  *
  * output JSON for ingenOverlay: update customer of the lead
  *
  * @param lead_id: lead ID encoded -> update lead
  */
  public function overlayCustomerMod (Request $request)
  {
    return $this->traitCustomerMod($request, route('project.ajax-customer-update', ['id'=> $request->lead_id]));
  }
  /**
  * Action: update currently selected customer (NOT available in new lead page) => output customer data in JSON.
  *  use LeadTrait->traitCustomerUpdate()
  */
  public function ajaxCustomerUpdate (Request $request)
  {
    return $this->traitCustomerUpdate($request);
  }

  /**
  * **********     lead x log     **********
  *
  * output JSON for ingenOverlay: new lead x log
  *  use LeadTrait->traitLogNew()
  */
  public function overlayLogNew(Request $request)
  {
    return $this->traitLogNew($request, route('project.ajax-log-add', ['lead_id'=> $request->lead_id]));
  }
  /**
  * output JSON for ingenOverlay: update lead x log message (= mark the log "corrected" and create new log)
  *  use LeadTrait->traitLogMod()
  */
  public function overlayLogMod(Request $request)
  {
    return $this->traitLogMod($request, route('project.ajax-log-correct', ['log_id'=> $request->log_id]));
  }
  /**
  * output JSON for ingenOverlay: show all lead x logs
  *  use LeadTrait->traitLogHistory()
  */
  public function overlayLogHistory(Request $request)
  {
    return $this->traitLogHistory($request);
  }
  /**
  * Action: add new log -> output data in JSON.
  *  use LeadTrait->traitLogAdd()
  */
  public function ajaxLogAdd (Request $request)
  {
    return $this->traitLogAdd($request);
  }
  /**
  * Action: correct existing log -> mark log as "corrected", and new log -> output data in JSON.
  *  use LeadTrait->traitLogCorrect()
  */
  public function ajaxLogCorrect (Request $request)
  {
    return $this->traitLogCorrect($request);
  }

  /**
  * **********     lead x follower     **********
  * output JSON for ingenOverlay: update follower(s) - list of agents + provider-contacts
  *  use LeadTrait->traitFollowerMod()
  */
  public function overlayFollowerMod(Request $request)
  {
    return $this->traitFollowerMod($request, route('project.ajax-follower-update', ['lead_id'=> $request->lead_id]));
  }
  /**
  * Action: update lead x followers (agent and/or provider-contacts) => output data in JSON.
  *  use LeadTrait->traitFollowerUpdate()
  */
  public function ajaxFollowerUpdate (Request $request)
  {
    return $this->traitFollowerUpdate($request);
  }
  /**
  * AJAX Action: delete lead x followers (agent) => output data in JSON.
  *  use LeadTrait->traitFollowerUpdate()
  */
  public function ajaxFollowerAgentDelete (Request $request)
  {
    return $this->traitFollowerAgentDelete($request);
  }
  /**
  * AJAX Action: delete lead x followers (provider contact) => output data in JSON.
  *  use LeadTrait->traitFollowerUpdate()
  */
  public function ajaxFollowerProviderDelete (Request $request)
  {
    return $this->traitFollowerProviderDelete($request);
  }

  /**
  * **********     lead x location     **********
  * output JSON for ingenOverlay: open file attachements
  *  us LeadTrait->traitOverlayLocationFiles()
  */
  public function overlayLocationFiles(Request $request)
  {
    return $this->traitOverlayLocationFiles($request, TRUE);
  }
  /**
  * Action: attach uploaded file(s)
  *  use LeadTrait->traitLocationFileAttach()
  */
  public function locationFileAttach(Request $request)
  {
    return $this->traitLocationFileAttach($request);
  }
  /**
  * AJAX Action: delete attached file
  *  use LeadTrait->traitLocationFileDelete()
  */
  public function ajaxLocationFileDelete(Request $request)
  {
    return $this->traitLocationFileDelete($request);
  }
}
