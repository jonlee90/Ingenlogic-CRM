<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\User;
use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Validator;

class CustomerController extends Controller
{
  /*
   * custom variable
   */
  private $log_src = 'CustomerController';

  /*
  * View: list of service providers.
   *
   * @return \Illuminate\Http\Response
   */
  public function list (Request $request)
  {
    return view('customers.list')
      ->with('preapp', $request->get('preapp'));
  }
  /**
   * View: customer overview
   *
   * @param enc_id: customer ID encoded
   * @return \Illuminate\Http\Response
   */
  public function view (Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@view';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);

    $cust_id = dec_id($enc_id);
    $cust = DB::table('customers')->whereRaw(" id =:id AND agent_id =:agent_id ", [$cust_id, $agent_id])->first();

    if (!$cust)
      return log_redirect('Customer Not found.', ['src'=>$log_src, 'target-customer-id'=> $cust_id], 'err');

    $state = DB::table('states')->find($cust->state_id);
    $state_code = ($state)? $state->code : '';
    
    $row_contacts = DB::select(" SELECT id, fname, lname, title, email, tel FROM customer_contacts WHERE customer_id=:cust_id ORDER BY fname, lname ", [$cust_id]);
    
    $data = (object) [
      'state_code'=> $state_code,
      'row_contacts'=> $row_contacts,
    ];
    return view('customers.view')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('cust', $cust);
  }
  /**
   * View: create new customer.
   *
   * @return \Illuminate\Http\Response
   */
  public function new (Request $request)
  {
    $db_rows = DB::table('states')->whereRaw(" country ='USA' ")->orderBy('state','ASC')->get();
    $row_states = [''=>'Please select a State'];
    if ($db_rows->count() >0) {
      foreach ($db_rows as $row)
        $row_states[$row->id] = $row->code.' - '.ucfirst(strtolower($row->state));
    }
              
    $data = (object) array(
      'row_states'=> $row_states,
    );
    return view('customers.new')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('cust', (object)[
          'name'=>'', 'tel'=>'', 'tax_id'=>'', 'email'=>'', 'addr'=>'', 'addr2'=>'', 'city'=>'', 'state_id'=>6, 'zip'=>'',
        ]);
  }
  /**
   * View: update customer
   *
   * @param enc_id: customer ID encoded
   * @return \Illuminate\Http\Response
   */
  public function mod (Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@mod';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);

    $cust_id = dec_id($enc_id);
    $cust = DB::table('customers')->whereRaw(" id =:id AND agent_id =:agent_id ", [$cust_id, $agent_id])->first();
    if (!$cust)
      return log_redirect('Customer Not found.',
        [
          'src'=>$log_src, 'agent-id'=> $agent_id, 'customer-id'=>$cust_id
        ], 'err', route('customer.list'));

    
    $db_rows = DB::table('states')->whereRaw(" country ='USA' ")->orderBy('state','ASC')->get();
    $row_states = [''=>'Please select a State'];
    if ($db_rows->count() >0) {
      foreach ($db_rows as $row)
        $row_states[$row->id] = $row->code.' - '.ucfirst(strtolower($row->state));
    }
    
    $data = (object) array(
      'row_states'=> $row_states,
    );
    return view('customers.mod')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('cust', $cust);
  }

  /**
   * Action: create new customer.
   */
  public function create (Request $request)
  {
    $log_src = $this->log_src.'@create';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);
    
