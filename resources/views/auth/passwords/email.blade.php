@extends('layouts.app')

@section('content')
<div class='container-login'>
  <h2>Reset Password</h2>

  <div class='panel'>
    {!! Form::open(['url'=>route('password.email')]) !!}

    <div class='input-group'>
      <label>Email Address</label>
      {!! Form::email('email', old('email'), ['placeholder'=>'Enter your Email to reset password', 'autocomplete'=>'off', 'autofocus', 'required']) !!}
    </div>
    <?php
    /*
    @if ($errors->has('email'))
    <span class="help-block">
      <strong>{{ $errors->first('email') }}</strong>
    </span>
    @endif
    */
    ?>
    <div class='btn-group'>
      {!! Form::submit('Send Reset Link') !!}
    </div>

    {!! Form::close() !!}

    <div class="forgot-pw">
      <a href="{{ route('login') }}">Cancel and Return to Login</a>
    </div>
  </div>
</div>
@endsection
