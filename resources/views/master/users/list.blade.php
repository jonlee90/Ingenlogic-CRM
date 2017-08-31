@extends('layouts.master')

@section('title', "Users | ".SITE_TITLE." Control Panel v2")

@section('content')
<a href="{{ route('master.user.new') }}"><input type="button" value="Add New User" /></a>
  
<div class="panel block">
  <div class="datatables-wrapper">
    <table id="tbl-user-list" class="datatables-table">
      <thead>
        <th></th>
        <th>Mod</th>
        <th>Email</th>
        <th>Agency</th>
        <th>Name</th>
        <th>Access LV</th>
        <th>Status</th>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function aUlT1() {
  openDataTable('#tbl-user-list', "{{ route('master.datatables.users') }}", { _token: "{{ csrf_token() }}" }, function() {
    $('.btn-del-item').click(function() {
      var $frm = $(this).closest('form');
      confirmUser("Do you want to delete the user? You cannot undo this.",
        function() {
          submitFrm($frm.get(0));
        }, "Delete User");
    });
  });
}
// declare overlay as global var to be used in oVsE1()
var overlay;aUlT1();
</script>
@endsection