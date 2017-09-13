<?php
/**
* required vars
* @param $agencies: array of agency object
* @param $managers: array of manager object
* @param $account: account object (+ agencies, managers)
*/
?>
<div class="overlay-container-change-wrapper overlay-comm-accnt-view-share">
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
            <td>
              <div class="popup-base">
                <i class="fa-plus-square"></i>
                <div class="popup-tip left"><div>Add Agency</div></div>
              </div>
            </td>
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
            <td>
              <div class="popup-base">
                <i class="fa-plus-square"></i>
                <div class="popup-tip left"><div>Add Channel Manager</div></div>
              </div>
            </td>
            <td class="manager-name">{{ trim($manager->fname.' '.$manager->lname) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="spacer-h"></div>
  
  {!! Form::open(['url'=> route('master.account.update-commission', ['accnt_id'=> enc_id($account->id)])]) !!}
    <table class="tbl-comm-accnt-share">
      <thead>
        <tr>
          <th></th>
          <th>Name</th>
          <th>Position</th>
          <th>Spiff</th>
          <th>Residual</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($account->agencies as $agency)
        <tr>
          <td>
            <span class="popup-base">
              <i class="md s btn-del-party">close</i>
              <div class="popup-tip left"><div>Remove Agency</div></div>
            </span>
          </td>
          <td>
            @if (!$agency->valid)
            {{ $agency->agency }} 
            <span class="popup-base">
              <i class="md s danger">report_problem</i>
              <div class="popup-tip"><div>Associated Agency is Invalid</div></div>
            </span>
            
            @else
            {{ $agency->new_name }}
              @if ($agency->new_name != $agency->agency)
            <span class="popup-base">
              ({{ $agency->agency }})
              <div class="popup-tip"><div>Associated Agency has a New Name</div></div>
            </span>
              @endif

              @if (!$agency->active)
            <span class="popup-base">
              <i class="md s danger">report_problem</i>
              <div class="popup-tip"><div>Associated Agency is Inactive</div></div>
            </span>
              @endif
              
            @endif
          </td>
          <td>Agency</td>
          <td>{!! Form::number('spiff_share[agency]['.enc_id($agency->agency_id).']', number_format($agency->spiff, 2, '.',''), [
                    'min'=> 0, 'step'=> 0.01, 'max'=> 100, 'required']) !!}</td>
          <td>{!! Form::number('resid_share[agency]['.enc_id($agency->agency_id).']', number_format($agency->residual, 2, '.',''), [
                    'min'=> 0, 'step'=> 0.01, 'max'=> 100, 'required']) !!}</td>
        </tr>
        @empty
        @endforelse
        
        @forelse ($account->managers as $manager)
        <tr>
          <td>
            <span class="popup-base">
              <i class="md s btn-del-party">close</i>
              <div class="popup-tip left"><div>Remove Manager</div></div>
            </span>
          </td>
          <td>
            @if (!$manager->valid)
            {{ $manager->manager }} 
            <span class="popup-base">
              <i class="md s danger">report_problem</i>
              <div class="popup-tip"><div>Associated Manager is Invalid</div></div>
            </span>
            
            @else
            {{ trim($manager->fname.' '.$manager->lname) }}
              @if (trim($manager->fname.' '.$manager->lname) != $manager->manager)
            <span class="popup-base">
              ({{ $manager->manager }})
              <div class="popup-tip"><div>Associated Manager has a New Name</div></div>
            </span>
              @endif

              @if (!$manager->active)
            <span class="popup-base">
              <i class="md s danger">report_problem</i>
              <div class="popup-tip"><div>Associated Manager is Inactive</div></div>
            </span>
              @endif
              
            @endif
          </td>
          <td>Channel Manager</td>
          <td>{!! Form::number('spiff_share[manager]['.enc_id($manager->user_id).']', number_format($manager->spiff, 2, '.',''), [
                    'min'=> 0, 'step'=> 0.01, 'max'=> 100, 'required']) !!}</td>
          <td>{!! Form::number('resid_share[manager]['.enc_id($manager->user_id).']', number_format($manager->residual, 2, '.',''), [
                    'min'=> 0, 'step'=> 0.01, 'max'=> 100, 'required']) !!}</td>
        </tr>
        @empty
        @endforelse
      </tbody>
    </table>
    <div class="spacer-h"></div>
      
    <div class='btn-group'>
      {!! Form::submit('update') !!}
      {!! Form::button('cancel', ['class'=> 'btn-cancel']) !!}
    </div>

  {!! Form::close() !!}
</div>
<script>moAccountCommission()</script>
