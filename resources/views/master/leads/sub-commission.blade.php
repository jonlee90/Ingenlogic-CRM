<?php
/**
* required vars
* @param $lead_id: lead ID
* @param $permissions: [mod, manager, commission]
* @param $agencies: array of agency objects
* @param $managers: array of manager (ch-manager users) objects
*/
?>
<h3>Agencies</h3>
<ul>
@forelse ($agencies as $agency)
  <li>
    <div class="float-r">
    @if ($permissions->mod && $agency->is_accessible)

    {!! Form::open(['url'=> route('master.lead.agency-remove', ['lead_id'=> enc_id($lead_id), 'agency_id'=> enc_id($agency->id)]), 'class'=> 'frm-agency-remove']) !!}

    <span class="popup-base">
      <i class="md s btn-agency-del">close</i>
      <div class="popup-tip right"><div>Remove the Agency</div></div>
    </span>

    {!! Form::close() !!}

    @else
      <i class="md s grayed">close</i>

    @endif
    </div>
    {{ $agency->name }}
  </li>

@empty
  <li class="err">There is No Agency assigned.</li>

@endforelse
</ul>

@if ($permissions->mod && count($agencies) < MAX_AGENCY_PER_LEAD)
<button type="button" class="btn-assign-agency">Assign New Agency</button>
@endif

<div class="spacer-h"></div>

<h3>Managers</h3>
<ul>
@forelse ($managers as $manager)
  <li>
    <div class="float-r">
      @if ($manager->is_primary)
        <span class="popup-base">
          <i class="md s primary">star</i>
          <div class="popup-tip"><div>Primary Manager</div></div>
        </span>
        <span class="popup-base">
          <i class="md s grayed">close</i>
          <div class="popup-tip right"><div>Primary Manager cannot be Removed</div></div>
        </span>

      @elseif ($permissions->manager)
      {!! Form::open(['url'=> route('master.lead.manager-primary', ['lead_id'=> enc_id($lead_id), 'manager_id'=> enc_id($manager->id)]), 'class'=> 'frm-manager-action inline']) !!}
        <span class="popup-base">
          <i class="md s btn-manager-primary">star</i>
          <div class="popup-tip"><div>Set as Primary Manager</div></div>
        </span>
      {!! Form::close() !!}

      {!! Form::open(['url'=> route('master.lead.manager-remove', ['lead_id'=> enc_id($lead_id), 'manager_id'=> enc_id($manager->id)]), 'class'=> 'frm-manager-action inline']) !!}
        <span class="popup-base">
          <i class="md s btn-manager-del">close</i>
          <div class="popup-tip right"><div>Remove the Manager</div></div>
        </span>

      {!! Form::close() !!}

      @else
        <i class="md s grayed">star</i>
        <i class="md s grayed">close</i>
        
      @endif
    </div>
    {{ trim($manager->fname.' '.$manager->lname) }}
  </li>

@empty
  <li class="err">There is No Manager assigned.</li>

@endforelse
</ul>

@if ($permissions->manager && count($managers) < MAX_MANAGER_PER_LEAD)
<button type="button" class="btn-assign-manager">Assign New Manager</button>
@endif
