<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use App\User;
use App\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Validator;

class MasterAgencyController extends Controller
{
  /**
  * custom variable
  **/
  private $log_src = 'Master\MasterAgencyController';

  /**
  * View: list of agencies.
  *
  * @return \Illuminate\Http\Response
  **/
  public function list (Request $request)
  {
    return view('master.agencies.list')
      ->with('preapp', $request->get('preapp'));
  }
  /**
  * View: overview of a agency.
  *
  * @param $id: agency ID encoded
  * @return \Illuminate\Http\Response
  **/
  public function view (Request $request)
  {
    $log_src = $this->log_src.'@view';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($request->id);

    $agency = Agency::find($agency_id);
    if (!$agency)
      return log_redirect('Agent Not found.', ['src'=>$log_src, 'target-agent-id'=>$agent_id ]);

    $agency->manager = DB::table('relation_agency_manager AS am')->select('u.fname','u.lname')
      ->leftJoin('login_users AS u', 'am.user_id','=','u.id')
      ->whereRaw(" am.agency_id =:agency_id ", [$agency_id])->first();
      
    $state = DB::table('states')->find($agency->state_id);
    $agency->state_code = ($state)? $state->code : '';
    

    return view('master.agencies.view')
      ->with('preapp', $request->get('preapp'))
      ->with('agency', $agency);
  }
  /**
  * View: create new agency.
  *
  * @return \Illuminate\Http\Response
  **/
  public function new (Request $request)
  {
    $log_src = $this->log_src.'@mod';
    $preapp = $request->get('preapp');

    // check is auth-user has permission-rec for agency
    if (!$preapp->perm_agency_rec)
      return no_access(['src'=> $log_src]);


    $data = (object) array(
      'row_states'=> get_state_list(),
    );
    return view('master.agencies.new')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data);
  }
  /**
   * View: update agency.
   *
   * @param $id: agency ID encoded
   * @return \Illuminate\Http\Response
   */
  public function mod (Request $request)
  {
    $log_src = $this->log_src.'@mod';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($request->id);

    // check is auth-user has permission-mod for agency
    if (!$preapp->perm_agency_mod)
      return no_access(['src'=> $log_src, 'agency-id'=> $agency_id]);


    $agency = Agency::find($agency_id);
    if (!$agency)
      return log_redirect('Agency Not found.', ['src'=>$log_src, 'target-agency-id'=>$agent_id ]);

    $rec = DB::table('relation_agency_manager')->whereRaw(" agency_id =:agency_id ", [$agency_id])->first();
    $agency->manager_id = ($rec)?  $rec->user_id : 0;

    $managers = DB::select(
      " SELECT id, fname, lname, active
          FROM login_users WHERE access_lv =:lv ORDER BY active DESC, fname, lname, id DESC
    ", [POS_LV_CH_MANAGER]);


    $data = (object) array(
      'managers'=> $managers,
      'row_states'=> get_state_list(),
    );
    return view('master.agencies.mod')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('agency', $agency);
  }

  /**
   * Action: create new agent.
   *
   * on success - return to overview
   * on fail - return to new view
   */
  public function create (Request $request)
  {
    $log_src = $this->log_src.'@create';
    $preapp = $request->get('preapp');
    $me = Auth::user();

    // check is auth-user has permission-rec for agency
    if (!$preapp->perm_agency_rec)
      return no_access(['src'=> $log_src]);

    
    // input validation
    $v = Validator::make($request->all(), [
      'c_name' => 'required',
      'is_active' => 'required|numeric',
      'state_id' => 'nullable|numeric',
      'zip' => ['nullable','max:10', 'regex:/^\d{5}(-\d{4})?$/'],
      'tel' => ['required','max:10', 'regex:/^\d{10}$/'],
      'spiff' => 'required|numeric|min:0|max:'.MAX_SHARED_RATE,
      'resid' => 'required|numeric|min:0|max:'.MAX_SHARED_RATE,
    ], [
      'c_name.*' => 'Company Name is required.',
      'is_active.*' => 'Active Status has invalid input.',
      'state_id.*' => 'State has invalid input.',
      'zip.*'=> 'Please use a valid US Zip code.',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
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
    
    
    // validation passed -> create new agent (Eloquent ORM)
    $agency = Agency::create([
      'mod_id' => $me->id, 'mod_user' => trim($me->fname.' '.$me->lname),
      'name' => $request->c_name,
      'addr' => $p_addr, 'addr2' => $p_addr2,
      'city' => $p_city, 'state_id' => $p_state_id, 'zip' => $p_zip,
      'tel' => $request->tel,
      'spiff' => $request->spiff,
      'residual' => $request->resid,
      'active' => DB::raw($request->is_active),
    ]);
    
    // action SUCCESS: leave a log and redirect to agency mod
    log_write('Agency Created.', ['src'=> $log_src, 'new-agency-id'=> $agency->id]);
    return msg_redirect('Agency has been created.', route('master.agency.mod', ['id'=> enc_id($agency->id)]));
  }

  /**
   * Action: update agency.
   *
   * @param $id: agency ID encoded
   *
   * on success - return to overview
   * on fail - return to update view
   */
  public function update (Request $request)
  {
    $log_src = $this->log_src.'@update';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($request->id);

    // check is auth-user has permission-mod for agency
    if (!$preapp->perm_agency_mod)
      return no_access(['src'=> $log_src, 'agency-id'=> $agency_id]);


    $agency = Agency::find($agency_id);
    if (!$agency)
      return log_redirect('Agency Not found.', ['src'=>$log_src, 'agency-id'=>$agent_id ]);

    
    // input validation
    $v = Validator::make($request->all(), [
      'c_name' => 'required',
      'is_active' => 'required|numeric',
      'state_id' => 'nullable|numeric',
      'zip' => ['nullable','max:10', 'regex:/^\d{5}(-\d{4})?$/'],
      'tel' => ['required','max:10', 'regex:/^\d{10}$/'],
      'spiff' => 'required|numeric|min:0|max:'.MAX_SHARED_RATE,
      'resid' => 'required|numeric|min:0|max:'.MAX_SHARED_RATE,
    ], [
      'c_name.*' => 'Company Name is required.',
      'is_active.*' => 'Active Status has invalid input.',
      'state_id.*' => 'State has invalid input.',
      'zip.*'=> 'Please use a valid US Zip code.',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
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


    // validation passed -> update agency (Eloquent ORM)
    $agency->mod_id = $me->id;
    $agency->mod_user = trim($me->fname.' '.$me->lname);
    $agency->name = $request->c_name;
    $agency->addr = $p_addr;
    $agency->addr2 = $p_addr2;
    $agency->city = $p_city;
    $agency->state_id = $p_state_id;
    $agency->zip = $p_zip;
    $agency->tel = $request->tel;
    $agency->spiff = $request->spiff;
    $agency->residual = $request->resid;
    $agency->active = DB::raw($request->is_active);
    $agency->save();

    // action SUCCESS: leave a log and redirect
    log_write('Agency Updated.', ['src'=> $log_src, 'agency-id'=> $agency_id]);
    return msg_redirect('Agency has been updated.', route('master.agency.view', ['id'=> $request->id]));
  }

  /**
  * Action: update relationship - agency x channel-manager (user)
  *
  * @param $id: agency ID encoded
  *
  * on success - return to overview
  * on fail - return to update view
  **/
  public function channelManagerUpdate (Request $request)
  {
    $log_src = $this->log_src.'@channelManagerUpdate';

    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($request->id);

    // check is auth-user has permission-mod for agency
    if (!$preapp->perm_agency_mod)
      return no_access(['src'=> $log_src, 'agency-id'=> $agency_id]);


    $agency = Agency::find($agency_id);
    if (!$agency)
      return log_redirect('Agency Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id ]);
      

    // input validation
    if ($request->manager_id =='0') {
      $manager_id = 0;
      $manager = NULL;
      
    } else {
      $manager_id = dec_id($request->manager_id);
      $v = Validator::make(['manager_id' => $manager_id], [
        'manager_id' => 'required|numeric'
      ], [
        'manager_id.*'=> 'User ID of the Channel Manager is missing or invalid.',
      ]);
      if ($v->fails()) {
        return redirect()->back()
          ->withErrors($v)
          ->withInput();
      }
      $manager = User::find($manager_id);
    }
    

    // validation passed -> update agency x channel-manager: update to manager-id 0 = set to 'unassigned'
    if ($manager)
      DB::insert(
        " INSERT INTO relation_agency_manager (agency_id, user_id) VALUES (:agency_id, :manager_id)
            ON DUPLICATE KEY UPDATE user_id =:manager_id1
      ", [$agency_id, $manager_id, $manager_id]);
    else
      DB::table('relation_agency_manager')->whereRaw(" agency_id =:agency_id ", [$agency_id])->delete();
    
    // action SUCCESS: leave a log and redirect
    log_write('Agency x Channel Manager updated.', ['src'=> $log_src, 'agency-id'=> $agency_id, 'manager-id'=> $manager_id]);
    return msg_redirect('Channel Manager updated.');
  }

  /**
   * Action: delete agency - copy to 'recycle' table and delete record
   *
   * @param $id: ID encoded
   */
  public function delete (Request $request)
  {
    $log_src = $this->log_src.'@delete';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $agency_id = dec_id($request->id);

    // check is auth-user has permission-delete for agency
    if (!$preapp->perm_agency_del)
      return no_access(['src'=> $log_src, 'agency-id'=> $agency_id]);


    $agency = Agency::find($agency_id);
    if (!$agency)
      return log_redirect('Agency Not found.', ['src'=> $log_src, 'agency-id'=> $agency_id ]);


    // validate passed -> make deleted copy -> delete all agency x relationships -> delete agency
    DB::table('del_agencies')->insert([
      'id' => $agency->id,
      'date_rec' => $agency->date_rec,
      'mod_id' => $me->id, 'mod_user' => trim($me->fname.' '.$me->lname),
      'name' => $agency->name,
      'addr' => $agency->addr,
      'addr2' => $agency->addr2,
      'city' => $agency->city,
      'state_id' => $agency->state_id,
      'zip' => $agency->zip,
      'tel' => $agency->tel,
      'spiff' => $agency->spiff,
      'residual' => $agency->residual,
      'active' => DB::raw($agency->active),
    ]);
    
    DB::table('relation_user_agency')->whereRaw(" agency_id =:agency_id ", [$agency_id])->delete();
    DB::table('relation_agency_manager')->whereRaw(" agency_id =:agency_id ", [$agency_id])->delete();

    $agency->delete();
    

    // action SUCCESS: leave a log and redirect
    log_write('Agency DELETED.', ['src'=> $log_src, 'agency-id'=> $agency_id]);
    return msg_redirect('Agency has been Deleted.', route('master.agency.list'));
  }
}
