@extends('layouts.master')

@section('title', "Agency Overview | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Agency  Information</h2>
  
  <div class='input-group'>
    <label>Status</label>
    <div class="output"><?=($agency->active >0)? '<span class="primary">Active</span>' : '<span class="danger">Inactive</span>' ?></div>
  </div>
  <div class='input-group'>
    <label>Company Name</label>
    <div class="output">{{ $agency->name }}</div>
  </div>
  <div class='input-group'>
    <label>Channel Manager</label>
    <div class="output"><?=($agency->manager)? trim($agency->manager->fname.' '.$agency->manager->lname) : '<span class="err">* Not Assigned</span>' ?></div>
  </div>
  
  <div class="spacer-h"></div>
  
  <div class='input-group'>
    <label>Address</label>
    <div class="output">{{ $agency->addr }}</div>
  </div>
  <div class='input-group'>
    <label>Address 2</label>
    <div class="output">{{ $agency->addr2 }}</div>
  </div>
  <div class='input-group'>
  <?php
    $city_state = $agency->city;
    $city_state .= ($city_state && $agency->state_code)?  ', '.$agency->state_code : $agency->state_code;
  ?>
    <label>City/State/Zip</label>
    <div class="output">{{ trim($city_state.' '.$agency->zip) }}</div>
  </div>
  <div class='input-group'>
    <label>Phone Number</label>
    <div class="output">{{ format_tel($agency->tel) }}</div>
  </div>
  
  <div class="spacer-h"></div>

  <div class='input-group'>
    <label>Default Spiff Share</label>
    <div class="output">{{ $agency->spiff }} %</div>
  </div>
  <div class='input-group'>
    <label>Default Residual Share</label>
    <div class="output">{{ $agency->residual }} %</div>
  </div> 
  
  <div class='btn-group'>
    @if ($preapp->perm_agency_mod)
    <a href="{{ route('master.agency.mod', ['id'=>enc_id($agency->id)]) }}"><button>Update Information</button></a>
    @endif
    <a href="{{ route('master.agency.list') }}"><button type="button">Return to List</button></a>
  </div>
</div>
@endsection
