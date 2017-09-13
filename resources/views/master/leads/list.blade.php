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
function mLeadList() {
  openDataTable({
    tblSelector: '#tbl-lead-list', url: laraRoute('datatables.leads'),
    data: { _token: "{{ csrf_token() }}" },
  });
}
mLeadList();
</script>
@endsection