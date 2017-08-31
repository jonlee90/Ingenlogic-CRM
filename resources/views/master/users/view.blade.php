<?php
/**
* required vars
* @param $user: user object
* @param $data: [
*   agency: if user is 'Agent', agency of the user
*   manager: (string) if user is 'Channel Assistant', channel-manager
*   agencies: if user is 'Channel' partner, agencies assigned to the user (or the channel-manager associated)
* ]
**/
?>
@extends('layouts.master')

@section('title', "User Overview | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>User Account Information</h2>
  
  <div class='input-group'>
    <label>Position</label>
    <div class="output">{{ config_pos_name($user->access_lv) }}</div>
  </div>
  
  <div class='input-group'>
    <label>Status</label>
    <div class="output"><?=($user->active >0)? '<span class="primary">Active</span>' : '<span class="danger">Inactive</span>' ?></div>
  </div>
  <div class='input-group'>
    <label>Email Address</label>
    <div class="output">{{ $user->email }}</div>
  </div>
  <div class='input-group'>
    <label>Name</label>
    <div class="output">{{ trim($user->fname.' '.$user->lname) }}</div>
  </div>
  
  @if (POS_LV_AGENT_USER <= $user->access_lv && $user->access_lv <= POS_LV_AGENT_ADMIN)
    <div class="spacer-h"></div>

    <div class='input-group'>
      <label>Assigned Agency</label>
      <div class="output">
        @if ($data->agency)
        <?=($data->agency->active >0)?  $data->agency->name : '<span class="grayed">'.$data->agency->name.' (Inactive)</span>' ?>
        @else
          <span class="err">* Unassigned</span>
        @endif
      </div>
    </div>
    
  @elseif ($user->access_lv == POS_LV_CH_MANAGER && ($preapp->lv >= POS_LV_MASTER_USER || Auth::id() == $user->id))
  <div class="spacer-h"></div>
  
  <div class='input-group'>
    <label>Default Spiff Share</label>
    <div class="output">{{ number_format($user->spiff, 2) }} %</div>
  </div>
  <div class='input-group'>
    <label>Default Spiff Share</label>
    <div class="output">{{ number_format($user->residual, 2) }} %</div>
  </div>
  @endif
  
  <div class='btn-group'>
    
    @if ( $user->id == Auth::id() || in_array($user->access_lv, map_editable_positions($preapp->lv)) )
      <a href="{{ route('master.user.mod', ['id'=> enc_id($user->id)]) }}"><button>Update User</button></a>
    @endif
    
    <a href="{{ route('master.user.list') }}"><button>Return to List</button></a>
  </div>
</div>

<?php // ***** channel managers: agency association is available ***** ?>
@if ($user->access_lv == POS_LV_CH_MANAGER)
<div class="panel">
  <h2>Assigned Agencies</h2>

  @if (in_array($user->access_lv, map_editable_positions($preapp->lv)) )
    <button class="btn-agency-assign">Assign Agency</button>
  @endif

  @unless ($data->agencies)
    <p class="err">NO Agency assigned.</p>
  @else
    <table class="tbl-user-agent no-responsive">
    
    @foreach ($data->agencies as $r_agency)
      <tr>
        <td>
          {!! Form::open(['url'=>route('master.user.agency-unassign', ['user_id'=> enc_id($user->id), 'agent_id'=> enc_id($r_agency->id)]), 'method'=> 'DELETE']) !!}
            <i class="md s btn-del-agent">close</i>
          {!! Form::close() !!}
        </td>
        <?=($r_agency->active)? '<td>'.$r_agency->name.'</td>' : '<td class="grayed">'.$r_agency->name.' (Inactive)</td>' ?>
      </tr>
    @endforeach

    </table>
  @endunless

</div>
@endif

<?php // ***** channel assistants: if auth-user is master-agent, manager association is available ***** ?>
@if ($user->access_lv == POS_LV_CH_USER)
<div class="panel">
  <h2>Assigned Agencies</h2>

  @unless ($data->manager)
  <div class="input-group">
    <label>{{ config_pos_name(POS_LV_CH_MANAGER) }}</label>
    <div class="output"><span class="err">* Not associated</span></div>
  </div>

  @else
  <div class="input-group">
    <label>{{ config_pos_name(POS_LV_CH_MANAGER) }}</label>
    <div class="output"><?=($data->manager)? $data->manager : '<span class="err">* Not associated</span>' ?></div>
  </div>
  <div class="spacer-h"></div>

  @unless ($data->agencies)
  <p class="err">* NO Agency assigned.</p>
  @else
  <table class="tbl-user-agent no-responsive">
  
    @foreach ($data->agencies as $r_agency)
    <tr>
      <?=($r_agency->active)? '<td>'.$r_agency->name.'</td>' : '<td class="grayed">'.$r_agency->name.' (Inactive)</td>' ?>
    </tr>
    @endforeach

  </table>
  @endunless

  @endunless

</div>
@endif

@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function mUserView() {
  var overlay = new ingenOverlay('overlay-pane');
  
  window.moAssignAgency = function() {
    $('#tbl-user-agent-available').DataTable({
      autoWidth: false,
      ordering: false,
      scrollY: 200,
      scrollCollapse: true,
      language: { search: '', searchPlaceholder: 'Search Agency'},
      dom: '<ft>',
    });

    var fnUncheck = function() {
      var $p = $(this).closest('p');
      $('.list-available #' + $p.attr('data-for') ).prop('checked', false);
      $p.fadeOut();
    };
    $('#overlay-pane .btn-del-agent-tmp').click(fnUncheck);

    $('.agent-checker').click(function() {
      var $assignedList = $('.list-assigned .assigned-and-available');
      $assignedList.html("");


      var $checkedAgents = $(this).closest('.list-available').find(':checked');
      for (var i=0; i < $checkedAgents.length; i++) {
        var $label = $checkedAgents.eq(i).next('label');
        var $p = $('<p/>');
        if ($label.hasClass('grayed'))
          $p.addClass('grayed');
        $p.attr('data-for', $label.attr('for'));

        $elemDel = $('<span class="fa-remove btn-del-agent-tmp"/>');
        $elemDel.click(fnUncheck);

        $p.append($elemDel);
        $p.append(' ' + $label.text());
        $assignedList.append($p);
      }
    });
  }
  
  $('.btn-agency-assign').click(function() {
    overlay.setTitle('Assign Agencies');
    overlay.openAjax({
      url: "{{ route('master.user.overlay-agency-assign', ['id'=>enc_id($user->id)]) }}",
      method: 'GET', data: {}
    });
  });
  $('.btn-del-agent').click(function() {
    var $frm = $(this).closest('form');
    confirmUser("Do you want to unassign the agent from the User?",
      function() {
        submitFrm($frm.get(0));
      }, "Unassign Agent");
  });
}
mUserView();
</script>
@endsection