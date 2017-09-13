@extends('layouts.master')

@section('title', "Update Provider | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Service Provider Information</h2>

  {!! Form::open(['url'=>route('master.provider.update', ['id'=>enc_id($prov->id)])]) !!}

    @include ('master.providers.form', [
      'prov' => $prov,
      'row_states' => $data->row_states,
    ])
    
    <div class='btn-group'>
      {!! Form::submit('Save Information') !!}
      <a href="{{ route('master.provider.list') }}"><input type="button" value="Cancel" /></a>
    </div>

  {!! Form::close() !!}
</div>
@endsection
