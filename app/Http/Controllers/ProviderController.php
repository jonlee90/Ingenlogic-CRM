<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProviderController extends Controller
{
  /**
  * custom variable
  **/
  private $log_src = 'ProviderController';

  /**
  * View: list of service providers.
  *
  * @return \Illuminate\Http\Response
  */
  public function list (Request $request)
  {
    $log_src = $this->log_src.'@list';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);

    if (!$preapp->perm_prov_view)
      return no_access(['src'=> $log_src, 'agency-id'=> $agency_id]);

    return view('providers.list')
      ->with('preapp', $request->get('preapp'));
  }
  /**
  * View: overview of a provider.
  *
  * @param $id: provider ID encoded
  * @return \Illuminate\Http\Response
  */
  public function view (Request $request)
  {
    $log_src = $this->log_src.'@view';
    $preapp = $request->get('preapp');
    $agency_id = dec_id($preapp->agency_id);
    
    if (!$preapp->perm_prov_view)
      return no_access(['src'=> $log_src, 'agency-id'=> $agency_id]);

    $prov_id = dec_id($request->id);
    $prov = Provider::find($prov_id);
    if (!$prov)
      return log_redirect('Provider Not found.', ['src'=> $log_src, 'target-provider-id'=>$prov_id]);

        
    $row_contacts = DB::select(" SELECT id, fname, lname, title, email, tel FROM provider_contacts WHERE provider_id=:prov_id ORDER BY fname, lname ", [$prov_id]);

    $state = DB::table('states')->find($prov->state_id);
    $prov->state_code = ($state)? $state->code : '';
        
    $row_products = DB::select(
      " SELECT id, p_name, price
          FROM provider_products
          WHERE provider_id =:prov_id
          ORDER BY p_name ASC
      ", [$prov_id]);

    $data = (object) [
      'row_contacts'=> $row_contacts,
      'row_products'=> $row_products,
    ];
    return view('providers.view')
      ->with('preapp', $request->get('preapp'))
      ->with('data', $data)
      ->with('prov', $prov);
  }
}
