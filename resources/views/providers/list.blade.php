@extends('layouts.app')

@section('title', "Providers | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel block">
  <h2>Service Providers</h2>
  
  <div class="datatables-wrapper">
    <table id="tbl-provider-list" class="datatables-table tbl-provider-list">
      <thead>
        <th>Name</th>
        <th>Address</th>
        <th>Phone</th>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function aProviderList() {
  openDataTable('#tbl-provider-list', "{{ route('datatables.providers') }}", { _token: "{{ csrf_token() }}" });
}
aProviderList();
</script>
@endsection