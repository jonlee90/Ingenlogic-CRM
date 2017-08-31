<?php
/**
* required vars
* @param $accnt: location x account object
* @param $providers: name list of providers (active)
*/
?>
<div class="input-group">
  <label>Service Provider Name</label>
  {!! Form::text('p_name', $accnt->name, ['list'=> 'overlay-list-providers', 'maxlength'=>50, 'required']) !!}
</div>
<div class="input-group">
  <label>Account No</label>
  {!! Form::text('accnt_no', $accnt->accnt_no, ['maxlength'=> 30, 'required']) !!}
</div>
<div class="input-group">
  <label>Account Passcode</label>
  {!! Form::text('passcode', $accnt->passcode, ['maxlength'=> 50]) !!}
</div>
<div class="input-group">
  <label>Terms</label>
  <div class="wrapper-input">
    <div class="row">
      <div class="cell">
        {!! Form::number('term', $accnt->term, ['min'=> 0, 'step'=> 1, 'max'=> 500, 'pattern'=> '^\d+$', 'title'=> 'Enter the terms in months.']) !!}
      </div>
      <label><p>month</p></label>
    </div>
  </div>
</div>
<div class="input-group">
  <label>Contract End Date</label>
  <div class="wrapper-input">
    <div class="row">
      <div class="cell">
        {!! Form::text('date_end', $accnt->date_contract_end, ['id'=> 'cal-date-end', 'class'=> 'cal-date-end', 'readonly']) !!}
      </div>
      <label for="cal-date-end" class="btn-cal"><span class="fa-calendar"></span></label>
    </div>
  </div>
</div>
<div class="input-group">
  <label>ETF</label>
  <div class="wrapper-input">
    <div class="row">
      <label><span class="fa-usd"></span></label>
      <div class="cell">
        {!! Form::number('etf', $accnt->etf, ['min'=> 0, 'step'=> 0.01, ]) !!}
      </div>
    </div>
  </div>
</div>
<div class="input-group">
  <label>Memo</label>
  <div class="wrapper-textarea lead-curr-accnt-textarea">
    {!! Form::textarea('memo', $accnt->memo, ['maxlength'=> 500, ]) !!}
    <div class="chr-left">{{ strlen(str_replace("\r\n","\n", $accnt->memo)) }} / 500</div>
  </div>
</div>

<datalist id="overlay-list-providers">
  @forelse ($providers as $prov)
  <option value="{{ $prov->name }}"></option>
  @empty  
  @endforelse
</datalist>
