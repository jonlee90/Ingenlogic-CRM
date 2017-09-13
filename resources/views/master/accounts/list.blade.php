@extends('layouts.master')

@section('title', "Providers | ".SITE_TITLE." Control Panel v2")

@section('wrapper_type', 'tbl-1200')

@section('content')  
<div class="panel block">
  @if ($preapp->perm_prov_rec)
  <a href="{{ route('master.provider.new') }}"><input type="button" value="Add New Provider" /></a>
  @endif

  <div class="datatables-wrapper">
    <table id="tbl-provider-list" class="datatables-table tbl-provider-list">
      <thead>
        <th></th>
        <th>Mod</th>
        <th>Name</th>
        <th>Address</th>
        <th>Phone</th>
        <th>Default Term</th>
        <th>Default Spiff</th>
        <th>Default Residual</th>
        <th>Status</th>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function mProviderList() {
  openDataTable({
    tblSelector: '#tbl-provider-list', url: "{{ route('master.datatables.providers') }}",
    data: { _token: "{{ csrf_token() }}" },
    drawCallbackFn: function() {
      $('.btn-del-item').click(function() {
        var $frm = $(this).closest('form');
        confirmUser("Do you want to delete the user? You cannot undo this.",
          function() {
            submitFrm($frm.get(0));
          }, "Delete User");
      });
    }
  });
}
// declare overlay as global var to be used in oVsE1()
mProviderList();
</script>
@endsection
