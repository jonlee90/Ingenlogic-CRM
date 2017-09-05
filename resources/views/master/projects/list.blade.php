@extends('layouts.master')

@section('title', "Projects | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel block">
  <h2>Signed Accouts</h2>
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
  
  <h2>Accouts As Is</h2>
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
  
  <h2>Accouts to Cancel</h2>
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
  openDataTable('#tbl-proj-sign-list', laraRoute('datatables.projects-sign'), { _token: "{{ csrf_token() }}" }, function() {
    openDataTable('#tbl-proj-keep-list', laraRoute('datatables.projects-keep'), { _token: "{{ csrf_token() }}" }, function() {
      openDataTable('#tbl-proj-cancel-list', laraRoute('datatables.projects-cancel'), { _token: "{{ csrf_token() }}" });
    });
  });
  
}
mProjectList();
</script>
@endsection