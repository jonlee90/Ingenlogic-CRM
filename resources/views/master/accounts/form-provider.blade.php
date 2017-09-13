<?php
/**
* required vars
* @param $providers: array of provider object
*/
?>
<div class="overlay-comm-accnt-prov">
  <h2>Available Service Providers</h2>

  <table id="tbl-accnt-provider-available" class="tbl-accnt-provider-available">
    <thead><tr> <th></th> <th>Name</th> </tr></thead>
    <tbody>
      @foreach ($providers as $prov)
      <tr data-id="{{ enc_id($prov->id) }}" class="btn-prov-select">
        <td><i class="md md-18" title="Select Provider">done</i></td>
        <td class="prov-name">{{ $prov->name }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
    
  <div class='btn-group'>
    {!! Form::button('cancel', ['class'=> 'btn-cancel']) !!}
  </div>
  <script>moAccountProvider()</script>
</div>
