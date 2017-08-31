@extends('layouts.app')

@section('title', "New Customer | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Customer Information</h2>

  {!! Form::open(['url'=>route('customer.create'), 'method'=>'PUT']) !!}

    @include('customers.form')
    
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