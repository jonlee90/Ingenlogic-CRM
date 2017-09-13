<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;

use App\User;
use App\Provider;
use Collective\Html\FormFacade as Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class MasterProviderController extends Controller
{
  /*
   * custom variable
   */
  private $log_src = 'Master\MasterProviderController';

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
    $prov_id = dec_id($request->id);

    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_redirect('Provider Not found.', ['src'=> $log_src, 'target-provider-id'=>$prov_id]);


    $state = DB::table('states')->find($prov->state_id);
    $state_code = ($state)? $state->code : '';
        
    $row_contacts = DB::select(" SELECT id, fname, lname, title, email, tel FROM provider_contacts WHERE provider_id=:prov_id ORDER BY fname, lname ", [$prov_id]);
        
    $row_prov_svcs = DB::select(
      " SELECT p.id, p.service_id, p.p_name, p.price, p.rate_spiff, p.rate_residual, s.name AS svc_name
          FROM provider_products p
            LEFT JOIN services s ON p.service_id =s.id
          WHERE p.provider_id =:prov_id
          ORDER BY svc_name, p_name
      ", [$prov_id]);


    $data = (object) [
      'state_code'=> $state_code,
      'row_contacts'=> $row_contacts,
      'row_prov_svcs'=> $row_prov_svcs,
    ];
    return view('master.providers.view')
      ->with('preapp', $request->get('preapp'))
      ->with('prov', $prov)
      ->with('data', $data);
  }
  /**
   * View: create new service provider.
   *
   * @return \Illuminate\Http\Response
   */
  public function new (Request $request)
  {
    $log_src = $this->log_src.'@mod';
    $preapp = $request->get('preapp');
    
    // check if auth-user has permission-rec for provider
    if (!$preapp->perm_prov_rec)
      return no_access(['src'=> $log_src, ]);
      

    $data = (object) array(
      'row_states'=> get_state_list(),
    );
    return view('master.providers.new')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data);
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
   * Action: create new service provider.
   *
   * on success - return to overview
   * on fail - return to new view
   */
  public function create (Request $request)
  {
    $log_src = $this->log_src.'@create';
    $preapp = $request->get('preapp');
    
    // check if auth-user has permission-rec for provider
    if (!$preapp->perm_prov_rec)
      return no_access(['src'=> $log_src, ]);

    
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


    // validation passed -> create new provider (Eloquent ORM)
    $prov = Provider::create([
      'mod_id' => Auth::id(),
      'mod_user' => trim(Auth::user()->fname.' '.Auth::user()->lname),
      'name' => $request->c_name,
      'addr' => $p_addr, 'addr2' => $p_addr2,
      'city' => $p_city, 'state_id' => $p_state_id, 'zip' => $p_zip,
      'tel' => $request->tel,
      'default_term' => $request->term,
      'default_spiff' => $request->spiff, 'default_residual' => $request->resid,
      'active' => DB::raw($request->is_active),
    ]);
    
    // action SUCCESS: leave a log and redirect to provider.view
    log_write('Provider Created.', ['src'=> $log_src, 'new-provider-id'=> $prov->id]);
    return msg_redirect('Provider has been created.', route('master.provider.view', ['id'=> enc_id($prov->id)]));
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
   * Action: delete provider - copy to 'recycle' table and delete record
   *
   * @param $id: provider ID encoded
   */
  public function delete (Request $request)
  {
    $log_src = $this->log_src.'@delete';
    $preapp = $request->get('preapp');
    $me = Auth::user();
    $prov_id = dec_id($request->id);

    // check if auth-user has permission-del for provider
    if (!$preapp->perm_prov_del)
      return no_access(['src'=> $log_src, 'provider-id'=> $prov_id]);

    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_redirect('Provider Not found.', ['src'=> $log_src, 'provider-id'=> $prov_id]);
      

    // validation passed -> copy to delete-record -> delete provider x contact/products -> delete provider (Query Builder)
    DB::table('del_providers')->insert([
      'id'=> $prov_id,
      'date_rec'=> $prov->date_rec, 'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
      'name'=> $prov->name,
      'addr'=> $prov->addr, 'addr2'=> $prov->addr2, 'city'=> $prov->city, 'state_id'=> $prov->state_id, 'zip'=> $prov->zip, 'tel'=> $prov->tel,
      'default_term'=> $prov->default_term, 'default_spiff'=> $prov->default_spiff, 'default_residual'=> $prov->default_residual,
      'active'=> $prov->active,
    ]);

    DB::table('provider_contacts')->where('provider_id', $prov_id)->delete();
    DB::table('provider_products')->where('provider_id', $prov_id)->delete();
    $prov->delete();
    

    // action SUCCESS: leave a log and redirect to provider list
    log_write('Provider deleted.', ['src'=> $log_src, 'provider-id'=> $prov_id]);
    return msg_redirect('Provider has been deleted.');
  }

  /**
   * ******************************************************* provider x contact *******************************************************
   *
   * output JSON for ingenOverlay: assign new contact
   *
   * @param $id: provider ID encoded
   **/
  public function overlayContactNew(Request $request)
  {
    $log_src = $this->log_src.'@overlayContactNew';
    $preapp = $request->get('preapp');
    $prov_id = dec_id($request->id);

    // check if auth-user has permission-rec for provider (contact)
    if (!$preapp->perm_prov_rec)
      return no_access_ajax(['src'=> $log_src, 'provider-id'=> $prov_id]);

    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_ajax_err('Provider Not found.', ['src'=> $log_src, 'provider-id'=> $prov_id]);


		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('master.provider.create-contact', ['id'=> $request->id]), 'method'=> 'PUT']).'

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
   * output JSON for ingenOverlay: update existing provider-contact
   *
   * @param $id: provider-contact ID encoded
   **/
  public function overlayContactMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayContactMod';
    $preapp = $request->get('preapp');
    $contact_id = dec_id($request->id);

    // check if auth-user has permission-mod for provider (contact)
    if (!$preapp->perm_prov_mod)
      return no_access_ajax(['src'=> $log_src, 'provider-contact-id'=> $contact_id]);

    // validate: if provider-contact exist
    $contact = DB::table('provider_contacts')->find($contact_id);
    if (!$contact)
      return log_ajax_err('Contact Not found.', ['src'=>$log_src, 'msg'=>'provider-contact not found.', 'contact-id'=> $contact_id]);

    $prov = Provider::find($contact->provider_id);
    if (!$prov)
      return log_redirect('Provider Not found.', ['src'=> $log_src, 'provider-id'=> $prov_id]);


		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('master.provider.update-contact', ['id'=> $request->id])]).'

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
   * Action: create new contact for the provider.
   *
   * @param $id: provider ID encoded
   */
  public function contactCreate (Request $request)
  {
    $log_src = $this->log_src.'@contactCreate';
    $preapp = $request->get('preapp');
    $prov_id = dec_id($request->id);

    // check if auth-user has permission-rec for provider (contact)
    if (!$preapp->perm_prov_rec)
      return no_access(['src'=> $log_src, 'provider-id'=> $prov_id]);

    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_redirect('Provider Not found.', ['src'=> $log_src, 'provider-id'=> $prov_id]);
    
    
    // input validation
    $v = Validator::make($request->all(), [
      'fname' => 'required',
      'lname' => 'required',
      'email' => 'required|email',
      'tel' => ['required','max:10', 'regex:/^\d{10}$/'],
    ], [
      'fname.*' => 'First Name is required.',
      'lname.*' => 'Last Name is required.',
      'email.*'=> 'Email entered is not a valid Email Address',
      'tel.*'=> 'Please enter the 10 digit Phone Number without dashes and spaces.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }

    $p_title = ($request->title)?  $request->title : '';
    
    // validation passed -> create new provider x contact
    $contact_id = DB::table('provider_contacts')->insertGetId([
      'provider_id' => $prov_id,
      'fname' => $request->fname,
      'lname' => $request->lname,
      'title' => $p_title,
      'email' => $request->email,
      'tel' => $request->tel,
    ]);
    
    // action SUCCESS: leave a log and redirect to provider view
    log_write('Provider Contact created.', ['src'=> $log_src, 'provider-id'=> $prov_id, 'contact-id'=> $contact_id]);
    return msg_redirect('New Contact has been saved.');
  }
  /**
   * Action: update provider-contact.
   *
   * @param $id: provider-contact ID encoded
   */
  public function contactUpdate (Request $request)
  {
    $log_src = $this->log_src.'@contactUpdate';
    $preapp = $request->get('preapp');
    $contact_id = dec_id($request->id);

    // check if auth-user has permission-mod for provider (contact)
    if (!$preapp->perm_prov_mod)
      return no_access(['src'=> $log_src, 'provider-contact-id'=> $contact_id]);

    // validate: if provider-contact exist
    $contact = DB::table('provider_contacts')->find($contact_id);
    if (!$contact)
      return log_redirect('Contact Not found.', ['src'=>$log_src, 'msg'=>'provider-contact not found.', 'contact-id'=> $contact_id]);

    $prov_id = $contact->provider_id;
    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_redirect('Provider Not found.', ['src'=> $log_src, 'provider-id'=> $prov_id, 'contact-id'=> $contact_id]);
    

    // input validation
    $v = Validator::make($request->all(), [
      'fname' => 'required',
      'lname' => 'required',
      'email' => 'required|email',
      'tel' => ['required','max:10', 'regex:/^\d{10}$/'],
    ], [
      'fname.*' => 'First Name is required.',
      'lname.*' => 'Last Name is required.',
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
    DB::update(" UPDATE provider_contacts SET fname =:fname, lname =:lname, title =:title, email =:email, tel =:tel WHERE id =:id ", [
      $request->fname, $request->lname, $p_title, $request->email, $request->tel, $contact_id
    ]);
    
    // action SUCCESS: leave a log and redirect to provider view
    log_write('Provider Contact Updated.', ['src'=>$log_src, 'provider-id'=> $prov_id, 'contact-id'=> $contact_id]);
    return msg_redirect('Contact has been updated.');
  }
  /**
   * Action: delete provider-contact.
   *
   * @param $id: provider-contact ID encoded
   */
  public function contactDelete (Request $request)
  {
    $log_src = $this->log_src.'@contactDelete';
    $preapp = $request->get('preapp');
    $contact_id = dec_id($request->id);

    // check if auth-user has permission-del for provider (contact)
    if (!$preapp->perm_prov_del)
      return no_access(['src'=> $log_src, 'provider-contact-id'=> $contact_id]);

    // validate: if provider-contact exist
    $contact = DB::table('provider_contacts')->find($contact_id);
    if (!$contact)
      return log_redirect('Contact Not found.', ['src'=>$log_src, 'msg'=>'provider-contact not found.', 'contact-id'=> $contact_id]);

    $prov_id = $contact->provider_id;
    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_redirect('Provider Not found.', ['src'=> $log_src, 'provider-id'=> $prov_id, 'contact-id'=> $contact_id]);
      

    // validation passed -> delete provider-contact
    DB::table('provider_contacts')->whereRaw(" id =:id ", [$contact_id])->delete();
    
    
    // action SUCCESS: leave a log and redirect to provider view
    log_write('Provider Contact deleted.', ['src'=> $log_src, 'provider-id'=> $prov_id, 'contact-id'=> $contact_id]);
    return msg_redirect('The Contact has been deleted.');
  }

  /**
   * ******************************************************* provider x service-product *******************************************************
   *
   * output JSON for ingenOverlay: assign new service
   *
   * @param $id: provider ID encoded
   **/
  public function overlayProductNew(Request $request)
  {
    $log_src = $this->log_src.'@overlayProductNew';
    $preapp = $request->get('preapp');
    $prov_id = dec_id($request->id);

    // check if auth-user has permission-del for provider (product)
    if (!$preapp->perm_prov_rec)
      return no_access_ajax(['src'=> $log_src, 'provider-id'=> $prov_id]);

    // get default spiff/residual rates
    $provider = Provider::find($prov_id);
    if (!$provider)
      return log_ajax_err('Provider NOT found', ['src'=> $log_src, 'provider-id'=> $prov_id]);
  
    // select only 'child' services
    $db_rows = DB::select(
      " SELECT p.id, p.name
          FROM services p LEFT JOIN services c ON p.id =c.parent_id
          WHERE c.id IS NULL
            group by p.id
          ORDER BY p.name
    ");
    if ($db_rows) {
      $row_svcs = [];
      foreach ($db_rows as $row)
        $row_svcs[enc_id($row->id)] = $row->name;
    } else
      return log_ajax_err('There are No services available. Please define a service first.', ['src'=> $log_src, 'msg'=>'0 child services found.', 'provider-id'=> $prov_id]);


		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('master.provider.prod-create', ['id'=> $request->id]), 'method'=> 'PUT']).
            view('master.providers.form-prod')
              ->with('data', (object)['services'=> $row_svcs])
              ->with('prod', (object)['service_id'=> '', 'p_name'=> '', 'price'=> 0, 'rate_spiff'=> $provider->default_spiff, 'rate_residual'=> $provider->default_residual, ])
              ->render().'
              
          <div class="btn-group">
            '.Form::submit('Assign Service').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
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
   * output JSON for ingenOverlay: update existing provider-product
   *
   * @param $prod_id: provider-product ID encoded
   **/
  public function overlayProductMod(Request $request)
  {
    $log_src = $this->log_src.'@overlayProductMod';
    $preapp = $request->get('preapp');
    $prod_id = dec_id($request->prod_id);

    // check if auth-user has permission-mod for provider (product)
    if (!$preapp->perm_prov_mod)
      return no_access_ajax(['src'=> $log_src, 'provider-product-id'=> $prod_id]);

    $prod = DB::table('provider_products')->find($prod_id);
    if (!$prod)
      return log_ajax_err('Assigned Product Not found.', ['src'=>$log_src, 'provider-product-id'=> $prod_id]);

    $prov_id = $prod->provider_id;
    $provider = Provider::find($prov_id);
    if (!$provider)
      return log_ajax_err('Provider NOT found', ['src'=> $log_src, 'provider-id'=> $prov_id, 'product-id'=> $prod_id,]);


    // select only 'child' services
    $db_rows = DB::select(
      " SELECT p.id, p.name
          FROM services p LEFT JOIN services c ON p.id =c.parent_id
          WHERE c.id IS NULL
            group by p.id
          ORDER BY p.name
    ");
    if ($db_rows) {
      $row_svcs = [];
      foreach ($db_rows as $row)
        $row_svcs[enc_id($row->id)] = $row->name;
    } else
      return log_ajax_err('There are No services available. Please define a service first.', [
        'src'=>$log_src, 'msg'=>'0 child services found.', 'provider-id'=> $prov_id, 'product-id'=> $prod_id,
      ]);


		$html_output = '
      <div class="overlay-form">
        '.Form::open(['url'=>route('master.provider.prod-update', ['id'=> $request->prod_id])]).
            view('master.providers.form-prod')
              ->with('data', (object)['services'=> $row_svcs])
              ->with('prod', $prod)
              ->render().'
              
          <div class="btn-group">
            '.Form::submit('Update Information').' '.Form::button('Cancel', ['class'=> 'btn-cancel']).'
          </div>

        '.Form::close().'
      </div>
		';
		
		return json_encode(array(
			'success'=>1, 'error'=>0, 'html'=> $html_output,
		));
  }

  /**
   * Action: assign new service-product to the provider.
   *
   * @param $id: provider ID encoded
   */
  public function productCreate (Request $request)
  {
    $log_src = $this->log_src.'@productCreate';
    $preapp = $request->get('preapp');
    $prov_id = dec_id($request->id);

    // check if auth-user has permission-rec for provider (product)
    if (!$preapp->perm_prov_rec)
      return no_access(['src'=> $log_src, 'provider-id'=> $prov_id]);

    $provider = Provider::find($prov_id);
    if (!$provider)
      return log_ajax_err('Provider NOT found', ['src'=> $log_src, 'provider-id'=> $prov_id]);
      
    
    // input validation
    $p_svc_id = dec_id($request->svc_id);
    $validate_vars = [
      'svc_id' => $p_svc_id,
      'prod' => $request->prod,
      'price' => $request->price,
      'spiff' => $request->spiff,
      'resid' => $request->resid,
    ];
    $v = Validator::make($validate_vars, [
      'svc_id' => 'required|integer|min:0',
      'prod' => 'required',
      'price' => 'required|numeric|min:0',
      'spiff' => 'required|numeric|min:0',
      'resid' => 'required|numeric|min:0',
    ], [
      'svc_id.*'=> 'Invalid Input has been entered for Service field.',
      'prod.*'=> 'Product Name is required.',
      'price.*'=> 'Price should be a decimal.',
      'spiff.*'=> 'Spiff Rate should be a decimal.',
      'resid.*'=> 'Residual Rate should be a decimal.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }
    
    // validation passed -> create new provider x product (Query Builder)
    $prod_id = DB::table('provider_products')->insertGetId([
      'provider_id' => $prov_id,
      'service_id' => $p_svc_id,
      'p_name' => $request->prod,
      'price' => $request->price,
      'rate_spiff' => $request->spiff,
      'rate_residual' => $request->resid,
    ]);
    
    // action SUCCESS: leave a log and redirect
    log_write('Service-Product Assigned to Provider.', ['src'=> $log_src, 'provider-id'=> $prov_id, 'product-id'=> $prod_id, 'svc-id'=> $p_svc_id]);
    return msg_redirect('Product has been assigned.');
  }
  /**
   * Action: reset ALL products to default spiff/residual rate of the provider
   *
   * @param $id: provider ID encoded
   */
  public function productResetRate (Request $request)
  {
    $log_src = $this->log_src.'@productResetRate';
    $preapp = $request->get('preapp');
    $prov_id = dec_id($request->id);

    // check if auth-user has permission-mod for provider (product)
    if (!$preapp->perm_prov_mod)
      return no_access(['src'=> $log_src, 'provider-id'=> $prov_id]);

    $provider = Provider::find($prov_id);
    if (!$provider)
      return log_redirect('Provider Not found.', ['src'=> $log_src, 'provider-id'=> $prov_id]);
      
    
    // validation passed -> create new provider x product (Query Builder)
    $prod_id = DB::table('provider_products')
      ->whereRaw(" provider_id =? ", [$prov_id])
      ->update([
        'rate_spiff'=> $provider->default_spiff,
        'rate_residual'=> $provider->default_residual,
      ]);
      
    // action SUCCESS: leave a log and redirect
    log_write('All Service-Product of the Provider has been reset to Default Rate.', ['src'=> $log_src, 'provider-id'=> $prov_id]);
    return msg_redirect('Products have been reset to Default Rate.');
  }
  /**
   * Action: update assigned service-product.
   *
   * @param $prod_id: provider-product ID encoded
   */
  public function productUpdate (Request $request)
  {
    $log_src = $this->log_src.'@productUpdate';
    $preapp = $request->get('preapp');
    $prod_id = dec_id($request->prod_id);

    // check if auth-user has permission-mod for provider (product)
    if (!$preapp->perm_prov_mod)
      return no_access(['src'=> $log_src, 'provider-product-id'=> $prod_id]);

    $prod = DB::table('provider_products')->find($prod_id);
    if (!$prod)
      return log_ajax_err('Assigned Product Not found.', ['src'=>$log_src, 'provider-product-id'=> $prod_id]);

    $prov_id = $prod->provider_id;
    $provider = Provider::find($prov_id);
    if (!$provider)
      return log_ajax_err('Provider NOT found', ['src'=> $log_src, 'provider-id'=> $prov_id, 'product-id'=> $prod_id,]);
      

    // input validation
    $p_svc_id = dec_id($request->svc_id);
    $validate_vars = [
      'svc_id' => $p_svc_id,
      'prod' => $request->prod,
      'price' => $request->price,
      'spiff' => $request->spiff,
      'resid' => $request->resid,
    ];
    $v = Validator::make($validate_vars, [
      'svc_id' => 'required|integer|min:0',
      'prod' => 'required',
      'price' => 'required|numeric|min:0',
      'spiff' => 'required|numeric|min:0',
      'resid' => 'required|numeric|min:0',
    ], [
      'svc_id.*'=> 'Invalid Input has been entered for Service field.',
      'prod.*'=> 'Product Name is required.',
      'price.*'=> 'Price should be a decimal.',
      'spiff.*'=> 'Spiff Rate should be a decimal.',
      'resid.*'=> 'Residual Rate should be a decimal.',
    ]);
    if ($v->fails()) {
      return redirect()->back()
        ->withErrors($v)
        ->withInput();
    }

    // validation passed -> update provider x service-product (Query Builder)
    DB::table('provider_products')->where('id', $prod_id)->update([
      'service_id' => $p_svc_id,
      'p_name' => $request->prod,
      'price' => $request->price,
      'rate_spiff' => $request->spiff,
      'rate_residual' => $request->resid,
    ]);
    

    // action SUCCESS: leave a log and redirect
    log_write('Assigned Service-Product Updated.', ['src'=> $log_src, 'provider-id'=> $prov_id, 'product-id'=> $prov_id, 'svc-id'=> $p_svc_id]);
    return msg_redirect('Assigned Product has been updated.');
  }
  /**
   * Action: unassign service-product from provider.
   *
   * @param $prod_id: provider-product ID encoded
   */
  public function productDelete (Request $request)
  {
    $log_src = $this->log_src.'@productDelete';
    $preapp = $request->get('preapp');
    $prod_id = dec_id($request->prod_id);

    // check if auth-user has permission-del for provider (product)
    if (!$preapp->perm_prov_del)
      return no_access(['src'=> $log_src, 'provider-product-id'=> $prod_id]);

    $prod = DB::table('provider_products')->find($prod_id);
    if (!$prod)
      return log_ajax_err('Assigned Product Not found.', ['src'=>$log_src, 'provider-product-id'=> $prod_id]);

    $prov_id = $prod->provider_id;


    // validation passed -> unassign provider-product from provider (= remove provider x product)
    DB::table('provider_products')->whereRaw(" id =:prod_id ", [$prod_id])->delete();
    
    // action SUCCESS: leave a log and redirect
    log_write('Provider x Service-Product deleted.', ['src'=> $log_src, 'provider-id'=> $prov_id, 'product-id'=> $prod_id, ]);
    return msg_redirect('The Product has been deleted.');
  }
}
