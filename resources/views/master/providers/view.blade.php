@extends('layouts.master')

@section('title', "Provider Overview | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Service Provider Information</h2>
  
  <div class='input-group'>
    <label>Status</label>
    <div class="output"><?=($prov->active >0)? '<span class="primary">Active</span>' : '<span class="danger">Inactive</span>' ?></div>
  </div>
  <div class='input-group'>
    <label>Company Name</label>
    <div class="output">{{ $prov->name }}</div>
  </div>
  
  <div class="spacer-h"></div>
  
  <div class='input-group'>
    <label>Address</label>
    <div class="output">{{ $prov->addr }}</div>
  </div>
  <div class='input-group'>
    <label>Address 2</label>
    <div class="output">{{ $prov->addr2 }}</div>
  </div>
  <div class='input-group'>
  <?php
    $city_state = $prov->city;
    $city_state .= ($city_state && $prov->state_code)?  ', '.$prov->state_code : $prov->state_code;
  ?>
    <label>City/State/Zip</label>
    <div class="output">{{ trim($city_state.' '.$prov->zip) }}</div>
  </div>
  <div class='input-group'>
    <label>Phone Number</label>
    <div class="output">{{ format_tel($prov->tel) }}</div>
  </div>
  
  <div class="spacer-h"></div>

  <div class='input-group'>
    <label>Default Term</label>
    <div class="output">{{ ($prov->default_term >1)?  $prov->default_term.' month' : 'Month to Month' }}</div>
  </div>
  <div class='input-group'>
    <label>Default Spiff Rate</label>
    <div class="output">{{ $prov->default_spiff }} %</div>
  </div>
  <div class='input-group'>
    <label>Default Residual Rate</label>
    <div class="output">{{ $prov->default_residual }} %</div>
  </div> 
  
  <div class='btn-group'>
    @if ($preapp->perm_prov_mod)
    <a href="{{ route('master.provider.mod', ['id'=>enc_id($prov->id)]) }}"><button>Update Information</button></a>
    @endif
    <a href="{{ route('master.provider.list') }}"><button type="button">Return to List</button></a>
  </div>
</div>

<div class="panel">
  <h2>Contacts</h2>

  @if (count($data->row_contacts) <1)
    <div class="err">There is No Available Contact.</div>
  @else
    <table>
      <thead>
        <th></th>
        <th>Name</th>
        <th>Title</th>
        <th>Email</th>
        <th>Phone</th>
      </thead>

      <tbody>
      
      @foreach ($data->row_contacts as $contact)
        <tr>
          <td>
            {!! Form::open(['url'=> route('master.provider.delete-contact', ['id'=> enc_id($contact->id)]), 'method'=> 'DELETE']) !!}
            <i class="md s <?=($preapp->perm_prov_mod)? 'btn-mod-contact' : 'grayed' ?>">edit</i>
            <i class="md s <?=($preapp->perm_prov_del)? 'btn-del-contact' : 'grayed' ?>">close</i>
            {!! Form::close() !!}
          </td>
          <td>{{ trim($contact->fname.' '.$contact->lname) }}</td>
          <td>{{ $contact->title }}</td>
          <td>{{ $contact->email }}</td>
          <td>{{ format_tel($contact->tel) }}</td>
        </tr>
      @endforeach

      </tbody>
    </table>
  @endif
    
  @if ($preapp->perm_prov_rec)
  <div class="btn-group">
    <button class="btn-new-contact">Create New</button>
  </div>
  @endif
</div>

<div class="panel">
  <h2>Service Products</h2>

  @if (count($data->row_prov_svcs) <1)
    <div class="err">There is No Assigned Products</div>
  @else
    <table>
      <thead>
        <th></th>
        <th>Service</th>
        <th>Product</th>
        <th>Price</th>
        <th>Spiff</th>
        <th>Residual</th>
      </thead>

      <tbody>
      
      @foreach ($data->row_prov_svcs as $prod)
        <tr>
          <td>
            {!! Form::open(['url'=> route('master.provider.prod-delete', ['id'=> enc_id($prod->id)]), 'method'=> 'DELETE']) !!}
              <i class="md s <?=($preapp->perm_prov_mod)? 'btn-mod-prod' : 'grayed' ?>">edit</i>
              <i class="md s <?=($preapp->perm_prov_del)? 'btn-del-prod' : 'grayed' ?>">close</i>
            {!! Form::close() !!}
          </td>
          <td>{{ $prod->svc_name }}</td>
          <td>{{ $prod->p_name }}</td>
          <td>$ {{ number_format($prod->price, 2) }}</td>
          <td>{{ $prod->rate_spiff }} %</td>
          <td>{{ $prod->rate_residual }} %</td>
        </tr>
      @endforeach

      </tbody>
    </table>
  @endif
    
  @if ($preapp->perm_prov_rec || ($preapp->perm_prov_mod && $data->row_prov_svcs))
  <div class="btn-group">
    @if ($preapp->perm_prov_rec)
    <button class="btn-new-prod">Assign New</button>
    @endif

    @if ($preapp->perm_prov_mod && $data->row_prov_svcs)
    {!! Form::open(['url'=> route('master.provider.prod-reset', ['id'=> enc_id($prov->id)]), 'class'=> 'inline']) !!}
      {!! Form::button('Reset to Default Rate', ['class'=> 'btn-reset-rate']) !!}
    {!! Form::close() !!}
    @endif
  </div>
  @endif

</div>
@endsection

@section('end_of_body')
<div class="data-group">
  <data attr-id="{{ enc_id($prov->id) }}"></data>
</div>
@endsection

@section('post_content_script')
<script>
function mProviderView() {
  var overlay = new ingenOverlay('overlay-pane');
  
  $('.btn-new-contact').click(function() {
    overlay.setTitle('Create New Contact');
    overlay.openAjax({
      url: "{{ route('master.provider.overlay-contact-new', ['id'=> enc_id($prov->id)]) }}",
      method: 'GET', data: {}
    });
  });
  $('.btn-mod-contact').click(function() {
    var $frm = $(this).closest('form');
    overlay.setTitle('Update Contact');
    overlay.openAjax({
      url: $frm.prop('action').replace('provider/contact/delete/','provider/json/contact/mod/'),
      method: 'GET', data: {}
    });
  });
  $('.btn-del-contact').click(function() {
    var $frm = $(this).closest('form');
    confirmUser("Do you want to delete the contact? You cannot undo this.",
      function() {
        submitFrm($frm.get(0));
      }, "Delete Contact");
  });
  
  $('.btn-new-prod').click(function() {
    overlay.setTitle('Assign New Product');
    overlay.openAjax({
      url: laraRoute('master.provider.overlay-prod-new') + $('.data-group data').first().attr('attr-id'),
      method: 'GET', data: {}
    });
  });
  $('.btn-mod-prod').click(function() {
    var $frm = $(this).closest('form');
    overlay.setTitle('Update Product');
    overlay.openAjax({
      url: $frm.prop('action').replace('provider/product/delete/','provider/json/product/mod/'),
      method: 'GET', data: {}
    });
  });
  $('.btn-del-prod').click(function() {
    var $frm = $(this).closest('form');
    confirmUser("Do you want to remove the product?",
      function() {
        submitFrm($frm.get(0));
      }, "Unassign Product");
  });
  $('.btn-reset-rate').click(function() {
    var $frm = $(this).closest('form');
    confirmUser("Spiff and Residual Rate of ALL products will be reset to default. Do you want to continue?",
      function() {
        submitFrm($frm.get(0));
      }, "Reset Rate");
  });
}
mProviderView();
</script>
@endsection