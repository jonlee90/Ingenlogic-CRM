<?php
/**
* required vars
* @param $accnt: location x quote object
*/
?>
<div class="overlay-form overlay-lead-product">
  {!! Form::open(['url'=> route('project.ajax-sign-update', ['accnt_id'=> enc_id($accnt->id)]), 'class'=>'frm-update']) !!}
  
    <div class="input-group">
      <label>Contract Signed Date</label>
      <div class="wrapper-input">
        <div class="row">
          <div class="cell">
            {!! Form::text('sign_date', $accnt->date_signed, ['id'=> 'cal-date-sign', 'class'=> 'cal-date-input', 'readonly', ] ) !!}
          </div>
          <label for="cal-date-sign" class="btn-cal"><span class="fa-calendar"></span></label>
        </div>
      </div>
    </div>
    <div class="input-group">
      <label>Site Survey Date</label>
      <div class="wrapper-input">
        <div class="row">
          <div class="cell">
            {!! Form::text('inspect_date', $accnt->date_inspect, ['id'=> 'cal-date-inspect', 'class'=> 'cal-date-input', 'readonly', ] ) !!}
          </div>
          <label for="cal-date-inspect" class="btn-cal"><span class="fa-calendar"></span></label>
        </div>
      </div>
      <div class="col-n">
        {!! Form::checkbox('inspect_done', 1, $accnt->inspect_done, ['id'=> 'k-inspect-complete', ] ) !!}
        <label for="k-inspect-complete">Completed</label>
      </div>
    </div>
    <div class="input-group">
      <label>Construction Date</label>
      <div class="wrapper-input">
        <div class="row">
          <div class="cell">
            {!! Form::text('construct_date', $accnt->date_construct, ['id'=> 'cal-date-construct', 'class'=> 'cal-date-input', 'readonly', ] ) !!}
          </div>
          <label for="cal-date-construct" class="btn-cal"><span class="fa-calendar"></span></label>
        </div>
      </div>
      <div class="col-n">
        {!! Form::checkbox('construct_done', 1, $accnt->construct_done, ['id'=> 'k-construct-complete', ] ) !!}
        <label for="k-construct-complete">Completed</label>
      </div>
    </div>
    <div class="input-group">
      <label>Installation Date</label>
      <div class="wrapper-input">
        <div class="row">
          <div class="cell">
            {!! Form::text('install_date', $accnt->date_install, ['id'=> 'cal-date-install', 'class'=> 'cal-date-input', 'readonly', ] ) !!}
          </div>
          <label for="cal-date-install" class="btn-cal"><span class="fa-calendar"></span></label>
        </div>
      </div>
      <div class="col-n">
        {!! Form::checkbox('install_done', 1, $accnt->install_done, ['id'=> 'k-install-complete', ] ) !!}
        <label for="k-install-complete">Completed</label>
      </div>
    </div>
    <div class="input-group">
      <label>Port In Date</label>
      <div class="wrapper-input">
        <div class="row">
          <div class="cell">
            {!! Form::text('port_date', $accnt->date_portin, ['id'=> 'cal-date-port', 'class'=> 'cal-date-input', 'readonly', ] ) !!}
          </div>
          <label for="cal-date-port" class="btn-cal"><span class="fa-calendar"></span></label>
        </div>
      </div>
      <div class="col-n">
        {!! Form::checkbox('port_done', 1, $accnt->portin_done, ['id'=> 'k-port-complete', ] ) !!}
        <label for="k-port-complete">Completed</label>
      </div>
    </div>

    <div class="btn-group">
      {!! Form::submit('Save Dates').' '.Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
    </div>
  {!! Form::close() !!}
</div>
