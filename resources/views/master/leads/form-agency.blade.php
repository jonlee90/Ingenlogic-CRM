<?php
/**
* required vars
* @param $agencies: available agencies
* @param $lead_id: currently opened lead ID
* @param $frm_url: URL to submit form
*/
?>
{!! Form::open(['url'=> $frm_url, 'class'=> 'frm-agency']) !!}
  {!! Form::hidden('agency_id') !!}
  
  <h2>Available Agencies</h2>

  <div class="overlay-list-frame lead-list-agency">
    <table id="tbl-lead-agency-available">
      <thead><tr> <th></th> <th>Name</th> <th>Default Spiff</th> <th>Default Residual</th> </tr></thead>
      <tbody>
        @foreach ($agencies as $agency)
        <tr data-id="{{ enc_id($agency->id) }}" class="btn-agency-select">
          <td><i class="md md-18" title="Assign the Agency">done</i></td>
          <td>{{ $agency->name }}</td>
          <td>{{ number_format($agency->spiff, 2) }} %</td>
          <td>{{ number_format($agency->residual, 2) }} %</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
                    
  <div class="btn-group">
    {!! Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
  </div>

{!! Form::close() !!}

<script>moAgencyChange()</script>
