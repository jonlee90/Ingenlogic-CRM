<?php
/*
* UPDATEd for Laravel integration
*/
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/***************** convert MySQL time to local time  ****************
*
* when a input for MySQL date/time uses MySQL functions (e.g. NOW(), CURRENT_TIMESTAMP),
*  the values are saved in MySQL time-zone
* convert such values back to server (Los Angeles as set in configure.php) time-zone
*
* $datetime is a date-time formatted string (e.g. 2000-01-01 12:00:00)
* return value can be formatted using OPTIONAL $format
* if reverse is TRUE, timezone will be reversed from server time to MySQL timezone
*
* require: MYSQL_TIMEZONE, DEFAULT_TIMEZONE defined in configure.php
*************************************************************/
function convert_mysql_timezone($datetime, $format = 'Y-m-d', $reverse =FALSE) {
	$tz_from = ($reverse)? DEFAULT_TIMEZONE : MYSQL_TIMEZONE;
	$tz_to = ($reverse)? MYSQL_TIMEZONE : DEFAULT_TIMEZONE;
	$dt = new DateTime($datetime, new DateTimeZone($tz_from));
	$dt->setTimezone(new DateTimeZone($tz_to));
	return $dt->format($format);
}
/**
 * ************** format phone number ****************
 * @param $tel
 *
 * if $tel is 10 digit: return in 123-456-7890 format
 * otherwise, return as-is
*************************************************************/
function format_tel($tel) {
	return (preg_match('/^(\d){10}$/', $tel))?  substr($tel, 0,3).'-'.substr($tel, 3,3).'-'.substr($tel, 6) : $tel;
}
/**
 * ************** format phone number ****************
 * @param $date
 *
 * return date formatted to "m/d/Y"
*************************************************************/
function format_date($date) {
	$time = strtotime($date);
	return ($time)?  date('m/d/Y', $time) : FALSE;
}
/**
* ************** format city, state, zip ****************
* @param $city
* @param $state: (string)
* @param $zip
*
* return city/state/zip formatted in "city, state zip"
*************************************************************/
function format_city_state_zip ($city, $state, $zip) {
	$state_zip = trim($state.' '.$zip);
	$city_state_zip = ($city && $state_zip)?  $city.', '.$state_zip : $city.$state_zip;

	return $city_state_zip;
}


