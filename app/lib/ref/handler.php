<?php
require("preapp.php");
$script_name = 'handler';

// ***** set variables in $_POST (e.g. $var = $_POST['var']), trim if NOT number *****
if (count($_POST) >0) {
	foreach ($_POST as $k=>$v)
		$$k = (is_string($v))?  trim($v) : $v;

	$_SESSION['tmpPOST'] = $_POST;
}
// ***** leave LOG for user's form activity *****
append_log("User Activity = ".$frmAction, LOG_FOLDER_INFO);



// ***** default redirect link is index page
$handler_link = ($return_url)? $return_url: "index";




$act = $_POST['frmAction'];
switch ($act) {
	// *************** users + login ***************
	case "cpanel_login":
	case "cpanel_req_reset":
	case "cpanel_reset_pw":
	case "user_new":
	case "user_update":
	case "user_pw":
	case "user_del":
		require('handler-user.php');
		break;
	
	case "user_group_new":
	case "user_group_update":
	case "user_group_del":
	case "user_group_unassign":
		require('handler-group.php');
		break;

	// *************** messages ***************
	case "msg_new":
	case "msg_update":
	case "msg_del":
	case "msg_reply_new":
	case "msg_reply_update":
	case "msg_reply_del":
	
	case "msg_group_new":
	case "msg_group_update":
	case "msg_group_del":
		require('handler-msg.php');
		break;

		
		
	default:
		append_err("User Activity = ".$frmAction." : NO action available. ", LOG_FOLDER_ERR, "Invalid action command.");
}
/**
 * remove temporarily saved $_POST values
 *  if there was an error, saved values would be used on redirected page
 */
$_SESSION['tmpPOST'] ='';
unset($_SESSION["tmpPOST"]);


/**
 * redirect to $handler_link
 *  any message to show on redirected page should be saved in $handler_msg
 */
relocate_pg($handler_link, $handler_msg);	
die();
?>