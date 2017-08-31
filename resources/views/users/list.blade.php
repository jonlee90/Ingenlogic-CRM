@extends('layouts.app')

@section('title', "Users | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel block">
  <h2>Users</h2>
  <?php /*
  <a href="{{ route('user.new') }}"><input type="button" value="Add New User" /></a>
  */ ?>
  <div class="datatables-wrapper">
    <table id="tbl-user-list" class="datatables-table">
      <thead>
        <th></th>
        <th>Mod</th>
        <th>Email</th>
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
function aUserList() {
  openDataTable('#tbl-user-list', "{{ route('datatables.users') }}", { _token: "{{ csrf_token() }}" }, function() {
    $('.btn-close-item').click(function() {
      var $frm = $(this).closest('form');
      confirmUser("Do you want to delete the user? You cannot undo this.",
        function() {
          submitFrm($frm.get(0));
        }, "Delete User");
    });
  });
}
aUserList();
</script>
@endsection
