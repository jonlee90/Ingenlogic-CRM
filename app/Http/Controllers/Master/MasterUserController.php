<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use App\Agency;
use App\User;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Validator;

class MasterUserController extends Controller
{
  /*
   * custom variable
   */
  private $log_src = 'Master\MasterUserController';

  /**
   * View: list of users.
   *
   * @return \Illuminate\Http\Response
   */
  public function list (Request $request)
  {
    return view('master.users.list')
      ->with('preapp', $request->get('preapp'));
  }
  /**
   * View: overview of a user.
   *
   * @param $id: user ID encoded
   * @return \Illuminate\Http\Response
   */
  public function view (Request $request)
  {
    $log_src = $this->log_src.'@view';

    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->id);

    $user = User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', ['src'=>$log_src, 'target-user-id'=> $user_id, ]);

    if ($user_id != $me->id && !in_array($user->access_lv, map_accessible_positions($me->access_lv)))
      return no_access(['src'=> $log_src, 'target-user-id'=> $user_id]);
      
    $manager = $agency = NULL;
    $agencies = [];
    
    if (POS_LV_AGENT_USER <= $user->access_lv && $user->access_lv <= POS_LV_AGENT_ADMIN) {
    // if user is agent (admin, manager, user), get associated agency.
      $agency = DB::table('relation_user_agency AS ua')->leftJoin('agencies AS a', 'ua.agency_id','=','a.id')
        ->whereRaw(" ua.user_id =:user_id ", [$user_id])->first();

    } elseif ($user->access_lv == POS_LV_CH_MANAGER)  {
      // if user is channel-manager: get default spiff/residual rate -> only if auth-user is master-agent OR self
      if ($me->id == $user_id || $me->access_lv >= POS_LV_MASTER_USER) {
        $rec = DB::table('relation_manager_commission')->whereRaw(" user_id =:user_id ", [$user_id])->first();
        if ($rec) {
          $user->spiff = $rec->spiff;
          $user->residual = $rec->residual;
        } else
          $user->spiff = $user->residual = 0;
      }
      
      // if user is channel-manager: get assigned agency(s)
      $agencies = DB::select(
        " SELECT a.id, a.name, a.active
            FROM relation_agency_manager am
              LEFT JOIN agencies a ON am.agency_id =a.id
            WHERE am.user_id =:user_id
            ORDER BY a.active DESC, a.name, a.id DESC ", [$user_id]);

    } elseif ($user->access_lv == POS_LV_CH_USER)  {
    // if user is channel-assistant: get assigned agency(s) of the associated channel-manager
      $rec = DB::table('relation_assistant_manager AS am')->select('u.id','u.fname','u.lname')->leftJoin('login_users AS u', 'am.user_id','=','u.id')
        ->whereRaw(" am.assistant_id =:user_id ", [$user_id])->first();
      if ($rec) {
        $manager = trim($rec->fname.' '.$rec->lname);
        $agencies = DB::select(
          " SELECT a.id, a.name, a.active
              FROM relation_agency_manager am
                LEFT JOIN agencies a ON am.agency_id =a.id
              WHERE am.user_id =:user_id
              ORDER BY a.active DESC, a.name, a.id DESC ", [$rec->id]);
      }
    }
    
    $data = (object) [
      'agency' => $agency,
      'manager' => $manager,
      'agencies' => $agencies,
    ];
    return view('master.users.view')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('user', $user);
  }
  /**
   * View: create new user.
   *
   * @return \Illuminate\Http\Response
   */
  public function new (Request $request)
  {
    $log_src = $this->log_src.'@new';
    $preapp = $request->get('preapp');
    
    // only auth-user with access lv of master-manager or above can perform create
    if ($preapp->lv < POS_LV_MASTER_MANAGER)
      return no_access(['src'=> $log_src]);
    

    $row_positions = map_editable_positions($preapp->lv);

    $row_pos = [];
    foreach ($row_positions as $p_lv)
      $row_pos[$p_lv] = config_pos_name($p_lv);

    $data = (object) array(
      'row_pos'=> $row_pos
    );
    return view('master.users.new')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data);
  }
  /**
   * View: update user.
   *
   * @param $id: user ID encoded
   * @return \Illuminate\Http\Response
   */
  public function mod (Request $request)
  {
    $log_src = $this->log_src.'@mod';

    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->id);


