<?php
/**
* required vars
* @param $account: account object
* @param $providers: array of provider object
*/
?>
<div class="overlay-comm-accnt-view-prov">
  {!! Form::open(['url'=> route('master.account.update-provider', ['accnt_id'=> enc_id($account->id)])]) !!}
    {!! Form::hidden(['orig_prov_id'=> enc_id($account->provider_id)]) !!}
    
    <h2>Available Service Providers</h2>

    <table id="tbl-accnt-provider-available" class="tbl-accnt-provider-available">
      <thead><tr> <th></th> <th>Name</th> </tr></thead>
      <tbody>
        @foreach ($providers as $prov)
        <tr class="btn-prov-select">
          <td>{!! Form::radio('prov_id', enc_id($prov->id), ($prov->id == $account->provider_id)) !!}</td>
          <td class="prov-name">{{ $prov->name }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    
    <div class='input-group'>
      <label>Term</label>
      <div class="wrapper-input">
        <div class="row">
          <div class="cell popup-base">
            {!! Form::number('term', $account->term, ['min'=> 1, 'step'=> 1, 'required']) !!}
            <div class="popup-tip">
              <div><p>Default Term in months.</p><p>Use 1 for Month to Month (No Contract).</p></div>
            </div>
          </div>
          <label><p>month</p></label>
        </div>
      </div>
    </div>
      
    <div class='btn-group'>
      {!! Form::submit('update') !!}
      {!! Form::button('cancel', ['class'=> 'btn-cancel']) !!}
    </div>
  {!! Form::close() !!}
</div>
<script>moAccountProvider()</script>
