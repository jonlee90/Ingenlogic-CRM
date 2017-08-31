@extends('layouts.app')

@section('title', "Update Agency Information | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Agency Information</h2>

  {!! Form::open(['url'=>route('user.update-agency') ]) !!}

    <div class='input-group'>
      <label>Company Name</label>
      {!! Form::text('c_name', $agency->name, ['maxlength'=>50, 'required']) !!}
    </div>

    <div class='input-group'>
      <label>Address</label>
      {!! Form::text('addr', $agency->addr, ['maxlength'=> 100, 'placeholder'=> 'Street Address',]) !!}
    </div>
    <div class='input-group'>
      <label>Address 2</label>
      {!! Form::text('addr2', $agency->addr2, ['maxlength'=> 50, 'placeholder'=> 'Ste, Unit, Apt']) !!}
    </div>
    <div class='input-group'>
      <label>City</label>
      {!! Form::text('city', $agency->city, ['maxlength'=> 50,]) !!}
    </div>
    <div class='input-group'>
      <label>State</label>
      {!! Form::select('state_id', $data->row_states, $agency->state_id, []) !!}
    </div>
    <div class='input-group'>
      <label>Zip Code</label>
      {!! Form::tel('zip', $agency->zip, ['maxlength'=> 10, 'pattern'=> '^\d{5}(-\d{4})?$', 'title'=> 'Valid US zip code']) !!}
    </div>
    <div class='input-group'>
      <label>Phone Number</label>
      {!! Form::tel('tel', $agency->tel, ['maxlength'=> 20, 'required', 'pattern'=> '^\d{10}$', 'title'=> '10 digit Phone number without dash or space.']) !!}
    </div>

    <div class='btn-group'>
      {!! Form::submit('Save Information') !!}
      <a href="{{ route('user.view', ['id'=> $data->enc_id]) }}"><button type="button">Cancel</button></a>
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