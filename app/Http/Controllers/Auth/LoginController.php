<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\lib\classes\UUID;

class LoginController extends Controller
{
/*
|--------------------------------------------------------------------------
| Login Controller
|--------------------------------------------------------------------------
|
| This controller handles authenticating users for the application and
| redirecting them to your home screen. The controller uses a trait
| to conveniently provide its functionality to your applications.
|
*/

use AuthenticatesUsers;

  /**
  * Where to redirect users after login.
  *
  * @var string
  */
  protected $redirectTo = '/home';

  /**
  * custom variable
  */
  private $log_src = 'Auth\LoginController';

  /**
  * Create a new controller instance.
  *
  * @return void
  */
  public function __construct()
  {
    $this->middleware('guest')->except('logout');
  }

  /**
  * handle POST login
  */
  public function login(Request $request)
  {
    $err_return = '/login';

    // input validation
    $v = Validator::make($request->all(), [
      'login_email' => 'required',
      'login_pw' => 'required'
    ], [
      'login_email.required'=> 'Email Address is required.',
      'login_pw.required'=> 'Password is required.'
    ]);
    if ($v->fails()) {
      return redirect($err_return)
        ->withErrors($v)
        ->withInput();
    }
    
    $log_src = $this->log_src.'@login';
    $login_email = $_POST['login_email'];
    $login_pw = $_POST['login_pw'];

    Auth::attempt([
      'email'=> $_POST['login_email'],
      'active'=>1,

      'password'=> $_POST['login_pw']
    ]);

    // login FAIL
    if (!Auth::check())
      return log_redirect('Login Failed. Your login name and/or password was not matching.',
        [
          'src'=>$log_src, 'msg'=>'user not found: not-exist, not active, or wrong-password.',
          'login-email'=> $login_email
        ], 'warn', 'login');

    // login SUCCESS
    $login_token = UUID::v4();
    session()->put('login_token', $login_token);
    
    DB::insert(
      " INSERT INTO rec_user_login (user_id, ip_addr, login_token) VALUES (:auth_id, :ip, :token)
          ON DUPLICATE KEY UPDATE ip_addr =:ip1, login_token =:token1, date_login =NOW(), date_act =NOW(), login_mod =0
    ", [
      Auth::id(), $_SERVER['REMOTE_ADDR'], $login_token,
      $_SERVER['REMOTE_ADDR'], $login_token
    ]);
    

    // leave a log and re-direct to dashboard
    log_write('User Logged in.', ['src'=> $log_src, 'login-email'=> $login_email]);
    return msg_redirect('Welcome to: '.SITE_TITLE.' Telecom Site', $this->redirectTo);
  }

  /**
  * handle GET/POST logout
  */
  public function logout()
  {
    $log_src = $this->log_src.'@logout';

    // leave a log
    log_write('User logged out.', ['src'=> $log_src, 'user-id'=> Auth::id()]);
    
    // reset session, logout and return
    session()->flush();
    Auth::logout();
    return msg_redirect('You have logged out.');
  }
}
