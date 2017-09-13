<?php
/**
* required vars
* @param $prov: Provider object
* @param $row_states: array of states (for 'select' element)
**/
?>
<div class='input-group'>
  <label>Status</label>
  <div class="col-n">
    {!! Form::radio('is_active', 1, ($prov->active >0), ['id'=>'r-active-1']) !!}
    <label for="r-active-1">Active</label>
    {!! Form::radio('is_active', 0, !($prov->active >0), ['id'=>'r-active-0']) !!}
    <label for="r-active-0">Inactive</label>
  </div>
</div>
<div class='input-group'>
  <label>Company Name</label>
  {!! Form::text('c_name', $prov->name, ['maxlength'=>50, 'required']) !!}
</div>

<div class="spacer-h"></div>

<div class='input-group'>
  <label>Address</label>
  {!! Form::text('addr', $prov->addr, ['maxlength'=> 100, 'placeholder'=> 'Street Address',]) !!}
</div>
<div class='input-group'>
  <label>Address 2</label>
  {!! Form::text('addr2', $prov->addr2, ['maxlength'=> 50, 'placeholder'=> 'Ste, Unit, Apt']) !!}
</div>
<div class='input-group'>
  <label>City</label>
  {!! Form::text('city', $prov->city, ['maxlength'=> 50,]) !!}
</div>
<div class='input-group'>
  <label>State</label>
  {!! Form::select('state_id', $row_states, $prov->state_id, []) !!}
</div>
<div class='input-group'>
  <label>Zip Code</label>
  {!! Form::tel('zip', $prov->zip, ['maxlength'=> 10, 'pattern'=> '^\d{5}(-\d{4})?$', 'title'=> 'Valid US zip code']) !!}
</div>
<div class='input-group'>
  <label>Phone Number</label>
  {!! Form::tel('tel', $prov->tel, ['maxlength'=> 20, 'required', 'pattern'=> '^\d{10}$', 'title'=> '10 digit Phone number without dash or space.']) !!}
</div>

<div class="spacer-h"></div>

<div class='input-group'>
  <label>Default Term</label>
  <div class="wrapper-input">
    <div class="row">
      <div class="cell popup-base">
        {!! Form::number('term', $prov->default_term, ['min'=> 1, 'step'=> 1, 'required']) !!}
        <div class="popup-tip">
          <div><p>Default Term in months.</p><p>Use 1 for Month to Month (No Contract).</p></div>
        </div>
      </div>
      <label><p>month</p></label>
    </div>
  </div>
</div>
<div class='input-group'>
  <label>Default Spiff Rate</label>
  <div class="wrapper-input">
    <div class="row">
      <div class="cell">
        {!! Form::number('spiff', $prov->default_spiff, ['min'=>0, 'step'=>0.01, 'required']) !!}
      </div>
      <label><span class="fa-percent"></span></label>
    </div>
  </div>
</div>
<div class='input-group'>
  <label>Default Residual Rate</label>
  <div class="wrapper-input">
    <div class="row">
      <div class="cell">
        {!! Form::number('resid', $prov->default_residual, ['min'=>0, 'step'=>0,01, 'required']) !!}
      </div>
      <label><span class="fa-percent"></span></label>
    </div>
  </div>
</div>
