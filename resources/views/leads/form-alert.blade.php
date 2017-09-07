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

  <div class="lead-list-available follower">
    <table id="tbl-lead-follower-available">
      <thead>
        <tr> <th></th> <th>Type</th> <th>Name</th> <th>Title</th> <th>Phone</th> <th>Email</th> </tr>
      </thead>
      <tbody>
      @forelse ($followers as $follower)
        <tr class="btn-row-add">
          <td><i class="fa-plus-square" title="Add Follower"></i></td>
          <td class="row-type">follower
           {!! Form::hidden('user_id[]', enc_id($follower->id)) !!}
          </td>
          <td class="row-name">{{ trim($follower->fname.' '.$follower->lname) }}</td>
          <td class="row-title">{{ $follower->title }}</td>
          <td class="row-phone">{{ $follower->tel }}</td>
          <td class="row-email">{{ $follower->email }}</td>
        </tr>
      @empty
          
      @endforelse
      </tbody>
    </table>
  </div>
  
  <div class="spacer-h"></div>

  <?php // ******************************* Monthly Recurring products table form ******************************* ?>

  {!! Form::open(['url'=> $frm_url, 'class'=>'frm-follower']) !!}
    <h3>Selected Followers</h3>

    <table class="tbl-lead-follower-list">
      <thead>
        <tr> <th></th> <th>Type</th> <th>Name</th> <th>Title</th> <th>Phone</th> <th>Email</th> </tr>
      </thead>
      {!! Form::hidden('lead_logs_id', $lead_logs_id) !!}
      <tbody>
      @forelse ($alerted_followers as $follower)
        <tr>
      
          <td><i class="md s btn-del-row" title="Remove Follower">close</i></td>
          <td class="row-type">follower
           {!! Form::hidden('user_id[]', enc_id($follower->user_id)) !!} 
          </td>
          <td class="row-name">{{ $follower->f_name }}</td>
          <td class="row-title">{{ $follower->title }}</td>
          <td class="row-phone">{{ $follower->tel }}</td>
          <td class="row-email">{{ $follower->email }}</td>
        </tr>
      @empty
      @endforelse
      </tbody>
    </table>

    <div class="btn-group">
      {!! Form::submit('Send Alert').' '.Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
    </div>
  {!! Form::close() !!}
</div>
