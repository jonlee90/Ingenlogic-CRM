@extends('layouts.master')

@section('title', "Projects | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel block">
  <h2>Signed Accounts</h2>
  <div class="datatables-wrapper">
    <table id="tbl-proj-sign-list" class="datatables-table tbl-lead-list">
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

  <div class="spacer-h"></div>
  
  <h2>Accounts As Is</h2>
  <div class="datatables-wrapper">
    <table id="tbl-proj-keep-list" class="datatables-table tbl-lead-list">
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

  <div class="spacer-h"></div>
  
  <h2>Accounts to Cancel</h2>
  <div class="datatables-wrapper">
    <table id="tbl-proj-cancel-list" class="datatables-table tbl-lead-list">
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
function mProjectList() {
  openDataTable({ tblSelector: '#tbl-proj-sign-list', url: laraRoute('datatables.projects-sign'), data: { _token: "{{ csrf_token() }}" }, additionalFn: function() {
    openDataTable({ tblSelector: '#tbl-proj-keep-list', url: laraRoute('datatables.projects-keep'), data: { _token: "{{ csrf_token() }}" }, additionalFn: function() {
      openDataTable({ tblSelector: '#tbl-proj-cancel-list', url: laraRoute('datatables.projects-cancel'), data: { _token: "{{ csrf_token() }}" }, });
    }, });
  }, });
}
mProjectList();
</script>
@endsection