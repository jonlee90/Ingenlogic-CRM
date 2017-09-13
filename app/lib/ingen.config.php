<?php
/**
* configure.php
*  constant definition will be defined here
*
* php.ini configuration to be made:
*  max-upload-size: 10M or above (total file size per location is set to 10M)
*  post-max: 10M or above (total file size per location is set to 10M)
*  file-upload-limit: 20 or above (set enough # of files for uploading)
*/
 
// ***** test purpose, set to empty string (or '/') on LIVE
define ("BASE_FOLDER", '/');

// ***** site info
define ("SITE_TITLE", "Ingenlogic");
define ("SITE_ADDRESS", "www.ingenlogic.com");
define ("SITE_KEY_SECRET", "www.ingenlogic.com/ingencpanel"); // hard-coded value for double-layer key protection, change periodically once in a while.

define ("OPENSSL_CIPHER_METHOD", "AES-256-CBC"); // cipher-method for openssl_encrypt/decrypt
define ("OPENSSL_CIPHER_IV_LEN", openssl_cipher_iv_length(OPENSSL_CIPHER_METHOD)); // cipher-IV length to use on openssl_encrypt/decrypt

// define ("SITE_UUID_KEY", "www.ingenlogic.com/ingencpanel"); // to-be-deleted -> hardcoded value for double-layer key protection", change periodically once in a while.

/*
// ***** API
define ("GOOGLE_API_KEY", "AIzaSyBzcJ7mpHQbvliDbRbNLw0i8ayP6YpP6x0"); // API key is for: ingen.ultimate@gmail
define ("FIREBASE_FCM_SERVER_KEY", "AAAAFTG_e0k:APA91bEglK9SGd7_WywCfk5w-5uGOr6RjPokWCFeKd7PX-cIk84BYJ3v9l_L9ei41P3VlKmlKTImjiMJKN9FZTeglqqpXpD6-EcdAeQGeV4UJNFKqw1tujefX07UKODkCSaFoxIJayI6"); // FCM SERVER-key for Firebase (account: ingen.ultimate@gmail)
define ("FIREBASE_FCM_SENDER_ID", "91028945737"); // FCM sender-ID for Firebase (account: ingen.ultimate@gmail)
define ("FIREBASE_FCM_URL_SEND", "https://fcm.googleapis.com/fcm/send"); // HTTP url to POST json encoded push notification
define ("FIREBASE_FCM_URL_GROUP", "https://android.googleapis.com/gcm/notification"); // HTTP url to POST json encoded device-group management (create,add,remove)
define ("FIREBASE_FCM_MSG_TITLE", "Ultimate Wireless App"); // HTTP url to POST json encoded device-group management (create,add,remove)
*/

// ***** Swift mail library
define ("SWIFT_MAIL_USERNAME", "test@ingenlogic.com");
define ("SWIFT_MAIL_PW", "test@INlogic0");


// ***** developer info
define ("DEVELOPER_EMAIL", "info@ingenlogic.com");
define ("DEVELOPER_TITLE", "Ingenlogic");
define ("DEVELOPER_ADD", "3550 W. Wilshire Blvd. Ste 1136");
define ("DEVELOPER_ADD2", "Los Angeles, CA 90010");
define ("DEVELOPER_TEL", "213.986.7486");
define ("DEVELOPER_WEB", "ingenlogic.com");


//********** DEFAULT VALUES **********
define ("MYSQL_TIMEZONE", "UTC"); // godaddy servers are usually in 'America/Phoenix'
define ("DEFAULT_TIMEZONE", "America/Los_Angeles");
define ("DEFAULT_DATE", '1979-12-31');
define ("PAGING_PG_COUNT", 10);
define ("LIMIT_IMG_BYTE", 5242880); // uploaded img size limit to 5 MB = 5 x 1024 x 1024 = 5242880 bytes
define ("LIMIT_LOCATION_FILE_SIZE_MB", 10); // max size of all uploaded files is limit to 10 MB = 10 x 1024 x 1024 = 10485760 bytes

// ***** login session will expire after 1 hr = 60x60 = 3600 sec *****
// define ("LOGIN_EXPIRE_SEC", 10800); // testing - set to 3 hrs (10800sec) instead

