<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterServiceController extends Controller
{
  /**
  * custom variable
  **/
  private $log_src = 'MasterServiceController';

  /**
  * View: list of services
  *
  * @return \Illuminate\Http\Response
  **/
  public function list (Request $request)
  {
    return view('master.services.list')
      ->with('preapp', $request->get('preapp'));
  }

  /**
  * output JSON for ingenOverlay: new service
  **/
  public function overlayNew(Request $request)
  {
    $log_src = $this->log_src.'@overlayNew';
    $preapp = $request->get('preapp');
    
    // check if auth-user has permission-rec for service
    if (!$preapp->perm_svc_rec)
      return no_access_ajax(['src'=> $log_src, ]);

    
    $row_parents = ['New service is a Parent Service'];
    $db_rows = DB::select(
      " SELECT s.id, s.name
          FROM services s LEFT JOIN provider_products p ON s.id = p.service_id
          WHERE s.parent_id =s.id AND p.id IS NULL
            GROUP BY s.id
          ORDER BY s.name
    ");
    if ($db_rows) {
      foreach ($db_rows as $row)
        $row_parents[enc_id($row->id)] = $row->name;
    } else
      return log_ajax_err('System was unable to get available Parent Services.', ['src'=>$log_src, 'msg'=>'0 parent services found.', 'service-id'=>$svc_id], 'err');

		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('master.service.create'), 'method'=> 'PUT']).'

          <div class="input-group">
            <label>Parent Service</label>
            '.Form::select('parent_id', $row_parents, '', ['required']).'
          </div>
          <div class="input-group">
            <label>Service Name</label>
            '.Form::text('s_name', '', ['maxlength'=>50, 'required']).'
          </div>
          
          <div class="btn-group">
            '.Form::submit('Save New Service').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
          </div>

        '.Form::close().'
      </div>
		';
		
		return json_encode(array(
			'success'=>1, 'error'=>0,
			'html'=> $html_output
		));
  }
  /**
  * output JSON for ingenOverlay: mod service
  *
  * @param $id: service ID encoded
  */
  public function overlayMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayMod';
    $preapp = $request->get('preapp');
    $svc_id = dec_id($request->id);
    
    // check if auth-user has permission-mod for service
    if (!$preapp->perm_svc_mod)
    return no_access_ajax(['src'=> $log_src, 'service-id' => $svc_id, ]);
    
    $svc = DB::table('services')->find($svc_id);
    if (!$svc)
      return log_ajax_err('Service Not found.', ['src'=>$log_src, 'msg'=>'target service not found.', 'service-id'=>$svc_id], 'err');


    $n_child = DB::table('services')->whereRaw(" id <> parent_id AND parent_id =:svc_id ", [$svc_id])->count();

    // get list of possible parent services ONLY if the service has 0 child services
    if (!$n_child) {
      $row_parents = [];
      $db_rows = DB::select(
        " SELECT s.id, s.name
            FROM services s LEFT JOIN provider_products p ON s.id = p.service_id
            WHERE s.id =:id OR (s.parent_id =s.id AND p.id IS NULL)
              GROUP BY s.id
            ORDER BY s.name
      ", [$svc_id]);
      if ($db_rows) {
        foreach ($db_rows as $row)
          $row_parents[enc_id($row->id)] = ($row->id == $svc_id)?  $row->name.' (self)' : $row->name;
      } else
        return log_ajax_err('System was unable to get available Parent Services.', ['src'=>$log_src, 'msg'=>'0 parent services found.', 'service-id'=>$svc_id], 'err');
    }
    
		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('master.service.update', ['id'=>$request->id])]).'

          <div class="input-group">
            <label>Parent Service</label>
    ';
    $html_output .= ($n_child)?
            Form::hidden('has_child', 1).
            '<div class="output">
              <span class="popup-base">
                '.$svc->name.'
                <div class="popup-tip">
                  <div>
                    <p>The service already has child service(s)</p>
                    <p>and cannot change the Parent service</p>
                  </div>
                </div>
              </span>
            </div>' :
            Form::select('parent_id', $row_parents, enc_id($svc->parent_id), ['required']);
    $html_output .= '
          </div>
          <div class="input-group">
            <label>Service Name</label>
            '.Form::text('s_name', $svc->name, ['maxlength'=>50, 'required']).'
          </div>
          
          <div class="btn-group">
            '.Form::submit('Save Information').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
          </div>

        '.Form::close().'
      </div>
		';
		
		return json_encode(array(
			'success'=>1, 'error'=>0,
			'html'=> $html_output
		));
  }

  /**
   * Action: create new service
   */
  public function create (Request $request)
  {
    $log_src = $this->log_src.'@create';
    $preapp = $request->get('preapp');
    
    // check if auth-user has permission-rec for service
    if (!$preapp->perm_svc_rec)
      return no_access(['src'=> $log_src, ]);


    // input validation
    $p_parent = ($request->parent_id)?  dec_id($request->parent_id) : 0;
    $validate_vars = [
      'parent_id'=> $p_parent,
      's_name'=> $request->s_name,
    ];
    $v = Validator::make($validate_vars, [
      'parent_id'=> 'required|integer',
      's_name'=> 'required|unique:services,name',
    ], [
      'parent_id.*'=> 'Invalid value for Parent Service.',
      's_name.required'=> 'Service Name is required and cannot have duplicate name.',
      's_name.unique'=> 'Duplicate Service Name is not allowed.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }

    // validation passed -> create new service (Query Builder)
    $svc_id = DB::table('services')->insertGetId([
      'name'=> $request->s_name,
      'parent_id'=> $p_parent,
    ]);
    // if parent_id is 0 (= new service is a parent service), update to make the service a parent service
    if ($p_parent ==0)
      DB::table('services')->where('id', $svc_id)->update(['parent_id'=> $svc_id]);
      
    // action SUCCESS: leave a log and redirect to service list
    log_write('New Service Created.', ['src'=> $log_src, 'new-svc-id'=> $svc_id]);
    return msg_redirect('Service has been created.');
  }
  /**
   * Action: update service
   *
   * @param $id: service ID encoded
   */
  public function update (Request $request)
  {
    $log_src = $this->log_src.'@update';
    $preapp = $request->get('preapp');
    $svc_id = dec_id($request->id);
    
    // check if auth-user has permission-mod for service
    if (!$preapp->perm_svc_mod)
      return no_access(['src'=> $log_src, 'service-id' => $svc_id, ]);
    
    $svc = DB::table('services')->find($svc_id);
    if (!$svc)
      return log_redirect('Service Not found.', ['src'=> $log_src, 'service-id'=> $svc_id, ]);
        
    // if service has child: parent ID should equal service ID
    $p_parent = ($request->has_child >0)?  $svc_id : dec_id($request->parent_id);
    $validate_vars = [
      'parent_id'=> $p_parent,
      's_name'=> $request->s_name,
    ];

    // input validation
    $v = Validator::make($validate_vars, [
      'parent_id'=> 'required|integer|min:1',
      's_name'=> 'required|unique:services,name,'.$svc_id
    ], [
      'parent_id.*'=> 'Invalid value for Parent Service.',
      's_name.required'=> 'Service Name is required and cannot have duplicate name.',
      's_name.unique'=> 'Duplicate Service Name is not allowed.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }

    // validation passed -> update service (query builder)
    DB::table('services')->where('id', $svc_id)->update([
      'parent_id'=> $p_parent,
      'name'=> $request->s_name,
    ]);
    // DB::update(" UPDATE services SET name =:name WHERE id=:id ", [$request->s_name, $svc_id]);

    // action SUCCESS: leave a log and redirect to list
    log_write('Service updated.', ['src'=> $log_src, 'service-id'=>$svc_id]);
    return msg_redirect('Service has been updated.');
  }

  /**
   * Action: delete service - NO recycling for services
   *
   * @param $id: service ID encoded
   */
  public function delete (Request $request)
  {
    $log_src = $this->log_src.'@delete';
    $preapp = $request->get('preapp');
    $svc_id = dec_id($request->id);
    
    // check if auth-user has permission-del for service
    if (!$preapp->perm_svc_del)
      return no_access(['src'=> $log_src, 'service-id' => $svc_id, ]);

    // validate: service exists
    $svc = DB::table('services')->find($svc_id);
    if (!$svc)
      return log_redirect('Service Not found.', ['src'=>$log_src, 'service-id'=>$svc_id, ]);

    $n_child = DB::table('services')->whereRaw(" id <> parent_id AND parent_id =:svc_id ", [$svc_id])->count();
    $n_prod = DB::table('provider_products')->where('service_id', $svc_id)->count();
    
    if ($n_child >0 || $n_prod >0)
      return log_redirect('You cannot delete a service that has child services or associated service products', [
        'src'=>$log_src, 'msg'=> 'Service has child service OR product is associated with the service.', 'service-id'=> $svc_id, '# child'=> $n_child, '# product'=> $n_prod,
      ]);
      

    // validation passed -> delete service (stored procedure)
    DB::table('services')->whereRaw(" id =:id ", [$svc_id])->delete();
    
    // action SUCCESS: leave a log and redirect to service list
    log_write('Service deleted.', ['src'=> $log_src, 'service-id'=> $svc_id]);
    return msg_redirect('Service has been deleted.');
  }
}
