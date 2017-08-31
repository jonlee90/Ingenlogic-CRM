@extends('layouts.master')

@section('title', "Create New Lead | ".SITE_TITLE." Control Panel v2")

@section('wrapper_type', 'lead-management')

@section('content')
<section class="lead-control-general">
  @include('leads.sub-progress-bar', [
    'progress_stat' => 'New Lead',
    'progress_index' => 1,
  ])
</section>

{!! Form::open(['url'=>route('master.lead.create'), 'class'=> 'frm-lead-progress', 'method'=>'PUT']) !!}

  <div class="container-flex">
    <div class="panel">
      <h2>Create New Lead</h2>

      <div class="input-group">
        <label>Customer Name</label>
        {!! Form::text('c_name', '', ['maxlength'=>50, 'required']) !!}
      </div>
      <div class="input-group">
        <label>Phone Number</label>
        {!! Form::tel('tel', '', ['maxlength'=> 20, 'required', 'pattern'=> '^\d{10}$', 'title'=> '10 digit Phone number without dash or space.']) !!}
      </div>

      <div class="spacer-h"></div>

      <div class="input-group">
        <label>Tax ID</label>
        {!! Form::tel('tax_id', '', ['maxlength'=>10, 'placeholder'=> '12-3456789', 'pattern'=> '^\d{2}-\d{7}$', 'title'=> '12-3456789 format']) !!}
      </div>
      <div class="input-group">
        <label>Email Address</label>
        {!! Form::email('email', '', ['maxlength'=>100]) !!}
      </div>

      <div class="input-group">
        <label>Address</label>
        {!! Form::text('addr', '', ['maxlength'=> 100, 'placeholder'=> 'Street Address']) !!}
      </div>
      <div class="input-group">
        <label>Address 2</label>
        {!! Form::text('addr2', '', ['maxlength'=> 50, 'placeholder'=> 'Ste, Unit, Apt']) !!}
      </div>
      <div class="input-group">
        <label>City</label>
        {!! Form::text('city', '', ['maxlength'=> 50]) !!}
      </div>
      <div class="input-group">
        <label>State</label>
        {!! Form::select('state_id', $data->row_states, '', []) !!}
      </div>
      <div class="input-group">
        <label>Zip Code</label>
        {!! Form::tel('zip', '', ['maxlength'=> 10, 'pattern'=> '^\d{5}(-\d{4})?$', 'title'=> 'Valid US zip code']) !!}
      </div>
      
      <div class="btn-group btn-group-lead-save">
        {!! Form::submit('save and continue') !!}
        <a href="{{ route('master.lead.list') }}"><button type="button">cancel</button></a>
      </div>
    </div>
  </div>
{!! Form::close() !!}
@endsection