// ***** iteration count used in class: PasswordHash *****
// define ("BCRYPT_ITERATION_COUNT", 15);

// ********* spiff/residual rate for agency *********
define ('MAX_AGENCY_RATE', 100); // 0 ~ 100%
define ('MAX_SHARED_RATE', 100); // 0 ~ 100%, maximum spiff/residual rate for manager or agency
define ('MAX_AGENCY_PER_LEAD', 3); // upto 3 agencies can be assigned to a lead
define ('MAX_MANAGER_PER_LEAD', 3); // upto 3 managers (master-agent) can be assigned to a lead

// ********* Log folders *********
define ("LOG_FOLDER_INFO", '/info');
define ("LOG_FOLDER_ERR", '/err');
define ("LOG_FOLDER_CRON", '/cron'); // for logs generated from cron-job




// ********* user access LVs (0-99): should match with DB, "user_positions" *********
define ("POS_LV_SYS_MASTER", 99);
define ("POS_LV_SYS_ADMIN", 90);
define ("POS_LV_MASTER_ADMIN", 89);
define ("POS_LV_MASTER_SUPER", 88);
define ("POS_LV_MASTER_MANAGER", 85);
define ("POS_LV_MASTER_USER", 80);
define ("POS_LV_CH_MANAGER", 65);
define ("POS_LV_CH_USER", 60);
define ("POS_LV_AGENT_ADMIN", 49);
define ("POS_LV_AGENT_MANAGER", 45);
define ("POS_LV_AGENT_USER", 42);
define ("POS_LV_GUEST", 0);
/*
99: system > master
90: system > admin
--
89: master-agent > admin
88: master-agent > super manager: FULL control except modify admin
85: master-agent > manager: control over agencies, providers, products and other settings + assign agency to channel-managers
80: master-agent > user: limited access as a master-agent (mostly view, no user/setting control)
-- 
65: channel-manager >
      access to leads/accounts for assigned agencies + invite/remove other channel-managers for leads +
      gets commission from accounts + control channel users
60: channel-user > assistant: access to leads/accounts the associated channel managers has access to (each channel-user has 1 channel-manager)
--
49: agency > admin > agency-admin can update agency info
45: agency > manager > agency user control (modify, but no create/delete)
42: agency > employee
40: agency > sales?
--
20: sales?
 0: guest
*/

/**
 * functions to get config arrays: use 'defined' array in PHP 7
 */
/**
 * get position name (must match with DB)
 *
 * @param $lv: logged-in user's access lv
 *
 * @return string
 */
function config_pos_name($lv) {
  switch ($lv) {
    case POS_LV_SYS_MASTER:
      return 'System Master';
    case POS_LV_SYS_ADMIN:
      return 'System Admin';
    case POS_LV_MASTER_ADMIN:
      return 'Admin';
    case POS_LV_MASTER_SUPER:
      return 'Super Manager';
    case POS_LV_MASTER_MANAGER:
      return 'Manager';
    case POS_LV_MASTER_USER:
      return 'User';
    case POS_LV_CH_MANAGER:
      return 'Channel Manager';
    case POS_LV_CH_USER:
      return 'Assistant';
    case POS_LV_AGENT_ADMIN:
      return 'Agency Admin';
    case POS_LV_AGENT_MANAGER:
      return 'Agency Manager';
    case POS_LV_AGENT_USER:
      return 'Agent';
    // case 0
    default:
      return 'Guest';
  }
}
/**
 * mapping: get accessible positions
 *
 * @param $lv: logged-in user's access lv
 * @return array: positions the user can view
 */
