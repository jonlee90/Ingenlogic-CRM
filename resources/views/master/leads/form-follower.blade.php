<?php
/**
* required vars
* @param $lead_id: lead ID
* @param $masters: available users (master)
* @param $prov_contacts: available provider contacts
* @param $master_followers: currently saved lead x master-follower objects
* @param $prov_followers: currently saved lead x provider-follower objects
*/

$row_prod_options = [];
?>
<div class="overlay-form overlay-lead-product">
  <h2>Available Contacts</h2>

  <div class="lead-list-available master">
    <table id="tbl-lead-master-available">
      <thead>
        <tr> <th></th> <th>Type</th> <th>Name</th> <th>Title</th> <th>Phone</th> <th>Email</th> </tr>
      </thead>
      <tbody>
      @forelse ($masters as $master)
        <?php
        $r_master_type = ($master->is_master)?  'Master Agent' : config_pos_name($master->access_lv)
        ?>
        <tr class="btn-row-add">
          <td><i class="fa-plus-square" title="Add Follower"></i></td>
          <td class="row-type">
            {{ $r_master_type }}
            {!! Form::hidden('user_id[]', enc_id($master->id)) !!}
          </td>
          <td class="row-name">{{ trim($master->fname.' '.$master->lname) }}</td>
          <td class="row-title">{{ $master->title }}</td>
          <td class="row-phone">{{ $master->tel }}</td>
          <td class="row-email">{{ $master->email }}</td>
        </tr>
      @empty
          
      @endforelse
      </tbody>
    </table>
  </div>
  
  <div class="lead-list-available provider">
    <table id="tbl-lead-contact-available">
      <thead>
        <tr> <th></th> <th>Type</th> <th>Name</th> <th>Title</th> <th>Phone</th> <th>Email</th> </tr>
      </thead>
      <tbody>
      @forelse ($prov_contacts as $contact)
        <tr class="btn-row-add">
          <td><i class="fa-plus-square" title="Add Follower"></i></td>
          <td class="row-type">{{ $contact->prov_name }}
            {!! Form::hidden('prov_id[]', enc_id($contact->prov_id)) !!}
            {!! Form::hidden('contact_id[]', enc_id($contact->contact_id)) !!}
          </td>
          <td class="row-name">{{ trim($contact->fname.' '.$contact->lname) }}</td>
          <td class="row-title">{{ $contact->title }}</td>
          <td class="row-phone">{{ $contact->tel }}</td>
          <td class="row-email">{{ $contact->email }}</td>
        </tr>
      @empty
          
      @endforelse
      </tbody>
    </table>
  </div>
  
  <div class="spacer-h"></div>

  <?php // ******************************* Monthly Recurring products table form ******************************* ?>

  {!! Form::open(['url'=> route('master.lead.ajax-follower-update', ['lead_id'=> enc_id($lead_id)]), 'class'=>'frm-follower']) !!}
    <h3>List of Followers</h3>

    <table class="tbl-lead-follower-list">
      <thead>
        <tr> <th></th> <th>Type</th> <th>Name</th> <th>Title</th> <th>Phone</th> <th>Email</th> </tr>
      </thead>
      
      <tbody>
      @forelse ($master_followers as $follower)
        <tr>
        @if($follower->valid)
          <td><i class="md s btn-del-row" title="Remove Follower">close</i></td>
          <td class="row-type">Master Agent
            {!! Form::hidden('user_id[]', enc_id($follower->user_id)) !!}
          </td>
        @else
        <tr class="grayed">
          <td>
            <span class="popup-base">
              <i class="md s danger">report_problem</i>
              <div class="popup-tip left"><div><p>The follower mismatch with</p><p>Agent in the system.</p></div></div>
            </span>
          </td>
          <td class="row-type">Agent</td>
        @endif
        
          <td class="row-name">{{ $follower->f_name }}</td>
          <td class="row-title">{{ $follower->title }}</td>
          <td class="row-phone">{{ $follower->tel }}</td>
          <td class="row-email">{{ $follower->email }}</td>
        </tr>
      @empty
      @endforelse

      @forelse ($prov_followers as $follower)
        @if($follower->valid)
        <tr>
          <td><i class="md s btn-del-row" title="Remove Follower">close</i></td>
          <td class="row-type">{{ $follower->prov }}
            {!! Form::hidden('prov_id[]', enc_id($follower->provider_id)) !!}
            {!! Form::hidden('contact_id[]', enc_id($follower->contact_id)) !!}
          </td>
        @else
        <tr class="grayed">
          <td>
            <span class="popup-base">
              <i class="md s danger">report_problem</i>
              <div class="popup-tip left"><div><p>The follower mismatch with</p><p>Provider Contact in the system.</p></div></div>
            </span>
          </td>
          <td class="row-type">{{ $follower->prov }}</td>
        @endif

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
      {!! Form::submit('Save Followers').' '.Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
    </div>
  {!! Form::close() !!}
</div>
