<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\User;
use App\Agent;
use App\Provider;
use App\MasterAgent;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataTablesController extends Controller
{
  /**
  * custom variable
  */
  private $log_src = 'DataTablesController';

  /**
    * output JSON for DataTables: user-list
    */
  public function users(Request $request)
  {
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($preapp->agency_id);

    /*
    <th></th>
    <th>Mod</th>
    <th>Email</th>
    <th>Name</th>
    <th>Access LV</th>
    <th>Status</th>
    */
    $highest_access_lv = map_accessible_positions($preapp->lv)[0];
    
    $db_rows = DB::select(
      " SELECT u.id
          FROM login_users u
            LEFT JOIN relation_user_agency ua ON u.id =ua.user_id
          WHERE ua.agency_id =:agency_id AND u.access_lv <=:lv
    ", [$agency_id, $highest_access_lv]);
    $row_count = count($db_rows);
    
    $db_rows_paginated = DB::select(
      " SELECT u.*, r.date_login
          FROM login_users u
            LEFT JOIN rec_user_login r ON u.id =r.user_id
            LEFT JOIN relation_user_agency ua ON u.id =ua.user_id
          WHERE ua.agency_id =:agency_id AND u.access_lv <=:lv
          ORDER BY u.active DESC, u.access_lv DESC, u.email ASC, u.id DESC
            LIMIT :skip,:take
    ", [$agency_id, $highest_access_lv, $request->start, $request->length]);
    
    $row_users = [];
    if (count($db_rows_paginated) >0) {
      foreach ($db_rows_paginated as $r) {
        $r_row_class = 'grayed';
        $r_enc_id = enc_id($r->id);
        
        $no_mod = ($r->id == $me->id || $preapp->lv > POS_LV_AGENT_ADMIN || in_array($r->access_lv, map_editable_positions($preapp->lv)))?  0:1;

        $r_cell_act = 
          Form::open(['url'=> route('user.delete', ['id'=> $r_enc_id]), 'method'=>'DELETE']).'
            <a href="'.route('user.view', ['id'=> $r_enc_id]).'" title="View User Profile"><i class="md s btn-view">visibility</i></a>';
        $r_cell_act .= ($no_mod)?
            '<i class="md s grayed">edit</i>' :
            '<a href="'.route('user.mod', ['id'=> $r_enc_id]).'" title="Update User"><i class="md s btn-edit">edit</i></a>';
        /* no user deletion in agency page
        $r_cell_act .= ($no_mod || $r->id == $me->id)?
            '<i class="md s grayed">close</i>' : '<i class="md s btn-close-item">close</i>';
        */
        $r_cell_act .=
          Form::close();
          
        $r_last_login_txt = (DEFAULT_DATE.' 00:00:00' < $r->date_login)?  convert_mysql_timezone($r->date_login, 'Y-m-d h:i:s A') : 'Never';
        $r_cell_mod = '
          <div class="popup-base">
            '.date('Y-m-d', strtotime($r->updated_at)).'
            <div class="popup-pane popup-help">
              <h6>Mod Data</h6>
              <p>Date Created: '.convert_mysql_timezone($r->created_at, 'Y-m-d h:i:s A').'</p>
              <p>Last Update Date: '.convert_mysql_timezone($r->updated_at, 'Y-m-d h:i:s A').'</p>
              <p>Last Updated by: '.$r->mod_user.'</p>
              <p>Last Login: '.$r_last_login_txt.'</p>
            </div>
          </div>
        ';
        
        if ($r->active >0) {
          $r_active_html = 'Active';
          $r_row_class = '';
        } else
          $r_active_html = '<span class="err">Inactive</span>';
        
        $row_users[] = array(
          $r_cell_act,
          $r_cell_mod,
          '<strong><a href="'.route('user.view', ['id'=> $r_enc_id]).'">'.$r->email.'</a></strong>',
          trim($r->fname.' '.$r->lname),
          config_pos_name($r->access_lv),
          $r_active_html,
          'DT_RowClass' => $r_row_class
        );
      }
    }

		$json_output ='{
      "draw": '.$request->draw.',
      "recordsTotal": '.$row_count.',
      "recordsFiltered": '.$row_count.',
      "data": '.json_encode($row_users).'
		}';
		return $json_output;
  }

  /**
    * output JSON for DataTables: provider list
    */
  public function providers(Request $request)
  {
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    if (!$preapp->perm_prov_view)
      return no_access(['src'=> $log_src, 'agency-id'=> $agency_id, ]);

    /*
    <th>Name</th>
    <th>Address</th>
    <th>Phone</th>
    */
    $row_count = Provider::whereRaw(" active =1 ")->count();
    
    $db_rows_paginated = DB::select(
      " SELECT p.id, p.name, p.city, p.zip, p.tel,  s.code AS state_code
          FROM providers p
            LEFT JOIN states s ON p.state_id =s.id
          ORDER BY p.name ASC, p.id DESC
            LIMIT :skip,:take
    ", [$request->start, $request->length]);
    
    $row_providers = [];
    if (count($db_rows_paginated) >0) {
      foreach ($db_rows_paginated as $row)
        $row_providers[] = [
          '<strong><a href="'.route('provider.view', ['id'=>enc_id($row->id)]).'" title="View Provider Overview">'.$row->name.'</a></strong>',
          format_city_state_zip($row->city, $row->state_code, $row->zip),
          format_tel($row->tel),
        ];
      // END foreach: providers
    }

		$json_output ='{
      "draw": '.$request->draw.',
      "recordsTotal": '.$row_count.',
      "recordsFiltered": '.$row_count.',
      "data": '.json_encode($row_providers).'
		}';
		return $json_output;
  }

  /*
  /**
  * output JSON for DataTables: predefined-service list
  */
  /*
  public function services(Request $request)
  {
    $preapp = $request->get('preapp');

    /*
    <th></th>
    <th>ID</th>
    <th>Name</th>
    */ /*
    $agency_id = dec_id($preapp->agency_id);

    $db_rows = DB::select(" SELECT id FROM services WHERE agent_id =:agent_id", [$agency_id]);
    $row_count = count($db_rows);
    
    $db_rows_paginated = DB::select(
      " SELECT id, name
          FROM services
          WHERE agent_id =:agent_id
          ORDER BY name ASC, id DESC
            LIMIT :skip,:take
    ", [$agency_id, $request->start,$request->length]);

    $row_items = [];
    if (count($db_rows_paginated) >0) {
      foreach ($db_rows_paginated as $row) {
        $r_enc_id = enc_id($row->id);
        
        $r_cell_act = 
          Form::open(['url'=>route('service.delete', ['id'=> $r_enc_id]), 'method'=> 'DELETE']).'
            <span class="fa-pencil btn-mod-item"></span>
            <span title="Delete Service" class="fa-remove btn-del-item"></span>'.
          Form::close();
        
        $row_items[] = [
          $r_cell_act,
          $row->id,
          '<strong>'.$row->name.'</strong>',
        ];
      } // END foreach: $db_rows_paginated
    }

		$json_output ='{
      "draw": '.$request->draw.',
      "recordsTotal": '.$row_count.',
      "recordsFiltered": '.$row_count.',
      "data": '.json_encode($row_items).'
		}';
		return $json_output;
  }
  /**
  * output JSON for DataTables: agent's customers list
  **/
  /*
  public function customers(Request $request)
  {
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $db_rows = DB::select(" SELECT id FROM customers WHERE agent_id =:id ", [$agency_id]);
    $row_count = count($db_rows);
    
    $db_rows_paginated = DB::select(
      " SELECT c.id, c.date_rec, c.date_mod, c.mod_user,
            c.name, c.city, c.zip, c.tel, 
            s.code AS state_code
          FROM customers c
            LEFT JOIN states s ON c.state_id =s.id
          WHERE c.agent_id =:id
          ORDER BY c.name ASC, c.id DESC
            LIMIT :skip,:take
    ", [$agency_id, $request->start,$request->length]);

    $row_items = [];
    if (count($db_rows_paginated) >0) {
      foreach ($db_rows_paginated as $row) {
        $r_enc_id = enc_id($row->id);

        $r_cell_act = 
          Form::open(['url'=> route('customer.delete', ['id'=> $r_enc_id]), 'method'=>'DELETE']).'
            <a href="'.route('customer.view', ['id'=> $r_enc_id]).'" title="Overview"><i class="md s btn-view">visibility</i></a>
            <a href="'.route('customer.mod', ['id'=> $r_enc_id]).'" title="Update Customer"><i class="md s btn-edit">edit</i></a>
            <i title="Delete Customer" class="md s btn-close-item">close</i>
          '.Form::close();
          
        $r_cell_mod = '
          <div class="popup-base">
            '.date('Y-m-d', strtotime($row->date_mod)).'
            <div class="popup-pane popup-help">
              <h6>Mod Data</h6>
              <p>Date Created: '.convert_mysql_timezone($row->date_rec, 'Y-m-d h:i:s A').'</p>
              <p>Last Update Date: '.convert_mysql_timezone($row->date_mod, 'Y-m-d h:i:s A').'</p>
              <p>Last Updated by: '.$row->mod_user.'</p>
            </div>
          </div>
        ';

        $r_cell_addr = $row->city;
        $r_cell_addr .= ($r_cell_addr && $row->state_code)?  ', '.$row->state_code : $row->state_code;
        /*
        <th></th>
        <th>Mod</th>
        <th>Name</th>
        <th>Address</th>
        <th>Phone</th>
        */ /*
        $row_items[] = [
          $r_cell_act,
          $r_cell_mod,
          '<strong><a href="'.route('customer.view', ['id'=> $r_enc_id]).'">'.$row->name.'</a></strong>',
          trim($r_cell_addr.' '.$row->zip),
          format_tel($row->tel),
        ];
      } // END foreach: $db_rows_paginated
    }

		$json_output ='{
      "draw": '.$request->draw.',
      "recordsTotal": '.$row_count.',
      "recordsFiltered": '.$row_count.',
      "data": '.json_encode($row_items).'
		}';
		return $json_output;
  }

  /**
  * output JSON for DataTables: agent's salesperson list
  */
  /*
  public function salesperson(Request $request)
  {
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $db_rows = DB::select(" SELECT id FROM sales_contacts WHERE agent_id =:id ", [$agency_id]);
    $row_count = count($db_rows);
    
    $db_rows_paginated = DB::table('sales_contacts')->whereRaw(" agent_id =:id ", [$agency_id])->skip($request->start)->take($request->length)->get();
    
    $row_items = [];
    if (count($db_rows_paginated) >0) {
      foreach ($db_rows_paginated as $row) {
        $r_enc_id = enc_id($row->id);

        $r_cell_act = 
          Form::open(['url'=> route('salesperson.delete', ['id'=> $r_enc_id]), 'method'=>'DELETE']).'
            <i title="Update Information" class="md s btn-mod-item">edit</i>
            <i title="Delete Customer" class="md s btn-close-item">close</i>
          '.Form::close();
        /*
        <th></th>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        */ /*
        $row_items[] = [
          $r_cell_act,
          '<strong>'.trim($row->fname.' '.$row->lname).'</strong>',
          format_tel($row->tel),
          $row->email,
        ];
      } // END foreach: $db_rows_paginated
    }

		$json_output ='{
      "draw": '.$request->draw.',
      "recordsTotal": '.$row_count.',
      "recordsFiltered": '.$row_count.',
      "data": '.json_encode($row_items).'
		}';
		return $json_output;
  }
  */

  /**
    * output JSON for DataTables: leads list
    */
  public function leads(Request $request)
  {
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    $db_rows = DB::select(
      " SELECT l.id
          FROM lead_relation_agency la LEFT JOIN leads l ON la.lead_id =l.id
          WHERE la.agency_id =:id AND l.id >0
            GROUP BY l.id
    ", [$agency_id]);
    $row_count = count($db_rows);
    
    $db_rows_paginated = DB::select(
      " SELECT l.id, l.quote_requested, l.cust_name, l.tel, l.email,
            IF(lc.id >0 OR lq.id >0, 1,0) is_project
          FROM lead_relation_agency la
            LEFT JOIN leads l ON la.lead_id =l.id
              LEFT JOIN lead_locations ll ON l.id = ll.lead_id
                LEFT JOIN lead_current_accounts lc ON ll.id =lc.location_id AND lc.is_project >0
                LEFT JOIN lead_quotes lq ON ll.id =lq.location_id AND lq.is_project >0
          WHERE la.agency_id =:id AND l.id >0
            GROUP BY l.id
          ORDER BY l.quote_requested DESC, l.id DESC
            LIMIT :skip, :take
    ", [$agency_id, $request->start, $request->length]);

    $row_items = [];
    if (count($db_rows_paginated) >0) {
      foreach ($db_rows_paginated as $row) {
        $r_enc_id = enc_id($row->id);
        
        if ($row->is_project)
          $r_status = 'Project Management';
        elseif ($row->quote_requested)
          $r_status = 'Quote Requested';
        else
          $r_status = 'Open';

        $r_cell_act = '<a href="'.route('lead.manage',['id'=> $r_enc_id]).'"><i title="Manage Lead" class="md s btn-mod-item">edit</i></a>';
        if ($row->is_project)
          $r_cell_act .= '<a href="'.route('project.manage',['id'=> $r_enc_id]).'"><i title="Project Management" class="md s btn-mod-item">assignment_turned_in</i></a>';
        /*
        <th></th>
        <th>Status</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Address</th>
        */
        $row_items[] = [
          $r_cell_act,
          $r_status,
          '<strong><a href="'.route('lead.manage',['id'=> $r_enc_id]).'">'.$row->cust_name.'</a></strong>',
          format_tel($row->tel),
          $row->email,
        ];
      } // END foreach: $db_rows_paginated
    }

		$json_output ='{
      "draw": '.$request->draw.',
      "recordsTotal": '.$row_count.',
      "recordsFiltered": '.$row_count.',
      "data": '.json_encode($row_items).'
		}';
		return $json_output;
  }
}
