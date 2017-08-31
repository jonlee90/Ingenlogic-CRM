<?php
include_once('class.phpass.php');

$script_name = 'handler-user';



switch ($act) {
	// ************************************************* public: user login and reset *************************************************

	// ***** validate and login user *****
	case "cpanel_login":
		if ($cSQL->prepare(" CALL sp_user_getUserLogin (?) ") ===FALSE)
			append_err(" DB ERROR l1-1 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
		if (($rows = $cSQL->select( array('id','pw', 'fname','lname', 'invalid'), array('s', $loginName) )) ===FALSE)
			append_err(" DB ERROR l1-1 [$act]: get login credential. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
			
			
		if (count($rows) >0) {
			$r = $rows[0];
			// invalid user (= inactive OR deleted)
			if ($r['invalid'] >0)
				append_err(" Login Failed: user inactive/removed (login name attempted: $loginName). ", LOG_FOLDER_INFO,
					'Login Failed. Your login name and/or password was not matching.', $preapp_err_return);

			$phpass = new PasswordHash(BCRYPT_ITERATION_COUNT, FALSE);
			// incorrect password
			if ($phpass->CheckPassword($loginPW, $r['pw']) ===FALSE)
				append_err(" Login Failed due to Wrong Password (login name attempted: $loginName). ", LOG_FOLDER_INFO,
					'Login Failed. Your login name and/or password was not matching.', $preapp_err_return);


			// login: SUCCESS
			$userId = $r['id'];
			$login_key = UUID::v4();

			if ($cSQL->prepare(" CALL sp_user_loginUser (?, ?,?) ") ===FALSE)
				append_err(" DB ERROR l1-2 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
			if ($cSQL->update( array('iss',  $userId, $login_key, $_SERVER['REMOTE_ADDR']) ) ===FALSE)
				append_err(" DB ERROR l1-2 [$act]: leave a record of last-login AND create login record (user ID: $userId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
			
			$cSQL->close();
			
			/**
			 * save session variables from logged-in user info
			 *  !! important: assign login key first, since 'obfs_str' function requires loginKey
			 *  then save obfuscated ID in session 
			 **/
			// 
			$_SESSION['loginKey'] = $login_key;
			$_SESSION['sess_login_id'] = obfs_str($userId); 
			$_SESSION['sess_login_user'] = trim($r['fname'].' '.$r['lname']);

			// leave a log
			append_log(" User Activity = ".$frmAction." : user has logged-in (login name: $loginName). ", LOG_FOLDER_INFO);
			$handler_link = 'index';
			$handler_msg = 'Welcome to : '.SITE_TITLE.' Community Site';
				
		} else
			// user not found from DB
			append_err(" [$act]: Login Failed due to User NOT found (login name attempted: $loginName). ", LOG_FOLDER_INFO,
				'Login Failed. Your login name and/or password was not matching.');
		break;



	// ***** request to reset user password *****
	case "cpanel_req_reset":
		$err_return = 'forgot-password';
		$req_key = str_replace('-','', UUID::v4());
		
		if ($cSQL->prepare(" CALL sp_user_requestPasswordReset (?,?) ") ===FALSE)
			append_err(" DB ERROR l2 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('email','key'), array('ss', $loginName, $req_key) )) ===FALSE)
			append_err(" DB ERROR l2a [$act]: request to reset user password (login name attempted: $loginName). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
			
		if (count($rows) >0) {
			$email_to = $rows[0]['email'];
			$prev_key = $rows[0]['key'];

			// if previous key is not NULL, generated key is already in use: re-generate the key until key is unique, limit retry count to 100
			for ($i=0; $i<100 && $prev_key !== NULL; $i++) {
				$req_key = str_replace('-','', UUID::v4());

				$cSQL->reset_sp();
				if (($rows2 = $cSQL->select( array('email','key'), array('ss', $loginName, $req_key) )) ===FALSE)
					append_err(" DB ERROR l2b [$act]: re-request password reset with a new key (login name attempted: $loginName). \n ".$cSQL->error,
						LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
				if (count($rows2) >0)
					$prev_key = $rows2[0]['key'];
				else
					// SHOULD NOT happen: since initial request was successful, count(rows2) should always have result
					report_err(" [$act]: 0 result from re-request.", LOG_FOLDER_ERR, $preapp_err_msg_system, $err_return);
			} // END for: key is already in use

			// if unique key was NOT generated after 100 tries, throw an error
			if ($prev_key !== NULL)
				report_err(" [$act]: unable to generate unique key after 100 tries.", LOG_FOLDER_ERR, "The system was unable to generate a key. Please try again.", $err_return);
			

			// send email to the user with link to reset password
			$email_subj = 'Password Reset Instructions';
			$email_msg = '
				<p style="margin:0 0 10px">Dear '.SITE_TITLE.' Control Panel User,</p>
				<p style="margin:0">You have requested a reset for your user account password.</p>
				<p style="margin:0">To continue with the password reset, go to:</p>
				<p style="margin:0"><br/></p>
				<p style="margin:0">http://'.SITE_ADDRESS.BASE_FOLDER.'/reset-password?key='.urlencode($req_key).'</p>
				<p style="margin:0"><br/></p>
				<p style="margin:0">If you did not make or authorize this request, please contact our web adminstrator immediately.</p>
				<p style="margin:10px 0"></p>
				<p style="margin:0">Thank you,</p>
				<p style="margin:0">'.SITE_TITLE.' Support</p>
			';

			$email_success = send_Swift_mail ($email_to, $email_subj, $email_msg);
			if ($email_success === FALSE)
				report_err('Our system was unable to send out message. Please try again, or contact the web administrator.');
				
		} else
			// user not found from DB
			append_err(" [$act]: User NOT found from entered login name (login name attempted: $loginName). ", LOG_FOLDER_INFO,
				'We were unable to find a user from the entered login name. Please double check your login name.', $err_return);
			
		$cSQL->close();

		// leave a log
		append_log(" User Activity = ".$frmAction.": user requested to reset password (login name: $loginName). ", LOG_FOLDER_INFO);
		$handler_link = 'index';
		$handler_msg = 'We have sent a link to your email on how to reset your password. Please check your email to proceed further.';
		break;



	// ***** reset user password (forgotten password reset) *****
	case "cpanel_reset_pw":
		$err_return = 'reset-password?key='.$resetKey;

		// validate: the password should be a valid password and confirmed
		if (validInput($pw, 'pw') ===FALSE)
			append_err(" [$act]: invalid password entered. ", LOG_FOLDER_ERR,
				"Your entered password is invalid. The password should be: Minimum 6 letters long with at least 1 Alphabet, 1 Number, and 1 Special Character (_$@$!%*#?&).", $err_return);
		if ($pw != $pw2)
			append_err(" [$act]: password NOT confirmed (pw <> pw2). ", LOG_FOLDER_ERR,	"Please confirm your password.", $err_return);
			

		$phpass = new PasswordHash(BCRYPT_ITERATION_COUNT, FALSE);
		$pwHash = $phpass->HashPassword($pw);

		// ***** proceed with DB: reset password + remove reset request
		if ($cSQL->prepare(" CALL sp_user_resetForgottenPW (?,?) ") ===FALSE)
			append_err(" DB ERROR l3 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('id'), array('ss', $resetKey, $pwHash) )) ===FALSE)
			append_err(" DB ERROR l3 [$act]: reset forgotten password (key: $resetKey). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

		if (count($rows) >0) {
			$userId = $rows[0]['id'];
			if ($userId >0) {
				// SUCCESS
				append_log(" User Activity = ".$frmAction.": user reset password (user ID: $userId). ", LOG_FOLDER_INFO);
				$handler_link = 'index';
				$handler_msg = 'Your Password has been reset. Please login with the New Password.';
				break;
			}
		}
		// at this point, reset password failed: user not found using the key OR key is invalid/expired
		append_err(" [$act]: reset request NOT found (key: $resetKey). ", LOG_FOLDER_ERR,
			"The attempted request has been expired. Please obtain a new link.", $err_return);
		break;




	// ************************************************* logged-in: user related *************************************************

	// ***** add new user *****
	case "user_new":
		if ($preapp_access_lv < POS_LV_SUPER)
			append_err("[$act]: User has NO access to the page ", LOG_FOLDER_ERR, $preapp_err_msg_access);

    $err_return = 'user-edit?new=1';

		// ***** validate dupe email
		if ($cSQL->prepare(" CALL sp_user_checkDupe_byEmail (?, NULL) ") ===FALSE)
			append_err(" DB ERROR 1-1 [$act] : failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('id'), array('s', $email) )) ===FALSE)
			append_err(" DB ERROR 1-1 [$act] : check dupe email address (email: $email). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

		if (count($rows) >0)
			append_err(" [$act]: Duplicate Email Address: ".$email, LOG_FOLDER_ERR, " Duplicate Email Address: ".$email, $err_return);

		// ***** validate dupe login name
		if ($cSQL->prepare(" CALL sp_user_checkDupe_byLoginName (?, NULL) ") ===FALSE)
			append_err(" DB ERROR 1-2 [$act] : failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('id'), array('s', $loginName) )) ===FALSE)
			append_err(" DB ERROR 1-2 [$act] : check dupe login username (login name: $loginName). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

		if (count($rows) >0)
			append_err(" [$act]: Entered login name is already in use: ".$loginName, LOG_FOLDER_ERR, " Entered login name is already in use: ".$loginName, $err_return);



		// ***** proceed with creating a new user
		$phpass = new PasswordHash(BCRYPT_ITERATION_COUNT, FALSE);
		$pwHash = $phpass->HashPassword($pw);

		$p_access_lv = ($accessLV >0)? $accessLV : 0;
		
		if ($cSQL->prepare(" CALL sp_user_addUser (?,?,  ?,?,?,?, ?,?) ") ===FALSE)
			append_err(" DB ERROR 1-3 [$act] : failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($r_id = $cSQL->insert_sp(
				array('is'.'isss'.'ss',  $sess_login_id, $sess_login_user,  $p_access_lv, $loginName, $email, $pwHash,  $fname, $lname)
    )) ===FALSE)
			append_err(" DB ERROR 1-3 [$act] : add new user. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
            

		// user added: SUCCESS
		append_log(" User Activity = ".$frmAction." : new user created (user ID: $r_id). ", LOG_FOLDER_INFO);
		$handler_link = 'user-edit?'.ingen_query_id($r_id);
		$handler_msg = 'New User has been created.';
		break;




	// ***** update user *****
	case "user_update":
		ingen_valid_key($_POST['userId'], $_POST['key']);
		$uID = de_obfs($_POST['userId']);
		$err_return = 'user-edit?'.ingen_query_id($uID);

		// regardless of accessible function, user can self-update
		if ($uID != $sess_login_id && $preapp_access_lv < POS_LV_SUPER)
			append_err("[$act]: User has NO access to the page ", LOG_FOLDER_ERR, $preapp_err_msg_access);

		// ***** validate: check if currently logged-in user is authorized to make changes on target user: lv=99 OR self update OR lv > target-lv
		if (($cSQL->prepare(" CALL sp_user_checkAccess (?,?) ")) ===FALSE)
			append_err(" DB ERROR 2-1 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('id'), array('ii', $uID, $sess_login_id) )) ===FALSE)
			append_err(" DB ERROR 2-1 [$act]: check if logged-in user has access to make changes. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (count($rows) <1)
			append_err(" User have NO access to modify the user (ID: $uID). ", LOG_FOLDER_ERR, " You do NOT have access to modify the user. ", $err_return);

		// ***** validate dupe email
		if (($cSQL->prepare(" CALL sp_user_checkDupe_byEmail (?,?) ")) ===FALSE)
			append_err(" DB ERROR 2-2 [$act] : failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('id'), array('si',  $email, $uID) )) ===FALSE)
			append_err(" DB ERROR 2-2 [$act] : check dupe email address (email: $email). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (count($rows) >0)
			append_err(" [$act]: Duplicate Email Address: ".$email, LOG_FOLDER_ERR, " Duplicate Email Address: ".$email, $err_return);

		// ***** validate dupe login name
		if (($cSQL->prepare(" CALL sp_user_checkDupe_byLoginName (?,?) ")) ===FALSE)
			append_err(" DB ERROR 2-3 [$act] : failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('id'), array('si',  $loginName, $uID) )) ===FALSE)
			append_err(" DB ERROR 2-3 [$act] : check dupe login username (login name: $loginName). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

		if (count($rows) >0)
			append_err(" [$act]: Entered login name is already in use: ".$loginName, LOG_FOLDER_ERR, "Entered login name is already in use: ".$loginName, $err_return);


		$p_active = isset($_POST['isActive'])?  $isActive : NULL;
		$p_lv = isset($_POST['accessLV'])?  $accessLV : NULL;

		// ***** proceed with updating user
		if (($cSQL->prepare(" CALL sp_user_updateUser (?,?,?, ?,?,?,?, ?,?) ")) ===FALSE)
			append_err(" DB ERROR 2-4 [$act] : failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if ($cSQL->update( array('iis'.'ssssii',  $uID, $sess_login_id, $sess_login_user,  $loginName, $email, $fname, $lname,  $isActive, $p_lv) ) ===FALSE)
			append_err(" DB ERROR 2-4 [$act] : update user (user ID: $uID). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
			

		// update SUCCESS
		append_log(" User Activity = ".$frmAction." : user updated (user ID: $uID). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'User has been updated.';
		break;




	// ***** update user-password *****
	case "user_pw":
		ingen_valid_key($_POST['userId'], $_POST['key']);
		$uID = de_obfs($_POST['userId']);
		$err_return = 'user-edit?'.ingen_query_id($uID);

		// regardless of accessible function, user can self-update
		if ($uID != $sess_login_id && $preapp_access_lv < POS_LV_SUPER)
			append_err("[$act]: User has NO access to the page ", LOG_FOLDER_ERR, $preapp_err_msg_access);


		// ***** get user access LV and make sure currently logged-in user is authorized to make changes
		if (($cSQL->prepare(" CALL sp_user_checkAccess (?,?) ")) ===FALSE)
			append_err(" DB ERROR 3-1 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('id'), array('ii', $uID, $sess_login_id) )) ===FALSE)
			append_err(" DB ERROR 3-1 [$act]: check if logged-in user has access to make changes. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

		if (count($rows) <1)
			append_err(" [$act]: User has NO access to modify the user (ID: $uID). ", LOG_FOLDER_ERR, " You do NOT have access to modify the user. ", $err_return);
			

		$phpass = new PasswordHash(BCRYPT_ITERATION_COUNT, FALSE);
		$pwHash = $phpass->HashPassword($pw);

		// ***** proceed with updating user - sp_user_updateUserPW: if not self-update (by higher access LV), modify login-record to force the user to login again with new pw
		if (($cSQL->prepare(" CALL sp_user_updateUserPW (?,?,?,?) ")) ===FALSE)
			append_err(" DB ERROR 3-2 [$act] : failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if ($cSQL->update( array('iiss',  $uID, $sess_login_id, $sess_login_user,  $pwHash) ) ===FALSE)
			append_err(" DB ERROR 3-2 [$act] : update user password (user ID: $uID). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
			

		// update SUCCESS
		append_log(" User Activity = ".$frmAction." : user updated password (user ID: $uID). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'User Password has been updated.';
		break;




	// ***** delete user *****
	case "user_del":
		if ($preapp_access_lv < POS_LV_SUPER)
			append_err("User has NO access to the page ", LOG_FOLDER_ERR, $preapp_err_msg_access);

		$err_return = 'user-list';
		ingen_valid_key($_POST['userId'], $_POST['key']);
		$userId = de_obfs($_POST['userId']);


		// ***** get user access LV and make sure currently logged-in user is authorized to make changes
		if (($cSQL->prepare(" CALL sp_user_checkAccess (?,?) ")) ===FALSE)
			append_err(" DB ERROR 4-1 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('id'), array('ii', $userId, $sess_login_id) )) ===FALSE)
			append_err(" DB ERROR 4-1 [$act]: check if logged-in user has access to make changes. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

		if (count($rows) <1)
			append_err(" [$act]: User has NO access to delete the user (ID: $userId). ", LOG_FOLDER_ERR, " You do NOT have access to delete the user. ", $err_return);
			

		// ***** proceed with action
		if (($cSQL->prepare(" CALL sp_user_delUser (?,?,?) ")) ===FALSE)
			append_err(" DB ERROR 4-2 [$act] : failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if ($cSQL->update( array('iis',  $userId, $sess_login_id, $sess_login_user) ) ===FALSE)
			append_err(" DB ERROR 4-2 [$act] : remove (mark deleted) user (user ID: $userId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);


		// remove SUCCESS
		append_log(" User Activity = ".$frmAction." : user removed (user ID: $userId). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'User has been deleted.';
		break;
		

		
		
	default:
		append_err("User Activity = ".$frmAction." : NO action available. ", LOG_FOLDER_ERR, "Invalid action command.");
}
?>