<?php
/**
* required vars
* @param $agencies: array of agency object
* @param $managers: array of manager object
*/
?>
<div class="overlay-container-change-wrapper overlay-comm-accnt-share">
  <div class="btn-group">
    <button class="btn-agency">Agency</button>
    <button class="btn-manager">Channel Manager</button>
  </div>

  <div class="container-change agency">
    <div class="list-comm-accnt-agency">
      <h2>Available Agencies</h2>

      <table id="tbl-accnt-agency-available">
        <thead><tr> <th></th> <th>Name</th> </tr></thead>
        <tbody>
          @foreach ($agencies as $agency)
          <tr data-id="{{ enc_id($agency->id) }}" class="btn-agency-select">
            <td><i class="md md-18" title="Select Provider">done</i></td>
            <td class="agency-name">{{ $agency->name }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="list-comm-accnt-manager">
      <h2>Available Channel Managers</h2>

      <table id="tbl-accnt-manager-available">
        <thead><tr> <th></th> <th>Name</th> </tr></thead>
        <tbody>
          @foreach ($managers as $manager)
          <tr data-id="{{ enc_id($manager->id) }}" class="btn-manager-select">
            <td><i class="md md-18" title="Select Provider">done</i></td>
            <td class="manager-name">{{ trim($manager->fname.' '.$manager->lname) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
    
  <div class='btn-group'>
    {!! Form::button('cancel', ['class'=> 'btn-cancel']) !!}
  </div>
  <script>moAccountCommission()</script>
</div>
