@extends('layouts.app')

@section('title', "Update Customer | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Customer Information</h2>

  {!! Form::open(['url'=>route('customer.update', ['id'=>enc_id($cust->id)])]) !!}
  
    <div class="input-group">
      <label>Customer Name</label>
      {!! Form::text('c_name', $cust->name, ['maxlength'=>50, 'required']) !!}
    </div>
    <div class="input-group">
      <label>Phone Number</label>
      {!! Form::tel('tel', $cust->tel, ['maxlength'=> 20, 'required', 'pattern'=> '^\d{10}$', 'title'=> '10 digit Phone number without dash or space.']) !!}
    </div>
    
    <div class="spacer-h"></div>

    <div class="input-group">
      <label>Tax ID</label>
      {!! Form::tel('tax_id', $cust->tax_id, ['maxlength'=>10, 'placeholder'=> '12-3456789', 'pattern'=> '^\d{2}-\d{7}$', 'title'=> '12-3456789 format']) !!}
    </div>
    <div class="input-group">
      <label>Email Address</label>
      {!! Form::email('email', $cust->email, ['maxlength'=>100]) !!}
    </div>
    
    <div class="input-group">
      <label>Address</label>
      {!! Form::text('addr', $cust->addr, ['maxlength'=> 100, 'placeholder'=> 'Street Address']) !!}
    </div>
    <div class="input-group">
      <label>Address 2</label>
      {!! Form::text('addr2', $cust->addr2, ['maxlength'=> 50, 'placeholder'=> 'Ste, Unit, Apt']) !!}
    </div>
    <div class="input-group">
      <label>City</label>
      {!! Form::text('city', $cust->city, ['maxlength'=> 50]) !!}
    </div>
    <div class="input-group">
      <label>State</label>
      {!! Form::select('state_id', $data->row_states, $cust->state_id, []) !!}
    </div>
    <div class="input-group">
      <label>Zip Code</label>
      {!! Form::tel('zip', $cust->zip, ['maxlength'=> 10, 'pattern'=> '^\d{5}(-\d{4})?$', 'title'=> 'Valid US zip code']) !!}
    </div>
    
    <div class='btn-group'>
      {!! Form::submit('save information new') !!}
      <a href="{{ route('customer.list') }}"><button type="button">cancel</button></a>
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