@extends('layouts.app')

@section('title', "Salesperson | ".SITE_TITLE." Control Panel v2")

@section('content')
  
<div class="panel block">
  <h2>Salesperson</h2>

  <button class="btn-new-item">Create New Salesperson</button>
  
  <div class="datatables-wrapper">
    <table id="tbl-sales-list" class="datatables-table tbl-sales-list">
      <thead>
        <th></th>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function aSpL1() {
  var overlay = new ingenOverlay('overlay-pane');
  
  openDataTable('#tbl-sales-list', "{{ route('datatables.salesperson') }}", { _token: "{{ csrf_token() }}" }, function() {
    $('.btn-mod-item').click(function() {
      var $frm = $(this).closest('form');
      overlay.setTitle('Update Salesperson');
      overlay.openAjax({
        url: $frm.prop('action').replace('salesperson/delete/','salesperson/overlay/mod/'),
        data: { _token: "{{ csrf_token() }}" }
      });
    });
    $('.btn-close-item').click(function() {
      var $frm = $(this).closest('form');
      confirmUser("Do you want to delete the salesperson? You cannot undo this.",
        function() {
          submitFrm($frm.get(0));
        }, "Delete Salesperson");
    });
  });

  $('.btn-new-item').click(function() {
    overlay.setTitle('Create New Salesperson');
    overlay.openAjax({
      url: "{{ route('salesperson.overlay-new') }}",
      data: { _token: "{{ csrf_token() }}" }
    });
  });
}
// declare overlay as global var to be used in oVsE1()
aSpL1();
</script>
@endsection