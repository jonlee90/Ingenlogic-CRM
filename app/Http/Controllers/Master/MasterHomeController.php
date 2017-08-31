<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MasterHomeController extends Controller
{
  //

  /**
  * Show the application dashboard.
  *
  * @return \Illuminate\Http\Response
  */
  public function index(Request $request)
  {
    return view('master.home')->with('preapp', $request->get('preapp'));
  }
}
