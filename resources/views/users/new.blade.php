@extends('layouts.app')

@section('title', "Create New User | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>User Account Information</h2>

  {!! Form::open(['url'=>route('user.create'), 'method'=>'PUT']) !!}

    <div class='input-group'>
      <label>Position</label>
      <div class="col-n">
      @forelse ($data->row_pos as $r_lv=>$r_pos)
        @if ($loop->first)
          <div>
            {!! Form::radio('access_lv', $r_lv, TRUE, ['id'=>'r-lv-'.$r_lv]) !!}
            <label for="r-lv-{{ $r_lv }}">{{ $r_pos }}</label>
          </div>
        @else
          <div>{!! Form::radio('access_lv', $r_lv, FALSE, ['id'=>'r-lv-'.$r_lv]) !!} <label for="r-lv-{{ $r_lv }}">{{ $r_pos }}</label></div>
        @endif
      @empty
        <p>{{ $data->pos }}</p>
      @endforelse
      </div>
    </div>

    <div class='input-group'>
      <label>Email Address</label>
      {!! Form::email('email', '', ['maxlength'=>100, 'required']) !!}
    </div>
    
    <div class='input-group'>
      <label>Password</label>
      {!! Form::password('pw', [
        'maxlength'=>30, 'required', 'title'=> "Minimum 6 letters long with at least 1 Alphabet, 1 Number, and 1 Special Character (_$@$!%*#?&)", 'pattern'=> "^(?=.*[A-Za-z])(?=.*\d)(?=.*[_$@$!%*#?&])[A-Za-z\d_$@$!%*#?&]{6,}$"
      ]) !!}
    </div>
    <div class='input-group'>
      <label>Confirm Password</label>
      {!! Form::password('pw_confirmation', ['required']) !!}
    </div>
    
    <div class="spacer-h"></div>

    <div class='input-group'>
      <label>Status</label>
      <div class="col-n">
        {!! Form::radio('is_active', 1, TRUE, ['id'=>'r-active-1']) !!}
        <label for="r-active-1">Active</label>
        {!! Form::radio('is_active', 0, FALSE, ['id'=>'r-active-0']) !!}
        <label for="r-active-0">Inactive</label>
      </div>
    </div>
    
    <div class='input-group'>
      <label>First Name</label>
      {!! Form::text('fname', '', ['maxlength'=> 50, 'placeholder'=> 'First Name', 'required']) !!}
    </div>
    <div class='input-group'>
      <label>Last Name</label>
      {!! Form::text('lname', '', ['maxlength'=> 50, 'placeholder'=> 'Last Name', 'required']) !!}
    </div>
    

    <div class='btn-group'>
      {!! Form::submit('create new') !!}
      <a href="{{ route('user.list') }}"><input type="button" value="cancel" /></a>
    </div>

  {!! Form::close() !!}
</div>
@endsection

@section('post_content_script')
<script>
function mSlT2() {
  overlay = new ingenOverlay('overlay-pane');
  $('.btn-write-email').click(function() {
    openEmailForm( $(this).closest('td') );
  });
}
// declare overlay as global var to be used in oVsE1()
var overlay;mSlT2();
</script>
@endsection