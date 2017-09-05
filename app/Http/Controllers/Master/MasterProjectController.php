<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use App\Agency;
use App\Provider;
use App\Traits\MasterLeadTrait;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterProjectController extends Controller
{
  use MasterLeadTrait;
  /*
  * custom variable
  */
  private $log_src = 'MasterProjectController';

  /**
  * View: list of service providers.
  *
  * @return \Illuminate\Http\Response
  */
  public function list (Request $request)
  {
    return view('master.projects.list')
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
    $manager_id = $preapp->manager_id;

    $lead_id = dec_id($request->id);
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'manager-id'=> $manager_id, 'lead-id'=> $lead_id]);

    $data = $this->projectReload($lead, $manager_id);
    if (!$lead->project_open)
      return log_redirect('There is No account in the Project Management.', ['src'=> $log_src, 'manager-id'=> $manager_id, 'lead-id'=> $lead_id],
        'err', route('lead.manage', ['lead_id'=> $request->id])
      );


    return view('master.projects.manage')
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
    $manager_id = $preapp->manager_id;

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'manager-id'=> $manager_id, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'manager-id'=> $manager_id, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'manager-id'=> $manager_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'manager-id'=> $manager_id, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

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
    $manager_id = $preapp->manager_id;

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);


    // at least 1 product is required, all POST arrays should have same count
    $n_prods = count($request->prod);
    if (!($n_prods >0))
      return log_ajax_err('One or more Products are required.', [
        'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id
      ]);
    if ($n_prods != count($request->svc) || $n_prods != count($request->memo) || $n_prods != count($request->price) || $n_prods != count($request->qty))
      return log_ajax_err('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id
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
      'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
    ]);
    return $this->jsonReload($lead_id, $manager_id, [
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
    $manager_id = $preapp->manager_id;

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);


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
    $manager_id = $preapp->manager_id;

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
      

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
      'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
    ]);
    return $this->jsonReload($lead_id, $manager_id, [
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
    $manager_id = $preapp->manager_id;

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_redirect('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
      
    
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
      'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
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
    $manager_id = $preapp->manager_id;

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_redirect('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);
      
    
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
      'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
    ]);
    return msg_redirect('Account has been marked as Not Complete');
  }
  /**
  * Action: revert account and remove from project management
  *
  * @param accnt_id: account ID encoded
  */
  public function accountRevert (Request $request)
  {
    $log_src = $this->log_src.'@accountRevert';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $manager_id = $preapp->manager_id;

    // validate: account -> location -> lead exists
    $accnt_id = dec_id($request->accnt_id);
    $accnt = DB::table('lead_current_accounts')->find($accnt_id);
    if (!$accnt)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);
    
    if (!$accnt->is_project)
      return log_redirect('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'account-id'=> $accnt_id]);

    $loc_id = $accnt->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id]);

    
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
      'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'account-id'=> $accnt_id, 'log-id'=> $log_id,
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
    $manager_id = $preapp->manager_id;

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'quote-id'=> $quote_id]);
    
    if (!$quote->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);


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
    $manager_id = $preapp->manager_id;

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_ajax_err('Account Not found.', ['src'=> $log_src, 'quote-id'=> $quote_id]);
    
    if (!$quote->is_project)
      return log_ajax_err('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_ajax_err('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', [
        'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'provider-id'=> $quote->provider_id
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
      'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id'=> $log_id,
    ]);
    return $this->jsonReload($lead_id, $manager_id, [
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
    $manager_id = $preapp->manager_id;

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'quote-id'=> $quote_id]);
    
    if (!$quote->is_project)
      return log_redirect('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'quote-id'=> $quote_id]);
    if (!$quote->date_signed)
      return log_redirect('The Account should have a Date Signed.', ['src'=> $log_src, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_redirect('Provider Not found.', [
        'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'provider-id'=> $quote->provider_id
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
      'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id'=> $log_id,
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
    $manager_id = $preapp->manager_id;

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'quote-id'=> $quote_id]);
    
    if (!$quote->is_project)
      return log_redirect('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_redirect('Provider Not found.', [
        'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'provider-id'=> $quote->provider_id
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
      'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id'=> $log_id,
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
    $manager_id = $preapp->manager_id;

    // validate: quote -> location -> lead exists
    $quote_id = dec_id($request->quote_id);
    $quote = DB::table('lead_quotes')->find($quote_id);
    if (!$quote)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'quote-id'=> $quote_id]);
    
    if (!$quote->is_project)
      return log_redirect('The Account has Not been added to the Project Management.', ['src'=> $log_src, 'quote-id'=> $quote_id]);

    $loc_id = $quote->location_id;
    $loc = DB::table('lead_locations')->find($loc_id);
    if (!$loc)
      return log_redirect('Location Not found.', ['src'=> $log_src, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $lead_id = $loc->lead_id;
    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id]);

    $prov = Provider::find($quote->provider_id);
    if (!$prov)
      return log_redirect('Provider Not found.', [
        'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'provider-id'=> $quote->provider_id
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
      'src'=> $log_src, 'lead-id'=> $lead_id, 'location-id'=> $loc_id, 'quote-id'=> $quote_id, 'log-id'=> $log_id,
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
  * @param $manager_id: manager ID encoded
  * @return object: [permissions, agencies, managers, followers, locations, logs]
  */
  private function projectReload (& $lead, $manager_id)
  {
    $log_src = $this->log_src.'@projectReload';
    $me = Auth::user();
    
    $lead_id = $lead->id;
    
    // get customer state-code
    $state = DB::table('states')->find($lead->state_id);
    $lead->state_code = ($state)?  $state->code : '';

    // get assigned agencies, managers
    $agencies = DB::select(
      " SELECT a.id, a.name,  IF(am.user_id =:manager_id OR :lv >= :master_manager_lv, 1,0) is_accessible
          FROM lead_relation_agency la LEFT JOIN agencies a ON la.agency_id =a.id
            LEFT JOIN relation_agency_manager am ON a.id =am.agency_id
          WHERE la.lead_id =:lead_id AND a.id >0
            GROUP BY a.id
          ORDER BY a.name
    ", [$manager_id, $me->access_lv, POS_LV_MASTER_MANAGER, $lead_id]);

    $managers = DB::select(
      " SELECT u.id, u.fname, u.lname, lm.is_primary
          FROM lead_relation_manager lm LEFT JOIN login_users u ON lm.user_id =u.id
          WHERE lm.lead_id =:lead_id AND u.id >0 AND u.access_lv =:ch_manager_lv
          ORDER BY u.fname, u.lname, u.id DESC
    ", [$lead_id, POS_LV_CH_MANAGER, ]);


    /* permission on leads (by default, viewable)
      mod: modify lead + agency
      manager = modify managers (= master OR primary-manager)
      commission = modify spiff/residual
    */
    $lead_perm_mod = $lead_perm_manager = $lead_perm_commission = 0;

    if ($me->access_lv >= POS_LV_MASTER_MANAGER)
      $lead_perm_mod = $lead_perm_manager = $lead_perm_commission = 1;
    elseif ($me->access_lv >= POS_LV_MASTER_USER)
      $lead_perm_commission = 1;
    elseif ($managers) {
      foreach ($managers as $manager) {
        if ($manager->id == $manager_id) {
          $lead_perm_mod = 1;
          $lead_perm_manager = $manager->is_primary;
        }
      }
    }
    
    // get lead x followers
    $follower_masters = DB::select(
      " SELECT f.user_id, IF(u.id >0, 1,0) AS valid,
            IF(u.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), f.name) AS f_name,
            IF(u.id >0, u.title, f.title) AS title,
            IF(u.id >0, u.tel, f.tel) AS tel,
            IF(u.id >0, u.email, f.email) AS email,
            IF(u.id >0 AND (:auth_lv >= :master_manager_lv OR u.access_lv >= :master_user_lv OR u.id =:manager_id1 OR am.user_id =:manager_id2), 1,0) is_accessible
          FROM lead_follower_masters f
            LEFT JOIN login_users u ON f.user_id =u.id AND :lv_max >= u.access_lv AND u.access_lv >= :lv_min AND u.active >0
              LEFT JOIN relation_assistant_manager am ON u.id =am.assistant_id
          WHERE f.lead_id =:lead_id
            GROUP BY u.id
          ORDER BY valid, f_name, f.user_id DESC
    ", [$me->access_lv, POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER, $manager_id, $manager_id,  POS_LV_MASTER_ADMIN, POS_LV_CH_USER, $lead_id]);
    $follower_agents = DB::select(
      " SELECT f.order_no, IF(u.id >0 AND a.id >0, 1,0) AS valid,
            IF(u.id >0 AND a.id >0, TRIM(CONCAT(u.fname,' ',u.lname)), f.name) AS f_name,
            IF(u.id >0 AND a.id >0, u.title, f.title) AS title,
            IF(u.id >0 AND a.id >0, u.tel, f.tel) AS tel,
            IF(u.id >0 AND a.id >0, u.email, f.email) AS email,
            IF(u.id >0 AND a.id >0, a.name, f.agency) AS agency
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
          " SELECT q.*, p.name AS name
              FROM lead_quotes q LEFT JOIN providers p ON q.provider_id =p.id
                LEFT JOIN lead_locations l ON q.location_id =l.id
              WHERE l.lead_id =:lead_id AND l.id =:loc_id AND q.is_project >0 AND q.is_selected >0
              ORDER BY q.id
        ", [$lead_id, $row->id]);
        if ($signed_accounts) {
          foreach ($signed_accounts as $quote) {
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
      'permissions' => (object)['mod'=> $lead_perm_mod, 'manager'=> $lead_perm_manager, 'commission'=> $lead_perm_commission, ],
      'agencies'=> $agencies,
      'managers'=> $managers,
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
  * @param $manager_id: manager ID (if auth-user is channel manager/assistant, 0 for master-agents)
  * @param $vars (optional): array of additional output to include in JSON output (by default, empty)
  * @return JSON with HTML outputs
  */
  public function jsonReload ($lead_id, $manager_id, $vars = [])
  {
    $log_src = $this->log_src.'@jsonReload';
    $me = Auth::user();

    $lead = $this->getLead($lead_id, $manager_id);
    if (!$lead)
      return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'lead-id'=> $lead_id, 'manager-id'=> $manager_id,]);

    // if there is no location (= no account for project-management)
    $data = $this->projectReload($lead, $manager_id);
    if (!$data->locations)
      return json_encode([
        'success'=>1, 'error'=>0, 'noLocation'=> 1, 'leadManageUrl'=> route('master.lead.manage', ['lead_id'=> enc_id($lead_id)]),
      ]);
    
    
    // create HTML output to render on reload
    $html_commission = view('master.leads.sub-commission')
      ->with('lead_id', $lead_id)
      ->with('permissions', $data->permissions)
      ->with('agencies', $data->agencies)
      ->with('managers', $data->managers)
      ->render();

    
    $html_location_opts = '';
    foreach ($data->locations as $loc)
      $html_location_opts .= '<option value="'.enc_id($loc->id).'">'.$loc->name.'</option>';
    
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
    $html_follower = view('master.leads.sub-follower')
      ->with('lead_id', $lead->id)
      ->with('followers', $data->followers)
      ->with('route_name_master_del', 'master.project.ajax-follower-master-delete')
      ->with('route_name_prov_del', 'master.project.ajax-follower-provider-delete')
      ->render();

    $html_logs = view('leads.sub-log')
      ->with('show_detail', 0)
      ->with('logs', $data->logs)
      ->render();
      
    $html_location = view('projects.sub-location')
      ->with('locations', $data->locations)
      ->with('open_first', FALSE)
      ->with('is_master', TRUE)
      ->render();

    // output in JSON format: also include any additional output included in $vars
    $arr_output = [
			'success'=>1, 'error'=>0,
      'commissionHTML'=> $html_commission, 'custHTML'=> $html_customer, 'followerHTML'=> $html_follower, 'logHTML'=> $html_logs,
      'locOptHTML'=> $html_location_opts, 'locHTML'=> $html_location,
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
    return $this->traitCustomerMod($request, route('master.project.ajax-customer-update', ['id'=> $request->lead_id]));
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
    return $this->traitLogNew($request, route('master.project.ajax-log-add', ['lead_id'=> $request->lead_id]));
  }
  /**
  * output JSON for ingenOverlay: update lead x log message (= mark the log "corrected" and create new log)
  *  use LeadTrait->traitLogMod()
  */
  public function overlayLogMod(Request $request)
  {
    return $this->traitLogMod($request, route('master.project.ajax-log-correct', ['log_id'=> $request->log_id]));
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
  *  use MasterLeadTrait->traitFollowerMod()
  */
  public function overlayFollowerMod(Request $request)
  {
    return $this->traitFollowerMod($request, route('master.project.ajax-follower-update', ['lead_id'=> $request->lead_id]));
  }
  /**
  * Action: update lead x followers (master and/or provider-contacts) => output data in JSON.
  *  use MasterLeadTrait->traitFollowerUpdate()
  */
  public function ajaxFollowerUpdate (Request $request)
  {
    return $this->traitFollowerUpdate($request);
  }
  /**
  * AJAX Action: delete lead x followers (master) => output data in JSON.
  *  use MasterLeadTrait->traitFollowerMasterDelete()
  */
  public function ajaxFollowerMasterDelete (Request $request)
  {
    return $this->traitFollowerMasterDelete($request);
  }
  /**
  * AJAX Action: delete lead x followers (agent) => output data in JSON.
  *  use MasterLeadTrait->traitFollowerUpdate()
  */
  /*
  public function ajaxFollowerAgentDelete (Request $request)
  {
    return $this->traitFollowerAgentDelete($request);
  }
  /**
  * AJAX Action: delete lead x followers (provider contact) => output data in JSON.
  *  use MasterLeadTrait->traitFollowerUpdate()
  */
  public function ajaxFollowerProviderDelete (Request $request)
  {
    return $this->traitFollowerProviderDelete($request);
  }

  /**
  * **********     lead x commission     **********
  * output JSON for ingenOverlay: set commission share for all agency, managers assigned to the lead
  *  use MasterLeadTrait->traitFollowerMod()
  */
  public function overlayCommissionMod(Request $request)
  {
    return $this->traitCommissionMod($request, route('master.project.commission-update', ['lead_id'=> $request->lead_id]));
  }
  /**
  * Action: update commission share for all agency, manager assigned to the lead
  *  use MasterLeadTrait->traitCommissionUpdate()
  */
  public function commissionUpdate(Request $request)
  {
    return $this->traitCommissionUpdate($request);
  }

  /**
  * **********     lead x agency     **********
  * output JSON for ingenOverlay: assign new agency (amongst agency the auth-master-agent has access to)
  *  use MasterLeadTrait->traitOverlayAgencyAssign()
  */
  public function overlayAgencyAssign(Request $request)
  {
    return $this->traitOverlayAgencyAssign($request, route('master.lead.agency-assign', ['lead_id'=> $request->lead_id]));
  }
  /**
  * Action: assign new agency to the lead (cannot exceed # set in config = MAX_AGENCY_PER_LEAD)
  *  use MasterLeadTrait->traitAgencyAssign()
  */
  public function agencyAssign(Request $request)
  {
    return $this->traitAgencyAssign($request);
  }
  /**
  * Action: remove currently assigned agency
  *  use MasterLeadTrait->traitAgencyRemove()
  */
  public function agencyRemove(Request $request)
  {
    return $this->traitAgencyRemove($request);
  }
  /**
  * **********     lead x manager     **********
  * output JSON for ingenOverlay: assign new master-agent (amongst agency the auth-master-agent has access to)
  *  use MasterLeadTrait->traitOverlayManagerAssign()
  */
  public function overlayManagerAssign(Request $request)
  {
    return $this->traitOverlayManagerAssign($request, route('master.lead.manager-assign', ['id'=> $request->lead_id]));
  }
  /**
  * Action: assign new channel manager to the lead (cannot exceed # set in config = MAX_MANAGER_PER_LEAD)
  *  use MasterLeadTrait->traitManagerAssign()
  */
  public function managerAssign(Request $request)
  {
    return $this->traitManagerAssign($request);
  }
  /**
  * Action: set selected channel-manager as a primary manager of the lead
  *  use MasterLeadTrait->traitManagerSetPrimary()
  */
  public function managerSetPrimary(Request $request)
  {
    return $this->traitManagerSetPrimary($request);
  }
  /**
  * Action: remove currently assigned channel-manager
  *  use MasterLeadTrait->traitManagerRemove()
  */
  public function managerRemove(Request $request)
  {
    return $this->traitManagerRemove($request);
  }

  /**
  * **********     lead x location     **********
  * output JSON for ingenOverlay: open file attachements
  *  use MasterLeadTrait->traitOverlayLocationFiles()
  */
  public function overlayLocationFiles(Request $request)
  {
    return $this->traitOverlayLocationFiles($request, TRUE);
  }
  /**
  * Action: attach uploaded file(s)
  *  use MasterLeadTrait->traitLocationFileAttach()
  */
  public function locationFileAttach(Request $request)
  {
    return $this->traitLocationFileAttach($request);
  }
  /**
  * AJAX Action: delete attached file
  *  use MasterLeadTrait->traitLocationFileDelete()
  */
  public function ajaxLocationFileDelete(Request $request)
  {
    return $this->traitLocationFileDelete($request);
  }
}
