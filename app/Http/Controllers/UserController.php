<?php

namespace App\Http\Controllers;

use App\User;
use App\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Validator;

class UserController extends Controller
{
  /**
  * custom variable
  **/
  private $log_src = 'UserController';

  /**
  * Show list of users.
  *
  * @return \Illuminate\Http\Response
  **/
  public function list(Request $request)
  {
    $preapp = $request->get('preapp');
    if (!$preapp->perm_user_view)
      return no_access(['src'=> $log_src, ]);

    return view('users.list')
      ->with('preapp', $request->get('preapp'));
  }
  /**
  * View: create new user.
  *
  * @return \Illuminate\Http\Response
  **/
  /*
  public function new (Request $request)
  {
    $log_src = $this->log_src.'@new';
    $preapp = $request->get('preapp');
    
    $row_positions = map_editable_positions(Auth::user()->access_lv);

    $row_pos = [];
    foreach ($row_positions as $p_lv)
      $row_pos[$p_lv] = config_pos_name($p_lv);

    $data = (object) array(
      'row_pos'=> $row_pos
    );
    return view('users.new')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data);
  }
  */
  
  /**
  * View: overview of a user.
  *
  * @return \Illuminate\Http\Response
  **/
  public function view (Request $request)
  {
    $log_src = $this->log_src.'@mod';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->id);
    $agency_id = dec_id($preapp->agency_id);
    
    if ($me->id == $user_id)
      $user = $me;
    else {
      if (!$preapp->perm_user_view)
        return no_access(['src'=> $log_src, ]);
      $user = User::find($user_id);
    }
    if (!$user)
      return log_redirect('User Not found.', [
        'src'=>$log_src, 'msg'=>'target user not found: not-exist OR user has no access.', 'agency-id'=> $agency_id, 'target-user-id'=>$user_id
      ]);
      
    $agency = ($user->access_lv >= POS_LV_AGENT_ADMIN)?  Agency::find($agency_id) : NULL;
    if ($agency) {
      $state = DB::table('states')->find($agency->state_id);
      $agency->state_code = ($state)?  $state->code : '';
    }

    return view('users.view')
      ->with('preapp', $preapp)
      ->with('user', $user)
      ->with('agency', $agency);
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
    $agency_id = dec_id($preapp->agency_id);
    

