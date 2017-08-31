<?php
/**
* required vars
* @param $lead_id: lead ID (not encoded)
* @param $agency_id: agency ID (not encoded)
* @param $followers: array of follower objects -> [masters, agents, prov_contacts]
*/
?>
@if (count($followers->masters) ==0 && count($followers->agents) ==0 && count($followers->prov_contacts) ==0)
<div class="err">Followers Not added yet.</div>

@else
<ul class="follower-list master">
  @forelse ($followers->masters as $follower)
  <?php
    $emailHTML = ($follower->email)?
      '<a href="mailto:'.$follower->email.'"><i class="md s">email</i></a> <div class="popup-tip"><div>Send Email to Contact</div></div>' :
      '<i class="md s grayed">email</i></a> <div class="popup-tip"><div>Email Not Available</div></div>';
    $telHTML = ($follower->tel)?
      '<a href="mailto:'.$follower->tel.'"><i class="md s">phone</i></a> <div class="popup-tip right"><div>Call the Contact</div></div>' :
      '<i class="md s grayed">phone</i></a> <div class="popup-tip right"><div>Phone # Not Available</div></div>';
  ?>
  <li class="<?=($follower->valid)? '':'grayed' ?>">
    <div>
      <span class="follower-tag master"></span>
      <div class="follower-actions">
        <span class="popup-base">{!! $emailHTML !!}</span>
        <span class="popup-base">{!! $telHTML !!}</span>
      </div>
    </div>
    <div class="follower-name">
      @if(!$follower->valid)
      <span class="popup-base">
        <i class="md s danger">report_problem</i>
        <div class="popup-tip left"><div><p>The follower mismatch with</p><p>Master Agent in the system.</p></div></div>
      </span>
      @endif
      {{ $follower->f_name }}
    </div>
    <div class="follower-title">{{ ($follower->title)? '('.$follower->title.')' : '' }}</div>
  </li>
  
  @empty
  @endforelse
</ul>
<ul class="follower-list agency">
  @forelse ($followers->agents as $follower)
  <?php
    $emailHTML = ($follower->email)?
      '<a href="mailto:'.$follower->email.'"><i class="md s">email</i></a> <div class="popup-tip"><div>Send Email to Contact</div></div>' :
      '<i class="md s grayed">email</i></a> <div class="popup-tip"><div>Email Not Available</div></div>';
    $telHTML = ($follower->tel)?
      '<a href="mailto:'.$follower->tel.'"><i class="md s">phone</i></a> <div class="popup-tip"><div>Call the Contact</div></div>' :
      '<i class="md s grayed">phone</i></a> <div class="popup-tip"><div>Phone # Not Available</div></div>';
  ?>
  <li data-order="{{ enc_id($follower->order_no) }}" class="<?=($follower->valid)? '':'grayed' ?>">
    <div>
      @if($follower->valid)
      <span class="follower-tag agency"><?=($follower->agency)?  $follower->agency : '-' ?></span>
      @else
      <span class="follower-tag invalid"></span>
      @endif

      <div class="follower-actions">
        @if (!$follower->valid || $follower->agency_id == $agency_id)
        {!! Form::open(['url'=> route('lead.ajax-follower-agent-delete', ['lead_id'=> enc_id($lead_id), 'order_no'=> enc_id($follower->order_no)]) ]) !!}

        <span class="popup-base">{!! $emailHTML !!}</span>
        <span class="popup-base">{!! $telHTML !!}</span>
        <span class="popup-base">
          <i class="md s btn-follow-del">close</i></a>
          <div class="popup-tip right"><div>Remove the Follower</div></div>
        </span>

        {!! Form::close() !!}

        @else
        <span class="popup-base">{!! $emailHTML !!}</span>
        <span class="popup-base">{!! $telHTML !!}</span>
        
        @endif
      </div>
    </div>
    <div class="follower-name">
      @if(!$follower->valid)
      <span class="popup-base">
        <i class="md s danger">report_problem</i>
        <div class="popup-tip left"><div><p>The follower mismatch with</p><p>Agent in the system.</p></div></div>
      </span>
      @endif
      {{ $follower->f_name }}
    </div>
    <div class="follower-title">{{ ($follower->title)? '('.$follower->title.')' : '' }}</div>
  </li>
  @empty
  @endforelse
</ul>
<ul class="follower-list provider">
  @forelse ($followers->prov_contacts as $follower)
  <?php
    $emailHTML = ($follower->email)?
      '<a href="mailto:'.$follower->email.'"><i class="md s">email</i></a> <div class="popup-tip"><div>Send Email to Contact</div></div>' :
      '<i class="md s grayed">email</i></a> <div class="popup-tip"><div>Email Not Available</div></div>';
    $telHTML = ($follower->tel)?
      '<a href="mailto:'.$follower->tel.'"><i class="md s">phone</i></a> <div class="popup-tip"><div>Call the Contact</div></div>' :
      '<i class="md s grayed">phone</i></a> <div class="popup-tip"><div>Phone # Not Available</div></div>';
  ?>
  <li data-order="{{ enc_id($follower->order_no) }}" class="<?=($follower->valid)? '':'grayed' ?>">
    <div>
      <span class="follower-tag">{{ $follower->prov }}</span>
      <div class="follower-actions">
        {!! Form::open(['url'=> route('lead.ajax-follower-provider-delete', ['lead_id'=> enc_id($lead_id), 'order_no'=> enc_id($follower->order_no)]) ]) !!}

        <span class="popup-base">{!! $emailHTML !!}</span>
        <span class="popup-base">{!! $telHTML !!}</span>
        <span class="popup-base">
          <i class="md s btn-follow-del">close</i></a>
          <div class="popup-tip right"><div>Remove the Follower</div></div>
        </span>

        {!! Form::close() !!}
      </div>
    </div>
    <div class="follower-name">
      @if(!$follower->valid)
      <span class="popup-base">
        <i class="md s danger">report_problem</i>
        <div class="popup-tip left"><div><p>The follower mismatch with</p><p>Provider Contact in the system.</p></div></div>
      </span>
      @endif
      {{ $follower->f_name }}
    </div>
    <div class="follower-title">{{ ($follower->title)? '('.$follower->title.')' : '' }}</div>
  </li>
  @empty
  @endforelse
</ul>

@endif
