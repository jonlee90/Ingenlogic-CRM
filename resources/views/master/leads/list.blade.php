@extends('layouts.master')

@section('title', "Leads | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel block">
  <a href="{{ route('master.lead.new') }}"><button>Create New Lead</button></a>

  <div class="datatables-wrapper">
    <table id="tbl-lead-list" class="datatables-table tbl-lead-list">
      <thead>
        <th></th>
        <th>Status</th>
        <th>Agency</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Address</th>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function mLdL1() {
  openDataTable('#tbl-lead-list', laraRoute('datatables.leads'), { _token: "{{ csrf_token() }}" }, function() {
    /*
    $('.btn-del-item').click(function() {
      var $frm = $(this).closest('form');
      confirmUser("Do you want to delete the service provider? You cannot undo this.",
        function() {
          submitFrm($frm.get(0));
        }, "Delete Service Provider");
    });
    */
  });
}
mLdL1();
</script>
@endsection