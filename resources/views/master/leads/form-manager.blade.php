<?php
/**
* required vars
* @param $managers: available managers
* @param $lead_id: currently opened lead ID
* @param $frm_url: URL to submit form
*/
?>
{!! Form::open(['url'=> $frm_url, 'class'=> 'frm-manager']) !!}
  {!! Form::hidden('manager_id') !!}
  
  <h2>Available Managers</h2>

  <div class="overlay-list-frame lead-list-manager">
    <table id="tbl-lead-manager-available">
      <thead><tr> <th></th> <th>Name</th> </tr></thead>
      <tbody>
        @foreach ($managers as $manager)
        <tr data-id="{{ enc_id($manager->id) }}" class="btn-manager-select">
          <td><i class="md md-18" title="Assign the Manager">done</i></td>
          <td>{{ trim($manager->fname.' '.$manager->lname) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
                    
  <div class="btn-group">
    {!! Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
  </div>

{!! Form::close() !!}

<script>moManagerChange()</script>