    // input validation
    $v = Validator::make($request->all(), [
      'c_name' => 'required',
      'tel' => ['required', 'max:10', 'regex:/^\d{10}$/'],
      'tax_id' => ['nullable', 'regex:/^\d{2}-\d{7}$/'],
      'email' => 'nullable|email',
      'state_id' => 'nullable|numeric',
      'zip' => ['nullable', 'max:10', 'regex:/^\d{5}(-\d{4})?$/'],
    ], [
      'c_name.*'=> 'Customer Name is required.',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
      'tax_id.*'=> 'Please enter a valid Tax ID number (12-3456789 format).',
      'email.*'=> 'Please enter a valid Email Address.',
      'state_id.*'=> 'Invalid State ID entered.',
      'zip.*'=> 'Please use a valid US Zip code.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }

    $p_tax = ($request->tax_id)?  $request->tax_id : '';
    $p_email = ($request->email)?  $request->email : '';
    $p_addr = ($request->addr)?  $request->addr : '';
    $p_addr2 = ($request->addr2)?  $request->addr2 : '';
    $p_city = ($request->city)?  $request->city : '';
    $p_zip = ($request->zip)?  $request->zip : '';

    // validation passed -> create new provider (Eloquent ORM)
    $cust_id = DB::table('customers')->insertGetId([
      'mod_id'=> Auth::id(),
      'mod_user'=> trim(Auth::user()->fname.' '.Auth::user()->lname),
      'agent_id'=> $agent_id,
      'name'=> $request->c_name,
      'tel' => $request->tel,
      'tax_id' => $p_tax,
      'email' => $p_email,
      'addr' => $p_addr,
      'addr2' => $p_addr2,
      'city' => $p_city,
      'state_id' => $request->state_id,
      'zip' => $p_zip,
    ]);
    
    // action SUCCESS: leave a log and redirect to view
    log_write('Agent Customer Created.', ['src'=>$log_src, 'new-customer-id'=>$cust_id]);
    return msg_redirect('Customer has been created.', route('customer.view', ['id'=> enc_id($cust_id)]));
  }

  /**
   * Action: update customer.
   *
   * @param enc_id: provider ID encoded
   */
  public function update (Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@update';

    $me = Auth::user();
    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);

    $cust_id = dec_id($enc_id);
    $cust = DB::table('customers')->whereRaw(" id =:id AND agent_id =:agent_id ", [$cust_id, $agent_id])->first();
    if (!$cust)
      return log_redirect('Customer Not found.', ['src'=>$log_src, 'agent-id'=> $agent_id, 'customer-id'=>$cust_id]);
    
    // input validation
    $v = Validator::make($request->all(), [
      'c_name' => 'required',
      'tel' => ['required', 'max:10', 'regex:/^\d{10}$/'],
      'tax_id' => ['nullable', 'regex:/^\d{2}-\d{7}$/'],
      'email' => 'nullable|email',
      'state_id' => 'nullable|numeric',
      'zip' => ['nullable', 'max:10', 'regex:/^\d{5}(-\d{4})?$/'],
    ], [
      'c_name.*'=> 'Customer Name is required.',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
      'tax_id.*'=> 'Please enter a valid Tax ID number (12-3456789 format).',
      'email.*'=> 'Please enter a valid Email Address.',
      'state_id.*'=> 'Invalid State ID entered.',
      'zip.*'=> 'Please use a valid US Zip code.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }

    $p_tax = ($request->tax_id)?  $request->tax_id : '';
    $p_email = ($request->email)?  $request->email : '';
    $p_addr = ($request->addr)?  $request->addr : '';
    $p_addr2 = ($request->addr2)?  $request->addr2 : '';
    $p_city = ($request->city)?  $request->city : '';
    $p_zip = ($request->zip)?  $request->zip : '';

    DB::update(
      " UPDATE customers SET mod_id =?, mod_user =?,
          name =?, addr =?, addr2 =?, city =?, state_id =?, zip =?,  tel =?, tax_id =?, email =?
          WHERE id =?
    ", [$me->id, trim($me->fname.' '.$me->lname),
      $request->c_name, $p_addr, $p_addr2, $p_city, $request->state_id, $p_zip,  $request->tel, $p_tax, $p_email,
      $cust_id
    ]);

    // action SUCCESS: leave a log and redirect to view
    log_write('Agent Customer Updated.', ['src'=>$log_src, 'customer-id'=> $cust_id]);
    return msg_redirect('Customer has been updated.', route('customer.view', ['id'=> $enc_id]));
  }

  /**
   * Action: delete customer - copy to 'recycle' table and delete record
   *
   * @param enc_id: customer ID encoded
   */
  public function delete (Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@delete';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);

    $cust_id = dec_id($enc_id);
    $log_vars = ['src'=>$log_src, 'agent-id'=> $agent_id, 'customer-id'=>$cust_id];
    

    // validate: customer is found
    $cust = DB::table('customers')->whereRaw(" id =:id AND agent_id =:agent_id ", [$cust_id, $agent_id])->first();
    if (!$cust)
      return log_redirect('Customer Not found.', $log_vars, 'err');
      
    // validation passed -> delete customer (stored procedure)
    DB::update(" CALL sp_cust_delCustomer(:auth_id, :id) ", [Auth::id(), $cust_id]);
    
    // action SUCCESS: leave a log and redirect to back (list)
    log_write('Agent Customer deleted.', $log_vars);
    return msg_redirect('Customer has been deleted.');
  }

  /**
   * ******************************************************* customer x contact *******************************************************
   *
   * output JSON for ingenOverlay: assign new contact
   *
   * @param enc_id: customer ID encoded
   **/
  public function overlayNewContact(Request $request, $enc_id)
  {
		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('customer.create-contact', ['id'=> $enc_id]), 'method'=> 'PUT']).'

