<?php
/**
* required vars
* @param $accnt: location x account object
*/
?>
{!! Form::open(['url'=> route('project.ajax-cancel-update', ['accnt_id'=> enc_id($accnt->id)]), 'class'=>'frm-update' ]) !!}

  <div class="input-group">
    <label>Port Out Date</label>
    <div class="wrapper-input">
      <div class="row">
        <div class="cell">
          {!! Form::text('port_date', $accnt->date_portout, ['id'=> 'cal-date-port', 'class'=> 'cal-date-input', 'readonly', ] ) !!}
        </div>
        <label for="cal-date-port" class="btn-cal"><span class="fa-calendar"></span></label>
      </div>
    </div>
  </div>
  <div class="input-group">
    <label>Cancelled Date</label>
    <div class="wrapper-input">
      <div class="row">
        <div class="cell">
          {!! Form::text('cancel_date', $accnt->date_cancel, ['id'=> 'cal-date-cancel', 'class'=> 'cal-date-input', 'readonly', ] ) !!}
        </div>
        <label for="cal-date-cancel" class="btn-cal"><span class="fa-calendar"></span></label>
      </div>
    </div>
  </div>

  <div class="btn-group">
    {!! Form::submit('Save Dates').' '.Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
  </div>
{!! Form::close() !!}
