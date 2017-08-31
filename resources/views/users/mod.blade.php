@extends('layouts.app')

@section('title', "Update User | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel panel-user-mod-info">
  <h2>User Account Information</h2>

  {!! Form::open(['url'=>route('user.update', ['id'=> enc_id($user->id)])]) !!}

    @if ($user->id == Auth::id())
    <div class='input-group'>
      <label>Position</label>
      <div class="output">{{ config_pos_name($user->access_lv) }}</div>
    </div>
    
    @else
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
    @endif
    
    <div class='input-group'>
      <label>Email Address</label>
      {!! Form::email('email', $user->email, ['maxlength'=>100, 'required']) !!}
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
      <a href="{{ route('user.list') }}"><button type="button">Cancel</button></a>
    </div>
  
  {!! Form::close() !!}

  <div class="spacer-h"></div>

  <h2>Update Password</h2>

  {!! Form::open(['url'=>route('user.update-pw', ['id'=> enc_id($user->id)]) ]) !!}

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
@endsection
