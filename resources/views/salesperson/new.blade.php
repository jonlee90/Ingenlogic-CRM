@extends('layouts.app')

@section('title', "New Customer | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Customer Information</h2>

  {!! Form::open(['url'=>route('customer.create'), 'method'=>'PUT']) !!}
  
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
      {!! Form::select('state_id', $data->row_states, 6, []) !!}
    </div>
    <div class="input-group">
      <label>Zip Code</label>
      {!! Form::tel('zip', '', ['maxlength'=> 10, 'pattern'=> '^\d{5}(-\d{4})?$', 'title'=> 'Valid US zip code']) !!}
    </div>
    
    <div class='btn-group'>
      {!! Form::submit('create new') !!}
      <a href="{{ route('customer.list') }}"><button type="button">cancel</button></a>
    </div>

  {!! Form::close() !!}
  
</div>
@endsection

@section('post_content_script')
<script>
function mSlT2() {
}
mSlT2();
</script>
@endsection