@extends('layouts.app')

@section('title', "Create New Lead | ".SITE_TITLE." Control Panel v2")

@section('content')
{!! Form::open(['url'=>route('lead.create'), 'class'=> 'frm-lead-progress', 'method'=>'PUT']) !!}

  <div class="container-flex">
    <div class="panel">
      <h2>Create New Lead</h2>
      
      <div class="btn-group">
        <button type="button" class="open-customer-new">create new customer</button>
        <button type="button" class="open-customer-list">Select from existing Customer</button>
      </div>
      
      <div class="spacer-h"></div>

      <b class="primary">Selected Customer</b>
      <div id="selected-customer">
        <div class="output"></div>
        <div class="err">Please select a Customer.</div>
      </div>
    </div>

    <div class="panel panel-lead-sales">
      <h2>Referral</h2>
    
      {!! Form::radio('referral', 0, TRUE, ['id'=>'r-refer-0']) !!}
      <label for="r-refer-0">None</label>
      {!! Form::radio('referral', 1, FALSE, ['id'=>'r-refer-1']) !!}
      <label for="r-refer-1">Select Salesperson</label>

      <div class="select-sales">
        <div class="btn-group">
          <button type="button" class="open-sales-new">create new Salesperson</button>
          <button type="button" class="open-sales-list">Select from existing Salesperson</button>
        </div>
        
        <div class="spacer-h"></div>

        <b class="primary">Selected Salesperson</b>
        <div id="selected-sales">
          <div class="output"></div>
          <div class="err">Please select a Salesperson.</div>
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
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function oAlC1() {
  $('#tbl-lead-customer-available').DataTable({
    autoWidth: false,
    ordering: false,
    paging: false,
    scrollY: '100%',
    scrollCollapse: true,
    language: { search: '', searchPlaceholder: 'Search Customer'},
    dom: '<ft>',
  });
  $('.btn-select-customer').click(function() {
    var custId = $(this).attr('data-id');
    reqAJAX(laraRoute('lead.ajax-customer-select') + custId, {}, 
      function(json) {
        $('#selected-customer .err').hide();
        $('#selected-customer .output').html(json.html);
        overlay.close();
      },
      undefined);
  });
}
function oAlC2() {
  $('.overlay-form form.frm-create').submit(function(e) {
    e.preventDefault();

    reqAJAX(this.action, $(this).serializeArray(), 
      function(json) {
        $('#selected-customer .err').hide();
        $('#selected-customer .output').html(json.html);
        overlay.close();
      }, undefined);
  });
}
function oAlS1() {
  $('#tbl-lead-sales-available').DataTable({
    autoWidth: false,
    ordering: false,
    paging: false,
    scrollY: '100%',
    scrollCollapse: true,
    language: { search: '', searchPlaceholder: 'Search Salesperson'},
    dom: '<ft>',
  });
  $('.btn-sales-select').click(function() {
    var salesId = $(this).attr('data-id');
    reqAJAX(laraRoute('lead.ajax-salesperson-select') + salesId, {}, 
      function(json) {
        $('#selected-sales .err').hide();
        $('#selected-sales .output').html(json.html);
        overlay.close();
      },
      undefined);
  });
}
function oAlS2() {
  $('.overlay-form form.frm-create').submit(function(e) {
    e.preventDefault();

    reqAJAX(this.action, $(this).serializeArray(), 
      function(json) {
        $('#selected-sales .err').hide();
        $('#selected-sales .output').html(json.html);
        overlay.close();
      }, undefined);
  });
}
function aLdN1() {
  overlay = new ingenOverlay('overlay-pane');
  
  $('.open-customer-new').click(function() {
    overlay.setTitle('New Customer');
    overlay.openAjax({
      url: laraRoute('lead.overlay-customer-new'),
      data: {},
    }, 'GET');
  });
  $('.open-customer-list').click(function() {
    overlay.setTitle('Select Customer');
    overlay.openAjax({
      url: laraRoute('lead.overlay-customer-list'),
      data: {},
    }, 'GET');
  });
  $('.open-sales-new').click(function() {
    overlay.setTitle('New Salesperson');
    overlay.openAjax({
      url: laraRoute('lead.overlay-salesperson-new'),
      data: {},
    }, 'GET');
  });
  $('.open-sales-list').click(function() {
    overlay.setTitle('Select Salesperson');
    overlay.openAjax({
      url: laraRoute('lead.overlay-salesperson-list'),
      data: {},
    }, 'GET');
  });
}
// declare overlay as global var to be used in oVsE1()
var overlay;aLdN1();
</script>
@endsection