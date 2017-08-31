<?php
$script_name = 'handler-msg';



switch ($act) {
	// **************************************** Message Groups ****************************************
	case "msg_group_new":
		$err_return = 'msg-group';
		ingen_valid_key($_POST['rand'], $_POST['key']);


		// ***** proceed with DB
		if ($cSQL->prepare(" CALL sp_msg_addMsgGroup (?) ") ===FALSE)
			append_err(" DB ERROR g1 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($r_id = $cSQL->insert_sp( array('s',  $gName) )) ===FALSE)
			append_err(" DB ERROR g1 [$act]: add new message group. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);


		// SUCCESS
		append_log(" User Activity = ".$frmAction.": new message group added (group ID: $r_id). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'New Message Group has been added.';
		break;


	case "msg_group_update":
		$err_return = 'msg-group';
		ingen_valid_key($_POST['groupId'], $_POST['key']);
		$groupId = de_obfs($_POST['groupId']);


		// ***** proceed with DB
		if ($cSQL->prepare(" CALL sp_msg_updateMsgGroup (?,?) ") ===FALSE)
			append_err(" DB ERROR g2 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if ($r_id = $cSQL->update( array('is',  $groupId, $gName) ) ===FALSE)
			append_err(" DB ERROR g2 [$act]: update message group (group ID: $groupId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);


		// SUCCESS
		append_log(" User Activity = ".$frmAction.": message group updated (group ID: $groupId). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'Message Group has been updated.';
		break;


	case "msg_group_del":
		$err_return = 'msg-group';
		ingen_valid_key($_POST['groupId'], $_POST['key']);
		$groupId = de_obfs($_POST['groupId']);


		// ***** proceed with DB
		if ($cSQL->prepare(" CALL sp_msg_delMsgGroup (?) ") ===FALSE)
			append_err(" DB ERROR g3 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if ($r_id = $cSQL->update( array('i',  $groupId) ) ===FALSE)
			append_err(" DB ERROR g3 [$act]: message group deleted (group ID: $groupId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);


		// SUCCESS
		append_log(" User Activity = ".$frmAction.": message group deleted (group ID: $groupId). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'Message Group has been deleted.';
		break;




	// **************************************** Messages ****************************************

	// ***** add new message *****
	case "msg_new":
		ingen_valid_key($_POST['rand'], $_POST['key']);
    $err_return = 'msg-edit?new=1';
		$groupId = de_obfs($_POST['groupId']);
    

		// validate: group ID is a valid number
    if (!is_numeric($groupId) || $groupId < 1)
      append_err(" [$act]: Invalid group ID (group ID: $groupId). ", LOG_FOLDER_ERR, " Invalid Message Group selected. ", $err_return);
		// validate: message content is NOT empty
    if ($msgContent =='')
      append_err(" [$act]: Message has empty content. ", LOG_FOLDER_ERR, " Message content is required. ", $err_return);
      
    // validate: check file and link with message on success
		$f = $_FILES['msgFile'];
		if (count($f) >0 && $f['size'] >0 && $f['name'] !='') {
      if ($f['error'] ===0) {
        $f_name = $f['name'];
        $f_size = $f['size'];
        if ($f_size > LIMIT_IMG_BYTE)
          append_err(" [$act]: File size too big ".$f_size, LOG_FOLDER_ERR, " Image size over 5 MB. ", $err_return);
        $f_type = get_type_from_mime($f['type'], $f_name);
      } else
        append_err(" [$act]: Error on File Upload: ".print_r($f, true), LOG_FOLDER_ERR, " There was an error on uploaded File(s). ", $err_return);
        
      // save file information (to be handled after update-success)
      $f_success = array('name'=>$f_name, 'type'=>$f_type, 'tmp'=>$f['tmp_name']);
    }

		$p_active = ($isActive ===NULL)?  1 : $isActive;
		$p_notice = ($isNotice ===NULL)?  0 : $isNotice;
		$p_open = ($isOpen >0)?  1:0;
		

		// ***** proceed with DB
		if ($cSQL->prepare(" CALL sp_msg_addMsg (?,?, ?,0,?,?,?, ?,?,?) ") ===FALSE)
			append_err(" DB ERROR 1-1 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($r_id = $cSQL->insert_sp(
				array('is'.'iiiissi',  $sess_login_id, $sess_login_user,  $groupId, $sess_login_id, $p_notice, $p_open,  $msgTitle, $msgContent, $p_active)
    )) ===FALSE)
			append_err(" DB ERROR 1-1 [$act]: add new msg. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		$msgId = $r_id;

		// if user is SUPER+ user: set msg x user-group
		if ($preapp_access_lv >= POS_LV_SUPER) {
			$n = count($userGroupId);
			if ($n >0) {
				if ($cSQL->prepare(" CALL sp_msg_addUserGroup (?,?) ") ===FALSE)
					append_err(" DB ERROR 1-2 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
				foreach ($userGroupId as $r) {
					$r_id = de_obfs($r);
					if ($cSQL->update( array('ii',  $msgId, $r_id) ) ===FALSE)
						append_err(" DB ERROR 1-2 [$act]: add msg x user-group (msg ID: $msgId x group ID: $r_id). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
					$cSQL->reset_sp();
				}
			}
		} // END if: SUPER+ user


    // if file was uploaded successfully, create DB record for file
    if (count($f_success) >0) {
      $tmp_url = $f_success['tmp'];
      $f_key = str_replace('-','', UUID::v3($sess_key, basename($tmp_url)));

      if ($cSQL->prepare(" CALL sp_msg_addFile (?,?,?,?) ") ===FALSE)
        append_err(" DB ERROR 1-3 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);     
      if (($r_id = $cSQL->insert_sp( array('sssi',  $f_success['name'], $f_success['type'], $f_key, $msgId) )) ===FALSE)
        append_err(" DB ERROR 1-3 [$act]: add new file. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
      $file_id = $r_id;

      $abs_f_path = $_SERVER['DOCUMENT_ROOT'].BASE_FOLDER.'/files/db/'.$file_id.'/'.$f_key;
      if (!file_exists($abs_f_path))
        mkdir($abs_f_path, 0755, true);
      move_uploaded_file($tmp_url, $abs_f_path.'/'.$f_success['name']);
    }
    $cSQL->close();
            

		// store added: SUCCESS
		append_log(" User Activity = ".$frmAction." : new msg created (msg ID: $msgId). ", LOG_FOLDER_INFO);
		$handler_link = 'msg-edit?'.ingen_query_id($msgId);
		$handler_msg = 'New Message has been created.';
		break;




	// ***** update forum message *****
	case "msg_update":
		ingen_valid_key($_POST['msgId'], $_POST['key']);
		$msgId = de_obfs($_POST['msgId']);
		$err_return = 'msg-edit?'.ingen_query_id($msgId);
		$groupId = de_obfs($_POST['groupId']);
    

		// validate: group ID is a valid number
    if (!is_numeric($groupId) || $groupId < 1)
      append_err(" [$act]: Invalid group ID (group ID: $groupId). ", LOG_FOLDER_ERR, " Invalid Message Group selected. ", $err_return);
		// validate: message content is NOT empty
    if ($msgContent =='')
      append_err(" [$act]: Message has empty content. ", LOG_FOLDER_ERR, " Message content is required. ", $err_return);
      
    // validate: check file and link with message on success
		$f = $_FILES['msgFile'];
		if (count($f) >0 && $f['size'] >0 && $f['name'] !='') {
      if ($f['error'] ===0) {
        $f_name = $f['name'];
        $f_size = $f['size'];
        if ($f_size > LIMIT_IMG_BYTE)
          append_err(" [$act]: File size too big ".$f_size, LOG_FOLDER_ERR, " Image size over 5 MB. ", $err_return);
        $f_type = get_type_from_mime($f['type'], $f_name);
      } else
        append_err(" [$act]: Error on File Upload: ".print_r($f, true), LOG_FOLDER_ERR, " There was an error on uploaded File(s). ", $err_return);
        
      // save file information (to be handled after update-success)
      $f_success = array('name'=>$f_name, 'type'=>$f_type, 'tmp'=>$f['tmp_name']);
    }

    
		$p_active = ($isActive ===NULL)?  NULL : $isActive;
		$p_notice = ($isNotice ===NULL)?  NULL : $isNotice;
		// if user is SUPER+ user: set isOpen 1 or 0
		$p_open = NULL;
		if ($preapp_access_lv >= POS_LV_SUPER)
			$p_open = ($isOpen >0)?  1:0;


		// ***** proceed with DB
    if ($cSQL->prepare(" CALL sp_msg_updateMsg (?,?, ?,?, ?,?,?, ?,?,?) ") ===FALSE)
			append_err(" DB ERROR 2-1 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select(
        array('success'),
        array('iiis'.'issiii',  $preapp_access_lv, $msgId,  $sess_login_id, $sess_login_user,  $groupId, $msgTitle, $msgContent,  $p_notice, $p_open, $p_active)
    )) ===FALSE)
			append_err(" DB ERROR 2-1 [$act]: update message (msg ID: $msgId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

    // SHOULD NOT happen: user has no access to update the reply
    if (count($rows) <1 || $rows[0]['success'] <1)
			append_err("[$act]: Unable to update message - not a MASTER-admin OR not a writer of the reply (msg ID: $msgId). ", LOG_FOLDER_ERR, $preapp_err_msg_system, $err_return);

		// if user is SUPER+ user: reset associated msg x user-group
		if ($preapp_access_lv >= POS_LV_SUPER) {
			if ($cSQL->prepare(" CALL sp_msg_resetUserGroup (?) ") ===FALSE)
				append_err(" DB ERROR 2-2 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
			if ($cSQL->delete( array('i',  $msgId) ) ===FALSE)
				append_err(" DB ERROR 2-2 [$act]: reset msg x user-group (msg ID: $msgId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
				
			$n = count($userGroupId);
			if ($n >0) {
				if ($cSQL->prepare(" CALL sp_msg_addUserGroup (?,?) ") ===FALSE)
					append_err(" DB ERROR 2-3 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
				foreach ($userGroupId as $r) {
					$r_id = de_obfs($r);
					if ($cSQL->update( array('ii',  $msgId, $r_id) ) ===FALSE)
						append_err(" DB ERROR 2-3 [$act]: add msg x user-group (msg ID: $msgId x group ID: $r_id). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
					$cSQL->reset_sp();
				}
			}
		} // END if: SUPER+ user


    // if file was uploaded successfully, create DB record for file (clear existing files)
    if (count($f_success) >0) {
      $tmp_url = $f_success['tmp'];
      $f_key = str_replace('-','', UUID::v3($sess_key, basename($tmp_url)));

      if ($cSQL->prepare(" CALL sp_msg_clearFile (?) ") ===FALSE)
        append_err(" DB ERROR 2-4 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);     
      if ($cSQL->update( array('i',  $msgId) ) ===FALSE)
        append_err(" DB ERROR 2-4 [$act]: clear existing file(s). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

      if ($cSQL->prepare(" CALL sp_msg_addFile (?,?,?,?) ") ===FALSE)
        append_err(" DB ERROR 2-5 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);     
      if (($r_id = $cSQL->insert_sp( array('sssi',  $f_success['name'], $f_success['type'], $f_key, $msgId) )) ===FALSE)
        append_err(" DB ERROR 2-5 [$act]: add new file. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
      $file_id = $r_id;

      $abs_f_path = $_SERVER['DOCUMENT_ROOT'].BASE_FOLDER.'/files/db/'.$file_id.'/'.$f_key;
      if (!file_exists($abs_f_path))
        mkdir($abs_f_path, 0755, true);
      move_uploaded_file($tmp_url, $abs_f_path.'/'.$f_success['name']);
    }
    $cSQL->close();


		// update SUCCESS
		append_log(" User Activity = ".$frmAction." : msg updated (msg ID: $msgId). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'Message has been updated.';
		break;




	// ***** delete forum-message *****
	case "msg_del":
		ingen_valid_key($_POST['msgId'], $_POST['key']);
		$msgId = de_obfs($_POST['msgId']);
		$err_return = 'msg-view?'.ingen_query_id($msgId);
		

		// ***** proceed to DB
		if (($cSQL->prepare(" CALL sp_msg_delMsg (?,?,?,?) ")) ===FALSE)
			append_err(" DB ERROR 3 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('success'), array('iiis',  $preapp_access_lv, $msgId, $sess_login_id, $sess_login_user) )) ===FALSE)
			append_err(" DB ERROR 3 [$act]: remove (mark deleted) message (msg ID: $msgId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

    $cSQL->close();


		// remove SUCCESS
		append_log(" User Activity = ".$frmAction." : msg removed (msg ID: $msgId). ", LOG_FOLDER_INFO);
		$handler_link = 'msg-list?gi='.$_POST['groupId'];
		$handler_msg = 'Message has been deleted.';
		break;






	// ******************************************************* message replies *******************************************************

	// ***** add reply to a message *****
	case "msg_reply_new":
		ingen_valid_key($_POST['msgId'], $_POST['key']);
		$msgId = de_obfs($_POST['msgId']);
		$groupId = de_obfs($_POST['groupId']);
    $err_return = 'msg-view?'.ingen_query_id($msgId);
    

		// validate: message content is NOT empty
    if ($msgAddReply =='')
			append_err(" [$act]: Reply message is empty. ", LOG_FOLDER_ERR, " Message content is required. ", $err_return);
		$msgAddReply = str_replace("\r","", trim($_POST['msgAddReply']));


		// ***** proceed with DB
		if ($cSQL->prepare(" CALL sp_msg_addReply (?,?,?,?) ") ===FALSE)
			append_err(" DB ERROR r1 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($r_id = $cSQL->insert_sp( array('isis',  $sess_login_id, $sess_login_user,  $msgId, $msgAddReply) )) ===FALSE)
			append_err(" DB ERROR r1 [$act]: add reply to msg (msg ID: $msgId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		
    $cSQL->close();
            

		// SUCCESS
		append_log(" User Activity = ".$frmAction." : reply added to a message (msg ID: $msgId x reply ID: $r_id). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'New Reply added to the message.';
		break;




	// ***** update reply message *****
	case "msg_reply_update":
		ingen_valid_key($_POST['replyId'], $_POST['key']);
		$replyId = de_obfs($_POST['replyId']);
		$msgId = de_obfs($_POST['msgId']);
    $err_return = 'msg-view?'.ingen_query_id($msgId);
    

		// validate: message content is NOT empty
    if ($msgReply =='')
			append_err(" [$act]: The message has empty content. ", LOG_FOLDER_ERR, " Message content is required. ", $err_return);
		$msgReply = str_replace("\r","", trim($_POST['msgReply']));
		
		
		// ***** proceed with DB
		if ($cSQL->prepare(" CALL sp_msg_updateReply  (?,?, ?,?, ?) ") ===FALSE)
			append_err(" DB ERROR r2 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('success'), array('iiiss',  $preapp_access_lv, $replyId,  $sess_login_id, $sess_login_user,  $msgReply) )) ===FALSE)
			append_err(" DB ERROR r2 [$act]: update reply msg (msg ID: $msgId x reply ID: $replyId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

    // SHOULD NOT happen: user has no access to update the reply
    if (count($rows) <1 || $rows[0]['success'] <1)
			append_err("[$act]: Unable to update reply - not a MASTER-admin OR not a writer of the reply (reply ID: $replyId). ", LOG_FOLDER_ERR, $preapp_err_msg_system, $err_return);
		
    $cSQL->close();
            

		// SUCCESS
		append_log(" User Activity = ".$frmAction." : reply updated (msg ID: $msgId x reply ID: $replyId). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'Reply Message has been updated.';
		break;




	// ***** delete (PERMANENTLY) reply message *****
	case "msg_reply_del":
		ingen_valid_key($_POST['replyId'], $_POST['key']);
		$replyId = de_obfs($_POST['replyId']);
		$msgId = de_obfs($_POST['msgId']);
    $err_return = 'msg-view?'.ingen_query_id($msgId);
		
		
		// ***** proceed with DB
		if ($cSQL->prepare(" CALL sp_msg_delReply (?,?, ?,?) ") ===FALSE)
			append_err(" DB ERROR r3 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);
		if (($rows = $cSQL->select( array('success'), array('iiis',  $preapp_access_lv, $replyId,  $sess_login_id, $sess_login_user) )) ===FALSE)
			append_err(" DB ERROR r3 [$act]: delete reply msg (msg ID: $msgId x reply ID: $replyId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db, $err_return);

    // SHOULD NOT happen: user has no access to update the reply
    if (count($rows) <1 || $rows[0]['success'] <1)
			append_err("[$act]: Unable to delete reply - not a MASTER-admin OR not a writer of the reply (reply ID: $replyId). ", LOG_FOLDER_ERR, $preapp_err_msg_system, $err_return);
		
    $cSQL->close();
            

		// SUCCESS
		append_log(" User Activity = ".$frmAction." : reply DELETED (msg ID: $msgId x reply ID: $replyId). ", LOG_FOLDER_INFO);
		$handler_link = $err_return;
		$handler_msg = 'Reply Message has been deleted.';
		break;


		
		
	default:
		append_err("User Activity = ".$frmAction." : NO action available. ", LOG_FOLDER_ERR, "Invalid action command.");
}



/***************** local function: get file type from MIME type ****************
*
* first check with MIME type in the array, then check with file extension
*************************************************************/
function get_type_from_mime($mime, $file_name) {
  $arr_mime = array(
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'excel',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'word',
    'application/msword'=>'word',
    'application/x-zip-compressed'=>'zip',
    'application/pdf'=>'pdf',
    'image/jpeg'=>'img',
    'image/jpg'=>'img',
    'image/png'=>'img',
    'image/gif'=>'img',
    'text/plain'=>'txt'
  );
  if (isset($arr_mime[$mime]) && $arr_mime[$mime] !='')
    return $arr_mime[$mime];
  if (substr($mime, 0,6) =='image/')
    return 'img';
  if (substr($mime, 0,5) =='text/')
    return 'txt';
  if (preg_match('/^application\/(.*)excel$/', $mime))
    return 'excel';

  $extension = preg_replace('/(.*)\.(.+)$/','$2', $file_name);
  switch ($extension) {
    case 'pdf': return 'pdf';
    case 'doc':
    case 'docx': return 'word';
    case 'xls':
    case 'xlsx':
    case 'csv': return 'excel';
    case 'zip': return 'zip';
  }
  return 'file';
}
?>