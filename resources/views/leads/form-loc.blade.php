<?php
/**
 * required vars
 * @param $loc: location object
 * @param $data: object with $row_states (= array list of states)
 */
?>
<div class="input-group">
  <label>Location Name</label>
  {!! Form::text('l_name', $loc->name, ['maxlength'=>50, 'required']) !!}
</div>
<div class="input-group">
  <label>Address</label>
  {!! Form::text('addr', $loc->addr, ['maxlength'=> 100, 'placeholder'=> 'Street Address']) !!}
</div>
<div class="input-group">
  <label>Address 2</label>
  {!! Form::text('addr2', $loc->addr2, ['maxlength'=> 50, 'placeholder'=> 'Ste, Unit, Apt']) !!}
</div>
<div class="input-group">
  <label>City</label>
  {!! Form::text('city', $loc->city, ['maxlength'=> 50]) !!}
</div>
<div class="input-group">
  <label>State</label>
  {!! Form::select('state_id', $data->row_states, $loc->state_id, []) !!}
</div>
<div class="input-group">
  <label>Zip Code</label>
  {!! Form::tel('zip', $loc->zip, ['maxlength'=> 10, 'pattern'=> '^\d{5}(-\d{4})?$', 'title'=> 'Valid US zip code']) !!}
</div>