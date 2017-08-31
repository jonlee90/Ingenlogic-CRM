@extends('layouts.app')

@section('title', "Update User | ".SITE_TITLE." Control Panel v2")

@section('content')
  <div class="panel">
    <h2><?=(Auth::id() == $user->id)? 'My Profile' : 'User Account Information' ?></h2>

    <div class='input-group'>
      <label>Status</label>
      <div class="output"><?=($user->active >0)? '<span class="primary">Active</span>' : '<span class="danger">Inactive</span>' ?></div>
    </div>
    <div class='input-group'>
      <label>Position</label>
      <div class="output">{{ config_pos_name($user->access_lv) }}</div>
    </div>
    <div class='input-group'>
      <label>Email Address</label>
      <div class="output">{{ $user->email }}</div>
    </div>
    <div class='input-group'>
      <label>Name</label>
      <div class="output">{{ trim($user->fname.' '.$user->lname) }}</div>
    </div>

    <div class="btn-group">
    
    @if ( $user->id == Auth::id() || $preapp->lv >= POS_LV_SYS_ADMIN || in_array($user->access_lv, map_editable_positions($preapp->lv)) )
      <a href="{{ route('user.mod',['id'=>enc_id($user->id)]) }}"><input type="button" value="Update User"></a>
    @endif

    @if ($preapp->perm_user_view)
      <a href="{{ route('user.list') }}"><input type="button" value="Return to List"></a>
    @endif
    </div>
  </div>

  @if ($preapp->perm_agency_view && $agency)
  <div class="panel">
    <h2>Agency Information</h2>

    <div class='input-group'>
      <label>Company Name</label>
      <div class="output">{{ $agency->name }}</div>
    </div>
    <div class='input-group'>
      <label>Address</label>
      <div class="output">{{ trim($agency->addr.' '.$agency->addr2) }}</div>
    </div>
    <div class='input-group'>
      <?php
        $state_zip = trim($agency->state_code.' '.$agency->zip);
        $city_state_zip = ($agency->city && $state_zip)?  $agency->city.', '.$state_zip : $agency->city.$state_zip;
      ?>
      <label>City, State, Zip</label>
      <div class="output">{{ $city_state_zip }}</div>
    </div>
    <div class='input-group'>
      <label>Phone Number</label>
      <div class="output">{{ format_tel($agency->tel) }}</div>
    </div>

    @if ($preapp->perm_agency_mod)
    <div class="btn-group">
      <a href="{{ route('user.agency') }}"><input type="button" value="Update Agency"></a>
    </div>
    @endif
  </div>
  
  @endif
@endsection