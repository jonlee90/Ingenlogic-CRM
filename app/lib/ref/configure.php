<?php
/**
 * configure.php
 *  constant definition will be defined here
 */

// ***** test purpose, set to empty string (or '/') on LIVE
define ("BASE_FOLDER", '/');

// ***** database name
define ("DB_HOST", "127.0.0.1");
define ("DB_NAME", "homestead");
/*
define ("DB_USER", "mysql_limit");
define ("DB_PW", "limit@INlogic1");
*/
define ("DB_USER", "homestead");
define ("DB_PW", "secret");
define ("DB_USER_MASTER", "homestead"); // db user for invoker stored-procedures
define ("DB_PW_MASTER", "secret");

// ***** session name
define ("SESS_NAME", "ingenlogicCPanel");
define ("SESS_EXPIRE_MIN", "30");

// ***** site info
define ("SITE_TITLE", "Ingenlogic");
define ("SITE_ADDRESS", "www.ingenlogic.com");
define ("SITE_UUID_KEY", "www.ingenlogic.com/ingencpanel"); // hardcoded value for double-layer key protection", change periodically once in a while.

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
define ("MYSQL_TIMEZONE", "America/Phoenix");
define ("DEFAULT_TIMEZONE", "America/Los_Angeles");
define ("DEFAULT_DATE", '1979-12-31');
define ("PAGING_PG_COUNT", 10);
define ("LIMIT_IMG_BYTE", 5242880); // uploaded img size limit to 5 MB = 5 x 1024 x 1024 = 5242880 bytes

// ***** login session will expire after 1 hr = 60x60 = 3600 sec *****
define ("LOGIN_EXPIRE_SEC", 10800); // testing - set to 3 hrs (10800sec) instead

// ***** iteration count used in class: PasswordHash *****
define ("BCRYPT_ITERATION_COUNT", 15);

// ********* Log folders *********
define ("LOG_FOLDER_INFO", '/info');
define ("LOG_FOLDER_ERR", '/err');
define ("LOG_FOLDER_CRON", '/cron'); // for logs generated from cron-job




// ********* user access LVs (0-99): should match with DB, "user_positions" *********
define ("POS_LV_MASTER", 99);
define ("POS_LV_SUPER", 90);
define ("POS_LV_MANAGER", 70);
define ("POS_LV_EMPLOYEE", 0);
define ("POS_LV_AGENT", 10);
$config_access_lv =
  array( POS_LV_MASTER=>'Master', POS_LV_SUPER=>'Super', POS_LV_MANAGER=>'Manager', POS_LV_EMPLOYEE=>'Regular', POS_LV_AGENT=>'Agent');

// ********* list of accessible-actions available: should match with key names in preapp *********
/*
$config_list_acts =
  array('usView','usNew','usMod','usDel', 'stView','stNew','stMod','stDel',
    'phView','phNew','phMod','phDel', 'plView','plNew','plMod','plDel',
    'adView','adNew','adMod','adDel', 'lnView','lnNew','lnMod','lnDel',
    'cuView','cuNew','cuMod','cuDel', 'foView','foNew','foMod','foDel',
    'pushNoti'
  );

//  ********* forum-message groups: MUST match DB values *********
define ("MSG_GROUP_ID_NOTICE", 1);
define ("MSG_GROUP_ID_QNA", 2);
define ("MSG_GROUP_ID_YOUTUBE", 3);
$config_msg_groups = array(1=>'Notices', 2=>'Q & A', 3=>'Youtube');


// ********* defined arrays *********

// day of week
$config_wkdays = array('sun'=>'Sunday','mon'=>'Monday','tue'=>'Tuesday','wed'=>'Wednesday','thu'=>'Thursday','fri'=>'Friday','sat'=>'Saturday');
*/
?>