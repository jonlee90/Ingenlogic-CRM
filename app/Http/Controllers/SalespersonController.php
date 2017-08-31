<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Validator;

class SalespersonController extends Controller
{
  /*
   * custom variable
   */
  private $log_src = 'SalespersonController';

  /*
  * View: list of salesperson
   *
   * @return \Illuminate\Http\Response
   */
  public function list (Request $request)
  {
    return view('salesperson.list')
      ->with('preapp', $request->get('preapp'));
  }

  /**
    * output JSON for ingenOverlay: new salesperson
    */
  public function overlayNew(Request $request)
  {
		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('salesperson.create'), 'method'=> 'PUT']).'

          <div class="input-group">
            <label>First Name</label>
            '.Form::text('fname', '', ['maxlength'=>50, 'required']).'
          </div>
          <div class="input-group">
            <label>Last Name</label>
            '.Form::text('lname', '', ['maxlength'=>50, 'required']).'
          </div>
          <div class="input-group">
            <label>Email Address</label>
            '.Form::email('email', '', ['maxlength'=>100, 'required']).'
          </div>
          <div class="input-group">
            <label>Phone Number</label>
            '.Form::tel('tel', '', ['maxlength'=> 20, 'required', 'pattern'=> '^\d{10}$', 'title'=> '10 digit Phone number without dash or space.']).'
          </div>
          
          <div class="btn-group">
            '.Form::submit('Save New').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
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
    * output JSON for ingenOverlay: mod salesperson
    */
  public function overlayMod(Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@overlayMod';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);
    $sales_id = dec_id($enc_id);
    
    // validate: if record exists and correctly associated with the agent
    $result = $this->validate_record($sales_id, $agent_id, $log_src, TRUE);
    if (!isset($result->id))
      return $result;

    $salesperson = $result;
      
		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('salesperson.update', ['id'=> $enc_id])]).'

          <div class="input-group">
            <label>First Name</label>
            '.Form::text('fname', $salesperson->fname, ['maxlength'=>50, 'required']).'
          </div>
          <div class="input-group">
            <label>Last Name</label>
            '.Form::text('lname', $salesperson->lname, ['maxlength'=>50, 'required']).'
          </div>
          <div class="input-group">
            <label>Email Address</label>
            '.Form::email('email', $salesperson->email, ['maxlength'=>100, 'required']).'
          </div>
          <div class="input-group">
            <label>Phone Number</label>
            '.Form::tel('tel', $salesperson->tel, ['maxlength'=> 20, 'required', 'pattern'=> '^\d{10}$', 'title'=> '10 digit Phone number without dash or space.']).'
          </div>
          
          <div class="btn-group">
            '.Form::submit('Update Information').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
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
   * Action: create new salesperson
   */
  public function create (Request $request)
  {
    $log_src = $this->log_src.'@create';
    $preapp = $request->get('preapp');

    $agent_id = dec_id($preapp->agent_id);
    
    // input validation
    $v = Validator::make($request->all(), [
      'fname' => 'required',
      'lname' => 'required',
      'email' => 'required|email',
      'tel' => ['required','max:10', 'regex:/^\d{10}$/'],
    ], [
      'fname.*'=> 'First Name is required.',
      'lname.*'=> 'Last Name is required.',
      'email.*'=> 'Email entered is not a valid Email Address',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }

    // validation passed -> create new
    $sales_id = DB::table('sales_contacts')->insertGetId([
      'agent_id'=> $agent_id,
      'fname' => $request->fname,
      'lname' => $request->lname,
      'email' => $request->email,
      'tel' => $request->tel,
    ]);
    
    // action SUCCESS: leave a log and redirect to back (list)
    log_write('New Salesperson (Sales Contact) Created.', ['src'=>$log_src, 'agent-id'=>$agent_id, 'new-salesperson-id'=>$sales_id]);
    return msg_redirect('Salesperson has been created.');
  }

  /**
   * Action: update salesperson
   *
   * @param int $enc_id: salesperson ID encoded
   */
  public function update (Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@update';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);
    $sales_id = dec_id($enc_id);

    // validate: if record exists and correctly associated with the agent
    $result = $this->validate_record($sales_id, $agent_id, $log_src);
    if (!isset($result->id))
      return $result;
      
    // input validation
    $v = Validator::make($request->all(), [
      'fname' => 'required',
      'lname' => 'required',
      'email' => 'required|email',
      'tel' => ['required','max:10', 'regex:/^\d{10}$/'],
    ], [
      'fname.*'=> 'First Name is required.',
      'lname.*'=> 'Last Name is required.',
      'email.*'=> 'Email entered is not a valid Email Address',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }

    DB::update(" UPDATE sales_contacts SET fname =?, lname =?, email =?, tel =? WHERE id =? ", [
      $request->fname, $request->lname, $request->email, $request->tel, $sales_id
    ]);

    // action SUCCESS: leave a log and redirect to list
    log_write('Salesperson (Sales Contact) updated.', ['src'=> $log_src, 'agent-id'=> $agent_id, 'salesperson-id'=>$sales_id]);
    return msg_redirect('Salesperson has been updated.');
  }

  /**
   * Action: delete salesperson - NO recycling for salesperson/sales-contact
   *
   * @param enc_id: salesperson ID encoded
   */
  public function delete (Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@delete';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);
    $sales_id = dec_id($enc_id);

    // validate: if record exists and correctly associated with the agent
    $result = $this->validate_record($sales_id, $agent_id, $log_src);
    if (!isset($result->id))
      return $result;
      
    // validation passed -> delete record
    DB::delete(" DELETE FROM sales_contacts WHERE id =:id ", [$sales_id]);
    
    // action SUCCESS
    log_write('Salesperson (Sales Contact) deleted.', ['src'=> $log_src, 'agent-id'=> $agent_id, 'salesperson-id'=> $sales_id]);
    return msg_redirect('Salesperson has been deleted.');
  }

  /**
   * PRIVATE function: validate if record exists and correctly associated with the agent
   *
   * @param id: salesperson ID
   * @param agent_id: agent ID the salesperson associated with
   * @param log_src: function name for log purpose
   */
  private function validate_record ($id, $agent_id, $log_src, $ajax = FALSE)
  {
    $record = DB::table('sales_contacts')->find($id);

    // on error, write log and return JSON with error message
    if (!$record) {
      $toast_msg = 'Contact Not found.';
      $log_var = ['src'=>$log_src, 'msg'=>'sales-contact not found.', 'agent-id'=> $agent_id, 'salesperson-id'=> $id];
    
    } elseif ($record->agent_id != $agent_id) {
      $toast_msg = 'You have No Access to the Page.';
      $log_var = ['src'=>$log_src, 'msg'=>'auth user has NO access.', 'agent-id'=> $agent_id, 'salesperson-id'=> $id];
    
    } else
      return $record;

    if (!$ajax)
      return log_redirect($toast_msg, $log_var);
    else
      return log_ajax_err($toast_msg, $log_var);
  }
}
