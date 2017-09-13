<?php
/**
* required vars
* @param $cust: customer object [name, tel, tax_id, email, addr, addr2, city, state_id, zip]
* @param $data: object with $row_states (= array list of states)
*/
?>
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

{!! Form::open(['url'=>route('lead.create'), 'class'=> 'frm-lead-new', 'method'=>'PUT']) !!}

  <div class="container-flex">
    <div class="panel">
      <h2>Create New Lead</h2>
      
      @include('customers.form', [
        'cust'=> $cust,
        'data'=> $data,
      ])

      <div class="input-group">
        <div>
          <input type="checkbox" name="create_loc" id="k-create-loc" value="1" checked />
          <label for="k-create-loc">Create Location with the Address</label>
        </div>
      </div>

      <div class="btn-group btn-group-lead-save">
        {!! Form::submit('save and continue') !!}
        <a href="{{ route('lead.list') }}"><button type="button">cancel</button></a>
      </div>
    </div>
  </div>
{!! Form::close() !!}
@endsection

@section('post_content_script')
<script>
function aLeadNew() {
  $('.frm-lead-new input[name="tel"]').on('input blur', function() {
    cleanInput(this, 'tel');
  });
  $('.frm-lead-new').submit(function(e) {
    e.preventDefault();

    if ($('#k-create-loc').prop('checked') && (this.addr.value =='' || this.city.value =='' || this.state_id.selectedIndex <1 || this.zip.value ==''))
      return alertUser('Location requires Address.');
    submitFrm(this);
  });
}
aLeadNew();
</script>
@endsection