    $user = ($me->id == $user_id)?  $me : User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', ['src'=>$log_src, 'msg'=>'target user not found.', 'target-user-id'=>$user_id]);

    if ($me->id == $user_id)
      $row_positions = [$me->access_lv];
    else {
      $editable_lvs = map_editable_positions($me->access_lv);
      if (!in_array($user->access_lv, $editable_lvs))
        return no_access(['src'=> $log_src, 'target-user-id'=> $user_id]);

      // access lv for system master cannot be changed 
      $row_positions = ($user->access_lv == POS_LV_SYS_MASTER)? 
        [POS_LV_SYS_MASTER] : map_editable_positions($me->access_lv);
    }
    
    $row_pos = [];
    foreach ($row_positions as $p_lv)
      $row_pos[$p_lv] = config_pos_name($p_lv);

    $agency_id = $manager_id = 0;
    $agencies = $managers = [];

    if (POS_LV_AGENT_USER <= $user->access_lv && $user->access_lv <= POS_LV_AGENT_ADMIN) {
      // agent users: get assigned agency + list of available agencies
      $rec = DB::table('relation_user_agency')->whereRaw(" user_id =? ", [$user_id])->first();
      $agency_id = ($rec)?  $rec->agency_id : 0;

      $agencies = Agency::select(['id','name','active'])->orderBy('active','DESC')->orderBy('name','ASC')->get();

    } elseif ($user->access_lv == POS_LV_CH_MANAGER && $me->id != $user_id) {
      // channel-manager: get default spiff/residual rate -> only if auth-user is master-agent (already validated above) AND not self
      $rec = DB::table('relation_manager_commission')->whereRaw(" user_id =:user_id ", [$user_id])->first();
      if ($rec) {
        $user->spiff = $rec->spiff;
        $user->residual = $rec->residual;
      } else
        $user->spiff = $user->residual = 0;
    
    } elseif ($user->access_lv == POS_LV_CH_USER && $me->access_lv >= POS_LV_MASTER_MANAGER) {
      // channel-assistants: get associated channel-manager + list of available managers -> available to master users only (managers or above)
      $rec = DB::table('relation_assistant_manager')->whereRaw(" assistant_id =? ", [$user_id])->first();
      $manager_id = ($rec)?  $rec->user_id : 0;

      $managers = DB::select(
        " SELECT id, fname, lname, active
            FROM login_users WHERE access_lv =:lv ORDER BY active DESC, fname, lname, id DESC
      ", [POS_LV_CH_MANAGER]);
    }
    
    $data = (object) [
      'row_pos'=> $row_pos,
      'agency_id'=> $agency_id,
      'agencies'=> $agencies,
      'manager_id'=> $manager_id,
      'managers'=> $managers,
    ];
    return view('master.users.mod')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('user', $user);
  }

