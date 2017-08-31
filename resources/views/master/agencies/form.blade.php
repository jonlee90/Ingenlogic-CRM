<?php
/**
* required vars
* @param $agency: Agency object
* @param $row_states: array of states (for 'select' element)
**/
?>
<div class='input-group'>
  <label>Status</label>
  <div class="col-n">
    {!! Form::radio('is_active', 1, ($agency->active >0), ['id'=>'r-active-1']) !!}
    <label for="r-active-1">Active</label>
    {!! Form::radio('is_active', 0, !($agency->active >0), ['id'=>'r-active-0']) !!}
    <label for="r-active-0">Inactive</label>
  </div>
</div>
<div class='input-group'>
  <label>Company Name</label>
  {!! Form::text('c_name', $agency->name, ['maxlength'=>50, 'required']) !!}
</div>

<div class="spacer-h"></div>

<div class='input-group'>
  <label>Address</label>
  {!! Form::text('addr', $agency->addr, ['maxlength'=> 100, 'placeholder'=> 'Street Address',]) !!}
</div>
<div class='input-group'>
  <label>Address 2</label>
  {!! Form::text('addr2', $agency->addr2, ['maxlength'=> 50, 'placeholder'=> 'Ste, Unit, Apt']) !!}
</div>
<div class='input-group'>
  <label>City</label>
  {!! Form::text('city', $agency->city, ['maxlength'=> 50,]) !!}
</div>
<div class='input-group'>
  <label>State</label>
  {!! Form::select('state_id', $row_states, $agency->state_id, []) !!}
</div>
<div class='input-group'>
  <label>Zip Code</label>
  {!! Form::tel('zip', $agency->zip, ['maxlength'=> 10, 'pattern'=> '^\d{5}(-\d{4})?$', 'title'=> 'Valid US zip code']) !!}
</div>
<div class='input-group'>
  <label>Phone Number</label>
  {!! Form::tel('tel', $agency->tel, ['maxlength'=> 20, 'required', 'pattern'=> '^\d{10}$', 'title'=> '10 digit Phone number without dash or space.']) !!}
</div>

<div class="spacer-h"></div>

<div class='input-group'>
  <label>Default Spiff Share</label>
  <div class="wrapper-input">
    <div class="row">
      <div class="cell popup-base">
        {!! Form::number('spiff', $agency->spiff, ['min'=>0, 'step'=>0.01, 'max'=>100, 'required']) !!}
        <div class="popup-tip"><div>
          <p>Agency share of Spiff Rate (0 - {{ MAX_AGENCY_RATE }} %)</p>
        </div></div>
      </div>
      <label><span class="fa-percent"></span></label>
    </div>
  </div>
</div>
<div class='input-group'>
  <label>Default Residual Share</label>
  <div class="wrapper-input">
    <div class="row">
      <div class="cell popup-base">
        {!! Form::number('resid', $agency->residual, ['min'=>0, 'step'=>0.01, 'max'=>100, 'required']) !!}
        <div class="popup-tip"><div>
          <p>Agency share of Residual Rate (0 - {{ MAX_AGENCY_RATE }} %)</p>
        </div></div>
      </div>
      <label><span class="fa-percent"></span></label>
    </div>
  </div>
</div>