<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use App\Agency;
use App\User;
use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterAccountController extends Controller
{
  /*
   * custom variable
   */
  private $log_src = 'Master\MasterAccountController';

  /*
  * View: list of service providers.
   *
   * @return \Illuminate\Http\Response
   */
  public function list (Request $request)
  {
    return view('master.providers.list')
      ->with('preapp', $request->get('preapp'));
  }
  /**
   * View: provider overview
   *
   * @param $id: provider ID encoded
   * @return \Illuminate\Http\Response
   */
  public function view (Request $request)
  {
    $log_src = $this->log_src.'@view';
    $preapp = $request->get('preapp');
    $accnt_id = dec_id($request->id);

    $accnt_id = 2;

    $accnt = DB::table('commission_accounts')->find($accnt_id);
    if (!$accnt)
      return log_redirect('Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);

    $accnt->agencies = DB::table('commission_account_agency')->where('account_id', $accnt_id)->orderBy('agency')->get();
    $accnt->managers = DB::table('commission_account_manager')->where('account_id', $accnt_id)->orderBy('manager')->get();
    $accnt->mrc_prods = DB::table('commission_account_mrc_products')->where('account_id', $accnt_id)->orderBy('order_no')->get();
    $accnt->nrc_prods = DB::table('commission_account_nrc_products')->where('account_id', $accnt_id)->orderBy('order_no')->get();

    return view('master.accounts.view')
      ->with('preapp', $request->get('preapp'))
      ->with('account', $accnt);
  }
  /**
   * View: create new commission account.
   *
   * @return \Illuminate\Http\Response
   */
  public function new (Request $request)
  {
    $log_src = $this->log_src.'@new';
    $preapp = $request->get('preapp');
    
    // check if auth-user has permission-rec for commission-account
    if (!$preapp->perm_account_rec)
      return no_access(['src'=> $log_src, ]);

    // get imported quote information (if exists)
    $quote = NULL;

    if ($request->quote_id) {
      $quote_id = dec_id($request->quote_id);
      $quote = DB::table('lead_quotes')->find($quote_id);
      if (!$quote)
        return log_redirect('Quote Not found.', ['src'=> $log_src, 'quote-id'=> $quote_id, ]);
          
      $quote->provider = Provider::find($quote->provider_id);
      if (!$quote->provider)
        return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'quote-id'=> $quote_id, 'provider-id'=> $quote->provider_id, ]);

      $quote->location = DB::table('lead_locations')->find($quote->location_id);
      if (!$quote->location)
        return log_ajax_err('Location Not found.', ['src'=> $log_src, 'quote-id'=> $quote_id, 'location-id'=> $quote->location_id, ]);

      $lead_id = $quote->location->lead_id;
      $quote->lead = DB::table('leads')->find($lead_id);
      if (!$quote->lead)
        return log_ajax_err('Lead Not found.', ['src'=> $log_src, 'quote-id'=> $quote_id, 'lead-id'=> $lead_id, ]);

      $quote->share_agency = DB::select(
        " SELECT a.id, a.name, ra.spiff, ra.residual
            FROM lead_relation_agency la
              LEFT JOIN lead_quote_rate_agency ra ON ra.lead_id =la.lead_id AND ra.agency_id =la.agency_id AND ra.quote_id =:quote_id
              LEFT JOIN agencies a ON la.agency_id =a.id
            WHERE la.lead_id =:lead_id AND a.id >0
              GROUP BY la.agency_id
            ORDER BY a.name, a.id DESC
      ", [$quote_id, $lead_id, ]);

      $quote->share_manager = DB::select(
        " SELECT u.id, u.fname, u.lname, rm.spiff, rm.residual
            FROM lead_relation_manager lm
              LEFT JOIN lead_quote_rate_manager rm ON rm.lead_id =lm.lead_id AND rm.user_id =lm.user_id AND rm.quote_id =:quote_id
              LEFT JOIN login_users u ON lm.user_id =u.id
            WHERE lm.lead_id =:lead_id AND u.access_lv =:lv
              GROUP BY lm.user_id
            ORDER BY u.fname, u.lname, u.id DESC
      ", [$quote_id, $lead_id, POS_LV_CH_MANAGER]);

      // recurring products
      $quote->mrc_prods = DB::select(
        " SELECT p.*
            FROM lead_quote_mrc_products p
              LEFT JOIN provider_products pp ON p.product_id =pp.id AND pp.provider_id =:provider_id
              LEFT JOIN services s ON pp.service_id =s.id
            WHERE p.quote_id =:quote_id AND pp.id >0 AND s.id >0
            ORDER BY p.order_no
      ", [$quote->provider_id, $quote_id]);

      // non-recurring products
      $quote->nrc_prods = DB::select(
        " SELECT p.*
            FROM lead_quote_nrc_products p
              LEFT JOIN provider_products pp ON p.product_id =pp.id AND pp.provider_id =:provider_id
              LEFT JOIN services s ON pp.service_id =s.id
            WHERE p.quote_id =:quote_id AND pp.id >0 AND s.id >0
            ORDER BY p.order_no
      ", [$quote->provider_id, $quote_id]);
    } else {

      // default values if account is created from scratch
      $quote = (object)[
        'id'=> NULL,
        'lead'=> (object)[ 'tel'=> '', ],
        'location'=> (object)[ 'addr'=> '', 'addr2'=> '', 'city'=> '', 'state_id'=> '', 'zip'=> '', ],
        'provider'=> NULL,
        'term'=> 1,
        'date_signed'=> NULL, 'date_contract_begin'=> NULL, 'date_contract_end'=> NULL,
          'date_inspect'=> NULL, 'date_construct'=> NULL, 'date_install'=> NULL, 'date_portin'=> NULL,
        'share_agency' => [],
        'share_manager' => [],
        'mrc_prods' => [],
        'nrc_prods' => [],
      ];
    }

    $data = (object) [
      'row_states'=> get_state_list(),
    ];
    return view('master.accounts.new')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('quote', $quote);
  }
  /**
   * View: update service provider.
   *
   * @param $id: provider ID encoded
   * @return \Illuminate\Http\Response
   */
  public function mod (Request $request)
  {
    $log_src = $this->log_src.'@mod';
    $preapp = $request->get('preapp');
    $prov_id = dec_id($request->id);

    // check if auth-user has permission-mod for provider
    if (!$preapp->perm_prov_mod)
      return no_access(['src'=> $log_src, 'provider-id'=> $prov_id]);

    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_redirect('Provider Not found.', ['src'=> $log_src, 'provider-id'=> $prov_id]);
      
    
    $data = (object) array(
      'row_states'=> get_state_list(),
    );
    return view('master.providers.mod')
      ->with('preapp', $request->get('preapp'))
      ->with('prov', $prov)
      ->with('data', $data);
  }

  /**
   * Action: create new commission account
   *
   * on success - return to overview
   * on fail - return to new view
   */
  public function create (Request $request)
  {
    $log_src = $this->log_src.'@create';
    $preapp = $request->get('preapp');
    
    // check if auth-user has permission-rec for account
    if (!$preapp->perm_account_rec)
      return no_access(['src'=> $log_src, ]);


    // input validation
    $n_spiff_agency = $n_spiff_manager = $n_resid_agency = $n_resid_manager = 0;
    if (isset($request->spiff_share['agency']))
      $n_spiff_agency = count($request->spiff_share['agency']);
    if (isset($request->spiff_share['manager']))
      $n_spiff_manager = count($request->spiff_share['manager']);
    if (isset($request->resid_share['agency']))
      $n_resid_agency = count($request->resid_share['agency']);
    if (isset($request->resid_share['manager']))
      $n_resid_manager = count($request->resid_share['manager']);

    if ($n_spiff_agency + $n_spiff_manager < 1)
      return log_redirect('At least one Agency or Channel Manager is required.', [ 'src'=> $log_src, ]);
    if ($n_spiff_agency > MAX_AGENCY_PER_LEAD || $n_spiff_manager > MAX_MANAGER_PER_LEAD)
      return log_redirect('Maximum number of Agency and/or Manager is '.MAX_AGENCY_PER_LEAD, [
        'src'=> $log_src, '# spiff-agency' => $n_spiff_agency, '# spiff-manager' => $n_spiff_manager,
      ]);
    if ($n_spiff_agency != $n_resid_agency ||  $n_spiff_manager != $n_resid_manager)
      return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'msg'=> 'spiff-share, residual-share count is mismatching',
        '# spiff-agency' => $n_spiff_agency, '# spiff-manager' => $n_spiff_manager, '# residual-agency' => $n_resid_agency, '# residual-manager' => $n_resid_manager,
      ]);

    $n_prod_id = count($request->prod_id);
    if ($n_prod_id < 1)
      return log_redirect('At least one Product is required.', [ 'src'=> $log_src, ]);
    if ($n_prod_id != count($request->is_mrc) || $n_prod_id != count($request->prod_memo) || $n_prod_id != count($request->prod_spiff) ||
      $n_prod_id != count($request->prod_resid) || $n_prod_id != count($request->prod_price) || $n_prod_id != count($request->prod_qty)
    )
      return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'msg'=> 'prod x variable count is mismatching', '# prod-id'=> $n_prod_id]);
    
    $v = Validator::make($request->all(), [
      'spiff_share.*.*' => 'required|numeric|min:0|max:100',
      'resid_share.*.*' => 'required|numeric|min:0|max:100',
      'prov_id' => 'required',
      'term' => 'required|numeric',
      'bill_addr' => 'required',
      'bill_city' => 'required',
      'bill_state_id' => 'required|numeric',
      'bill_zip' => ['nullable','max:10', 'regex:/^\d{5}(-\d{4})?$/'],
      'bill_tel' => ['required','max:10', 'regex:/^\d{10}$/'],
      'ship_addr' => 'required',
      'ship_city' => 'required',
      'ship_state_id' => 'required|numeric',
      'ship_zip' => ['nullable','max:10', 'regex:/^\d{5}(-\d{4})?$/'],
      'ship_tel' => ['required','max:10', 'regex:/^\d{10}$/'],
      'date_sign' => 'required|date_format:Y-m-d',
      'date_contract_begin' => 'nullable|date_format:Y-m-d',
      'date_contract_end' => 'nullable|date_format:Y-m-d',
      'date_inspect' => 'nullable|date_format:Y-m-d',
      'date_construct' => 'nullable|date_format:Y-m-d',
      'date_install' => 'nullable|date_format:Y-m-d',
      'date_port' => 'nullable|date_format:Y-m-d',
      'is_mrc.*' => 'required|boolean',
      'prod_spiff.*' => 'required|numeric|min:0',
      'prod_resid.*' => 'required|numeric|min:0',
      'prod_price.*' => 'required|numeric|min:0',
      'prod_qty.*' => 'required|integer|min:0',
    ], [
      'spiff_share.*' => 'Commission Share should be a decimal between 0 to 100 (%).',
      'resid_share.*' => 'Commission Share should be a decimal between 0 to 100 (%).',
      'prod_id.*' => 'Please select a Service Provider.',
      'term.*' => 'Account Term is required.',
      'bill_addr.*'=> 'Billing Address is required.',
      'bill_city.*'=> 'Billing City is required.',
      'bill_state_id.*'=> 'Billing State is required.',
      'bill_zip.*'=> 'Billing Zip Code is required.',
      'bill_tel.*'=> 'Billing Phone Number is required.',
      'ship_addr.*'=> 'Shipping Address is required.',
      'ship_city.*'=> 'Shipping City is required.',
      'ship_state_id.*'=> 'Shipping State is required.',
      'ship_zip.*'=> 'Shipping Zip Code is required.',
      'ship_tel.*'=> 'Shipping Phone Number is required.',
      'date_sign.*'=> 'Date signed is a required field.',
      'date_contract_begin.*'=> 'Date field should have a valid date format.',
      'date_contract_end.*'=> 'Date field should have a valid date format.',
      'date_inspect.*'=> 'Date field should have a valid date format.',
      'date_construct.*'=> 'Date field should have a valid date format.',
      'date_install.*'=> 'Date field should have a valid date format.',
      'date_port.*'=> 'Date field should have a valid date format.',
      'prod_id.*' => 'Invalid value for a Product entered.',
      'is_mrc.*' => 'Invalid value for a Product entered',
      'prod_spiff.*' => 'Product Spiff Rate should be a decimal.',
      'prod_resid.*' => 'Product Residual Rate should be a decimal.',
      'prod_price.*' => 'Product Price should be a decimal.',
      'prod_qty.*' => 'Product Quantity should be an integer.',
    ]);
    if ($v->fails()) {
      $errs = $v->errors()->all();
      $errs = [];
      // filter out duplicate error message(s) - since all fields are array, same error can occur multiple times
      foreach ($errs_tmp as $r) {
        if (!in_array($r, $errs))
          $errs[] = $r;
      }
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';
      return err_redirect($msg);
    }

    // validate and create input variables
    if ($request->quote_id) {
      $quote_id = dec_id($request->quote_id);
      $db_rows = DB::select(
        " SELECT q.id, l.lead_id  FROM lead_quotes q LEFT JOIN lead_locations l ON q.location_id =l.id 
            WHERE q.id=:quote_id AND q.is_selected =1 AND q.is_project =1 AND q.is_complete =1 AND l.id >0
              LIMIT 1
      ", [$quote_id]);
      if (!$db_rows)
        return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
          'src'=> $log_src, 'msg'=> 'Reference Quote (Lead x Signed Quote) NOT found.', 'quote-id'=> $quote_id]);
      $quote = $db_rows[0];
    } else
      $quote_id = 0;

    $prov_id = dec_id($request->prov_id);
    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'msg'=> 'Provider NOT found.', 'provider-id'=> $prov_id]);

    $state = DB::table('states')->find($request->bill_state_id);
    if (!$state)
      return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'msg'=> 'State NOT found.', 'billing-state-id'=> $bill_state_id]);
    $bill_state_code = $state->code;

    $state = DB::table('states')->find($request->ship_state_id);
    if (!$state)
      return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'msg'=> 'State NOT found.', 'shipping-state-id'=> $ship_state_id]);
    $ship_state_code = $state->code;

    $p_bill_addr2 = ($request->bill_addr2)?  $request->bill_addr2 : '';
    $p_ship_addr2 = ($request->ship_addr2)?  $request->ship_addr2 : '';
    
    $input_agencies = $input_managers = $input_mrc = $input_nrc = [];

    if ($n_spiff_agency) {
      foreach ($request->spiff_share['agency'] as $agency_id_enc => $r_spiff) {
        $agency_id = dec_id($agency_id_enc);
        $agency = Agency::find($agency_id);
        if (!$agency)
          return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
            'src'=> $log_src, 'msg'=> 'Agency NOT found.', 'agency-id'=> $agency_id]);
        
        $input_agencies[] = ['agency_id'=> $agency_id, 'agency'=> $agency->name, 'spiff'=> $r_spiff, 'residual'=> $request->resid_share['agency'][$agency_id_enc], ];
      }
    }
    if ($n_spiff_manager) {
      foreach ($request->spiff_share['manager'] as $user_id_enc => $r_spiff) {
        $manager_id = dec_id($user_id_enc);
        $manager = User::find($manager_id);
        if (!$manager)
          return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
            'src'=> $log_src, 'msg'=> 'Manager User NOT found.', 'manager-id'=> $manager_id]);
        
        $input_managers[] = ['user_id'=> $manager_id, 'manager'=> trim($manager->fname.' '.$manager->lname),
          'spiff'=> $r_spiff, 'residual'=> $request->resid_share['manager'][$user_id_enc],
        ];
      }
    }
    if ($total_spiff >100 || $total_resid > 100)
      return log_redirect('Total Spiff/Residual Share cannot exceed 100%.', [
        'src'=> $log_src, 'manager-id'=> $manager_id, 'total-spiff'=> $total_spiff, 'total-residual'=> $total_resid,
      ]);
      
    for ($i =0; $i < $n_prod_id; $i++) {
      $prod_id = dec_id($request->prod_id[$i]);
      $r_memo = ($request->prod_memo[$i])?  $request->prod_memo[$i] : '';

      $db_rows = DB::select(
        " SELECT p.*, s.name AS svc_name
            FROM provider_products p LEFT JOIN services s ON p.service_id =s.id
            WHERE p.id =:id AND provider_id =:prov_id AND s.id >0
              LIMIT 1
      ", [$prod_id, $prov_id]);
      if (!$db_rows)
        return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
          'src'=> $log_src, 'msg'=> 'Provider x Product NOT found.', 'provider-id'=> $prov_id, 'product-id'=> $prod_id, ]);
      
      if ($request->is_mrc[$i] >0)
        $input_mrc[] = ['product_id'=> $prod_id, 'svc_name'=> $db_rows[0]->svc_name, 'prod_name'=> $db_rows[0]->p_name,
          'memo'=> $r_memo, 'price'=> $request->prod_price[$i], 'qty'=> $request->prod_qty[$i],
          'spiff'=> $request->prod_spiff[$i], 'residual'=> $request->prod_resid[$i],
        ];
      else
        $input_nrc[] = ['product_id'=> $prod_id, 'svc_name'=> $db_rows[0]->svc_name, 'prod_name'=> $db_rows[0]->p_name,
          'memo'=> $r_memo, 'price'=> $request->prod_price[$i], 'qty'=> $request->prod_qty[$i],
        ];
    }


    // validation passed -> create new account (Query Builder)
    $accnt_id = DB::table('commission_accounts')->insertGetId([
      'mod_id' => Auth::id(), 'mod_user' => trim(Auth::user()->fname.' '.Auth::user()->lname),
      'provider_id' => $prov_id, 'provider' => $prov->name,
      'bill_addr' => $request->bill_addr, 'bill_addr2' => $request->bill_addr2,
      'bill_city' => $request->bill_city, 'bill_state' => $bill_state_code, 'bill_zip' => $request->bill_zip, 'bill_tel' => $request->bill_tel,
      'ship_addr' => $request->ship_addr, 'ship_addr2' => $request->ship_addr2,
      'ship_city' => $request->ship_city, 'ship_state' => $ship_state_code, 'ship_zip' => $request->ship_zip, 'ship_tel' => $request->ship_tel,
      'term' => $request->term,
      'date_signed' => $request->date_sign, 'date_contract_begin' => $request->date_contract_begin, 'date_contract_end' => $request->date_contract_end,
      'date_inspect' => $request->date_inspect, 'date_construct' => $request->date_construct, 'date_install' => $request->date_install, 'date_portin' => $request->date_port,
    ]);
    if ($input_agencies) {
      $n = count($input_agencies);
      for ($i =0; $i < $n; $i++)
        $input_agencies[$i]['account_id'] = $accnt_id;
      DB::table('commission_account_agency')->insert($input_agencies);
    }
    if ($input_managers) {
      $n = count($input_managers);
      for ($i =0; $i < $n; $i++)
        $input_managers[$i]['account_id'] = $accnt_id;
      DB::table('commission_account_manager')->insert($input_managers);
    }
    if ($input_mrc) {
      $n = count($input_mrc);
      for ($i =0; $i < $n; $i++) {
        $input_mrc[$i]['account_id'] = $accnt_id;
        $input_mrc[$i]['order_no'] = $i;
      }
      DB::table('commission_account_mrc_products')->insert($input_mrc);
    }
    if ($input_nrc) {
      $n = count($input_nrc);
      for ($i =0; $i < $n; $i++) {
        $input_nrc[$i]['account_id'] = $accnt_id;
        $input_nrc[$i]['order_no'] = $i;
      }
      DB::table('commission_account_nrc_products')->insert($input_nrc);
    }
    // if account created from quote (conversion): leave a record for quote x account relationship
    if ($quote)
      DB::table('relation_account_quote')->insert(['account_id'=> $accnt_id, 'lead_id'=> $quote->lead_id, 'quote_id'=> $quote_id, ]);
    

    // action SUCCESS: leave a log and redirect to provider.view
    log_write('Commission Account Created.', ['src'=> $log_src, 'new-account-id'=> $accnt_id, 'quote-id'=> $quote_id,]);
    return msg_redirect('Commission Account has been created.', route('master.account.view', ['id'=> enc_id($accnt_id)]));
  }

  /**
   * Action: update provider.
   *
   * @param $id: provider ID encoded
   *
   * on success - return to overview
   * on fail - return to update view
   */
  public function update (Request $request)
  {
    $log_src = $this->log_src.'@update';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $prov_id = dec_id($request->id);

    // check if auth-user has permission-mod for provider
    if (!$preapp->perm_prov_mod)
      return no_access(['src'=> $log_src, 'provider-id'=> $prov_id]);

    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_redirect('Provider Not found.', ['src'=> $log_src, 'provider-id'=> $prov_id]);


    // input validation
    $v = Validator::make($request->all(), [
      'c_name' => 'required',
      'is_active' => 'required|numeric',
      'state_id' => 'nullable|numeric',
      'zip' => ['nullable','max:10', 'regex:/^\d{5}(-\d{4})?$/'],
      'tel' => ['required','max:10', 'regex:/^\d{10}$/'],
      'term' => ['required','integer','min:1','max:1000', 'regex:/^\d+$/'],
      'spiff' => 'required|numeric|min:0',
      'resid' => 'required|numeric|min:0',
    ], [
      'c_name.*' => 'Company Name is required.',
      'is_active.*' => 'Active Status has invalid input.',
      'state_id.*' => 'State has invalid input.',
      'zip.*'=> 'Please use a valid US Zip code.',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',      
      'term.*'=> 'Invalid value for Default Term. Default Term should be Integer. Use 0 for No Contract.',
      'spiff.*'=> 'Spiff Rate should be a decimal.',
      'resid.*'=> 'Residual Rate should be a decimal.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }

    $p_addr = ($request->addr)?  $request->addr : '';
    $p_addr2 = ($request->addr2)?  $request->addr2 : '';
    $p_city = ($request->city)?  $request->city : '';
    $p_state_id = ($request->state_id)?  $request->state_id : 0;
    $p_zip = ($request->zip)?  $request->zip : '';


    // validation passed -> update provider (Eloquent ORM)
    $prov = Provider::find($prov_id);
    $prov->mod_id = $me->id;
    $prov->mod_user = trim($me->fname.' '.$me->lname);
    $prov->name = $request->c_name;
    $prov->addr = $p_addr;
    $prov->addr2 = $p_addr2;
    $prov->city = $p_city;
    $prov->state_id = $p_state_id;
    $prov->zip = $p_zip;
    $prov->tel = $request->tel;
    $prov->default_term = $request->term;
    $prov->default_spiff = $request->spiff;
    $prov->default_residual = $request->resid;
    $prov->active = DB::raw($request->is_active);

    $prov->save();

    // action SUCCESS: leave a log and redirect to provider.view
    log_write('Provider Updated.', ['src'=> $log_src, 'provider-id'=> $prov_id]);
    return msg_redirect('Provider has been updated.', route('master.provider.view', ['id'=> $request->id]));
  }
  /**
  * Action: update account x commission
  *
  * @param $accnt_id: account ID encoded
  */
  public function updateCommission (Request $request)
  {
    $log_src = $this->log_src.'@updateCommission';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $accnt_id = dec_id($request->accnt_id);

    // check if auth-user has permission-mod for provider
    if (!$preapp->perm_prov_mod)
      return no_access(['src'=> $log_src, 'provider-id'=> $prov_id]);

    
    $accnt = DB::table('commission_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Commission Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);


    // input validation
    $n_spiff_agency = $n_spiff_manager = $n_resid_agency = $n_resid_manager = 0;
    if (isset($request->spiff_share['agency']))
      $n_spiff_agency = count($request->spiff_share['agency']);
    if (isset($request->spiff_share['manager']))
      $n_spiff_manager = count($request->spiff_share['manager']);
    if (isset($request->resid_share['agency']))
      $n_resid_agency = count($request->resid_share['agency']);
    if (isset($request->resid_share['manager']))
      $n_resid_manager = count($request->resid_share['manager']);

    if ($n_spiff_agency + $n_spiff_manager < 1)
      return log_redirect('At least one Agency or Channel Manager is required.', [ 'src'=> $log_src, ]);
    if ($n_spiff_agency > MAX_AGENCY_PER_LEAD || $n_spiff_manager > MAX_MANAGER_PER_LEAD)
      return log_redirect('Maximum number of Agency and/or Manager is '.MAX_AGENCY_PER_LEAD, [
        'src'=> $log_src, '# spiff-agency' => $n_spiff_agency, '# spiff-manager' => $n_spiff_manager,
      ]);
    if ($n_spiff_agency != $n_resid_agency ||  $n_spiff_manager != $n_resid_manager)
      return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
        'src'=> $log_src, 'msg'=> 'spiff-share, residual-share count is mismatching',
        '# spiff-agency' => $n_spiff_agency, '# spiff-manager' => $n_spiff_manager, '# residual-agency' => $n_resid_agency, '# residual-manager' => $n_resid_manager,
      ]);
      
    $v = Validator::make($request->all(), [
      'spiff_share.*.*' => 'required|numeric|min:0|max:100',
      'resid_share.*.*' => 'required|numeric|min:0|max:100',
    ], [
      'spiff_share.*' => 'Commission Share should be a decimal between 0 to 100 (%).',
      'resid_share.*' => 'Commission Share should be a decimal between 0 to 100 (%).',
    ]);
    if ($v->fails()) {
      $errs = $v->errors()->all();
      $errs = [];
      // filter out duplicate error message(s) - since all fields are array, same error can occur multiple times
      foreach ($errs_tmp as $r) {
        if (!in_array($r, $errs))
          $errs[] = $r;
      }
      $msg = '';
      foreach ($errs as $err)
        $msg .= '<p>'.$err.'</p>';
      return err_redirect($msg);
    }

    // validate and create input variables
    $input_agencies = $input_managers = [];
    $total_spiff = $total_resid = 0;

    if ($n_spiff_agency) {
      foreach ($request->spiff_share['agency'] as $agency_id_enc => $r_spiff) {
        $agency_id = dec_id($agency_id_enc);
        $agency = Agency::find($agency_id);
        if (!$agency)
          return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
            'src'=> $log_src, 'msg'=> 'Agency NOT found.', 'agency-id'=> $agency_id]);

        $total_spiff += $r_spiff;
        $total_resid += $request->resid_share['agency'][$agency_id_enc];
        
        $input_agencies[] = ['account_id'=> $accnt_id, 'agency_id'=> $agency_id, 'agency'=> $agency->name,
          'spiff'=> $r_spiff, 'residual'=> $request->resid_share['agency'][$agency_id_enc], ];
      }
    }
    if ($n_spiff_manager) {
      foreach ($request->spiff_share['manager'] as $user_id_enc => $r_spiff) {
        $manager_id = dec_id($user_id_enc);
        $manager = User::find($manager_id);
        if (!$manager)
          return log_redirect('Form Input is misconfigured. Please contact the adminstrator.', [
            'src'=> $log_src, 'msg'=> 'Manager User NOT found.', 'manager-id'=> $manager_id]);

        $total_spiff += $r_spiff;
        $total_resid += $request->resid_share['manager'][$user_id_enc];
        
        $input_managers[] = ['account_id'=> $accnt_id, 'user_id'=> $manager_id, 'manager'=> trim($manager->fname.' '.$manager->lname),
          'spiff'=> $r_spiff, 'residual'=> $request->resid_share['manager'][$user_id_enc],
        ];
      }
    }
    if ($total_spiff >100 || $total_resid > 100)
      return log_redirect('Total Spiff/Residual Share cannot exceed 100%.', [
        'src'=> $log_src, 'manager-id'=> $manager_id, 'total-spiff'=> $total_spiff, 'total-residual'=> $total_resid,
      ]);
      
      
    // validation passed -> reset account x agency/manager table -> update (Query Builder)
    DB::table('commission_account_agency')->whereRaw(" account_id =:accnt_id ", [$accnt_id])->delete();
    DB::table('commission_account_manager')->whereRaw(" account_id =:accnt_id ", [$accnt_id])->delete();
    $accnt_id = DB::table('commission_accounts')
      ->where('id', $accnt_id)
      ->update(['mod_id' => Auth::id(), 'mod_user' => trim(Auth::user()->fname.' '.Auth::user()->lname),]);

    if ($input_agencies)
      DB::table('commission_account_agency')->insert($input_agencies);
    if ($input_managers)
      DB::table('commission_account_manager')->insert($input_managers);
    

    // action SUCCESS: leave a log and redirect to provider.view
    log_write('Commission Account x Commission Share Updated.', ['src'=> $log_src, 'account-id'=> $accnt_id, ]);
    return msg_redirect('Commission Share has been udated.');
  }
  

  /**
  * ******************************************************* overlay (account-new) *******************************************************
  *
  * output JSON for ingenOverlay: update account x commission parties (agency or managers)
  */
  public function overlayCommission(Request $request)
  {
    $log_src = $this->log_src.'@overlayCommission';
    $preapp = $request->get('preapp');

    // check if auth-user has permission-rec for provider (contact)
    if (!$preapp->perm_account_rec)
      return no_access_ajax(['src'=> $log_src, ]);
    
    
    $agencies = DB::select(" SELECT id, name FROM agencies  WHERE active =1  ORDER BY name, id DESC ");
    if (!$agencies)
      return log_ajax_err('There are No Agencies to select from.', ['src'=> $log_src, ]);

    $managers = DB::select(" SELECT id, fname, lname FROM login_users  WHERE active =1 AND access_lv =:lv  ORDER BY fname, lname, id DESC ", [POS_LV_CH_MANAGER]);
    if (!$managers)
      return log_ajax_err('There are No Channel Managers to select from.', ['src'=> $log_src, ]);

    $html_output =  view('master.accounts.form-commission')
      ->with('agencies', $agencies)
      ->with('managers', $managers)
      ->render();
		
		return json_encode(array(
			'success'=>1, 'error'=>0,
			'html'=> $html_output
		));
  }
  /**
  *
  * output JSON for ingenOverlay: update account x provider
  *
  * @param $prov_id: provider ID encoded
  */
  public function overlayProvider(Request $request)
  {
    $log_src = $this->log_src.'@overlayProvider';
    $preapp = $request->get('preapp');

    // check if auth-user has permission-rec for provider (contact)
    if (!$preapp->perm_account_rec)
      return no_access_ajax(['src'=> $log_src, ]);
    
    
    $providers = DB::select(" SELECT id, name FROM providers  WHERE active =1  ORDER BY name, id DESC ");
    if (!$providers)
      return log_ajax_err('There are No service providers to select from.', ['src'=> $log_src, ]);

    $html_output =  view('master.accounts.form-provider')
      ->with('providers', $providers)
      ->render();
		
		return json_encode(array(
			'success'=>1, 'error'=>0,
			'html'=> $html_output
		));
  }
  /**
  * output JSON for ingenOverlay: update account x product
  *
  * @param $prov_id: provider ID encoded
  */
  public function overlayProduct(Request $request)
  {
    $log_src = $this->log_src.'@overlayProduct';
    $preapp = $request->get('preapp');
    $prov_id = dec_id($request->prov_id);

    // check if auth-user has permission-rec for provider (contact)
    if (!$preapp->perm_account_rec)
      return no_access_ajax(['src'=> $log_src, 'provider-id'=> $prov_id]);
      
    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'provider-id'=> $prov_id]);
      

    // get product list (serviced by current provider)
    $products = DB::select(
      " SELECT p.id, p.p_name, p.price, p.rate_spiff, p.rate_residual,  s.name AS svc_name
          FROM provider_products p LEFT JOIN services s ON p.service_id =s.id
          WHERE p.provider_id =:prov_id
          ORDER BY svc_name, name, id DESC
    ", [$prov_id]);

    $html_output =  view('master.accounts.form-prod')
      ->with('products', $products)
      ->render();
		
		return json_encode(array(
			'success'=>1, 'error'=>0,
			'html'=> $html_output
		));
  }
  

  /**
  * ******************************************************* overlay (account-view) *******************************************************
  *
  * output JSON for ingenOverlay: update account x commission parties (agency or managers)
  *
  * @param $accnt_id: commission account ID encoded
  */
  public function overlayCommissionMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayCommissionMod';
    $preapp = $request->get('preapp');
    $accnt_id = dec_id($request->accnt_id);

    // check if auth-user has permission-mod for provider (contact)
    if (!$preapp->perm_account_mod)
      return no_access_ajax(['src'=> $log_src, ]);

    
    $accnt = DB::table('commission_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Commission Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);    
    
    $accnt->agencies = DB::select(
      " SELECT ca.agency_id, ca.spiff, ca.residual, ca.agency,
            a.name AS new_name, a.active,
            IF(a.id >0, 1,0) valid
          FROM commission_account_agency ca LEFT JOIN agencies a ON ca.agency_id =a.id
          WHERE ca.account_id =:accnt_id
          ORDER BY ca.agency, ca.agency_id DESC
    ", [$accnt_id]);
    $accnt->managers = DB::select(
      " SELECT cm.user_id, cm.spiff, cm.residual, cm.manager,
            u.fname, u.lname, u.active,
            IF(u.id >0, 1,0) valid
          FROM commission_account_manager cm LEFT JOIN login_users u ON cm.user_id =u.id
          WHERE cm.account_id =:accnt_id
          ORDER BY cm.manager, user_id DESC
    ", [$accnt_id]);
    
    
    $agencies = DB::select(" SELECT id, name FROM agencies  WHERE active =1  ORDER BY name, id DESC ");
    if (!$agencies)
      return log_ajax_err('There are No Agencies to select from.', ['src'=> $log_src, ]);

    $managers = DB::select(" SELECT id, fname, lname FROM login_users  WHERE active =1 AND access_lv =:lv  ORDER BY fname, lname, id DESC ", [POS_LV_CH_MANAGER]);
    if (!$managers)
      return log_ajax_err('There are No Channel Managers to select from.', ['src'=> $log_src, ]);

    $html_output =  view('master.accounts.form-commission-mod')
      ->with('account', $accnt)
      ->with('agencies', $agencies)
      ->with('managers', $managers)
      ->render();
		
		return json_encode(array(
			'success'=>1, 'error'=>0,
			'html'=> $html_output
		));
  }
  /**
  *
  * output JSON for ingenOverlay: update account x provider
  *
  * @param $accnt_id: commission account ID encoded
  */
  public function overlayProviderMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayProductNew';
    $preapp = $request->get('preapp');
    $accnt_id = dec_id($request->accnt_id);

    // check if auth-user has permission-mod for provider (contact)
    if (!$preapp->perm_account_mod)
      return no_access_ajax(['src'=> $log_src, ]);

    
    $accnt = DB::table('commission_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Commission Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);    
      
    $prov_id = $accnt->provider_id;
    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id, 'provider-id'=> $prov_id]);
    
    $providers = DB::select(" SELECT id, name FROM providers  WHERE active =1  ORDER BY name, id DESC ");
    if (!$providers)
      return log_ajax_err('There are No service providers to select from.', ['src'=> $log_src, ]);


    $html_output = view('master.accounts.form-provider-mod')
      ->with('account', $accnt)
      ->with('providers', $providers)
      ->render();
		
		return json_encode(array(
			'success'=>1, 'error'=>0,
			'html'=> $html_output
		));
  }
  /**
  * output JSON for ingenOverlay: update account x product
  *
  * @param $prov_id: provider ID encoded
  */
  public function overlayProductNew(Request $request)
  {
    $log_src = $this->log_src.'@overlayProductNew';
    $preapp = $request->get('preapp');
    $accnt_id = dec_id($request->accnt_id);

    // check if auth-user has permission-mod for provider (contact)
    if (!$preapp->perm_account_mod)
      return no_access_ajax(['src'=> $log_src, ]);

    
    $accnt = DB::table('commission_accounts')->find($accnt_id);
    if (!$accnt)
      return log_ajax_err('Commission Account Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id]);    
      
    $prov_id = $accnt->provider_id;
    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'account-id'=> $accnt_id, 'provider-id'=> $prov_id]);
    
    $providers = DB::select(" SELECT id, name FROM providers  WHERE active =1  ORDER BY name, id DESC ");
    if (!$providers)
      return log_ajax_err('There are No service providers to select from.', ['src'=> $log_src, ]);


    $html_output = view('master.accounts.form-provider-mod')
      ->with('account', $accnt)
      ->with('providers', $providers)
      ->render();
		
		return json_encode(array(
			'success'=>1, 'error'=>0,
			'html'=> $html_output
		));
  }
}
