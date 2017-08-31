<?php
// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once("configure.php");
date_default_timezone_set(DEFAULT_TIMEZONE);


// ***** connect to database *****
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PW, DB_NAME);
if ($mysqli->connect_errno)
	die(" Failed to connect to DB: ".$mysqli->connect_error);


// ***** initiate session *****
session_name(SESS_NAME.'_comm');
session_start();

// ***** initiate general functions
include_once('class.uuid.php');
include_once('class.ingen.csql.php');
include_once("ingen.func.php");


// ***** assign session variables
$sess_key = $_SESSION['loginKey']; // UUID
$sess_login_id = de_obfs($_SESSION['sess_login_id']); // user ID is saved obfuscated. de-obfs the ID when assigning to a variable
$sess_login_user = $_SESSION['sess_login_user'];

// reset toast message
if ($_SESSION['toastMsg'] !='') {
	$sess_toast_msg = $_SESSION['toastMsg'];
	$_SESSION['toastMsg'] ='';
	unset($_SESSION['toastMsg']);
}
// reset saved POST variables
if ($_SESSION['tmpPOST'] !='') {
	$sess_temp_post = $_SESSION['tmpPOST'];
	$_SESSION['tmpPOST'] ='';
	unset($_SESSION['tmpPOST']);
}

// remove last ".php" (e.g. "abc.php.php" -> "abc.php",  "def.php" -> "def")
$script_name = preg_replace('/\.php$/','', basename($_SERVER['SCRIPT_NAME']));

$cSQL = new cSQL($mysqli);




// *************** get preapp values ***************
$preapp_time_now = time();
$preapp_today = date('Y-m-d');

$preapp_err_return = 'index';
$preapp_err_msg_db = "There was a database error. Please try again, and if error continues, please contact the administrator for help.";
$preapp_err_msg_system = "The system is mis-configured and was unable to processs your request properly. Please contact the administrator for help.";
$preapp_err_msg_logout = " There was a system error, and you have been logged off. Please try again, and if error continues, please contact the administrator.";
$preapp_err_msg_access = "You are NOT authorized to access the page.";

$preapp_log_prefix = "<span class='err'>[Preapp ERROR]</span> \n";





$preapp_login =($sess_key !='')? 1:0;


// ***** validate login : logout does NOT need to validate for login *****
if ($sess_login_id >0 && $script_name !== 'logout') {
	$preapp_access_lv = POS_LV_MASTER;
	
	if (($cSQL->prepare(" CALL sp_pre_checkLogin (?) ")) ===FALSE)
		sess_reset_err($preapp_log_prefix." > DB ERROR p1-1 : failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_logout);
	if (($rows = $cSQL->select(
			array('fname', 'lname', 'accessLV', 'time_activity','key','ip', 'invalid',
				'accessType'
			),
			array('i', $sess_login_id)
	)) ===FALSE)
		sess_reset_err($preapp_log_prefix." > DB ERROR p1-1 : check login-record. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_logout);

	if (count($rows) >0) {
		$r = $rows[0];

		// if logged-in user has been turned "inactive" OR "deleted"
		if ($r['invalid'] >0)
			sess_reset_err(" User turned inactive/removed by other user with higher access LV. ",
				LOG_FOLDER_INFO, " Your login credential has been modified, and you were automatically logged out. Please login again.");

		// if login-record has key of 'PW.MOD', someone with higher access LV modified pw. force logoff user to re-login
		if ($sess_key != $r['key'] && $r['key'] =='PW.MOD')
			sess_reset_err(" Login credential was updated by other user with higher access LV. ",
				LOG_FOLDER_INFO, " Your login credential has been modified, and you were automatically logged out. Please login again.");

		// check if user has valid key and ip-address (in case logged in 2 different locations)
		if ($sess_key != $r['key'] || $_SERVER['REMOTE_ADDR'] != $r['ip'])
			sess_reset_err("Duplicate Login. The user (user ID: $sess_login_id) has been force-logged out from previous login. New login IP-address: ".$r['ip'],
				LOG_FOLDER_INFO, " You have logged in a different location. You have been automatically logged out. Please login again.");

		/** check if user has been idle too long (login expire)
			*   convert date-time saved in DB (MYSQL_TIMEZONE - may vary depends on Mysql server) to local time-zone (DEFAULT_TIMEZONE)
			*/
		if (convert_mysql_timezone($r['time_activity'], 'U') +LOGIN_EXPIRE_SEC < $preapp_time_now )
			sess_reset_err("Login Session Expired. The user (user ID: $sess_login_id) has been force-logged out. Last activity: ".$r['time_activity'],
				LOG_FOLDER_INFO, " Your session has expired, and you were automatically logged out. Please login again.");
				
			
		// login record validate success - update time of last activity
		if (($cSQL->prepare(" CALL sp_pre_updateActivity (?) ")) ===FALSE)
			sess_reset_err($preapp_log_prefix." > DB ERROR p1-2 : failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_logout);
		if ($cSQL->update( array('i', $sess_login_id) ) ===FALSE)
			sess_reset_err($preapp_log_prefix." > DB ERROR p1-2 : update time of last-activity. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_logout);


		$sess_login_user = $_SESSION['sess_login_user'] = trim($r['fname'].' '.$r['lname']);
		$preapp_access_lv = $r['accessLV'];
		
		// MASTER admins use different database user: reset and re-connect with corresponding db user
		if ($preapp_access_lv >= POS_LV_MASTER) {
			$mysqli->close();
			$mysqli = mysqli_connect(DB_HOST, DB_USER_MASTER, DB_PW_MASTER, DB_NAME);
			if ($mysqli->connect_errno)
				die("Failed to connect to DB: ".$mysqli->connect_error);
			$cSQL = new cSQL($mysqli);
		}
		// END if: logged-in user found from DB
	} else
		sess_reset_err($preapp_log_prefix." > Unable to find login-record from given session (user ID: $sess_login_id x IP-address: ".$_SERVER['REMOTE_ADDR']."). ",
			LOG_FOLDER_ERR, " Your login session was lost, and you were automatically logged out. Please login again.");
			

// END if: logged-in (= $sess_global_id =$_SESSION['sess_global_id'] is set) AND curren-script is NOT logout
} else {
	/** check if page does NOT require login.
	 *   if page requires login, but global-ID is not found,
	 *   show error msg such as: you are not logged in, please login to access the site
	 */
	 // exception list : the following cases do NOT require login
	 switch ($script_name) {
		 case 'logout':
		 	break;

		 // index, forgot-pw, reset-pw, handler should have its own validation for login	 
		 case 'index':
		 case 'forgot-password':
		 case 'reset-password':
		 case 'handler':
		 	break;
		
		 default:
		 	// if not in the list, login-check failed. do not allow access, ALSO clear session
			sess_reset_err($preapp_log_prefix." > Page Access attempt without Login. ", LOG_FOLDER_INFO, "Please login before you can access the site.");
	 }
} // END validate login








// *************** access LV control for each page (pages NOT in the list will be handled separately in the page) ***************
switch ($script_name) {
	case 'user-login':
	case 'state-list':
	case 'log-home':
	case 'log-list':
	case 'log-view':
		if ($preapp_access_lv < POS_LV_MASTER)
			append_err("User has NO access to the page.", LOG_FOLDER_ERR, $preapp_err_msg_access);
		break;

	case 'msg-group':
	case 'user-group':
	case 'user-group-view':
	case 'user-list':
		if ($preapp_access_lv < POS_LV_SUPER)
			append_err("User has NO access to the page.", LOG_FOLDER_ERR, $preapp_err_msg_access);
		break;
}
?>