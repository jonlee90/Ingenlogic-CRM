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

class LeadReportController extends LeadController
{
  /*
   * custom variable
   */
  private $log_src = 'LeadReportController';
  
  /**
  * View: lead report page (current account summary)
  *
  * @param enc_id: lead ID encoded
  * @return \Illuminate\Http\Response
  */
  public function current (Request $request)
  {
    $log_src = $this->log_src.'@current';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->id);
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);


    $row_locations = [];
    $db_rows = DB::select(
      " SELECT l.id, l.name, l.addr, l.addr2, l.city, l.zip,  s.code AS state_code
          FROM lead_locations l LEFT JOIN states s ON l.state_id = s.id
          WHERE l.lead_id =:lead_id
          ORDER BY l.id
    ", [$lead_id]);
    if ($db_rows) {
      foreach ($db_rows as $row) {
        $r_curr = [];
        $accnts_tmp = DB::select(
          " SELECT id, provider_name AS name, term
              FROM lead_current_accounts
              WHERE location_id =:loc_id
              ORDER BY id
        ", [$row->id]);
        if ($accnts_tmp) {
          foreach ($accnts_tmp as $accnt) {
            $accnt->products = DB::select(" SELECT svc_name, prod_name, memo, price, qty  FROM lead_current_products  WHERE account_id =:accnt_id   ORDER BY order_no ", [
              $accnt->id
            ]);
            $r_curr[] = $accnt;
          }
        }
        
        $r_addr = $row->addr;
        $r_addr .= ($r_addr && $row->addr2)?  ', '.$row->addr2 : $row->addr2;
        $r_addr .= ($r_addr && $row->city)?  ', '.$row->city : $row->city;
        $r_addr .= ($r_addr && $row->state_code)?  ', '.$row->state_code : $row->state_code;
        $r_addr .= ($r_addr && $row->zip)?  ' '.$row->zip : $row->zip;

        $row->curr_accounts = $r_curr;
        $row_locations[] = $row;

        // $row_locations[] = (object)['id'=> $row->id, 'name'=> $row->name, 'addr'=> $r_addr, 'curr_accounts'=> $r_curr, 'quotes'=> $r_quotes];
      }
    }
    
    $data = (object)[
      'locations'=> $row_locations,
    ];
    return view('leads.rpt-current')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('lead', $lead);
  }
  /**
  * View: lead report page (quote summary)
  *
  * @param enc_id: lead ID encoded
  * @return \Illuminate\Http\Response
  */
  public function quote (Request $request)
  {
    $log_src = $this->log_src.'@quote';

    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $lead_id = dec_id($request->id);
    $lead = $this->getLead($lead_id, $agency_id);
    if (!$lead)
      return log_redirect('Lead Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'lead-id'=> $lead_id]);


    $row_locations = [];
    $db_rows = DB::select(
      " SELECT l.id, l.name, l.addr, l.addr2, l.city, l.zip,  s.code AS state_code
          FROM lead_locations l LEFT JOIN states s ON l.state_id = s.id
          WHERE l.lead_id =:lead_id
          ORDER BY l.id
    ", [$lead_id]);
    if ($db_rows) {
      foreach ($db_rows as $loc) {
        $r_quotes = [];

        $accnts_tmp = DB::select(
          " SELECT id, provider_name AS name, term, 1 AS is_curr
              FROM lead_current_accounts
              WHERE location_id =:loc_id AND is_selected >0
              ORDER BY id
        ", [$loc->id]);
        if ($accnts_tmp) {
          foreach ($accnts_tmp as $accnt) {
            $accnt->prods = DB::select(
              " SELECT svc_name, prod_name, memo, price, qty, 1 AS is_mrc
                  FROM lead_current_products
                  WHERE account_id =:accnt_id
                  ORDER BY order_no 
            ", [ $accnt->id, ]);
            $r_quotes[] = $accnt;
          }
        }
        $quotes_tmp = DB::select(
          " SELECT q.id, q.provider_id, q.term, q.date_contract_end AS date_end,
                p.name AS name,
                0 AS is_curr
              FROM lead_quotes q LEFT JOIN providers p ON q.provider_id =p.id
              WHERE q.location_id =:loc_id AND is_selected >0
              ORDER BY q.id
        ", [$loc->id]);

        if ($quotes_tmp) {
          foreach ($quotes_tmp as $quote) {
            // MRC products
            $quote_mrc_prods = DB::select(
              " SELECT p.memo, p.price, p.qty,
                    IF(pp.id >0 AND s.id >0 AND pp.provider_id =:prov_id ,1,0) AS valid,
                    IF(pp.id >0, pp.p_name, p.prod_name) AS prod_name,
                    IF(s.id >0, s.name, p.svc_name) AS svc_name,
                    1 AS is_mrc
                  FROM lead_quote_mrc_products p
                    LEFT JOIN provider_products pp ON p.product_id =pp.id
                    LEFT JOIN services s ON pp.service_id =s.id
                  WHERE p.quote_id =:quote_id
                  ORDER BY valid DESC, p.order_no
            ", [$quote->provider_id, $quote->id]);
            // NRC products
            $quote_nrc_prods = DB::select(
              " SELECT p.memo, p.price, p.qty,
                    IF(pp.id >0 AND s.id >0 AND pp.provider_id =:prov_id ,1,0) AS valid,
                    IF(pp.id >0, pp.p_name, p.prod_name) AS prod_name,
                    IF(s.id >0, s.name, p.svc_name) AS svc_name,
                    0 AS is_mrc
                  FROM lead_quote_nrc_products p
                    LEFT JOIN provider_products pp ON p.product_id =pp.id
                    LEFT JOIN services s ON pp.service_id =s.id
                  WHERE p.quote_id =:quote_id
                  ORDER BY valid DESC, p.order_no
            ", [$quote->provider_id, $quote->id]);

            $quote->prods = [];
            if (count($quote_mrc_prods) >0) {
              foreach ($quote_mrc_prods as $prod)
                $quote->prods[] = $prod;
            }
            if (count($quote_nrc_prods) >0) {
              foreach ($quote_nrc_prods as $prod)
                $quote->prods[] = $prod;
            }
            $r_quotes[] = $quote;
          }
        }
        $r_addr = $loc->addr;
        $r_addr .= ($r_addr && $loc->addr2)?  ', '.$loc->addr2 : $loc->addr2;
        $r_addr .= ($r_addr && $loc->city)?  ', '.$loc->city : $loc->city;
        $r_addr .= ($r_addr && $loc->state_code)?  ', '.$loc->state_code : $loc->state_code;
        $r_addr .= ($r_addr && $loc->zip)?  ' '.$loc->zip : $loc->zip;

        $loc->quotes = $r_quotes;
        $row_locations[] = $loc;
      }
    }
    
    $data = (object)[
      'locations'=> $row_locations,
    ];
    return view('leads.rpt-quote')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('lead', $lead);
  }
}
