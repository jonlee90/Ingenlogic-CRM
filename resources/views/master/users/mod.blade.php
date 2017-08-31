@extends('layouts.master')

@section('title', "Update User | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel panel-user-mod-info">
  <h2>User Account Information</h2>

  {!! Form::open(['url'=>route('master.user.update', ['id'=> enc_id($user->id)]), 'class'=> 'frm-update']) !!}

    <div class='input-group'>
      <label>Position</label>
      <div class="col-n">
        <ul>
        @forelse ($data->row_pos as $r_lv=>$r_pos)
          <li>
          @if ($loop->first)
            {!! Form::radio('access_lv', enc_id($r_lv), ($r_lv == $user->access_lv || !in_array($user->access_lv, $data->row_pos)), ['id'=>'r-lv-'.enc_id($r_lv)]) !!}
          @else
            {!! Form::radio('access_lv', enc_id($r_lv), ($r_lv == $user->access_lv), ['id'=>'r-lv-'.enc_id($r_lv)]) !!}
          @endif
            <label for="r-lv-{{ enc_id($r_lv) }}">{{ $r_pos }}</label>
          </li>
        @empty
          <li>{{ $data->pos }}</li>
        @endforelse
        </ul>
      </div>
    </div>
    
    <div class='input-group'>
      <label>Email Address</label>
      @if ($preapp->lv >= POS_LV_MASTER_SUPER || Auth::id() != $user->id)
      {!! Form::email('email', $user->email, ['maxlength'=> 100, 'required']) !!}
      @else
      <div class="output">{{ $user->email }}</div>
      @endif
    </div>
    <div class='input-group'>
      <label>First Name</label>
      {!! Form::text('fname', $user->fname, ['maxlength'=> 50, 'placeholder'=> 'First Name', 'required']) !!}
    </div>
    <div class='input-group'>
      <label>Last Name</label>
      {!! Form::text('lname', $user->lname, ['maxlength'=> 50, 'placeholder'=> 'Last Name', 'required']) !!}
    </div>

    <?php
    // UPDATE mode: 'active' field can be modified only if not self-update AND if target user is Not MASTER admin
    if (Auth::id() != $user->id && $user->access_lv < POS_LV_SYS_MASTER) {
    ?>
    <div class='input-group'>
      <label>Status</label>
      <div class="col-n">
        <input type="radio" name="is_active" id="r-active-1" value='1' <?=($user->active !==0)? 'checked':'' ?> />
        <label for="r-active-1">Active</label>
        <input type="radio" name="is_active" id="r-active-0" value='0' <?=($user->active !==0)? '':'checked' ?> />
        <label for="r-active-0">Inactive</label>
      </div>
    </div>
    <?php
    } // END if-else: NEW mode
    ?>
    <div class='btn-group'>
      {!! Form::submit('Save User Information') !!}
      <a href="{{ route('master.user.list') }}"><input type="button" value="Cancel" /></a>
    </div>
  
  {!! Form::close() !!}

  <div class="spacer-h"></div>

  <h2>Update Password</h2>

  {!! Form::open(['url'=>route('master.user.update-pw', ['id'=> enc_id($user->id)])]) !!}

    <div class='input-group'>
      <label>Password</label>
      <input type='password' name='pw' maxlength='30'
          pattern="^(?=.*[A-Za-z])(?=.*\d)(?=.*[_$@$!%*#?&])[A-Za-z\d_$@$!%*#?&]{6,}$" title="Minimum 6 letters long with at least 1 Alphabet, 1 Number, and 1 Special Character (_$@$!%*#?&)" required />
    </div>
    <div class='input-group'>
      <label>Confirm Password</label>
      <input type='password' name='pw_confirmation' maxlength='30' required />
    </div>

    <div class='btn-group'>
      {!! Form::submit('Update Password') !!}
    </div>

  {!! Form::close() !!}
</div>

<?php // ***** for agents, show additional panel to assign(change) agency ***** ?>
@if (POS_LV_AGENT_USER <= $user->access_lv && $user->access_lv <= POS_LV_AGENT_ADMIN)
<div class="panel panel-user-mod-assign-list">
  <h2>Assigned Agency</h2>

  {!! Form::open(['url'=>route('master.user.update-agency', ['id'=> enc_id($user->id)]), 'class'=> 'frm-agency']) !!}

    @if (!count($data->agencies))
    <div class="err">* There is No available Agency</div>

    @else
    <table id="tbl-user-agency-available">
      <thead> <th></th> </thead>
      <tbody>
        @forelse ($data->agencies as $r_agency)
        <?php
        $r_enc_id = enc_id($r_agency->id);
        $r_name = $r_agency->name;
        if ($r_agency->active >0)
          $r_td_class = '';
        else {
          $r_td_class = 'grayed';
          $r_name .= ' (Inactive)';
        }
        ?>
        <tr>
          <td class="{{ $r_td_class }}">
            {!! Form::radio('agency_id', $r_enc_id, ($data->agency_id == $r_agency->id), ['id'=>'r-agency-'.$r_enc_id]) !!}
            <label for="r-agency-{{ $r_enc_id }}">{{ $r_name }}</label>
          </td>
        </tr>
        @empty
        @endforelse
        
      </tbody>
    </table>

    @endif

    <div class="item-unassigned">
      {!! Form::radio('agency_id', 0, ($data->agency_id <= 0), ['id'=>'r-agency-0']) !!} <label for="r-agency-0">Unassigned</label>
    </div>
    
    <div class='btn-group'>
      {!! Form::submit('Update Agency') !!}
    </div>
  
  {!! Form::close() !!}
</div>

<?php // ***** for channel-assistants, show additional panel to assign(change) channel-manager *****?>
@elseif ($user->access_lv == POS_LV_CH_MANAGER && $preapp->lv >= POS_LV_MASTER_MANAGER && Auth::id() != $user->id)
<div class="panel">
  <h2>Default Commission Share</h2>

  {!! Form::open(['url'=>route('master.user.commission-update', ['id'=> enc_id($user->id)])]) !!}

    <div class='input-group'>
      <label>Spiff</label>
      <div class="wrapper-input">
        <div class="row">
          {!! Form::number('spiff', $user->spiff, ['min'=> 0, 'step'=> 0.01, 'max'=> MAX_SHARED_RATE, 'required']) !!}
          <label class="fa-percent"></label>
        </div>
      </div>
    </div>
    <div class='input-group'>
      <label>Residual</label>
      <div class="wrapper-input">
        <div class="row">
          {!! Form::number('residual', $user->residual, ['min'=> 0, 'step'=> 0.01, 'max'=> MAX_SHARED_RATE, 'required']) !!}
          <label class="fa-percent"></label>
        </div>
      </div>
    </div>

    <div class='btn-group'>
      {!! Form::submit('Update Rates') !!}
    </div>

  {!! Form::close() !!}
</div>

<?php // ***** for channel-assistants, show additional panel to assign(change) channel-manager *****?>
@elseif ($user->access_lv == POS_LV_CH_USER && $preapp->lv >= POS_LV_MASTER_MANAGER)
<div class="panel panel-user-mod-assign-list">
  <h2>Associated Channel Manager</h2>

  {!! Form::open(['url'=>route('master.user.manager-update', ['id'=> enc_id($user->id)]), 'class'=> 'frm-manager']) !!}

    @if (!count($data->managers))
    <div class="err">* There is No available Channel Manager</div>

    @else
    <table id="tbl-user-manager-available">
      <thead> <th></th> </thead>
      <tbody>
        @forelse ($data->managers as $r_manager)
        <?php
        $r_enc_id = enc_id($r_manager->id);
        $r_name = trim($r_manager->fname.' '.$r_manager->lname);
        if ($r_manager->active >0)
          $r_td_class = '';
        else {
          $r_td_class = 'grayed';
          $r_name .= ' (Inactive)';
        }
        ?>
        <tr>
          <td class="{{ $r_td_class }}">
            {!! Form::radio('manager_id', $r_enc_id, ($data->manager_id == $r_manager->id), ['id'=>'r-manager-'.$r_enc_id]) !!}
            <label for="r-manager-{{ $r_enc_id }}">{{ $r_name }}</label>
          </td>
        </tr>
        @empty
        @endforelse
        
      </tbody>
    </table>

    @endif

    <div class="item-unassigned">
      {!! Form::radio('manager_id', 0, ($data->manager_id <= 0), ['id'=>'r-manager-0']) !!} <label for="r-manager-0">Unassigned</label>
    </div>
    
    <div class='btn-group'>
      {!! Form::submit('Update Manager') !!}
    </div>
  
  {!! Form::close() !!}
</div>
@endif

@endsection

@section('end_of_body')
<div class="data-group">
  <data data-key="lv">{{ enc_id($user->access_lv) }}</data>
</div>
@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function mUserUpdate() {
  // if access lv changed, warn the customer relationship (agency, manager, etc) will be reset
  $('.frm-update').submit(function(e) {
    e.preventDefault();
    var frm = this;
    if (frm.access_lv.value != $('.data-group data[data-key=lv]').text()) {
      confirmUser("<p>The user's position has changed.</p>" + 
          "<p>Any agency or manager relationship with the user will be removed.</p><p>Do you want to continue?</p>",
        function() {
          submitFrm(frm);
        }, "Position Changed");
    } else
      submitFrm(frm);
  });

  // if user is agent
  var $tbl = $('#tbl-user-agency-available');
  if ($tbl.length >0) {
    $tbl.DataTable({
      autoWidth: false,
      ordering: false,
      scrollY: 400,
      scrollCollapse: true,
      language: { search: '', searchPlaceholder: 'Search Agency'},
      dom: '<ft>',
    });
    $('.frm-agency').submit(function(e) {
      e.preventDefault();
      if ($(this).find('input[name=agency_id]:visible:checked').length <= 0)
        alertUser('Please select an Agency. If agency is not available, please select Unassigned.');
      else
        submitFrm(this);
    });
    return;
  }
  // if user is channel-assistant
  $tbl = $('#tbl-user-manager-available');
  if ($tbl.length >0) {
    $tbl.DataTable({
      autoWidth: false,
      ordering: false,
      scrollY: 400,
      scrollCollapse: true,
      language: { search: '', searchPlaceholder: 'Search Managers'},
      dom: '<ft>',
    });
    $('.frm-manager').submit(function(e) {
      e.preventDefault();
      if ($(this).find('input[name=manager_id]:visible:checked').length <= 0)
        alertUser('Please select a Channel Manager. If manager is not available, please select Unassigned.');
      else
        submitFrm(this);
    });
  }
}
mUserUpdate();
</script>
@endsection
