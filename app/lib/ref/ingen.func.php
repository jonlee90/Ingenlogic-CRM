<?php
/******************* returns modified links ******************
*
* arguments:
* $link - original link
*
* returns:
* $link_mod - modified link
*
* returns link renamed with .php/.html extension removed
*************************************************************/
function ingen_link ($link) {
	list($pg_name, $query) = explode("?", $link);
	
	$len = strlen($pg_name);
	//***** remove .php extension
	if (substr($pg_name,$len-4) == ".php")
		$pg_rename = substr($pg_name, 0,$len-4);
	//***** remove .html extension
	elseif (substr($pg_name,$strlen-5) == ".html")
		$pg_rename = substr($pg_name, 0,$len-5);
	else
		$pg_rename = $pg_name;
		
	// exceptions
	switch ($pg_rename) {
		case "/":
			$link_mod = "index";
			break;
		case "handler":
		case "handler-ajax":
		case "handler-overlay":
			$link_mod = 'code/'.$pg_rename;
			break;
		default:
			$link_mod = $pg_rename;
	}
	$link_mod .= '.php';

	if (strlen($query) >0)
		$link_mod .='?'.$query;
		
	if (BASE_FOLDER == '/')
		return BASE_FOLDER.$link_mod;
	return BASE_FOLDER.'/'.$link_mod;
}
/***************** return query for ID combined with key based on the ID  ****************
*
* return $id as "id=&key=" format
*************************************************************/
function ingen_query_id($id) {
	return 'key='.gen_key($id).'&id='.obfs_str($id);
}
/***************** validate obfsucated value with key ****************
*
* the value is obfuscated, and if failed to validate the value, save to log and output a message
* if validation was performed on AJAX call, output to JSON instead via 'append_err_ajax'
*************************************************************/
function ingen_valid_key($obfs_val, $key) {
	global $handling_ajax;

  $v = de_obfs($obfs_val);
  if (valid_key($key, $v) ===FALSE) {
		$log_msg = " Invalid key x value combination (key: $key x value: $v x login key: ".$_SESSION['loginKey'].")";
		if ($handling_ajax)
			append_err_ajax($log_msg, LOG_FOLDER_ERR, "The system was unable to validate your session.");
		else
			append_err($log_msg, LOG_FOLDER_ERR,
				'<p>The system was unable to validate your session. Since the session has reset, the Navigation Back-button may not work as expected.</p>
				<p>Otherwise, please try again, and if error continues, please contact the administrator.</p>');
	}
}

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




/***************** generate 2-layered key  ****************
*
* layer 1 key using SESSION key + layer 2 key using config key
*  requires: UUID class, $_SESSION['loginKey']
*************************************************************/
function gen_key ($val) {
	$sess_key = $_SESSION['loginKey'];
	return UUID::v3(UUID::v3($sess_key, $val), SITE_UUID_KEY);
}
/***************** validate value with UUID key  ****************
*
* compare given value with SESSION-saved key
*  requires: UUID class, $_SESSION['loginKey']
*  also need fixed key, SITE_UUID_KEY, defined in config for 2-layer key protection
*  = need to know both SESSION and config key
*************************************************************/
function valid_key ($key, $val) {
	if ($key =='' || $val =='')
		return FALSE;
	
	$sess_key = $_SESSION['loginKey'];
	
	// double-layered key protection
	return ($key === UUID::v3(UUID::v3($sess_key, $val), SITE_UUID_KEY));
}
/***************** obfuscate string *****************
*
* obfuscate raw string -> NOT for security reason : just to make everyday user hard to guess the ID
*  use XOR of ascii code to obfuscate + base64-encode (remove possible whitespaces)
*  requires: $_SESSION['loginKey'] (will be used as key)
*************************************************************/
function obfs_str ($str) {
	return base64_encode( xor_ascii($str, $_SESSION['loginKey']) );
}
/***************** decode obfuscated string *****************
*
* decode obfuscated string = also use same XOR method to de-obfuscate
*  the obfuscted string should be base64-encoded
*  mark FALSE to $encode to correctly shift to left
*  requires: $_SESSION['loginKey'] (will be used as key)
*************************************************************/
function de_obfs ($str) {
	return trim( xor_ascii(base64_decode($str), $_SESSION['loginKey'], FALSE) );
}
/***************** XOR 2 strings using ASCII representation of each character *****************
*
* helper functon for obfuscating string
*  uses ascii code for "space" to match the string length : so that it can be "trim"med when decoded
*  shift is used to handle XOR-ing 0 (which will not change the ascii value and end up trimming when decoded)
*  shift-right by 5 applied before XOR on encode, shift-left by 5 applied after XOR on decode (default is encode)
*************************************************************/
function xor_ascii ($str1, $str2, $encode =TRUE) {
	$str1 = ''.$str1;
	$str2 = ''.$str2;
	
    $len1 = strlen($str1);
    $len2 = strlen($str2);
    $n = ($len1 < $len2)? $len2 : $len1;
    
    $xor ='';
    for ($i=0; $i<$n; $i++) {
		$asc1 =($str1[$i] !='')? ord($str1[$i]) : 32;
		$asc2 =($str2[$i] !='')? ord($str2[$i]) : 32;
		$xor .= ($encode)? chr($asc1 +5 ^ $asc2 +5) : chr(($asc1 ^ $asc2 +5) -5);
	}
	return $xor;
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



/***************** replace quote to html entity *****************
*
* replace " to &quot;
*************************************************************/
function quot_html ($str) {
	return str_replace("\"","&quot;", $str);
}
/***************** relocate (with message) *****************
*
* relocate to designated page
*  optional: if message exists, output message
*************************************************************/
function relocate_pg ($link, $msg ='') {
	if ($msg)
		$_SESSION["toastMsg"] = '<span class="info">'.$msg.'</span>';
	header("Location: ".ingen_link($link));
	die();
}
/***************** report error with message *****************
*
* save message to SESSION for output, relocate to page
*************************************************************/
function report_err ($msg, $link = "index.php") {
	$_SESSION["toastMsg"] = '<span class="err">'.$msg.'</span>';
	header("Location: ".ingen_link($link));
	die();
}
/***************** append msg to log ****************
*
* log to file - especially in case error occurred where msg cannot be displayed
*************************************************************/
function append_log($msg, $path="") {
	global $script_name;

	$uri = $script_name;
	if ($_SERVER['QUERY_STRING'])
		$uri = $script_name.'?'.$_SERVER['QUERY_STRING'];
	
	//***** if there is login-ID saved in session, mark on the log-message
	$log_header ='<p>'.date("Y-m-d h:i:s A").' ['.$_SERVER['REMOTE_ADDR'].']: <span>@ '.$uri.':</span>';
	if ($_SESSION["sess_login_user"])
		$log_header .= "<span>User: ".$_SESSION["sess_login_user"].'</span>';
	if ($_SESSION["sess_login_id"])
		$log_header .= "<span>User ID: ".de_obfs($_SESSION["sess_login_id"]).'</span>';
	$log_header .='</p>';
	
		
	$abs_log_path = $_SERVER['DOCUMENT_ROOT'].BASE_FOLDER.'/log'.$path;
	if (!file_exists($abs_log_path))
		mkdir($abs_log_path, 0700, true);
	$f = fopen($abs_log_path.'/'.date("Ymd").".txt", "a+");
	
	date_default_timezone_set(DEFAULT_TIMEZONE);
	fwrite($f, '<div>'.$log_header.'<p>'.$msg.'</p></div>'."\n");
	fclose($f);
}
/***************** append msg to log and show error to the customer ****************
*
* log to file - to separate message for admin and for customer
*************************************************************/
function append_err($append_msg, $path="", $err_msg="", $return_url ="index.php") {
	if ($err_msg =='')
		$err_msg = $append_msg;
	append_log ('<span class="err">[ERROR]</span> '.$append_msg, $path);
	report_err ($err_msg, $return_url);
}
/***************** AJAX error: append msg to log and terminate script with failure msg  ****************
*
* log to file - to separate message for admin and for customer
* after logging, output JSON data with error message
*************************************************************/
function append_err_ajax($append_msg, $path="", $err_msg) {
	append_log ('<span class="err">[ERROR]</span> '.$append_msg, $path);
	$json_out = json_encode(array(
		'success'=>0, 'error'=>1,
		'msg'=> $err_msg
	));
	die($json_out);
}
/******************* append_err + session reset ******************
*
* same as append_err, but also reset session (usually for force-logout)
*************************************************************/
function sess_reset_err($append_msg, $path="", $err_msg, $return_url = "index.php") {
	global $handling_ajax;

	// append to log first (before session resets), then reset session = clear all values in session
	append_log($append_msg, $path);
	$_SESSION = array();

	if ($handling_ajax === TRUE) {
		/**
		 *  if function call was originated from AJAX call:
		 *   instead of redirect, attach special string that would trigger redirecting the user to logout via javascript
		 *   $err_msg should be saved, so the proper message woud show up when redirected.
		 */
		$_SESSION["toastMsg"] = '<span class="err">'.$err_msg.'</span>';
		$json_out = json_encode(array(
			'success'=>0, 'error'=>1,
			'msg'=> "[FORCELOGOUT]".$err_msg
		));
		die($json_out);
	}
	report_err($err_msg, $return_url);
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
?>