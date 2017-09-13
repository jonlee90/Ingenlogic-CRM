@extends('layouts.master')

@section('title', "Predefined Service | ".SITE_TITLE." Control Panel v2")

@section('content')
  
<div class="panel block">
  @if ($preapp->perm_svc_rec)
  <input type="button" value="Add New Service" class="btn-new-item" />
  @endif

  <div class="datatables-wrapper">
    <table id="tbl-svc-list" class="datatables-table tbl-svc-list">
      <thead>
        <th></th>
        <th>Parent Service</th>
        <th>Name</th>
        <th># Products</th>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function mServiceList() {
  var overlay = new ingenOverlay('overlay-pane');

  openDataTable({
    tblSelector: '#tbl-svc-list', url: "{{ route('master.datatables.services') }}",
    data: { _token: "{{ csrf_token() }}" },
    drawCallbackFn: function() {
      $('.btn-mod-item').click(function() {
        var $frm = $(this).closest('form');

        overlay.setTitle('Update Service');
        overlay.openAjax({ url: $frm.prop('action').replace('service/delete/','service/json/mod/'), data: {}, method: 'GET' });
      });
      $('.btn-del-item').click(function() {
        var $frm = $(this).closest('form');
        confirmUser("Do you want to delete the service? You cannot undo this.",
          function() {
            submitFrm($frm.get(0));
          }, "Delete Service");
      });
    }
  });
  $('.btn-new-item').click(function() {
    overlay.setTitle('New Service');
    overlay.openAjax({ url: '/service/json/new', data: {}, method: 'GET', });
  });
}
mServiceList();
</script>
@endsection