@extends('layouts.master')

@section('title', "Create New Provider | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Service Provider Information</h2>

  {!! Form::open(['url'=>route('master.provider.create'), 'method'=>'PUT']) !!}

    @include ('master.providers.form', [
      'row_states' => $data->row_states,
      'prov' => (object)[
        'active' => 1,
        'name' => '',
        'addr' => '', 'addr2' => '',
        'city' => '', 'state_id' => 0, 'zip' => '',
        'tel' => '',
        'default_term' => 1, 'default_spiff' => 0, 'default_residual' => 0,
      ],
    ])
    
    <div class='btn-group'>
      {!! Form::submit('create new') !!}
      <a href="{{ route('master.provider.list') }}"><input type="button" value="cancel" /></a>
    </div>

  {!! Form::close() !!}
  
</div>
@endsection
