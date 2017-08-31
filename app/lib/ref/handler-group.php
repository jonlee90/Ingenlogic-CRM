<?php
$script_name = 'handler-group';



switch ($act) {
	// **************************************** User Groups ****************************************
	case "user_group_new":
		$err_return = 'user-group';
		ingen_valid_key($_POST['rand'], $_POST['key']);


		// ***** proceed with DB
		if ($cSQL->prepare(" CALL sp_user_addUserGroup (?) ") ===FALSE)
			append_err(" DB ERROR 1 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($r_id = $cSQL->insert_sp( array('s',  $gName) )) ===FALSE)
			append_err(" DB ERROR 1 [$act]: add new user group. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);


		// SUCCESS
		append_log(" User Activity = ".$frmAction.": new user group added (group ID: $r_id). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'New User Group has been created.';
		break;


	case "user_group_update":
		$err_return = 'user-group';
		ingen_valid_key($_POST['groupId'], $_POST['key']);
		$groupId = de_obfs($_POST['groupId']);


		// ***** proceed with DB
		if ($cSQL->prepare(" CALL sp_user_updateUserGroup (?,?) ") ===FALSE)
			append_err(" DB ERROR 2 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if ($r_id = $cSQL->update( array('is',  $groupId, $gName) ) ===FALSE)
			append_err(" DB ERROR 2 [$act]: update user group (group ID: $groupId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);


		// SUCCESS
		append_log(" User Activity = ".$frmAction.": user group updated (group ID: $groupId). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'User Group has been updated.';
		break;


	case "user_group_del":
		$err_return = 'user-group';
		ingen_valid_key($_POST['groupId'], $_POST['key']);
		$groupId = de_obfs($_POST['groupId']);


		// ***** proceed with DB
		if ($cSQL->prepare(" CALL sp_user_delUserGroup (?) ") ===FALSE)
			append_err(" DB ERROR 3 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if ($r_id = $cSQL->update( array('i',  $groupId) ) ===FALSE)
			append_err(" DB ERROR 3 [$act]: user group deleted (group ID: $groupId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);


		// SUCCESS
		append_log(" User Activity = ".$frmAction.": user group deleted (group ID: $groupId). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'User Group has been deleted.';
		break;




	// ***** unassign selected users from the given group *****
	case "user_group_unassign":
		ingen_valid_key($_POST['groupId'], $_POST['key']);
		$groupId = de_obfs($_POST['groupId']);
    $err_return = 'user-group-view?key='.$_POST['key'].'&id='.$_POST['groupId'];
    

		// validate: group ID is a valid number
    if (!is_numeric($groupId) || $groupId < 1)
      append_err(" [$act]: Invalid group ID (group ID: $groupId). ", LOG_FOLDER_ERR, " Invalid User Group selected. ", $err_return);
		// validate: 1+ user selected
    $user_count = count($userId);
    if ($user_count <1)
      append_err(" [$act]: 0 user selected. ", LOG_FOLDER_ERR, " Please select at least 1 user to remove from the Group ", $err_return);
		

		// ***** proceed with DB
    if ($cSQL->prepare(" CALL sp_user_assignGroup (?,?,0) ") ===FALSE)
			append_err_ajax(" DB ERROR 4 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
    foreach ($userId as $r) {
      $r_id = de_obfs($r);
      if ($cSQL->update( array('ii',  $r_id, $groupId) ) ===FALSE)
        append_err_ajax(" DB ERROR 4 [$act]: unassign user from group (user ID: $userId x group ID: $groupId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
      $cSQL->reset_sp();
    }
    $cSQL->close();
            

		// store added: SUCCESS
		append_log(" User Activity = ".$frmAction.": $user_count users unassiged from group (group ID: $groupId). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'Selected Users removed from the User Group.';
		break;


		
		
	default:
		append_err("User Activity = ".$frmAction." : NO action available. ", LOG_FOLDER_ERR, "Invalid action command.");
}
?>