<?php
$handling_ajax = TRUE;
require('preapp.php');


$act = $_POST['frmAction'];
switch ($act) {
	// ************************* messages *************************

	// ***** swap sort-order for message-group *****
	case "msg_group_reorder":
		ingen_valid_key($_POST['id1'], $_POST['key']);
		$id1 = de_obfs($_POST['id1']);
		$id2 = de_obfs($_POST['id2']);

		if ($id1 >0 && $id2 >0) {
			// proceed to DB
			if ($cSQL->prepare(" CALL sp_msg_swapOrderMsgGroup (?, ?,?) ") ===FALSE)
				append_err_ajax(" DB ERROR m1 @ AJAX [$act]: failed prep. \n".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
			if ($cSQL->update( array('iii',  $sess_login_id,  $id1, $id2) ) ===FALSE)
				append_err_ajax(" DB ERROR m1 @ AJAX [$act]: swap sort-order for message group (group ID1: $id1 <-> ID2: $id2). \n".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);

			$cSQL->close();
		} else
			// SHOULD NOT happen: both ID should be greater than 0
			append_err_ajax(" AJAX [$act]: one (or both) group ID is not valid (group ID1: $id1 <-> ID2: $id2). ", LOG_FOLDER_ERR, $preapp_err_msg_system);


		// SUCCESS
		$json_out = json_encode(array(
			'success'=>1, 'error'=>0
		));
		break;



	// ***** get list of writers matching with given keyword *****
	case "msg_search_writer":
		$keyword = $_POST['val'];
		if (strlen($keyword) <3)
			append_err_ajax(" [$act]: Search keyword is less than 3 letters long. ", LOG_FOLDER_ERR, $preapp_err_msg_access);
		
		if ($cSQL->prepare(" CALL sp_user_searchUser  (?,?) ") ===FALSE)
			append_err_ajax(" DB ERROR m2 @ AJAX [$act]: failed prep. \n".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
		if (($rows = $cSQL->select(
				array('id','email','fname','lname','active','del'),
				array('is', $preapp_access_lv, $keyword)
		)) ===FALSE)
			append_err_ajax(" DB ERROR m2 @ AJAX [$act]: search writer (keyword: $keyword). \n".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);

		$cSQL->close();


		$html_output = '';
		if (count($rows) >0) {
			foreach ($rows as $r) {
				$r_txt = trim($r['fname'].' '.$r['lname']).' ('.$r['email'].')';
				if ($r['del'] >0)
					$r_txt .= ' * Deleted';
				elseif ($r['active'] <1)
					$r_txt .= ' * Inactive';
				$html_output .= '<option value="'.obfs_str($r['id']).'">'.$r_txt.'</option>';
			}
			$json_out = json_encode(array(
				'success'=>1, 'error'=>0,
				'html'=>$html_output
			));
		} else {
			$json_out = json_encode(array(
				'success'=>0, 'error'=>1
			));
		}
		break;



	// ***** remove selected file from message *****
	case "msg_file_del":
		ingen_valid_key($_POST['id'], $_POST['key']);
		$fileId = de_obfs($_POST['id']);
		
		// ***** proceed with DB
    if ($cSQL->prepare(" CALL sp_msg_delFile (?,?,?) ") ===FALSE)
			append_err_ajax(" DB ERROR m3 @ AJAX [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('success'), array('iii',  $preapp_access_lv, $sess_login_id,  $fileId) )) ===FALSE)
			append_err_ajax(" DB ERROR m3 @ AJAX [$act]: delete msg-file (file ID: $fileId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

    // SHOULD NOT happen: user has no access to delete the file
    if (count($rows) <1 || $rows[0]['success'] <1)
			append_err_ajax("[$act]: Unable to delete msg-file (DB record) - not a MASTER-admin OR not a writer of the message (file ID: $fileId). ", LOG_FOLDER_ERR, $preapp_err_msg_system, $err_return);
		$cSQL->close();

		// SUCCESS
		$json_out = json_encode(array(
			'success'=>1, 'error'=>0
		));
		break;


		
	// ************************* users *************************

	// ***** assign user to a user-group *****
	case "user_assign_group":
		if ($preapp_access_lv < POS_LV_SUPER)
			append_err_ajax("[$act]: User has NO access to modify (user ID: $userId). ", LOG_FOLDER_ERR, $preapp_err_msg_access, $err_return);

		ingen_valid_key($_POST['userId'], $_POST['key']);
		$userId = de_obfs($_POST['userId']);
		$groupId = de_obfs($_POST['groupId']);
		$assigned = $_POST['val'];
		

		// ***** proceed with DB
    if ($cSQL->prepare(" CALL sp_user_assignGroup (?,?,?) ") ===FALSE)
			append_err_ajax(" DB ERROR u1 @ AJAX [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if ($cSQL->update( array('iii',  $userId, $groupId, $assigned) ) ===FALSE)
			append_err_ajax(" DB ERROR u1 @ AJAX [$act]: assign user to a user-group (user ID: $userId x group ID: $groupId x assign: $assigned). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

		$cSQL->close();

		// SUCCESS
		$json_out = json_encode(array(
			'success'=>1, 'error'=>0
		));
		break;



	// ***** send email to a user *****
	case "user_send_email":
		ingen_valid_key($_POST['id'], $_POST['key']);
		$userId = de_obfs($_POST['id']);
		

		// verification: subject, email should not be empty
		$mail_subj = trim($_POST['subj']);
		if (strlen($mail_subj) <1)
			append_err_ajax(" [$act]: empty msg subject.", LOG_FOLDER_ERR, "Email Subject is required.");
		$mail_content = trim($_POST['content']);
		if (strlen($mail_content) <1)
			append_err_ajax(" [$act]: empty msg content.", LOG_FOLDER_ERR, "Email Content is required.");

		
		// get sending user info
		if ($cSQL->prepare(" CALL sp_user_getUser (?) ") ===FALSE) 
			append_err_ajax(" DB ERROR u2 @ AJAX [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
		if (($rows = $cSQL->select( array('accessLV', 'loginName', 'email',  'fname', 'lname', 'isActive'), array('i', $sess_login_id) )) ===FALSE)
			append_err_ajax(" DB ERROR u2a @ AJAX [$act]: get user info (user ID: $sess_login_id). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
		if (count($rows) > 0) {
			$from_email = $rows[0]['email'];
		} else
			// SHOULD NOT happen: user NOT found from DB with given ID
			append_err_ajax(" [$act]: user NOT found from given ID (self)", LOG_FOLDER_ERR, $preapp_err_msg_system);
		
		// get target user info
		$cSQL->reset_sp();
		if (($rows = $cSQL->select( array('accessLV', 'loginName', 'email',  'fname', 'lname', 'isActive'), array('i', $userId) )) ===FALSE)
			append_err_ajax(" DB ERROR u2b @ AJAX [$act]: get user info (user ID: $userId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
		if (count($rows) > 0) {
			$r = $rows[0];
			if ($r['isActive'] <1)
				append_err_ajax(" [$act]: user is NOT active (user ID: $userId)", LOG_FOLDER_ERR, "You cannot send email to an inactive user.");

			$to_email = $r['email'];
			$to_name = trim($r['fname'].' '.$r['lname']);
		} else
			// SHOULD NOT happen: user NOT found from DB with given ID
			append_err_ajax(" [$act]: target user NOT found from given ID (user ID: $userId)", LOG_FOLDER_ERR, $preapp_err_msg_system);
			
		$cSQL->close();

		// send email
		$mail_reply_to = array(
			$from_email=>$sess_login_user
		);
		$mail_content = '<p style="margin-bottom:20px">Email from: '.$sess_login_user.' ('.$from_email.')</p>'.$mail_content;

		$to_email = 'sm0523@gmail.com'; // DELETE on LIVE
		/*
		$sent_success = send_Swift_mail($to_email, "1:".$mail_subj, $mail_content, SITE_TITLE, SWIFT_MAIL_USERNAME, $mail_reply_to);
		echo $sent_success? ' 1->OK ':' 1->NO ';
		$sent_success = send_Swift_mail($to_email, "2:".$mail_subj, $mail_content, $sess_login_user, 'test@jeilearning.com');
		echo $sent_success? ' 2->OK ':' 2->NO ';

		die(" >> $to_email, $mail_subj, $mail_content, $sess_login_user, $from_email >> ");
die(" >> $from_email -> $to_email >> ");
		*/

		$sent_success = send_Swift_mail($to_email, $mail_subj, $mail_content, SITE_TITLE, SWIFT_MAIL_USERNAME, $mail_reply_to);
		if ($sent_success !== TRUE)
			append_err_ajax(" [$act]: fail to send email (to-user ID: $userId)", LOG_FOLDER_ERR,
				"The system was unable to send email. Please try again, or if error persists, please contact the administrator.");
				
		// leave log for success email sent
		append_log(" AJAX Activity [$act]: email successfully sent to <$to_name> (ID: $userId). ", LOG_FOLDER_INFO);

		// SUCCESS
		$json_out = json_encode(array(
			'success'=>1, 'error'=>0,
			'targetName'=>$to_name
		));
		break;



		
		
	default:
		$json_out = json_encode(array(
			'success'=>0, 'error'=>1,
			'msg'=> 'Action NOT found.'
		));
}
// ***** END of script: handler-ajax does not output any HTML *****
die($json_out);
?>