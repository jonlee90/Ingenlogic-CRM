<?php
$pg_header_submenu = array();

?>
@extends('layouts.app')

@section('content')
<div class='container-login'>
  <h2>Login to the Site</h2>

  <div class='panel'>
    {!! Form::open(['url'=>route('login')]) !!}
      <div class='input-group'>
        <label>Email</label>
        {!! Form::email('login_email', old('login_email'), ['autocomplete'=>'off', 'autofocus', 'required']) !!}
      </div>  
      <div class='input-group'>
        <label>Password</label>
        {!! Form::password('login_pw', ['autocomplete'=>'off', 'required']) !!}
      </div>

      <div class='btn-group'>
        {!! Form::submit('login') !!}
      </div>
    {!! Form::close() !!}
    <div class="forgot-pw">
      <a href="{{ route('password.request') }}">I forgot my password</a>
    </div>
  </div>
  
</div>
@endsection
