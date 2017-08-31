@extends('layouts.app')

@section('title', "Create New Lead | ".SITE_TITLE." Control Panel v2")

@section('wrapper_type', 'lead-management')

@section('content')
<section class="lead-control-general">
  @include('leads.sub-progress-bar', [
    'progress_stat' => 'New Lead',
    'progress_index' => 0,
  ])
</section>

{!! Form::open(['url'=>route('lead.create'), 'class'=> 'frm-lead-progress', 'method'=>'PUT']) !!}

  <div class="container-flex">
    <div class="panel">
      <h2>Create New Lead</h2>
      
      @include('customers.form', [
        'cust'=> $cust,
        'data'=> $data,
      ])

      <div class="btn-group btn-group-lead-save">
        {!! Form::submit('save and continue') !!}
        <a href="{{ route('lead.list') }}"><button type="button">cancel</button></a>
      </div>
    </div>
  </div>
{!! Form::close() !!}
@endsection