function map_accessible_positions ($lv) {
	switch ($lv) {
		case POS_LV_SYS_MASTER:
		case POS_LV_SYS_ADMIN:
      return [POS_LV_SYS_MASTER, POS_LV_SYS_ADMIN,
        POS_LV_MASTER_ADMIN, POS_LV_MASTER_SUPER, POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER,
        POS_LV_CH_MANAGER, POS_LV_CH_USER,
        POS_LV_AGENT_ADMIN, POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER,
        POS_LV_GUEST
      ];
		case POS_LV_MASTER_ADMIN:
		case POS_LV_MASTER_SUPER:
		case POS_LV_MASTER_MANAGER:
		case POS_LV_MASTER_USER:
      return [POS_LV_SYS_MASTER, POS_LV_SYS_ADMIN,
        POS_LV_MASTER_ADMIN, POS_LV_MASTER_SUPER, POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER,
        POS_LV_CH_MANAGER, POS_LV_CH_USER,
        POS_LV_AGENT_ADMIN, POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER,
        POS_LV_GUEST
      ];
		case POS_LV_CH_MANAGER:
		case POS_LV_CH_USER:
      return [POS_LV_CH_MANAGER, POS_LV_CH_USER, POS_LV_AGENT_ADMIN, POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER, POS_LV_GUEST];
		case POS_LV_AGENT_ADMIN:
		case POS_LV_AGENT_MANAGER:
      return [POS_LV_AGENT_ADMIN, POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER, POS_LV_GUEST];
		case POS_LV_AGENT_USER:
      return [POS_LV_AGENT_USER, POS_LV_GUEST];

		default:
			return [POS_LV_GUEST];
	}
}
/**
 * mapping: get editable positions
 *
 * @param $lv: logged-in user's access lv
 * @return array: positions the user can modify (user can modify oneself regardless)
 */
function map_editable_positions ($lv) {
	switch ($lv) {
		case POS_LV_SYS_MASTER:
      return [POS_LV_SYS_MASTER, POS_LV_SYS_ADMIN,
        POS_LV_MASTER_ADMIN, POS_LV_MASTER_SUPER, POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER,
        POS_LV_CH_MANAGER, POS_LV_CH_USER,
        POS_LV_AGENT_ADMIN, POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER,
        POS_LV_GUEST
      ];
		case POS_LV_SYS_ADMIN:
      return [POS_LV_MASTER_ADMIN, POS_LV_MASTER_SUPER, POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER,
        POS_LV_CH_MANAGER, POS_LV_CH_USER,
        POS_LV_AGENT_ADMIN, POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER,
      ];
		case POS_LV_MASTER_ADMIN:
      return [POS_LV_MASTER_SUPER, POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER,
        POS_LV_CH_MANAGER, POS_LV_CH_USER,
        POS_LV_AGENT_ADMIN, POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER,
      ];
		case POS_LV_MASTER_SUPER:
      return [POS_LV_MASTER_MANAGER, POS_LV_MASTER_USER,
        POS_LV_CH_MANAGER, POS_LV_CH_USER,
        POS_LV_AGENT_ADMIN, POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER,
      ];
		case POS_LV_MASTER_MANAGER:
      return [POS_LV_MASTER_USER, POS_LV_CH_MANAGER, POS_LV_CH_USER, POS_LV_AGENT_ADMIN, POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER, ];

		case POS_LV_CH_MANAGER:
      return [POS_LV_CH_USER];

		case POS_LV_AGENT_ADMIN:
      return [POS_LV_AGENT_MANAGER, POS_LV_AGENT_USER];
		case POS_LV_AGENT_MANAGER:
      return [POS_LV_AGENT_USER];

		default:
			return [POS_LV_GUEST];
	}
}
/*
// ********* list of accessible-actions available: should match with key names in preapp *********
$config_list_acts =
  array('usView','usNew','usMod','usDel', 'stView','stNew','stMod','stDel',
    'phView','phNew','phMod','phDel', 'plView','plNew','plMod','plDel',
    'adView','adNew','adMod','adDel', 'lnView','lnNew','lnMod','lnDel',
    'cuView','cuNew','cuMod','cuDel', 'foView','foNew','foMod','foDel',
    'pushNoti'
  );

// ********* defined arrays *********

// day of week
$config_wkdays = array('sun'=>'Sunday','mon'=>'Monday','tue'=>'Tuesday','wed'=>'Wednesday','thu'=>'Thursday','fri'=>'Friday','sat'=>'Saturday');
*/
?>