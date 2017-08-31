<?php
/**
 * required vars
 * @param $lead: lead object (with customer info)
 * @param $data: object with $row_states (= array list of states)
 */
?>
<div class="input-group">
  <label>Customer Name</label>
  {!! Form::text('c_name', $lead->cust_name, ['maxlength'=>50, 'required']) !!}
</div>
<div class="input-group">
  <label>Phone Number</label>
  {!! Form::tel('tel', $lead->tel, ['maxlength'=> 20, 'required', 'pattern'=> '^\d{10}$', 'title'=> '10 digit Phone number without dash or space.']) !!}
</div>

<div class="spacer-h"></div>

<div class="input-group">
  <label>Tax ID</label>
  {!! Form::tel('tax_id', $lead->tax_id, ['maxlength'=>10, 'placeholder'=> '12-3456789', 'pattern'=> '^\d{2}-\d{7}$', 'title'=> '12-3456789 format']) !!}
</div>
<div class="input-group">
  <label>Email Address</label>
  {!! Form::email('email', $lead->email, ['maxlength'=>100]) !!}
</div>

<div class="input-group">
  <label>Address</label>
  {!! Form::text('addr', $lead->addr, ['maxlength'=> 100, 'placeholder'=> 'Street Address']) !!}
</div>
<div class="input-group">
  <label>Address 2</label>
  {!! Form::text('addr2', $lead->addr2, ['maxlength'=> 50, 'placeholder'=> 'Ste, Unit, Apt']) !!}
</div>
<div class="input-group">
  <label>City</label>
  {!! Form::text('city', $lead->city, ['maxlength'=> 50]) !!}
</div>
<div class="input-group">
  <label>State</label>
  {!! Form::select('state_id', $data->row_states, $lead->state_id, []) !!}
</div>
<div class="input-group">
  <label>Zip Code</label>
  {!! Form::tel('zip', $lead->zip, ['maxlength'=> 10, 'pattern'=> '^\d{5}(-\d{4})?$', 'title'=> 'Valid US zip code']) !!}
</div>