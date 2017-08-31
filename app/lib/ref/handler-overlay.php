<?
$handling_ajax = TRUE;
require('preapp.php');


$script_name = 'AJAX-overlay';
$act = $_POST['frmAction'];


// ***** leave LOG for user's AJAX overlay activity *****
append_log(" User AJAX overlay Activity = ".$act, LOG_FOLDER_INFO);

switch ($act) {

	// ***** load to overlay: message reply FORM *****
	case "msg_reply_update":
    define('TEXTAREA_MAX_LENGTH', 1000);
		
		ingen_valid_key($_POST['replyId'], $_POST['key']);
		$replyId = de_obfs($_POST['replyId']);
		

		// get reply from DB
		if ($cSQL->prepare(" CALL sp_msg_getReply (?,?)") ===FALSE) 
			append_err_ajax(" DB ERROR m1 [$act]: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
		if (($rows = $cSQL->select( array('msgId','msgReply','del', 'uFname','uLname','uEmail','uDel'), array('ii', $preapp_access_lv, $replyId) )) ===FALSE)
			append_err_ajax(" DB ERROR m1 [$act]: get reply info (msg ID: $replyId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
		if (count($rows) > 0) {
			foreach ($rows[0] as $k=>$v)
				$$k = $v;
		} else
			// SHOULD NOT happen: record NOT found from DB with given ID
			append_err_ajax(" [$act]: reply NOT found from given ID (msg ID: $replyId)", LOG_FOLDER_ERR, $preapp_err_msg_system);

		$writerName = trim($uFname.' '.$uLname);
		if ($writerName =='')
			$writerName = '(Unknown)';
		else
			$writerName .= ' ('.$uEmail.')';
			
		$cSQL->close();


		$html_output = '
      <form class="frm-overlay" action="'.ingen_link('handler').'" method="POST">
        <input type="hidden" name="frmAction" value="msg_reply_update" />
        <input type="hidden" name="replyId" value="'.$_POST['replyId'].'" />
        <input type="hidden" name="key" value="'.$_POST['key'].'" />
        <input type="hidden" name="msgId" value="'.obfs_str($msgId).'" />
        
        <div class="input-group">
          <label>Writer</label>
          <div class="output">'.$writerName.'</div>
        </div>
        <div class="input-group overlay-fmsg-reply">
          <label>Message</label>
          <div class="wrapper-textarea">
            <textarea name="msgReply" maxlength="'.TEXTAREA_MAX_LENGTH.'" required>'.$msgReply.'</textarea>
            <div class="chr-left">'.strlen($msgReply).'/'.TEXTAREA_MAX_LENGTH.'</div>
          </div>
        </div>
      
        <div class="btn-group">
          <input type="submit" value="Save Changes" />
          <input type="button" value="Cancel" class="btn-cancel" />
        </div>
      </form>
      <script>oMrU1();</script>
		';
		
		$json_out = json_encode(array(
			'success'=>1, 'error'=>0,
			'html'=>$html_output
		));
		break;


		
	// ***** open send email form *****
	case "user_email_form":
		ingen_valid_key($_POST['userId'], $_POST['key']);
		$userId = de_obfs($_POST['userId']);
		
		// get user info
		if ($cSQL->prepare(" CALL sp_user_getUser (?) ") ===FALSE) 
			append_err_ajax(" DB ERROR u1: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
		if (($rows = $cSQL->select( array('accessLV', 'loginName', 'email',  'fname', 'lname', 'isActive'), array('i', $userId) )) ===FALSE)
			append_err_ajax(" DB ERROR u1: get user info (user ID: $userId). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
		if (count($rows) > 0) {
			foreach ($rows[0] as $k=>$v)
				$$k = $v;
		} else
			// SHOULD NOT happen: user NOT found from DB with given ID
			append_err_ajax(" [$act]: user NOT found from given ID (user ID: $userId)", LOG_FOLDER_ERR, $preapp_err_msg_system);
			
		$cSQL->close();

		if ($isActive <1)
			append_err_ajax(" [$act]: user is NOT active (user ID: $userId)", LOG_FOLDER_ERR, "You cannot send email to an inactive user.");
	

		$html_output = '
      <div class="frm-send-email">
        <input type="hidden" name="userId" value="'.$_POST['userId'].'" />
        <input type="hidden" name="key" value="'.$_POST['key'].'" />
        
        <div class="input-group">
          <label>From</label>
          <div class="output">'.$sess_login_user.'</div>
        </div>
        <div class="input-group">
          <label>To</label>
          <div class="output">'.trim($fname.' '.$lname).' (<a href="mailto:'.$email.'">'.$email.'</a>)</div>
        </div>
        <div class="input-group">
          <label>Subject</label>
        	<input type="text" name="subj" maxlength="100" class="email-user-subj" required />
        </div>
        <div class="input-group">
          <label>Message</label>
					<div class="tinymce-wrapper">
						<textarea id="overlay-email-content" class="email-user-content" name="msgContent"></textarea>
					</div>
        </div>
      
        <div class="btn-group">
          <input type="button" value="Send Email" class="btn-submit-email" />
          <input type="button" value="Cancel" class="btn-cancel" />
        </div>
      </div>
			<style>.mce-tooltip, .mce-floatpanel { position:fixed !important; }</style>
      <script>oVsE1()</script>
		';
		
		$json_out = json_encode(array(
			'success'=>1, 'error'=>0,
			'html'=>$html_output
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