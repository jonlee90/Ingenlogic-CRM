@extends('layouts.master')

@section('title', "Agencies | ".SITE_TITLE." Control Panel v2")

@section('wrapper_type', 'tbl-1200')

@section('content')
<div class="panel block">
  @if ($preapp->perm_agency_rec)
  <a href="{{ route('master.agency.new') }}"><input type="button" value="Add New Agency" /></a>
  @endif
  
  <div class="datatables-wrapper">
    <table id="tbl-agency-list" class="datatables-table">
      <thead>
        <th></th>
        <th>Mod</th>
        <th>Name</th>
        <th>Manager</th>
        <th>Address</th>
        <th>Phone</th>
        <th>Spiff</th>
        <th>Residual</th>
        <th>Status</th>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function mAgencyList() {
  openDataTable('#tbl-agency-list', "{{ route('master.datatables.agencies') }}", { _token: "{{ csrf_token() }}" }, function() {
    $('.btn-del-item').click(function() {
      var $frm = $(this).closest('form');
      confirmUser("Do you want to delete the Agency? You cannot undo this.",
        function() {
          submitFrm($frm.get(0));
        }, "Delete Agency");
    });
  });
}
// declare overlay as global var to be used in oVsE1()
mAgencyList();
</script>
@endsection