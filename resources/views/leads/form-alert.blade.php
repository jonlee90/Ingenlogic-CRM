<?php
/**
* required vars
* @param $lead_id: lead ID
* @param $frm_url: URL to submit form
* @param $followers: available users (follower)
* @param $alerted_followers: currently saved lead x follower-follower objects
*/

?>
<div class="overlay-form overlay-lead-product">
  <h2>List of Followers</h2>
  {!! Form::open(['url'=> route('lead.ajax-alert-send', ['id'=> enc_id($type_id), 'alert_type' => $alert_type, 'log' => $log_id]), 'class'=>'frm-mrc']) !!}
  <div class="lead-list-available follower">
    <table id="tbl-lead-follower-available">
      <thead> <!-- <label for='checkall'>Check All</label> -->
        <tr><th><input type='checkbox' class='alert-checkall' id='checkall'></th> <th>Name</th><th>Position</th> <th>Title</th> <th>Phone</th> <th>Email</th> </tr>
      </thead>
      <tbody>
        @if(count($followers) > 0)
          @foreach ($followers as $follower)
            <tr class="btn-row-add alert-user-row">
              <td>{!! Form::checkbox('is_read[]', enc_id($follower->id), ($follower->is_read == 0) ? 1 : 0, ['class' => 'alert-read']) !!}</td>
              <td class="row-name">{{ trim($follower->fname.' '.$follower->lname) }}</td>
              <td class="row-position">{{ $follower->access_lv }}</td>
              <td class="row-title">{{ $follower->title }}</td>
              <td class="row-phone">{{ $follower->tel }}</td>
              <td class="row-email">{{ $follower->email }}</td>
            </tr>
          @endforeach
        @else
          <tr><td colspan="9" class="not-found">* 0 Followers found.</td></tr>
        @endif
      </tbody>
    </table>
  </div>
  
  <div class="spacer-h"></div>

    <div class="btn-group">
      {!! Form::submit('Send Alert').' '.Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
    </div>
  {!! Form::close() !!}
</div>