    $user = ($me->id == $user_id)?  $me : User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', [
        'src'=> $log_src, 'msg'=>'target user not found: not-exist OR user has no access.',
        'agency-id'=> $agency_id, 'target-user-id'=> $user_id
      ]);

    if ($me->id == $user_id)
      $row_positions = [$me->access_lv];
    else {
      $row_positions = map_editable_positions($preapp->lv);
      if (!in_array($user->access_lv, $row_positions))
        return no_access(['src'=> $log_src, 'user-id'=> $user_id, 'agency-id'=> $agency_id, ]);
    }
      

    $row_pos = [];
    foreach ($row_positions as $p_lv)
      $row_pos[$p_lv] = config_pos_name($p_lv);
      
    $data = (object) array(
      'row_pos'=> $row_pos,
    );
    return view('users.mod')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('user', $user);
  }
  /**
   * View: update agency (of the user) - available for Agent Admin only.
   *
   * @return \Illuminate\Http\Response
   */
  public function agency (Request $request)
  {
    $log_src = $this->log_src.'@agency';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate user x agent AND if user has access to agent
    if (!$preapp->perm_agency_mod)
      return no_access(['src'=>$log_src, 'agency-id'=> $agency_id, ]);
    
    $agency = Agency::find($agency_id);
    if ($agency) {
      $db_rows = DB::table('states')->whereRaw(" country ='USA' ")->orderBy('state','ASC')->get();
      $row_states = [''=>'Please select a State'];
      if ($db_rows->count() >0) {
        foreach ($db_rows as $row)
          $row_states[$row->id] = $row->code.' - '.ucfirst(strtolower($row->state));
      }
    } else 
      return log_redirect('Agency Information Not Found.', ['src'=>$log_src, 'agency-id'=> $agency_id, 'user-id'=>Auth::id()], 'err', route('index'));
      

    $data = (object) array(
      'enc_id'=> enc_id(Auth::id()),
      'row_states'=> $row_states,
    );
    return view('users.agency')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('agency', $agency);
  }

  /**
   * Action: create new user.
   *
   * on success - return to overview
   * on fail - return to new view
   */
   /*
  public function create (Request $request)
  {
    $log_src = $this->log_src.'@create';

    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);
    
    // input validation
    $v = Validator::make($request->all(), [
      'access_lv' => ['required', Rule::in( map_editable_positions(Auth::user()->access_lv) )],
      'email' => 'required|email|unique:login_users,email,',
      'pw' => ['required','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[_$@$!%*#?&])[A-Za-z\d_$@$!%*#?&]{6,}$/'],
      'fname' => 'required|string|max:50',
      'lname' => 'required|string|max:50',
      'is_active' => 'required|numeric',
    ], [
      'access_lv.required'=> 'Position is required.',
      'access_lv.in'=> 'Selected Position is Invalid.',
      'email.required'=> 'Email Address is required.',
      'email.email'=> 'Email entered is not a valid Email Address',
      'email.unique'=> 'Duplicate Email Address found.',
      'pw.required'=> 'Password is required.',
      'pw.confirmed'=> 'Please confirm the Password.',
      'pw.regex'=> 'Invalid Password entered.',
      'fname.required'=> 'First Name is required.',
      'lname.required'=> 'Last Name is required.',
      'is_active.*'=> 'Please enter a valid input for Active Status.',
      'max'=> 'Maximum Length allowed is :max.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }    

    // validation passed -> create new user (Eloquent ORM)
    $user = User::create([
      'mod_id' => Auth::id(),
      'mod_user' => trim(Auth::user()->fname.' '.Auth::user()->lname),
      'email' => $_POST['email'],
      'password' => bcrypt($_POST['pw']),
      'access_lv' => $_POST['access_lv'],
      'fname' => $_POST['fname'],
      'lname' => $_POST['lname'],
      'active' => DB::raw($_POST['is_active']), // BIT data-type needs DB::raw(bit value)
    ]);

    // create relationship record for the newly created user
    DB::insert(" INSERT INTO relation_user_agency (user_id, agent_id) VALUES (?,?) ", [$user->id, $agency_id]);
    
    // action SUCCESS: leave a log and redirect to user view
    log_write('User Created.', ['src'=>$log_src, 'new-user-id'=>$user->id, 'agency-id'=>$agency_id]);
    return msg_redirect('User has been created.', '/user/mod/'.enc_id($user->id));
  }
  */

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
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->id);
    $agency_id = dec_id($preapp->agency_id);
    

    $is_self = ($me->id == $user_id);
    if ($is_self) {
      $user = $me;
      $editable_lvs = [$me->access_lv];
      $p_lv = $me->access_lv;
      $p_active = NULL;
    
    } else {
      $user = User::find($user_id);
      $editable_lvs = map_editable_positions($preapp->lv);
      $p_lv = dec_id($request->access_lv);
      $p_active = $request->is_active;
    }
    if (!$user)
      return log_redirect('User Not found.', [
        'src'=> $log_src, 'msg'=>'target user not found: not-exist OR user has no access.',
        'agency-id'=> $agency_id, 'target-user-id'=> $user_id
      ]);
      

    // input validation
    $v_inputs = array_merge(
      $request->all(), [
        'is_self' => $is_self,
        'lv' => $p_lv, 'active' => $p_active,
      ]);
      
    $v = Validator::make($v_inputs, [
      'email' => ['required','email', Rule::unique('login_users','email')->ignore($user_id)],
      'fname' => 'required|string|max:50',
      'lname' => 'required|string|max:50',
      'is_self' => 'required',
      'lv' => ['required', Rule::in($editable_lvs)],
      'active' => 'nullable|numeric|required_if:is_self,false',
    ], [
      'email.required'=> 'Email Address is required.',
      'email.email'=> 'Email entered is not a valid Email Address',
      'email.unique'=> 'Duplicate Email Address found.',
      'fname.required'=> 'First Name is required.',
      'lname.required'=> 'Last Name is required.',
      'lv.required'=> 'Position is required.',
      'lv.in'=> 'Selected Position is Invalid.',
      'active.*'=> 'Please enter a valid input for Active Status.',
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
    $user->email = $request->email;
    $user->fname = $request->fname;
    $user->lname = $request->lname;
    if (!$is_self) {
      $user->access_lv = $p_lv;
      $user->active = DB::raw($p_active); // BIT data-type needs DB::raw(bit value)
    }
    $user->save();
    
    // action SUCCESS: leave a log and redirect to user view
    log_write('User updated.', ['src'=> $log_src, 'target-user-id'=> $user->id, 'agency-id'=>$agency_id]);
    return msg_redirect('User has been updated.', route('user.view', ['id'=> $request->id]));
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
    $log_src = $this->log_src.'@updatePassword';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $user_id = dec_id($request->id);
    $agency_id = dec_id($preapp->agency_id);
    

    $user = ($me->id == $user_id)?  $me : User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', [
        'src'=> $log_src, 'msg'=>'target user not found: not-exist OR user has no access.',
        'agency-id'=> $agency_id, 'target-user-id'=> $user_id
      ]);

    // validate: if user has access to target
    if ($user_id != $me->id && !in_array($user->access_lv, map_editable_positions($preapp->lv)))
      return no_access(['src'=> $log_src, 'agency-id'=> $agency_id, 'target-user-id'=> $user_id]);

    
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

    // if user is NOT self, flag: rec_user_login.login_mod =1
    if ($user_id != $me->id)
      DB::update(" UPDATE rec_user_login  SET login_mod =1  WHERE user_id =:id ", [$user_id]);
  
    
    // action SUCCESS: leave a log and redirect to user view
    log_write('User password updated.', ['src'=> $log_src, 'user-id'=>$user->id, 'agency-id'=> $agency_id,]);
    return msg_redirect('User Password has been updated.', route('user.view', ['id'=> $request->id]));
  }

  /**
  * Action: update agency of the user.
  *
  * on success - return to overview
  * on fail - return to update view
  **/
  public function updateAgency (Request $request)
  {
    $log_src = $this->log_src.'@updateAgency';
    $preapp = $request->get('preapp');
    $me = Auth::user();

    // validate: user has access (= user is agent-admin)
    if (!$preapp->perm_agency_mod)
      return no_access(['src'=> $log_src, 'agency-id'=> $agency_id]);
    
    // input validation
    $v = Validator::make($request->all(), [
      'c_name' => 'required',
      'state_id' => 'nullable|numeric',
      'zip' => ['nullable','max:10', 'regex:/^\d{5}(-\d{4})?$/'],
      'tel' => ['required','max:10', 'regex:/^\d{10}$/'],
    ], [
      'c_name.required'=> 'Company Name is required.',
      'state_id.*'=> 'Please enter a valid input for State.',
      'zip.*'=> 'Please enter a valid US Zip code.',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
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


    // validation passed -> update user (Eloquent ORM)
    $agency_id = dec_id($preapp->agency_id);
    $agency = Agency::find($agency_id);

    $agency->mod_id = $me->id;
    $agency->mod_user = trim($me->fname.' '.$me->lname);
    $agency->name = $request->c_name;
    $agency->addr = $p_addr;
    $agency->addr2 = $p_addr2;
    $agency->city = $p_city;
    $agency->state_id = $p_state_id;
    $agency->zip = $p_zip;
    $agency->tel = $request->tel;
    $agency->save();

    // action SUCCESS: leave a log and redirect
    log_write('Agency Updated.', ['src'=> $log_src, 'agency-id'=> $agency_id,]);
    return msg_redirect('Agency Information has been updated.');
  }

  /**
   * Action: delete user - copy user to 'recycle' table and delete record
   *
   * @param enc_id: user ID encoded
   *
   * on success - return to overview
   * on fail - return to user-list
   */
  public function delete (Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@update';

    $user_id = dec_id($enc_id);
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    // validate: user has access
    $user = User::find($user_id);
    if (!$user)
      return log_redirect('User Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'user-id'=> $user_id], 'err');
    if (!in_array($user->access_lv, map_editable_positions($preapp->lv)))
      return no_access(['src'=> $log_src, 'agency-id'=>$agency_id, 'user-id'=> $user_id]);
      
    // validation passed -> delete user (stored procedure)
    DB::update(" CALL sp_user_delUser(:auth_id, :user_id) ", [Auth::id(), $user_id]);
    
    // action SUCCESS: leave a log and redirect to user list
    log_write('User updated.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'user-id'=> $user_id]);
    return msg_redirect('User has been updated.', route('user.list'));
  }
}
