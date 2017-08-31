<?php
/**
 * required vars
 * @param $prod: provider x service-product object
 * @param $data: [services]
 */
?>
<div class="input-group">
  <label>Service</label>
  {!! Form::select('svc_id', $data->services, enc_id($prod->service_id), ['maxlength'=>50, 'required']) !!}
</div>
<div class="input-group">
  <label>Product Name</label>
  {!! Form::text('prod', $prod->p_name, ['maxlength'=>50, 'required']) !!}
</div>
<div class="input-group">
  <label>Default Price</label>
  <div class="wrapper-input">
    <div class="row">
      <label><span class="fa-usd"></span></label>
      <div class="cell">
        {!! Form::number('price', $prod->price, ['min'=> 0, 'step'=> 0.01, 'required']) !!}
      </div>
    </div>
  </div>
</div>
<div class="input-group">
  <label>Spiff Rate</label>
  <div class="wrapper-input">
    <div class="row">
      <div class="cell">
        {!! Form::number('spiff', $prod->rate_spiff, ['min'=> 0, 'step'=> 0.01, 'required']) !!}
      </div>
      <label><span class="fa-percent"></span></label>
    </div>
  </div>
</div>
<div class="input-group">
  <label>Residual Rate</label>
  <div class="wrapper-input">
    <div class="row">
      <div class="cell">
        {!! Form::number('resid', $prod->rate_residual, ['min'=> 0, 'step'=> 0.01, 'required']) !!}
      </div>
      <label><span class="fa-percent"></span></label>
    </div>
  </div>
</div>