/***************** validate input value *****************
*
* validate value based on value type using regex
*************************************************************/
function validInput ($val, $type) {
	switch ($type) {
		case 'tel':
		case 'fax':
			return (preg_match("/^(\d){10}$/", $val) ===1); break;

		// use built-in PHP validation function for email: do not rely too much!
		case 'email':
			return filter_var($val, FILTER_VALIDATE_EMAIL); break;

		case 'pw':
			return (preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[_$@$!%*#?&])[A-Za-z\d_$@$!%*#?&]{6,}$/", $val) ===1); break;
			
		case 'zipus':
			return (preg_match("/^\d{5}(-\d{4})?$/", $val) ===1); break;
			
		case 'ccno':
			return (preg_match("/^\d{15,16}$/", $val) ===1); break;
		case 'cccvv':
			return (preg_match("/^\d{3,4}$/", $val) ===1); break;
	}
}
/***************** function to send mail via Swift Mail library *****************
*
* returns email sent successfully or not.
*************************************************************/
function send_Swift_mail ($email, $subj, $msg, $from_name =SITE_TITLE, $from_email =SWIFT_MAIL_USERNAME, $reply_to = array()) {
	try {
		// send message via Swift Mail library
		$swift_path = $_SERVER['DOCUMENT_ROOT'].BASE_FOLDER.'/lib/swift/lib/swift_required.php';
		require_once ($swift_path);
	
		$transport = Swift_SmtpTransport::newInstance('a2plcpnl0876.prod.iad2.secureserver.net', 465, "ssl")
		  ->setUsername(SWIFT_MAIL_USERNAME)
		  ->setPassword(SWIFT_MAIL_PW);
		
		$mailer = Swift_Mailer::newInstance($transport);
		
		//*** gmail disallows overriding "from" email - email will be equal to defined value: SWIFT_MAIL_USERNAME
		$message = Swift_Message::newInstance($subj)
		  ->setFrom(array($from_email => $from_name)) 
		  ->setTo(array($email))
		  ->setBody($msg)
			->setContentType("text/html; charset=utf-8");
		
		// set reply-to list if exists: reply_to MUST have array('email1'=>'name1','email2'=>'name2') format
		if (is_array($reply_to) && count($reply_to) >0)
			$message->setReplyTo($reply_to);
	
		if ($mailer->send($message)) {
			// success
			return TRUE;
		} else {
			// general failure
			append_log("Func [send_Swift_mail]: General failture to send email (email: $email, subject: $subj). ", LOG_FOLDER_ERR);
			return FALSE;
		}
	} catch (Exception $e) {
		append_log("Func [send_Swift_mail]: Caught Exception on sending email (email: $email, subject: $subj). \n".$e->getMessage(), LOG_FOLDER_ERR);
		return FALSE;
	}
}
/***************************************************************************************************************************************
* LARAVEL based functions
****************************************************************************************************************************************
*/
/**
* *************** log_write ****************
* log message
* use Illuminate\Support\Facades\Auth; 
* use Illuminate\Support\Facades\Log; 
*
* @param string $msg: message to log
* @param array $vars: array to pass to Log:: function(), append Auth::id() if logged in
* @param string $severe_lv: leave log with given severity level (info if NULL)
*************************************************************/
function log_write($msg, $vars = [], $severe_lv = NULL) {
	$log_vars = (Auth::user())? 
		array_merge(['auth-id'=> Auth::id()], $vars) : $vars;
		
	switch ($severe_lv) {
		case 'warn':
			Log::warning($msg, $log_vars);
		break;
		case 'err':
			Log::error($msg, $log_vars);
		break;
		default:
			Log::info($msg, $log_vars);
	}
}
/**
* *************** log_redirect ****************
* log to file, output message to user, redirect to page
*
* @param string $msg
* @param array $vars
* @param string $link: link to redirect to - default to Redirect::back()
*
* @return return value of redirect()->back() (by default, or $link if set) ->withInput()
*************************************************************/
function log_redirect($msg, $vars, $severe_lv ='err', $link = NULL) {
	log_write($msg, $vars, $severe_lv);
	return err_redirect($msg, $link);
}
/**
* ************** msg_redirect ****************
* redirect to page with output message to user, 
*
* @param string $msg
* @param string $link: link to redirect to - default to back()
*
* @return return value of Redirect::to
*/
function msg_redirect($msg, $link = NULL) {
	session()->put('toast_msg', '<span class="info">'.$msg.'</span>');
	
	if ($link === NULL)
		return redirect()->back();
	return redirect()->to($link);
}
/**
* ************** err_redirect ****************
* redirect to page with err-output message to user, 
*
* @param string $msg
* @param string $link: link to redirect to - default to back()
*
* @return return value of Redirect::to -> withInput()
*/
function err_redirect($msg, $link = NULL) {
	session()->put('toast_msg', '<span class="err">'.$msg.'</span>');
	
	if ($link === NULL)
		return redirect()->back()
			->withInput();
	return redirect()->to($link)
		->withInput();
}
/**
* ***************** no_access *****************
* use Illuminate\Support\Facades\Auth; 
*  shortcut to log_redirect specifically when user has NO access to the page/action
*
* @param array $vars: additional variable(s) to include in the log
*/
function no_access ($vars =[]) {
	return log_redirect('You have No Access to the Page.', 
		array_merge(
			['msg'=>'logged in user has NO access to the page.', 'access-lv'=> Auth::user()->access_lv], $vars
		), 'err', route('index'));
}
/**
* ***************** log_ajax_err *****************
* shortcut to log_redirect specifically when user has NO access to the page/action
*
* @param string $msg
* @param array $vars
* @return JSON: error flag to 1 with message
*/
function log_ajax_err ($msg, $vars =[]) {
	log_write($msg, $vars, 'err');
	return json_encode(array(
		'success'=>0, 'error'=>1,
		'msg'=> $msg
	));
}
/**
* ***************** no_access (AJAX version) *****************
* use Illuminate\Support\Facades\Auth; 
*  shortcut to log_ajax_err specifically when user has NO access to the page/action
*
* @param array $vars
* @return JSON: error flag to 1 with message
**/
function no_access_ajax ($vars =[]) {
	return log_ajax_err('You have No Access to the Page.', 
		array_merge(
			['msg'=>'logged in user has NO access to the page.', 'access-lv'=> Auth::user()->access_lv], $vars
		), 'err');
}
/***************** encrypt ID *****************
*
* encrypt ID using openssl_encrypt (PHP built-in) with SITE_KEY_SECRET + csrf_token (as IV value)
* base64-encode the result
*************************************************************/
function enc_id ($id) {
	return base64_encode(openssl_encrypt($id, OPENSSL_CIPHER_METHOD, SITE_KEY_SECRET, 0, substr(session('login_token'), 0,OPENSSL_CIPHER_IV_LEN)));
}
/***************** decrypt ID *****************
*
* decrypt ID using openssl_decrypt (PHP built-in) with SITE_KEY_SECRET + csrf_token (as IV value)
* $data should be base64-decoded first
*************************************************************/
function dec_id ($data) {
	return openssl_decrypt(base64_decode($data), OPENSSL_CIPHER_METHOD, SITE_KEY_SECRET, 0, substr(session('login_token'), 0,OPENSSL_CIPHER_IV_LEN));
}
/***************** get toast-msg *****************
*
* return toast message to show
* add any Laravel Validator errors (=$errors)
*************************************************************/
function get_toast_msg ($lara_err = NULL) {
	$toast_msg = '';
	if (session()->has('toast_msg'))
		$toast_msg = session()->pull('toast_msg');
	
	if ($lara_err !== NULL && $lara_err->any()) {
		$lara_msg = '';
		foreach ($lara_err->all() as $err)
			$lara_msg .= '<p>'.$err.'</p>';
		$toast_msg .= '<div class="laravel-errors">'.$lara_msg.'</div>';
	}
	return $toast_msg;
}
/**
* ************** get array of states for state input *****************
*
* @return array: list of states
*************************************************************/
function get_state_list () {
	$db_rows = DB::table('states')->whereRaw(" country ='USA' ")->orderBy('state','ASC')->get();
	$row_states = [''=> 'Please select a State'];
	if ($db_rows->count() >0) {
		foreach ($db_rows as $row)
			$row_states[$row->id] = $row->code.' - '.ucfirst(strtolower($row->state));
	}
	return $row_states;
}
/**
* ************** leave a log for leads *****************
* use Illuminate\Support\Facades\Auth; 
*
* @param $obj: object [
*		id: lead ID
*		msg: log message
*		auto: message is generated by system, 1 or 0
*		detail (optional): detail log info
* @return $log_id: log ID (= last insert id)
*************************************************************/
function log_lead ($obj) {
	$lead_id = $obj->id;
	$detail = (isset($obj->detail))?  $obj->detail : NULL;
	
	$me = Auth::user();

	$log_id = DB::table('lead_logs')->insertGetId([
		'lead_id'=> $lead_id,
		'mod_id'=> $me->id, 'mod_user'=> trim($me->fname.' '.$me->lname),
		'is_auto_gen'=> DB::raw($obj->auto),
		'log_msg'=> $obj->msg, 'log_detail'=> $detail,
	]);
	log_write('Lead Log has been recorded.', ['lead-id'=> $lead_id, 'log-id'=> $log_id, ]);

	return $log_id;
}
/**
* ************** leave a log for leads *****************
* use Illuminate\Support\Facades\Auth; 
*
* @param $obj: object [
*		id: lead ID
*		msg: log message
*		auto (optional): message is generated by system (by default 1)
*		detail (optional): array of detailed changes [field, old, new, old_val (optional, default is old), new_val (optional, default is new)]
*		 -> compare old_val and new_val -> output old, new (by default NULL)
* @return $log_id: log ID (= last insert id)
*************************************************************/
function log_lead_values ($obj) {
	$is_auto = (isset($obj->auto) && $obj->auto === 0)?  0:1;
	$detail = NULL;
	if (isset($obj->detail) && count($obj->detail) >0) {
		$detail .= '<table>';
		foreach ($obj->detail as $row) {
			$old_val = (isset($row->old_val))?  $row->old_val : $row->old;
			$new_val = (isset($row->new_val))?  $row->new_val : $row->new;

			$class_change = ($old_val != $new_val)?  'class="changed"':'';
			$detail .= '<tr '.$class_change.'> <th>'.$row->field.'</th> <td class="old">'.$row->old.'</td> <td class="new">'.$row->new.'</td> </tr>';
		}
		$detail .= '</table>';
	}
	return log_lead((object)[
		'id' => $obj->id, 'msg' => $obj->msg, 'auto' => $is_auto, 'detail' => $detail,
	]);
}
?>