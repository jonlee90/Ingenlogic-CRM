<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use App\User;
use App\Agency;
use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterDataTablesController extends Controller
{
  /**
  * custom variable
  */
  private $log_src = 'Master\MasterDataTablesController';

  /**
  * output JSON for DataTables: user-list
  */
  public function users(Request $request)
  {
    $preapp = $request->get('preapp');
    $me = Auth::user();

    /*
    <th></th>
    <th>Mod</th>
    <th>Email</th>
    <th>Agency</th>
    <th>Name</th>
    <th>Access LV</th>
    <th>Status</th>
    */
    $highest_accessible_lv = map_accessible_positions($me->access_lv)[0];

    if ($me->access_lv >= POS_LV_MASTER_USER) {
      // if auth-user is master: get all users
      $row_count = User::whereRaw(' access_lv <= ? ',[$highest_accessible_lv])->count();      
      $db_rows_paginated = DB::select(
        " SELECT u.*, r.date_login, a.name AS agency, a.active AS agency_active,
              IF(:master_user_lv <= u.access_lv, 1,0) AS is_master,
              IF(:ch_user_lv <= u.access_lv, 1,0) AS is_channel
            FROM login_users u
              LEFT JOIN rec_user_login r ON u.id =r.user_id
              LEFT JOIN relation_user_agency ua ON u.id =ua.user_id AND (:agent_user_lv <= u.access_lv OR u.access_lv <= :agent_admin_lv)
                LEFT JOIN agencies a ON ua.agency_id =a.id
            WHERE access_lv <= :accessible_lv
              GROUP BY u.id
            ORDER BY u.active DESC, is_master DESC, is_channel DESC, a.active DESC, agency, a.id DESC, u.access_lv DESC, u.email, u.id DESC
              LIMIT :skip, :take
      ", [POS_LV_MASTER_USER, POS_LV_CH_USER, POS_LV_AGENT_USER, POS_LV_AGENT_ADMIN, $highest_accessible_lv, $request->start, $request->length, ]);

    } else {
      // if auth-user is channel user/manager: get manager of the channel, assistants/users, and agents of the agencies associated with the manager
      if ($me->access_lv == POS_LV_CH_MANAGER)
        $manager_id = $me->id;
      else {
        $rec = DB::table('relation_assistant_manager')->whereRaw(" assistant_id =:auth_id ", [$me->id])->first();
        $manager_id = $rec->user_id;
      }
      $query = "
        FROM relation_agency_manager am1
          LEFT JOIN relation_assistant_manager am2 ON am1.user_id =am2.user_id
          LEFT JOIN relation_user_agency ua ON am1.agency_id =ua.agency_id
            LEFT JOIN login_users u ON am1.user_id =u.id OR am2.assistant_id =u.id OR ua.user_id =u.id
              LEFT JOIN rec_user_login r ON u.id =r.user_id
            LEFT JOIN agencies a ON ua.agency_id =a.id
        WHERE am1.user_id =:manager_id AND u.access_lv <= :high_lv
          GROUP BY u.id
      ";
      $db_rows = DB::select(" SELECT u.id $query ", [$manager_id, $highest_accessible_lv]);
      $row_count = count($db_rows);
      
      $db_rows_paginated = DB::select(
        " SELECT u.*, r.date_login, a.name AS agency, a.active AS agency_active,
              0 AS is_master,
              IF(:ch_user_lv <= u.access_lv, 1,0) AS is_channel
            $query
            ORDER BY u.active DESC, is_master DESC, is_channel DESC, a.active DESC, agency, a.id DESC, u.access_lv DESC, u.email, u.id DESC
              LIMIT :skip, :take
      ", [POS_LV_CH_USER, $manager_id, $highest_accessible_lv, $request->start, $request->length, ]);
    }
    
    $row_users = [];
    if (count($db_rows_paginated) >0) {
      foreach ($db_rows_paginated as $r) {
        $r_row_class = 'grayed';
        $r_enc_id = enc_id($r->id);
        
        $no_mod = ($r->id == $me->id || $me->access_lv == POS_LV_SYS_ADMIN || in_array($r->access_lv, map_editable_positions($me->access_lv)) )?  0:1;
        $r_cell_act =
          Form::open(['url'=> route('master.user.delete', ['id'=> $r_enc_id]), 'method'=>'DELETE']).'
            <a href="'.route('master.user.view', ['id'=> $r_enc_id]).'" title="Overview"><i class="md s btn-view">visibility</i></a>';
        $r_cell_act .= ($no_mod)?
            '<i class="md s grayed">edit</i>' :
            '<a href="'.route('master.user.mod', ['id'=> $r_enc_id]).'" title="Update User"><i class="md s btn-edit">edit</i></a>';
        $r_cell_act .= ($no_mod || $r->id == $me->id || $me->access_lv < POS_LV_MASTER_MANAGER)?
           '<i class="md s grayed">close</i>' : '<i class="md s btn-del-item">close</i>';
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
              <p>Last Updated by: '.$r->mod_user.' (ID: '.$r->mod_id.')</p>
              <p>Last Login: '.$r_last_login_txt.'</p>
            </div>
          </div>
        ';
        
        $r_cell_active = ($r->active >0)?  'Active' : '<span class="err">Inactive</span>';
        if ($r->is_master) {
          $r_cell_agency = 'Master Agent';
          if ($r->active)
            $r_row_class = '';
        } elseif ($r->is_channel) {
          $r_cell_agency = 'Channel';
          $r_row_class = '';
        } else {
          if ($r->agency)
            $r_cell_agency = ($r->agency_active)?  $r->agency : $r->agency.' (Inactive)';
          else
            $r_cell_agency = 'Unassigned';
          if ($r->agency_active && $r->active)
            $r_row_class = '';
        }
        
        $row_users[] = array(
          $r_cell_act,
          $r_cell_mod,
          '<strong><a href="'.route('master.user.view', ['id'=>enc_id($r->id)]).'">'.$r->email.'</a></strong>',
          $r_cell_agency,
          trim($r->fname.' '.$r->lname),
          config_pos_name($r->access_lv),
          $r_cell_active,
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

    /*
    <th></th>
    <th>Mod</th>
    <th>Name</th>
    <th>Address</th>
    <th>Phone</th>
    <th>Default Term</th>
    <th>Default Spiff</th>
    <th>Default Residual</th>
    <th>Status</th>
    */
    $row_count = Provider::whereRaw('1')->count();
    
    $db_rows_paginated = DB::select(
      " SELECT p.*, s.code AS state_code
          FROM providers p
            LEFT JOIN states s ON p.state_id =s.id
          ORDER BY p.active DESC, p.name ASC, p.id DESC
            LIMIT :skip,:take
    ", [$request->start,$request->length]);

    $row_providers = [];
    if (count($db_rows_paginated) >0) {
      foreach ($db_rows_paginated as $row) {
        $r_row_class = 'grayed';
        $r_enc_id = enc_id($row->id);
        $r_term = ($row->default_term >1)?  $row->default_term.' month' : 'M2M';
        
        $r_cell_act = 
          Form::open(['url'=> route('master.provider.delete', ['id'=> $r_enc_id]), 'method'=>'DELETE']).'
            <a href="'.route('master.provider.view', ['id'=> $r_enc_id]).'" title="Overview"><i class="md s btn-view">visibility</i></a> ';
        $r_cell_act .= ($preapp->perm_agency_mod)?
            '<a href="'.route('master.provider.mod', ['id'=> $r_enc_id]).'" title="Update Provider"><i class="md s btn-edit">edit</i></a>' :
            '<i class="md s grayed">edit</i>';
        $r_cell_act .= ($preapp->perm_agency_del)?
            '<i class="md s btn-del-item">close</i>' : '<i class="md s grayed">close</i>';
        $r_cell_act .=
          Form::close();

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

        $city_state = $row->city;
        $city_state .= ($city_state && $row->state_code)?  ', '.$row->state_code : $row->state_code;
        
        if ($row->active >0) {
          $r_active_html = 'Active';
          $r_row_class = '';
        } else
          $r_active_html = '<span class="err">Inactive</span>';
        
        $row_providers[] = [
          $r_cell_act,
          $r_cell_mod,
          '<strong><a href="'.route('master.provider.view', ['id'=>$r_enc_id]).'">'.$row->name.'</a></strong>',
          trim($city_state.' '.$row->zip),
          format_tel($row->tel),
          $r_term,
          $row->default_spiff.' %',
          $row->default_residual.' %',
          $r_active_html,
          'DT_RowClass' => $r_row_class,
        ];
      } // END foreach: $db_rows_paginated
    }

		$json_output ='{
      "draw": '.$request->draw.',
      "recordsTotal": '.$row_count.',
      "recordsFiltered": '.$row_count.',
      "data": '.json_encode($row_providers).'
		}';
		return $json_output;
  }

  /**
    * output JSON for DataTables: agency-list
    */
  public function agencies(Request $request)
  {
    $preapp = $request->get('preapp');
    $me = Auth::user();

    /*
    <th></th>
    <th>Mod</th>
    <th>Name</th>
    <th>Manager</th>
    <th>Address</th>
    <th>Phone</th>
    <th>Spiff</th>
    <th>Residual</th>
    <th>Status</th>    
    */
    $manager_id = 0;
    if ($me->access_lv == POS_LV_CH_MANAGER)
      $manager_id = $me->id;
    elseif ($me->access_lv == POS_LV_CH_USER) {
      $rec = DB::table('relation_assistant_manager')->whereRaw(" assistant_id =:auth_id ", [$me->id])->first();
      $manager_id = $rec->user_id;
    }

    $db_rows = DB::select(
      " SELECT a.id
          FROM agencies a
            LEFT JOIN relation_agency_manager am ON a.id =am.agency_id
          WHERE :auth_lv >= :master_user_lv OR am.user_id =:manager_id
    ", [$me->access_lv, POS_LV_MASTER_USER, $manager_id]);
    $row_count = count($db_rows);
    
    $db_rows_paginated = DB::select(
      " SELECT a.*,  s.code AS state_code,  u.fname, u.lname
          FROM agencies a
            LEFT JOIN relation_agency_manager am ON a.id =am.agency_id
              LEFT JOIN login_users u ON am.user_id =u.id
            LEFT JOIN states s ON a.state_id =s.id
          WHERE :auth_lv >= :master_user_lv OR am.user_id =:manager_id
          ORDER BY a.active DESC, a.name ASC
            LIMIT :skip,:take
    ", [$me->access_lv, POS_LV_MASTER_USER, $manager_id, $request->start,$request->length]);

    $row_agencies = [];
    if (count($db_rows_paginated) >0) {
      foreach ($db_rows_paginated as $row) {
        $r_row_class = 'grayed';
        $r_enc_id = enc_id($row->id);
        
        $r_cell_act = 
          Form::open(['url'=> route('master.agency.delete', ['id'=> $r_enc_id]), 'method'=>'DELETE']).'
            <a href="'.route('master.agency.view', ['id'=> $r_enc_id]).'" title="Overview"><i class="md s btn-view">visibility</i></a> ';
        $r_cell_act .= ($preapp->perm_agency_mod)?
            '<a href="'.route('master.agency.mod', ['id'=> $r_enc_id]).'" title="Update Agency"><i class="md s btn-edit">edit</i></a>' :
            '<i class="md s grayed">edit</i>';
        $r_cell_act .= ($preapp->perm_agency_del)?
            '<i class="md s btn-del-item">close</i>' : '<i class="md s grayed">close</i>';
        $r_cell_act .=
          Form::close();
          
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

        $r_manager = trim($row->fname.' '.$row->lname);
        if (!$r_manager)
          $r_manager = '(Unassigned)';
        
        if ($row->active >0) {
          $r_active_html = 'Active';
          $r_row_class = '';
        } else
          $r_active_html = '<span class="err">Inactive</span>';

        $city_state = $row->city;
        $city_state .= ($city_state && $row->state_code)?  ', '.$row->state_code : $row->state_code;
        
        $row_agencies[] = [
          $r_cell_act,
          $r_cell_mod,
          '<strong><a href="'.route('master.agency.view', ['id'=> $r_enc_id]).'">'.$row->name.'</a></strong>',
          $r_manager,
          trim($city_state.' '.$row->zip),
          format_tel($row->tel),
          number_format($row->spiff, 2).' %',
          number_format($row->residual, 2).' %',
          $r_active_html,
          'DT_RowClass' => $r_row_class,
        ];
      }
    }

		$json_output ='{
      "draw": '.$request->draw.',
      "recordsTotal": '.$row_count.',
      "recordsFiltered": '.$row_count.',
      "data": '.json_encode($row_agencies).'
		}';
		return $json_output;
  }

  /**
    * output JSON for DataTables: predefined-service list
    */
  public function services(Request $request)
  {
    $preapp = $request->get('preapp');

    /*
    <th></th>
    <th>Parent</th>
    <th>Name</th>
    <th># Products</th>
    */
    $row_count = DB::table('services')->count();
    
    $db_rows_paginated = DB::select(
      " SELECT s.id, s.parent_id, s.name,  p.name AS parent,
            IF(s.id = p.id, 1,0) is_parent,
            SUM(IF(p1.id >0, 1,0)) n_prod,
            COUNT(DISTINCT s1.id) n_child
          FROM services s
            LEFT JOIN services p ON s.parent_id =p.id
            LEFT JOIN services s1 ON (s.id =s.parent_id AND s1.id <> s1.parent_id AND s.id =s1.parent_id) OR (s.id <> s.parent_id AND s.id =s1.id)
              LEFT JOIN provider_products p1 ON s1.id =p1.service_id
          GROUP BY s.id
          ORDER BY p.name, is_parent DESC, s.name, s.id DESC
            LIMIT :skip,:take
    ", [$request->start, $request->length]);

    $row_items = [];
    if (count($db_rows_paginated) >0) {
      foreach ($db_rows_paginated as $row) {
        $r_enc_id = enc_id($row->id);
        $r_row_class = ($row->is_parent)?  'parent' : '';
        if ($preapp->perm_svc_del) {
          $r_del_class = 'btn-del-item';
          $r_del_tip = 'Delete Service';
          if ($row->n_prod >0 || ($row->is_parent && $row->n_child >0)) {
            $r_del_class = 'grayed';
            $r_del_tip = '<p>Services with Child Services</p><p>or Products associated</p><p>cannot be deleted</p>';
          }
        }
        $r_cell_act = 
          Form::open(['url'=>route('master.service.delete', ['id'=> $r_enc_id]), 'method'=> 'DELETE']);
        $r_cell_act .= ($preapp->perm_svc_mod)?
          '<span class="popup-base">
              <i class="md s btn-mod-item">edit</i>
              <div class="popup-tip"><div>Update Service</div></div>
            </span>' : '<i class="md s grayed">edit</i>';
        $r_cell_act .= ($preapp->perm_svc_del)?
            '<span class="popup-base">
              <i class="md s '.$r_del_class.'">close</i>
              <div class="popup-tip"><div>'.$r_del_tip.'</div></div>
            </span>' : '<i class="md s grayed">close</i>';
        $r_cell_act .=
          Form::close();
        
        $row_items[] = [
          $r_cell_act,
          $row->parent,
          $row->name,
          $row->n_prod,
          'DT_RowClass' => $r_row_class,
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
  * output JSON for DataTables: leads list
  **/
  public function leads(Request $request)
  {
    $preapp = $request->get('preapp');
    $me = Auth::user();

    if (POS_LV_CH_USER <= $me->access_lv && $me->access_lv <= POS_LV_CH_MANAGER) {
      $manager_id = $preapp->manager_id;
      $query_part =
        " FROM leads l
            LEFT JOIN lead_relation_manager lm ON l.id =lm.lead_id
              LEFT JOIN login_users u1 ON lm.user_id =u1.id
            LEFT JOIN lead_relation_agency la ON l.id =la.lead_id
              LEFT JOIN agencies a ON la.agency_id =a.id
              LEFT JOIN relation_agency_manager am ON la.agency_id =am.agency_id
                LEFT JOIN login_users u2 ON am.user_id =u2.id
            LEFT JOIN lead_locations ll ON l.id = ll.lead_id
              LEFT JOIN lead_current_accounts lc ON ll.id =lc.location_id AND lc.is_project >0
              LEFT JOIN lead_current_accounts lq ON ll.id =lq.location_id AND lq.is_project >0
          WHERE u1.id =:manager_id1 OR (a.id >0 AND u2.id =:manager_id2)
            GROUP BY l.id ";

      $db_rows = DB::select(" SELECT l.id  $query_part ", [$manager_id, $manager_id]);
      $row_count = count($db_rows);
      
      $db_rows_paginated = DB::select(
        " SELECT l.id, l.cust_name, l.tel, l.email, l.quote_requested,  a.name AS agency,
              SUM(IF(a.id >0, 1,0)) n_agency,
              IF(lc.id >0 OR lq.id >0, 1,0) is_project
            $query_part
            ORDER BY l.quote_requested DESC, l.id DESC
              LIMIT :skip, :take
      ", [$manager_id, $manager_id, $request->start, $request->length, ]);

    } else {
      // master-agents have access to any leads
      $row_count = DB::table('leads')->count();

      $db_rows_paginated = DB::select(
        " SELECT l.id, l.cust_name, l.tel, l.email, l.quote_requested,
              a.name AS agency, SUM(IF(a.id >0, 1,0)) n_agency,
              IF(lc.id >0 OR lq.id >0, 1,0) is_project
            FROM leads l
              LEFT JOIN lead_relation_agency la ON l.id =la.lead_id
                LEFT JOIN agencies a ON la.agency_id =a.id
              LEFT JOIN lead_locations ll ON l.id = ll.lead_id
                LEFT JOIN lead_current_accounts lc ON ll.id =lc.location_id AND lc.is_project >0
                LEFT JOIN lead_quotes lq ON ll.id =lq.location_id AND lq.is_project >0
            GROUP BY l.id
            ORDER BY is_project DESC, l.quote_requested DESC, l.id DESC
              LIMIT :skip, :take
      ", [$request->start, $request->length, ]);
    }
    
    $row_items = [];
    if ($db_rows_paginated) {
      foreach ($db_rows_paginated as $row) {
        $r_enc_id = enc_id($row->id);
        
        if ($row->is_project)
          $r_status = 'Project Management';
        elseif ($row->quote_requested)
          $r_status = 'Quote Requested';
        else
          $r_status = 'Open';

        if ($row->n_agency >1)
          $r_agency = '(Multiple Agencies)';
        elseif ($row->n_agency >0 && $row->agency)
          $r_agency = $row->agency;
        else
          $r_agency = '(Not Assigned)';

        $r_cell_act = '<a href="'.route('master.lead.manage',['id'=> $r_enc_id]).'"><i title="Lead Management" class="md s btn-mod-item">edit</i></a>';
        if ($row->is_project)
          $r_cell_act .= '<a href="'.route('master.project.manage',['id'=> $r_enc_id]).'"><i title="Project Management" class="md s btn-mod-item">assignment_turned_in</i></a>';
        /*
        <th></th>
        <th>Agency</th>
        <th>Status</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Address</th>
        */
        $row_items[] = [
          $r_cell_act,
          $r_status,
          $r_agency,
          '<strong><a href="'.route('master.lead.manage',['id'=> $r_enc_id]).'">'.$row->cust_name.'</a></strong>',
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

  /**
  * output JSON for DataTables: list of accounts (signed) in project-management ()
  */
  public function projectsSigned (Request $request)
  {
    $preapp = $request->get('preapp');
    $me = Auth::user();

    $query_select =
    " SELECT q.*, ll.addr, l.id AS lead_id, l.cust_name, l.city, s.code AS state_code, l.zip, l.tel,  a.name AS agency,
        SUM(IF(a.id >0, 1,0)) n_agency ";

    if (POS_LV_CH_USER <= $me->access_lv && $me->access_lv <= POS_LV_CH_MANAGER) {
      $manager_id = $preapp->manager_id;
      $query_part =
        " FROM lead_quotes q
            LEFT JOIN lead_locations ll ON q.location_id =ll.id
              LEFT JOIN leads l ON ll.lead_id =l.id
                LEFT JOIN lead_relation_manager lm ON l.id =lm.lead_id
                  LEFT JOIN login_users u1 ON lm.user_id =u1.id
                LEFT JOIN lead_relation_agency la ON l.id =la.lead_id
                  LEFT JOIN agencies a ON la.agency_id =a.id
                  LEFT JOIN relation_agency_manager am ON la.agency_id =am.agency_id
                    LEFT JOIN login_users u2 ON am.user_id =u2.id
                LEFT JOIN states s ON l.state_id =s.id
          WHERE q.is_project >0 AND (u1.id =:manager_id1 OR (a.id >0 AND u2.id =:manager_id2))
            GROUP BY q.id ";

      $db_rows = DB::select(" SELECT q.id  $query_part ", [$manager_id, $manager_id]);
      $row_count = count($db_rows);
      
      $db_rows_paginated = DB::select(
        " $query_select
            $query_part
            ORDER BY q.is_complete DESC, ll.name, l.id DESC, q.id DESC
              LIMIT :skip, :take
      ", [$manager_id, $manager_id, $request->start, $request->length, ]);

    } else {
      // master-agents have access to any accounts
      $row_count = DB::table('lead_quotes')->where('is_project', DB::raw(1))->count();

      $db_rows_paginated = DB::select(
        " $query_select
            FROM lead_quotes q
              LEFT JOIN lead_locations ll ON q.location_id =ll.id
                LEFT JOIN leads l ON ll.lead_id =l.id
                  LEFT JOIN lead_relation_agency la ON l.id =la.lead_id
                    LEFT JOIN agencies a ON la.agency_id =a.id
                  LEFT JOIN states s ON l.state_id =s.id
            WHERE q.is_project >0
              GROUP BY q.id
            ORDER BY q.is_complete DESC, ll.name, l.id DESC, q.id DESC
              LIMIT :skip, :take
      ", [$request->start, $request->length, ]);
    }
    
    $row_items = [];
    if ($db_rows_paginated) {
      foreach ($db_rows_paginated as $row) {
        $r_enc_id = enc_id($row->id);
        
        if ($row->is_complete)
          $r_status = 'Project Completed';
        else
          $r_status = 'In Progress';

        if ($row->n_agency >1)
          $r_agency = '(Multiple Agencies)';
        elseif ($row->n_agency >0 && $row->agency)
          $r_agency = $row->agency;
        else
          $r_agency = '(Not Assigned)';

        $r_cell_act = '<a href="'.route('master.project.manage',['id'=> $r_enc_id]).'"><i title="Project Management" class="md s btn-mod-item">assignment_turned_in</i></a>';
        /*
        <th></th>
        <th>Agency</th>
        <th>Status</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Address</th>
        */
        $row_items[] = [
          $r_cell_act,
          $r_status,
          $r_agency,
          '<strong><a href="'.route('master.project.manage',['id'=> $r_enc_id]).'">'.$row->cust_name.'</a></strong>',
          format_tel($row->tel),
          format_city_state_zip($row->city, $row->state_code, $row->zip),
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
  * output JSON for DataTables: list of accounts (kept, updated) in project-management ()
  */
  public function projectsKeep (Request $request)
  {
    $preapp = $request->get('preapp');
    $me = Auth::user();

    $query_select =
      " SELECT ca.*, ll.addr, l.id AS lead_id, l.cust_name, l.city, s.code AS state_code, l.zip, l.tel,  a.name AS agency,
          SUM(IF(a.id >0, 1,0)) n_agency ";

    if (POS_LV_CH_USER <= $me->access_lv && $me->access_lv <= POS_LV_CH_MANAGER) {
      $manager_id = $preapp->manager_id;
      $query_part =
        " FROM lead_current_accounts ca
            LEFT JOIN lead_locations ll ON ca.location_id =ll.id
              LEFT JOIN leads l ON ll.lead_id =l.id
                LEFT JOIN lead_relation_manager lm ON l.id =lm.lead_id
                  LEFT JOIN login_users u1 ON lm.user_id =u1.id
                LEFT JOIN lead_relation_agency la ON l.id =la.lead_id
                  LEFT JOIN agencies a ON la.agency_id =a.id
                  LEFT JOIN relation_agency_manager am ON la.agency_id =am.agency_id
                    LEFT JOIN login_users u2 ON am.user_id =u2.id
                LEFT JOIN states s ON l.state_id =s.id
          WHERE ca.is_project >0 AND ca.is_selected >0 AND (u1.id =:manager_id1 OR (a.id >0 AND u2.id =:manager_id2))
            GROUP BY ca.id ";

      $db_rows = DB::select(" SELECT ca.id  $query_part ", [$manager_id, $manager_id]);
      $row_count = count($db_rows);
      
      $db_rows_paginated = DB::select(
        " $query_select 
            $query_part
            ORDER BY ca.is_complete DESC, ll.name, l.id DESC, ca.id DESC
              LIMIT :skip, :take
      ", [$manager_id, $manager_id, $request->start, $request->length, ]);

    } else {
      // master-agents have access to any accounts
      $row_count = DB::table('lead_current_accounts')->whereRaw(" is_project >0 AND is_selected >0 ")->count();

      $db_rows_paginated = DB::select(
        " $query_select
            FROM lead_current_accounts ca
              LEFT JOIN lead_locations ll ON ca.location_id =ll.id
                LEFT JOIN leads l ON ll.lead_id =l.id
                  LEFT JOIN lead_relation_agency la ON l.id =la.lead_id
                    LEFT JOIN agencies a ON la.agency_id =a.id
                  LEFT JOIN states s ON l.state_id =s.id
            WHERE ca.is_project >0 AND ca.is_selected >0
              GROUP BY ca.id
            ORDER BY ca.is_complete DESC, ll.name, l.id DESC, ca.id DESC
              LIMIT :skip, :take
      ", [$request->start, $request->length, ]);
    }
    
    $row_items = [];
    if ($db_rows_paginated) {
      foreach ($db_rows_paginated as $row) {
        $r_enc_id = enc_id($row->id);
        
        if ($row->is_complete)
          $r_status = 'Project Completed';
        else
          $r_status = 'In Progress';

        if ($row->n_agency >1)
          $r_agency = '(Multiple Agencies)';
        elseif ($row->n_agency >0 && $row->agency)
          $r_agency = $row->agency;
        else
          $r_agency = '(Not Assigned)';

        $r_cell_act = '
          <a href="'.route('master.project.manage',['id'=> enc_id($row->lead_id)]).'"><i title="Project Management" class="md s btn-mod-item">assignment_turned_in</i></a>
        ';
        /*
        <th></th>
        <th>Agency</th>
        <th>Status</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Address</th>
        */
        $row_items[] = [
          $r_cell_act,
          $r_status,
          $r_agency,
          '<strong><a href="'.route('master.project.manage',['id'=>  enc_id($row->lead_id)]).'">'.$row->cust_name.'</a></strong>',
          format_tel($row->tel),
          format_city_state_zip($row->city, $row->state_code, $row->zip),
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
  * output JSON for DataTables: list of accounts (to be cancelled) in project-management ()
  */
  public function projectsCancel (Request $request)
  {
    $preapp = $request->get('preapp');
    $me = Auth::user();

    $query_select =
    " SELECT ca.*, ll.addr, l.id AS lead_id, l.cust_name, l.city, s.code AS state_code, l.zip, l.tel,  a.name AS agency,
        SUM(IF(a.id >0, 1,0)) n_agency ";

    if (POS_LV_CH_USER <= $me->access_lv && $me->access_lv <= POS_LV_CH_MANAGER) {
      $manager_id = $preapp->manager_id;
      $query_part =
        " FROM lead_current_accounts ca
            LEFT JOIN lead_locations ll ON ca.location_id =ll.id
              LEFT JOIN leads l ON ll.lead_id =l.id
                LEFT JOIN lead_relation_manager lm ON l.id =lm.lead_id
                  LEFT JOIN login_users u1 ON lm.user_id =u1.id
                LEFT JOIN lead_relation_agency la ON l.id =la.lead_id
                  LEFT JOIN agencies a ON la.agency_id =a.id
                  LEFT JOIN relation_agency_manager am ON la.agency_id =am.agency_id
                    LEFT JOIN login_users u2 ON am.user_id =u2.id
                LEFT JOIN states s ON l.state_id =s.id
          WHERE ca.is_project >0 AND ca.is_selected =0 AND (u1.id =:manager_id1 OR (a.id >0 AND u2.id =:manager_id2))
            GROUP BY ca.id ";

      $db_rows = DB::select(" SELECT ca.id  $query_part ", [$manager_id, $manager_id]);
      $row_count = count($db_rows);
      
      $db_rows_paginated = DB::select(
        " $query_select
            $query_part
            ORDER BY ca.is_complete DESC, ll.name, l.id DESC, ca.id DESC
              LIMIT :skip, :take
      ", [$manager_id, $manager_id, $request->start, $request->length, ]);

    } else {
      // master-agents have access to any accounts
      $row_count = DB::table('lead_current_accounts')->whereRaw(" is_project >0 AND is_selected =0 ")->count();

      $db_rows_paginated = DB::select(
        " $query_select
            FROM lead_current_accounts ca
              LEFT JOIN lead_locations ll ON ca.location_id =ll.id
                LEFT JOIN leads l ON ll.lead_id =l.id
                  LEFT JOIN lead_relation_agency la ON l.id =la.lead_id
                    LEFT JOIN agencies a ON la.agency_id =a.id
                  LEFT JOIN states s ON l.state_id =s.id
            WHERE ca.is_project >0 AND ca.is_selected =0
              GROUP BY ca.id
            ORDER BY ca.is_complete DESC, ll.name, l.id DESC, ca.id DESC
              LIMIT :skip, :take
      ", [$request->start, $request->length, ]);
    }
    
    $row_items = [];
    if ($db_rows_paginated) {
      foreach ($db_rows_paginated as $row) {
        $r_enc_id = enc_id($row->id);
        
        if ($row->is_complete)
          $r_status = 'Project Completed';
        else
          $r_status = 'In Progress';

        if ($row->n_agency >1)
          $r_agency = '(Multiple Agencies)';
        elseif ($row->n_agency >0 && $row->agency)
          $r_agency = $row->agency;
        else
          $r_agency = '(Not Assigned)';

        $r_cell_act = '
          <a href="'.route('master.project.manage',['id'=> enc_id($row->lead_id)]).'"><i title="Project Management" class="md s btn-mod-item">assignment_turned_in</i></a>
        ';
        /*
        <th></th>
        <th>Agency</th>
        <th>Status</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Address</th>
        */
        $row_items[] = [
          $r_cell_act,
          $r_status,
          $r_agency,
          '<strong><a href="'.route('master.project.manage',['id'=>  enc_id($row->lead_id)]).'">'.$row->cust_name.'</a></strong>',
          format_tel($row->tel),
          format_city_state_zip($row->city, $row->state_code, $row->zip),
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