  /**
   * Action: create new user.
   *
   * on success - return to overview
   * on fail - return to new view
   */
  public function create (Request $request)
  {
    $log_src = $this->log_src.'@create';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    
    // only auth-user with access lv of master-manager or above can perform create
    if ($preapp->lv < POS_LV_MASTER_MANAGER)
      return no_access(['src'=> $log_src]);


    // input validation
    $p_lv = dec_id($request->access_lv);
    $v_inputs = array_merge($request->all(), ['lv' => $p_lv]);
    
    $v = Validator::make($v_inputs, [
      'lv' => ['required', Rule::in( map_editable_positions($preapp->lv) )],
      'email' => 'required|email|unique:login_users,email,',
      'pw' => ['required','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[_$@$!%*#?&])[A-Za-z\d_$@$!%*#?&]{6,}$/'],
      'fname' => 'required|string|max:50',
      'lname' => 'required|string|max:50',
      'is_active' => 'required|numeric',
    ], [
      'lv.required'=> 'Position is required.',
      'lv.in'=> 'Selected Position is Invalid.',
      'email.required'=> 'Email Address is required.',
      'email.email'=> 'Email entered is not a valid Email Address',
      'email.unique'=> 'Duplicate Email Address found.',
      'pw.required'=> 'Password is required.',
      'pw.confirmed'=> 'Please confirm the Password.',
      'pw.regex'=> 'Invalid Password entered.',
      'fname.required'=> 'First Name is required.',
      'lname.required'=> 'Last Name is required.',
      'is_active.required'=> 'Active Status is required.',
      'is_active.numeric'=> 'Active Status has invalid input.',
      'max'=> 'Maximum Length allowed is :max.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }    

    // validation passed -> create new user (Eloquent ORM)
    $user = User::create([
      'mod_id' => $me->id,
      'mod_user' => trim($me->fname.' '.$me->lname),
      'email' => $request->email,
      'password' => bcrypt($request->pw),
      'access_lv' => $p_lv,
      'fname' => $request->fname,
      'lname' => $request->lname,
      'active' => DB::raw($request->is_active), // BIT data-type needs DB::raw(bit value)
    ]);
    
    // action SUCCESS: leave a log and redirect to user view
    log_write('User Created.', ['src'=> $log_src, 'new-user-id'=> $user->id]);
    return msg_redirect('User has been created.', route('master.user.view', ['id' => enc_id($user->id)]));
  }

  /**
   * Action: update user.
   *
   * @param $id: user ID encoded
   *
   * on success - return to overview
   * on fail - return to update view
   */
  public function update (Request $request)
  {
    $log_src = $this->log_src.'@update';
    $me = Auth::user();
    $user_id = dec_id($request->id);


    if ($me->id == $user_id) {
      $user = $me;
      $accessible_lvs = [$me->access_lv];
    } else {
      $user = User::find($user_id);
      $accessible_lvs = map_editable_positions($me->access_lv);

      // check if user has access to target
      if (!in_array($user->access_lv, $accessible_lvs))
        return no_access(['src'=> $log_src, 'target-user-id'=> $user_id]);    
    }
    if (!$user)
      return log_redirect('User Not found.', ['src'=> $log_src, 'user-id'=> $user_id]);


    // setup validation for 'active' and 'email' field
    $active_required = isset($request->is_active);
    $email_required = isset($request->email);

    $p_lv = dec_id($request->access_lv);
    $p_active = ($active_required)?  $request->is_active : NULL;
    $v_inputs = [
      'active_required' => $active_required, 'email_required' => $email_required,
      'lv' => $p_lv, 'active' => $p_active
    ];
    if (!$email_required)
      $v_inputs['email'] = NULL;
    $v_inputs = array_merge($request->all(), $v_inputs);
    

    // input validation
    $v = Validator::make($v_inputs, [
      'lv' => ['required', Rule::in($accessible_lvs)],
      'email_required' => 'required|boolean',
      'email' => ['nullable','required_if:email_required,true','email', Rule::unique('login_users','email')->ignore($user_id)],
      'fname' => 'required|string|max:50',
      'lname' => 'required|string|max:50',
      'active_required' => 'required|boolean',
      'active' => 'nullable|required_if:active_required,true|numeric',
    ], [
      'lv.required'=> 'Position is required.',
      'lv.in'=> 'Selected Position is Invalid.',
      'email.required_if'=> 'Email Address is required.',
      'email.email'=> 'Email entered is not a valid Email Address',
      'email.unique'=> 'Duplicate Email Address found.',
      'fname.required'=> 'First Name is required.',
      'lname.required'=> 'Last Name is required.',
      'active.required_if'=> 'Active Status is required.',
      'active.numeric'=> 'Active Status has invalid input.',
      'max'=> 'Maximum Length allowed is :max.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }


    // validation passed -> update user (Eloquent ORM)
    $user->mod_id = $me->id;
    $user->mod_user = trim($me->fname.' '.$me->lname);
    $user->access_lv = $p_lv;
    $user->fname = $request->fname;
    $user->lname = $request->lname;
    if ($active_required)
      $user->active = DB::raw($p_active);
    if ($email_required)
      $user->email = $request->email;
    $user->save();


    // if user position is not agent (admin, manager, user): remove user x agency relation (Query Builder)
    if (!(POS_LV_AGENT_USER <= $p_lv && $p_lv <= POS_LV_AGENT_ADMIN))
      DB::table('relation_user_agency')->whereRaw(" user_id =:user_id ", [$user_id])->delete();

    // if user position is not channnel-assistant: remove agency x manager relation, manager x commission relation (Query Builder)
    if ($p_lv != POS_LV_CH_MANAGER) {
      DB::table('relation_agency_manager')->whereRaw(" user_id =:user_id ", [$user_id])->delete();
      DB::table('relation_manager_commission')->whereRaw(" user_id =:user_id ", [$user_id])->delete();
    }

    // if user position is not channnel-assistant: remove assistant x manager relation (Query Builder)
    if ($p_lv != POS_LV_CH_USER)
      DB::table('relation_assistant_manager')->whereRaw(" assistant_id =:user_id ", [$user_id])->delete();
      

    // action SUCCESS: leave a log and redirect
    log_write('User Updated.', ['src'=> $log_src, 'user-id'=> $user_id]);
    return msg_redirect('User has been updated.');
  }

  /**
   * Action: update user password.
   *
   * @param $id: user ID encoded
   *
   * on success - return to overview
   * on fail - return to update view
   */
  public function updatePassword (Request $request)
  {
    $log_src = $this->log_src.'@update';

    $me = Auth::user();
    $user_id = dec_id($request->id);


    if ($me->id == $user_id) {
      $user = $me;
      $editable_lvs = [$me->access_lv];
    } else {
      $user = User::find($user_id);
      $editable_lvs = map_editable_positions($me->access_lv);

      // check if user has access to target
      if (!in_array($user->access_lv, $editable_lvs))
        return no_access(['src'=> $log_src, 'target-user-id'=> $user_id]);    
    }
    if (!$user)
      return log_redirect('User Not found.', ['src'=> $log_src, 'user-id'=> $user_id]);

    
    // input validation
    $v = Validator::make($request->all(), [
      'pw' => ['required','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[_$@$!%*#?&])[A-Za-z\d_$@$!%*#?&]{6,}$/'],
    ], [
      'pw.required'=> 'Password is required.',
      'pw.confirmed'=> 'Please confirm the Password.',
      'pw.regex'=> 'Invalid Password entered.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }
    
    // validation passed -> update user (Eloquent ORM)
    $user->mod_id = $me->id;
    $user->mod_user = trim($me->fname.' '.$me->lname);
    $user->password = bcrypt($request->pw);
    $user->save();

    // if user is NOT self, flag rec_user_login.login_mod =1
    if ($user_id != $me->id)
      DB::update(' UPDATE rec_user_login  SET login_mod =1  WHERE user_id =? ', [$user_id]);

    
    // action SUCCESS: leave a log and redirect
    log_write('User password updated.', ['src'=> $log_src, 'target-user-id'=> $user_id]);
    return msg_redirect('User Password has been updated.');
  }

  /**
   * Action: delete user.
   *
   * @param $id: user ID encoded
   *
   * on success - return to overview
   * on fail - return to update view
   */
  public function delete (Request $request)
  {
    return msg_redirect('User Deletion is currently Disabled.');


    $log_src = $this->log_src.'@delete';
    $me = Auth::user();
    $user_id = dec_id($request->id);


    $user = User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', ['src'=>$log_src, 'msg'=>'target user not found.', 'target-user-id'=>$user_id]);

    $accessible_lvs = map_editable_positions($me->access_lv);

    // check if user has access to target
    if ($me->id == $user_id || $me->access_lv < POS_LV_MASTER_MANAGER || !in_array($user->access_lv, $accessible_lvs))
      return no_access(['src'=> $log_src, 'target-user-id'=> $user_id]);
      

    // validate passed -> make deleted copy -> delete all user x relationships -> delete user
    DB::table('del_login_users')->insert([
      'id' => $user->id,
      'date_rec' => $user->created_at,
      'mod_id' => $me->id, 'mod_user' => trim($me->fname.' '.$me->lname),
      'email' => $user->email,
      'access_lv' => $user->access_lv,
      'fname' => $user->fname,
      'lname' => $user->lname,
      'title' => $user->title,
      'tel' => $user->tel,
      'active' => DB::raw($user->active),
    ]);
    
    DB::table('relation_user_agency')->whereRaw(" user_id =:user_id ", [$user_id])->delete();
    DB::table('relation_agency_manager')->whereRaw(" user_id =:user_id ", [$user_id])->delete();
    DB::table('relation_manager_commission')->whereRaw(" user_id =:user_id ", [$user_id])->delete();
    DB::table('relation_assistant_manager')->whereRaw(" assistant_id =:user_id ", [$user_id])->delete();

    $user->delete();
    

    // action SUCCESS: leave a log and redirect
    log_write('User DELETED.', ['src'=> $log_src, 'user-id'=> $user_id]);
    return msg_redirect('User has been Deleted.');
  }



  /**
  * ******************************************************* additional user related info: agency, manager *******************************************************
  * output JSON for ingenOverlay: assign agents
  *
  * @param #id: user ID encoded
  */
  public function overlayAssignAgency(Request $request)
  {
    $log_src = $this->log_src.'@overlayAssignAgency';

    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->id);

    // validate: target exists
    $user = ($me->id == $user_id)?  $me : User::find($user_id);
    if (!$user)
      return log_ajax_err('User Not found.', ['src'=>$log_src, 'msg'=>'target user not found.', 'target-user-id'=>$user_id], 'err');

    // validate: user can edit target 
    if ($user_id != $me->id && !in_array($user->access_lv, map_editable_positions($me->access_lv)))
      return log_ajax_err('You have No Access to the Page.', ['src'=> $log_src, 'target-user-id'=> $user_id], 'err');
      
    // validate: user is channel manager
    if ($user->access_lv != POS_LV_CH_MANAGER)
      return log_ajax_err('You can assign agency only to Channel Managers', ['src'=> $log_src, 'target-user-id'=> $user_id], 'err');


    $agencies = DB::select(
      " SELECT a.id, a.name, a.active,  IF(am.agency_id >0, 1,0) assigned
          FROM agencies a
            LEFT JOIN relation_agency_manager am ON am.user_id =:user_id AND a.id =am.agency_id
          ORDER BY a.active DESC, a.name ASC
    ", [$user_id]);
    
    if (!count($agencies))
      return log_ajax_err('There is No Agency available to assign.', ['src'=> $log_src, 'target-user-id'=> $user_id], 'err');
    

		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('master.user.agency-assign', ['id'=> $request->id])]).'

          <div class="col-n user-agent-wrapper">
            <h2>Available Agencies</h2>

            <div class="list-available">
    ';
    $html_output .= '
              <table id="tbl-user-agent-available">
                <thead><tr> <th></th> </tr></thead>
                <tbody>';
    $i =0;
    $html_assigned = $html_available = '';
    foreach ($agencies as $agency) {
      $r_label = ($agency->active)?  ' <label for="k-agent-'.$i.'">'.$agency->name.'</label>' : ' <label for="k-agent-'.$i.'" class="grayed">'.$agency->name.' (Inactive)</label>';
      $html_output .=
                  '<tr>
                    <td>'.Form::checkbox('agencyId[]', enc_id($agency->id), $agency->assigned, ['id'=> 'k-agent-'.$i, 'class'=> 'agent-checker']).$r_label.'</td>
                  </tr>';
                
      if ($agency->assigned) {
        $r_name = ($agency->active)?  $agency->name : '<span class="grayed">'.$agency->name.' (Inactive)</span>';
        $html_available .= '<p data-for="k-agent-'.$i.'"><span class="fa-remove btn-del-agent-tmp"></span> '.$r_name.'</p>';
      }
      $i++;
    }
    $html_output .= '
                </tbody>
              </table>
    ';
    if ($html_assigned !='')
      $html_assigned = '<div title="You have No access to following Agents">'.$html_assigned.'</div>';
    $html_output .= '
            </div>
          </div>
          <div class="col-n user-agent-wrapper">
            <h2>Assigned Agencies</h2>
            
            <div class="list-assigned">
              <div class="assigned-and-available">'.$html_available.'</div>
              '.$html_assigned.'
            </div>
          </div>
          
          <div class="btn-group">
            '.Form::submit('Update Assigned Agencies').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
          </div>

        '.Form::close().'
      </div>
      <script>moAssignAgency()</script>
		';
		
		return json_encode([
			'success'=>1, 'error'=>0,
			'html'=> $html_output
    ]);
  }

  /**
  * Action: update relationship - user x agency -> applies to 'agents' only (= agency admin/manager/user)
  *
  * @param $id: user ID encoded
  *
  * on success - return to overview
  * on fail - return to update view
  **/
  public function updateAgency (Request $request)
  {
    $log_src = $this->log_src.'@updateAgency';

    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->id);

    $user = User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', ['src'=> $log_src, 'user-id'=> $user_id]);

    // validate: auth-user can edit the user, self update not allowed
    if ($me->id == $user_id || !in_array($user->access_lv, map_editable_positions($me->access_lv)))
      return no_access(['src'=> $log_src, 'user-id'=> $user_id]);

    // validate: user is agent
    if (!in_array($user->access_lv, [POS_LV_AGENT_ADMIN, POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER]))
      return log_redirect('You can update agency only for Agents.', ['src'=> $log_src, 'user-id'=> $user_id]);
      

    // input validation
    if ($request->agency_id =='0') {
      $agency_id = 0;
      $agency = NULL;
      
    } else {
      $agency_id = dec_id($request->agency_id);
      $v = Validator::make(['agency_id' => $agency_id], [
        'agency_id' => 'required|numeric'
      ], [
        'agency_id.*'=> 'Agency ID is missing or invalid.',
      ]);
      if ($v->fails()) {
        return redirect()->back()
          ->withErrors($v)
          ->withInput();
      }
      $agency = Agency::find($agency_id);
    }
    

    // validation passed -> update user x agency: update to agency-id 0 = set agency to 'unassigned'
    if ($agency)
      DB::insert(
        " INSERT INTO relation_user_agency (user_id, agency_id) VALUES (:user_id, :agency_id)
            ON DUPLICATE KEY UPDATE agency_id =:agency_id1
      ", [$user_id, $agency_id, $agency_id]);
    else
      DB::table('relation_user_agency')->whereRaw(" user_id =:user_id ", [$user_id])->delete();
    
    // action SUCCESS: leave a log and redirect
    log_write('User x Agency updated.', ['src'=> $log_src, 'target-user-id'=> $user_id, 'agency-id'=> $agency_id]);
    return msg_redirect('User Agency updated.', route('master.user.view', ['id'=>$request->id]));
  }

  /**
  * Action: update relationship - user (assistant) x user (channel manager) -> applies to channel-assistants only
  *
  * @param $id: user ID encoded
  *
  * on success - return to overview
  * on fail - return to update view
  **/
  public function channelManagerUpdate (Request $request)
  {
    $log_src = $this->log_src.'@channelManagerUpdate';

    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->id);


    // validate: auth-user can edit the user
    if ($me->access_lv < POS_LV_MASTER_MANAGER)
      return no_access(['src'=> $log_src, 'user-id'=> $user_id]);

    $user = User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', ['src'=> $log_src, 'user-id'=> $user_id]);

    // validate: user is channel assistant
    if ($user->access_lv != POS_LV_CH_USER)
      return log_redirect('You can update a manager only for Assistants.', ['src'=> $log_src, 'user-id'=> $user_id]);
      

    // input validation
    if ($request->manager_id =='0') {
      $manager_id = 0;
      $manager = NULL;
      
    } else {
      $manager_id = dec_id($request->manager_id);
      $v = Validator::make(['manager_id' => $manager_id], [
        'manager_id' => 'required|numeric'
      ], [
        'manager_id.*'=> 'User ID is missing or invalid.',
      ]);
      if ($v->fails()) {
        return redirect()->back()
          ->withErrors($v)
          ->withInput();
      }
      $manager = User::find($manager_id);
    }
    

    // validation passed -> update assistant x channel-manager: update to manager-id 0 = set to 'unassigned'
    if ($manager)
      DB::insert(
        " INSERT INTO relation_assistant_manager (assistant_id, user_id) VALUES (:user_id, :manager_id)
            ON DUPLICATE KEY UPDATE user_id =:manager_id1
      ", [$user_id, $manager_id, $manager_id]);
    else
      DB::table('relation_assistant_manager')->whereRaw(" assistant_id =:user_id ", [$user_id])->delete();
    
    // action SUCCESS: leave a log and redirect
    log_write('Assistant x Channel Manager updated.', ['src'=> $log_src, 'target-user-id'=> $user_id, 'manager-id'=> $manager_id]);
    return msg_redirect('User Manager updated.', route('master.user.view', ['id'=> $request->id]));
  }

  /**
   * Action: update relationship - agency x master -> applies to channel managers only
   *
   * @param $id: target user ID encoded
   */
  public function assignAgency (Request $request)
  {
    $log_src = $this->log_src.'@assignAgency';

    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->id);


    $user = User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', ['src'=> $log_src, 'msg'=>'target user not found.', 'target-user-id'=> $user_id]);

    // validate: auth-user can edit the channel-manager, self update not allowed
    if ($me->id == $user_id || !in_array($user->access_lv, map_editable_positions($me->access_lv)))
      return no_access(['src'=> $log_src, 'auth-id'=> $me->id, 'target-user-id'=> $user_id]);

    // validate: user is channel manager
    if ($user->access_lv != POS_LV_CH_MANAGER)
      return log_redirect('You can assign agencies only to Channel Managers.', ['src'=> $log_src, 'target-user-id'=> $user_id]);
    

    // validation passed -> reset agency x master association
    DB::table('relation_agency_manager')->whereRaw(" user_id =:user_id ", [$user_id])->delete();

    // insert agents: skip if already exists
    $db_insert_params = [];
    $n_agency = count($request->agencyId);
    if ($n_agency) {
      foreach ($request->agencyId as $agency_id)
        $db_insert_params[] = ['user_id' => $user_id, 'agency_id' => dec_id($agency_id), ];
    }
    DB::table('relation_agency_manager')->insert($db_insert_params);


    // action SUCCESS: leave a log and redirect
    log_write('Agency(s) assigned to the Channel Manager.', ['src'=> $log_src, 'target-user-id'=> $user_id, '#-agencies'=> $n_agency]);
    return msg_redirect('Agency(s) assigned to the Channel Manager.');
  }
  /**
  * Action: unassign master x agent relationship -> applies to channel managers only
  *
  * @param user_id: target user ID encoded
  * @param agency_id: target agent ID encoded
  */
  public function unassignAgency (Request $request)
  {
    $log_src = $this->log_src.'@unassignAgency';

    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->user_id);
    $agency_id = dec_id($request->agency_id);


    // validate: auth-user can edit the channel-manager, self update not allowed
    if ($me->id == $user_id || !in_array($user->access_lv, map_editable_positions($me->access_lv)))
      return no_access(['src'=> $log_src, 'auth-id'=> $me->id, 'target-user-id'=> $user_id]);

    $user = User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', ['src'=> $log_src, 'msg'=>'target user not found.', 'target-user-id'=> $user_id, 'target-agency-id'=> $agency_id], 'err');

    $agency = Agency::find($agency_id);
    if (!$agency)
      return log_redirect('Ageny Not found.', ['src'=> $log_src, 'msg'=>'target agent not found.', 'target-user-id'=> $user_id, 'target-agency-id'=> $agency_id], 'err');

    // validate: user is channel-manager
    if ($user->access_lv != POS_LV_CH_MANAGER)
      return log_redirect('You can assign agencies only to Channel Manager', ['src'=> $log_src, 'target-user-id'=> $user_id], 'err');
    

    // validation passed -> unassign master x agency (Query Builder)
    DB::table('relation_agency_manager')->whereRaw(" user_id =:user_id AND agency_id =:agency_id ", [$user_id, $agency_id])->delete();
    
    // action SUCCESS: leave a log and redirect
    log_write('Master x Agency relationship unassigned.', ['src'=> $log_src, 'target-user-id'=> $user_id, 'target-agency-id'=> $agency_id]);
    return msg_redirect('Agency unassigned.');
  }

  /**
  * Action: update channle manager's commission rate -> applies to channel-managers only
  *
  * @param $id: user ID encoded
  *
  * on success - return to overview
  * on fail - return to update view
  **/
  public function channelManagerUpdateCommission (Request $request)
  {
    $log_src = $this->log_src.'@channelManagerUpdateCommission';

    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->id);


    // validate: auth-user can edit the channel-manager, self update not allowed
    $user = User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', ['src'=> $log_src, 'target-user-id'=> $user_id]);

    if ($me->id == $user_id || !in_array($user->access_lv, map_editable_positions($me->access_lv)))
      return no_access(['src'=> $log_src, 'auth-id'=> $me->id, 'target-user-id'=> $user_id]);
      
    // validate: user is channel-manager
    if ($user->access_lv != POS_LV_CH_MANAGER)
      return log_redirect('Only the Channel Managers have Commission Rates.', ['src'=> $log_src, 'user-id'=> $user_id]);
      

    // input validation
    $v = Validator::make($request->all(), [
      'spiff' => 'required|numeric|min:0|max:100',
      'residual' => 'required|numeric|min:0|max:100',
    ], [
      'spiff.*'=> 'Please enter Spiff Rate (from 0 to 100).',
      'residual.*'=> 'Please enter Residual Rate (from 0 to 100).',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }
    
    // validation passed -> update channel manager x commission rate
    DB::insert(
      " INSERT INTO relation_manager_commission (user_id, spiff, residual) VALUES (:id, :spiff, :resid)
          ON DUPLICATE KEY UPDATE spiff =:spiff1, residual =:resid1
    ", [$user_id, $request->spiff, $request->residual, $request->spiff, $request->residual]);
    
    
    // action SUCCESS: leave a log and redirect
    log_write('Channel Manager x Commission updated.', ['src'=> $log_src, 'target-user-id'=> $user_id]);
    return msg_redirect('Commission Rates for the Channel Manager updated.', route('master.user.view', ['id'=> $request->id]));
  }
}
