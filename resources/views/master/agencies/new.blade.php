@extends('layouts.master')

@section('title', "Create New Agent | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Agency Information</h2>

  {!! Form::open(['url'=>route('master.agency.create'), 'method'=>'PUT']) !!}

    @include('master.agencies.form', [
      'row_states'=> $data->row_states,
      'agency'=> (object)[
        'active' => 1,
        'name' => '', 'tel' => '',
        'addr' => '', 'addr2' => '',
        'city' => '', 'state_id' => 0, 'zip' => '',
        'spiff' => 0, 'residual' => 0,
      ],
    ])    

    <div class='btn-group'>
      {!! Form::submit('create new') !!}
      <a href="{{ route('master.agency.list') }}"><input type="button" value="cancel" /></a>
    </div>

  {!! Form::close() !!}
  
</div>
@endsection
