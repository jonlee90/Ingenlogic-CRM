@extends('layouts.app')

@section('title', "Update Customer | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Customer Information</h2>
  
  {!! Form::open(['url'=>route('customer.update', ['id'=>enc_id($cust->id)])]) !!}

    @include('customers.form')

    <div class="btn-group">
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