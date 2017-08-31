@extends('layouts.app')

@section('title', "Customers | ".SITE_TITLE." Control Panel v2")

@section('wrapper_type', 'tbl-1200')

@section('content')
  
<div class="panel block">
  <h2>Customers</h2>

  <a href="{{ route('customer.new') }}"><input type="button" value="Create New Customer" /></a>
  
  <div class="datatables-wrapper">
    <table id="tbl-customer-list" class="datatables-table tbl-customer-list">
      <thead>
        <th></th>
        <th>Mod</th>
        <th>Name</th>
        <th>Address</th>
        <th>Phone</th>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function aClT1() {
  openDataTable('#tbl-customer-list', "{{ route('datatables.customers') }}", { _token: "{{ csrf_token() }}" }, function() {
    $('.btn-close-item').click(function() {
      var $frm = $(this).closest('form');
      confirmUser("Do you want to delete the provider? You cannot undo this.",
        function() {
          submitFrm($frm.get(0));
        }, "Delete Provider");
    });
  });
}
// declare overlay as global var to be used in oVsE1()
var overlay;aClT1();
</script>
@endsection