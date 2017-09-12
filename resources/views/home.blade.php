<?php
// require_once(app_path().'/lib/preapp.php');
$pg_header_submenu = (object) array(
  'title'=> 'Dashboard',
  'menus'=> array()
);

/*
// get preview of first 10 messages available
if (($cSQL->prepare(" SELECT id FROM login_users WHERE 1 ")) ===FALSE)
  die('prep'); //sess_reset_err(" DB ERROR 1: failed prep. \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
if (($rows = $cSQL->select( array('id')  )) ===FALSE)
  die('exec'); //sess_reset_err(" DB ERROR 1: get dashboard statistics (= # new reply count). \n ".$cSQL->error, LOG_FOLDER_ERR, $preapp_err_msg_db);
if (count($rows) >0) {
  foreach ($rows[0] as $k=>$v)
    $$k = $v;
}  else
  // SHOULD NOT happen: 1 row should be returned regardless of the result
  die('fail'); //sess_reset_err(" 0 result from dashboard-statistics. ", LOG_FOLDER_ERR, $preapp_err_msg_system);
*/


$myMsgReplyCount = 0;
$n_group = 0;
$n_msg = 0;
$n_new_msg = 0;
$newReplyCount = $myMsgCount = 0;
$row_msgs = array();


$head_title = " Home | ".SITE_TITLE." Control Panel v2";

?>
@extends('layouts.app')

@section('title', $head_title)

@section('content')
<?php
// ***************************** logged-in, admin-home *****************************
if ($myMsgReplyCount >0) {
?>
  <div class="dash-notification">
    <a href="<?=('msg-list?ui='.obfs_str($sess_login_id)) ?>">There <?=($myMsgReplyCount >1)? 'are '.$myMsgReplyCount.' New replies' : 'is 1 New reply' ?> to your Message.</a>
  </div>
<?php
  } // END if: count(new reply to my message) >0