          <div class="input-group">
            <label>First Name</label>
            '.Form::text('fname', '', ['maxlength'=>50, 'required']).'
          </div>
          <div class="input-group">
            <label>Last Name</label>
            '.Form::text('lname', '', ['maxlength'=>50, 'required']).'
          </div>
          <div class="input-group">
            <label>Position Title</label>
            '.Form::text('title', '', ['maxlength'=>50]).'
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
            '.Form::submit('Save Contact').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
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
   * output JSON for ingenOverlay: update existing customer-contact
   *
   * @param enc_id: customer-contact ID encoded
   **/
  public function overlayModContact(Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@overlayModContact';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);

    $contact_id = dec_id($enc_id);
    $contact = DB::table('customer_contacts')->find($contact_id);

    // on error, write log and return JSON with error message
    if (!$contact)
      return log_ajax_err('Contact Not found.', ['src'=>$log_src, 'msg'=>'customer-contact not found.', 'agent-id'=> $agent_id, 'contact-id'=> $contact_id]);

		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('customer.update-contact', ['id'=> $enc_id])]).'

          <div class="input-group">
            <label>First Name</label>
            '.Form::text('fname', $contact->fname, ['maxlength'=>50, 'required']).'
          </div>
          <div class="input-group">
            <label>Last Name</label>
            '.Form::text('lname', $contact->lname, ['maxlength'=>50, 'required']).'
          </div>
          <div class="input-group">
            <label>Position Title</label>
            '.Form::text('title', $contact->title, ['maxlength'=>50]).'
          </div>
          <div class="input-group">
            <label>Email Address</label>
            '.Form::email('email', $contact->email, ['maxlength'=>100, 'required']).'
          </div>
          <div class="input-group">
            <label>Phone Number</label>
            '.Form::tel('tel', $contact->tel, ['maxlength'=> 20, 'required', 'pattern'=> '^\d{10}$', 'title'=> '10 digit Phone number without dash or space.']).'
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
   * Action: create new contact for the customer.
   *
   * @param enc_id: customer ID encoded
   */
  public function createContact (Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@createContact';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);
    
    $cust_id = dec_id($enc_id);
    $cust = DB::table('customers')->whereRaw(" id =:id AND agent_id =:agent_id ", [$cust_id, $agent_id])->first();
    
    // on error, write log and return JSON with error message
    if (!$cust)
      return log_ajax_err('Customer Not found.', ['src'=>$log_src, 'msg'=>'customer not found.', 'agent-id'=> $agent_id, 'customer-id'=> $cust_id]);

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

    $p_title = ($request->title)?  $request->title : '';
    
    // validation passed -> create new customer x contact
    $contact_id = DB::table('customer_contacts')->insertGetId([
      'customer_id'=> $cust_id,
      'fname' => $request->fname,
      'lname' => $request->lname,
      'title' => $p_title,
      'email' => $request->email,
      'tel' => $request->tel,
    ]);
    
    // action SUCCESS: leave a log and redirect to view (back)
    log_write('Agent Customer Contact created.', ['src'=>$log_src, 'agent-id'=> $agent_id, 'customer-id'=>$cust_id, 'contact-id'=>$contact_id]);
    return msg_redirect('New Contact has been saved.');
  }
  /**
   * Action: update customer-contact.
   *
   * @param enc_id: customer-contact ID encoded
   */
  public function updateContact (Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@updateContact';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);

    $contact_id = dec_id($enc_id);
    $contact = DB::table('customer_contacts')->find($contact_id);

    // validate: if customer-contact exist
    if (!$contact)
      return log_ajax_err('Contact Not found.', ['src'=>$log_src, 'msg'=>'customer-contact not found.', 'agent-id'=> $agent_id, 'contact-id'=>$contact_id]);

    $cust_id = $contact->customer_id;
    
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

    $p_title = ($request->title)?  $request->title : '';

    // validation passed -> update provider x provider
    DB::update(" UPDATE customer_contacts SET fname =?, lname =?, title =?, email =?, tel =? WHERE id =? ", [
      $request->fname, $request->lname, $p_title, $request->email, $request->tel, $contact_id
    ]);
    
    // action SUCCESS: leave a log and redirect to view (back)
    log_write('Agent Customer Contact Updated.', ['src'=>$log_src, 'agent-id'=> $agent_id, 'customer-id'=> $cust_id, 'contact-id'=> $contact_id]);
    return msg_redirect(' Contact has been updated.');
  }
  /**
   * Action: delete customer-contact.
   *
   * @param enc_id: customer-contact ID encoded
   */
  public function deleteContact (Request $request, $enc_id)
  {
    $log_src = $this->log_src.'@deleteContact';

    $preapp = $request->get('preapp');
    $agent_id = dec_id($preapp->agent_id);

    $contact_id = dec_id($enc_id);
    $contact = DB::table('customer_contacts')->find($contact_id);

    // validate: if customer-contact exist
    if (!$contact)
      return log_redirect('Contact Not found.', ['src'=>$log_src, 'msg'=>'provider-contact not found.', 'contact-id'=>$contact_id], 'err');

    $cust_id = $contact->customer_id;

    // validation passed -> delete customer-contact
    DB::delete(" DELETE FROM customer_contacts WHERE id =:id ", [$contact_id]);
    
    // action SUCCESS: leave a log and redirect to view (back)
    log_write('Agent Customer Contact deleted.', ['src'=>$log_src, 'agent-id'=> $agent_id, 'customer-id'=> $cust_id, 'contact-id'=> $contact_id]);
    return msg_redirect('The Contact has been deleted.');
  }
}