?>
  <div class="panel panel-dash-stat">
    {{ Auth::user()->fname }}
    <h2>Today is</h2>

    <div class="dash-today">
      <div class="col-n"><?=date('M nS Y') ?></div>
      <div class="col-n"><?=date('l') ?></div>
    </div>
    <div class="spacer-h"></div>

    <h2>Statistics</h2>

    <div class="input-group">
      <label>Message Groups</label>
      <div class="output"><span class="dash-tag-count"><?=$n_group ?></span> available</div>
    </div>
    <div class="input-group">
      <label>Total Messages</label>
      <div class="output"><span class="dash-tag-count"><?=$n_msg ?></span> message(s)</div>
    </div>
    <div class="input-group">
      <label>New Message</label>
      <div class="output"><span class="dash-tag-count"><?=$n_new_msg ?></span> message(s)</div>
    </div>
    <div class="input-group">
      <label>New Replies</label>
      <div class="output"><span class="dash-tag-count"><?=($newReplyCount >0)? $newReplyCount:0 ?></span> <?=($newReplyCount ==1)? 'reply':'replies' ?></div>
    </div>

    <h2>My Messages</h2>
    
    <div class="input-group">
      <label>Message Count</label>
      <div class="output"><span class="dash-tag-count"><?=($myMsgCount >0)? $myMsgCount:0 ?></span> <?=($myMsgReplyCount ==1)? 'reply':'replies' ?></div>
    </div>

    <div class="input-group">
      <label>New Replies</label>
      <div class="output"><span class="dash-tag-count"><?=($myMsgReplyCount >0)? $myMsgReplyCount:0 ?></span> <?=($myMsgReplyCount ==1)? 'reply':'replies' ?></div>
    </div>
    <div class="spacer-h"></div>

    <p class="inform danger">* New message/reply is less than 3 days old</p>
  </div>
  
  <div class="panel panel-dash-stat">
    <h2>Available Message Groups</h2>
    <?php
    if ($n_group >0) {
      echo '<div class="dash-msg-group-list">';

      foreach ($row_groups as $r) {
        $r_id = $r['id'];
        $r_msg_count = $r['msgCount'];
        $r_msg_class = 'count';
        if ($r_msg_count < 1)
          $r_msg_class .= ' empty';
        elseif ($r_msg_count >9) {
          $r_msg_count = '9+';
          $r_msg_class .= ' over';
        }
        $r_new_count = $r['newCount'];
        $r_new_class = 'count';
        if ($r_new_count < 1)
          $r_new_class .= ' empty';
        elseif ($r_new_count >9) {
          $r_new_count = '9+';
          $r_new_class .= ' over';
        }
      ?>
      <div class="fgroup-btn-wrapper">
        <a href='<?=('msg-list?gi='.obfs_str($r_id)) ?>' class='msg-home-btn'><?=$r['group'] ?></a>
        <div class="fgroup-noti">
          <div class="fa-commenting icon-noti" title="Total Messages"></div>
          <div class="<?=$r_msg_class ?>"><?=$r_msg_count ?></div>
          <div class="fa-plus-circle icon-noti" title="New Messages (last 3 days)"></div>
          <div class="<?=$r_new_class ?>"><?=$r_new_count ?></div>
        </div>
      </div>
      <?php
      } // END foreach: message groups
      echo '</div>';

    } else
      echo '<div class="danger">No Message Group available.</div>';
    
    if ($preapp->lv >= POS_LV_SYS_MASTER)
      echo '
      <div class="spacer-h"></div>
      <div class="btn-group">
        <a href="'.('msg-group').'"><input type="button" value="Create Group" /></a>
      </div>
      ';
    ?>
  </div>

  <div class="panel block">
    <h2>Alerts</h2>
    <div class="input-group">
      <label>Unread Alerts</label>
      <div class="output"><span class="dash-tag-count"><?= $alert_count ?></span> alerts</div>
    </div>
    <div class="input-group">
      <input type='button' class='view-alert btn-view-alerts' value='Show All Alerts'/>
    </div>
    @if(count($alerts) > 0)
      <div class='alerts-container'>
        @foreach ($alerts as $alert)
          <div class='alert-container' style='background-color: {{ $alert->is_read == 0 ? "rgba(248, 246, 149, .6)" : "" }}'>
            <div class='alert-link-container'>
              <div class='outer-link'>
                <div class='inner-link'>
                  <a href='{{ route("alert.manage", ["id" => $alert->alert_type_id, "type" => $alert->alert_type, "alert" => $alert->id]) }}'><i class="fa-external-link btn-go-lead" title="Go to Lead"></i></a>
                </div>
              </div>
            </div>
            <div class='alert-content-container'>
              <div class="a_date">Date: {{ $alert->date_added }}</div>
              <div>
                <div class="a_name">Sent By: {{ $alert->name }}</div>
              </div>
              <div class="a_msg">{!! $alert->alert_msg !!}</div>
            </div>
          </div>
        @endforeach
      </div>
    


    <!---
    <table>
      <thead>
        <tr> <th></th><th>Sent By</th><th>Log By</th> <th>Date</th> <th>Message</th> </tr>
      </thead>
      <tbody>
        @foreach ($alerts as $alert)
          <tr class="btn-row-add">
            <td><a href='{{ route("alert.manage", ["id" => $alert->alert_type_id, "type" => $alert->alert_type, "alert" => $alert->id]) }}'><i class="fa-external-link btn-go-lead" title="Go to Lead"></i></a></td>
            <td class="row-s_name">{{ $alert->name }}</td>
            <td class="row-date">{{ $alert->date_added }}</td>
            <td class="row-msg">{!! $alert->alert_msg !!}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    -->
    @endif
  </div>
  
  <div class="panel block">
    <a href="<?=('msg-edit?new=1') ?>"><input type="button" value="Write New Message" /></a>

    <table class='tbl-msg-list'>
      <thead>
        <th>ID</th>
        <th>Group</th>
        <th>Writer</th>
        <th>Title</th>
        <th>File</th>
        <th>Posted</th>
        <th>Views</th>
        <th>Replies</th>
        <th>Status</th>
      </thead>

      <tbody>
      <?php
      $n = count($row_msgs);
      if ($n <1)
        echo '<tr><td colspan="9" class="not-found">* 0 Messages found.</td></tr>';

      for ($i=0; $i<$n; $i++) {
        $r = $row_msgs[$i];
        $r_id = $r['id'];

        if ($r['del'] >0)
          $r_title = '<span class="del-title">'.$r['title'].'</span>';
        else {
          $r_title = '<a href="'.('msg-view?'.ingen_query_id($r_id)).'">'.$r['title'].'</a>';
          if ($r['dRec'] > date('Y-m-d h:i:s', strtotime('-3 day')))
            $r_title = '<span class="tag-new-msg"></span> '.$r_title;
        }
        
        $r_writer = trim($r['uFname'].' '.$r['uLname']);
        if ($r_writer =='')
          $r_writer = '(unknown)';
          
        $r_count = $r['replyCount'];
        if ($r['newCount'] >0)
          $r_count .= ' <span class="tag-new-reply">'.$r['newCount'].' new</span>';

        if ($r['f_type'] !==NULL && $r['f_type'] !='') {
          $r_type = 'fa-file-o';
          switch ($r['f_type']) {
            case 'img': $r_type = 'fa-file-image-o'; break;
            case 'word': $r_type = 'fa-file-word-o'; break;
            case 'excel': $r_type = 'fa-file-excel-o'; break;
            case 'pdf': $r_type = 'fa-file-pdf-o'; break;
            case 'txt': $r_type = 'fa-file-text-o'; break;
            case 'zip': $r_type = 'fa-file-zip-o'; break;
          }
          $r_type = '<span class="'.$r_type.'"></span>';
        } else
          $r_type = '';
          
        $r_time = strtotime($r['dRec']);

        // link is available if: user can modify AND target is not deleted
        if ($r['del'] >0) {
          $r_active_html = '<span class="err">Removed</span>';
          $r_row_class = "grayed";
        } elseif ($r['active'] <1) {
          $r_active_html = '<span class="err">Inactive</span>';
          $r_row_class = "grayed";
        } elseif ($r['isNotice'] >0) {
          $r_active_html = '<span class="fa-bullhorn"></span> Notice';
          $r_row_class = "";
        } else {
          $r_active_html = 'Active';
          $r_row_class = "";
        }
      ?>
        <tr class="<?=$r_row_class ?>">
          <td><?=$r_id ?></td>
          <td><?=$r['groupName'] ?></td>
          <td>
          <?php
          // if user is not self and is active: email can be sent to the user
          if ($r['del'] >0 || $r['active'] <1 || $r['userId'] == $sess_login_id)
            echo $r_writer;
          else {
          ?>
            <b><a class="btn-write-email" title="Write an Email to the User"><?=$r_writer ?></a></b>
            <div class="data-group">
              <data data-attr="id" value="<?=obfs_str($r['userId']) ?>"></data>
              <data data-attr="key" value="<?=gen_key($r['userId']) ?>"></data>
            </div>
          <?php
          } // END if-else: active user, not self
          ?>
          </td>
          <td><div class="cell-title"><?=$r_title ?></div></td>
          <td><?=$r_type ?></td>
          <td><?=date('m/d/y', $r_time) ?></td>
          <td><?=$r['viewCount'] ?></td>
          <td><?=$r_count ?></td>
          <td><?=$r_active_html ?></td>
        </tr>
      <?php
      } // END for: each message
      ?>
      </tbody>
    </table>
    <?php
    if ($n >0)
      echo '
    <div class="btn-group">
      <a href="'.('msg-list').'"><input type="button" value="Show More" /></a>
    </div>
      ';
    ?>
  </div>
  <?php
  // show log links if master admin
  if ($preapp->lv >=POS_LV_SYS_MASTER) {
  ?>
  <div>
    <div class="panel">
      <h2>Log Records</h2>

      <a href='<?=('log-list?cpanel=1&f=info') ?>' class='btn-home-menu'>Master Panel</a>
      <a href='<?=('log-list?cpanel=1&f=err') ?>' class='btn-home-menu'>Error</a>
    </div>
  </div>
<?php
  echo '<div style="width:100%; overflow: auto;">
    <div>'.print_r(Session::all(), TRUE).'</div>
    <div>login ID: '.Auth::id().'</div>
    </div>';
  } // END if: master admin
?>
@endsection

@section('post_content_script')
<script>
function mSlT2() {
  overlay = new ingenOverlay('overlay-pane');
  $('.btn-write-email').click(function() {
    openEmailForm( $(this).closest('td') );
  });
}
// declare overlay as global var to be used in oVsE1()
var overlay;mSlT2();

var $overlayPane = $('#overlay-pane');
$overlayPane.find('.overlay-inner').append( $('<div/>', {class: 'cal-pane'}) );
var fnFillContainerChange = function(json) {
  $overlayPane.find('.container-change').html(json.html);
  $("#overlay-pane .btn-cancel").click(function() { overlay.close(); });
};
$('.view-alert').on('click', function() {

  overlay.setTitle('My Alerts');
  overlay.openAjax({
    url: laraRoute('home.overlay-alert-mod'),
    method: 'GET', data: {}
  });

/*
      reqAjax({
        url: laraRoute('home.overlay-alert-mod'),
        method: 'GET', 
        data: {},
        fnSuccess: fnFillContainerChange,
        fnFail: function(json) { alertUser(json.msg); overlay.close(); },
      }); // END: reqAjax
      overlay.open();  
      */
});
</script>
@endsection