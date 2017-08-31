@extends('layouts.app')

@section('title', "Customer Overview | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Customer Information</h2>

  <div class="input-group">
    <label>Customer Name</label>
    <div class="output">{{ $cust->name }}</div>
  </div>
  <div class="input-group">
    <label>Phone Number</label>
    <div class="output">{{ format_tel($cust->tel) }}</div>
  </div>
  
  <div class="spacer-h"></div>

  <div class="input-group">
    <label>Tax ID</label>
    <div class="output">{{ $cust->tax_id }}</div>
  </div>
  <div class="input-group">
    <label>Email Address</label>
    <div class="output">{{ $cust->email }}</div>
  </div>
  
  <div class="input-group">
    <label>Address</label>
    <div class="output">{{ $cust->addr }}</div>
  </div>
  <div class="input-group">
    <label>Address 2</label>
    <div class="output">{{ $cust->addr2 }}</div>
  </div>
  <div class="input-group">
    <label>City/State/Zip</label>
    <?php
      $city_state = $cust->city;
      $city_state .= ($city_state && $data->state_code)?  ', '.$data->state_code : $data->state_code;
    ?>
    <div class="output">{{ trim($city_state.' '.$cust->zip) }}</div>
  </div>
  
  <div class='btn-group'>
    <a href="{{ route('customer.mod', ['id'=>enc_id($cust->id)]) }}"><button>Update Information</button></a>
    <a href="{{ route('customer.list') }}"><button>Return to List</button></a>
  </div>
</div>

<div class="panel">
  <h2>Contacts</h2>

  <button class="btn-new-contact">Create New</button>
  
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
            {!! Form::open(['url'=> route('customer.delete-contact', ['id'=> enc_id($contact->id)]), 'method'=> 'DELETE']) !!}
            <i class="md s btn-mod-contact">edit</i>
            <i class="md s btn-close-contact">close</i>
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
    
</div>
@endsection

@section('post_content_script')
<script>
function mPvW1() {
  overlay = new ingenOverlay('overlay-pane');
  
  $('.btn-new-contact').click(function() {
    overlay.setTitle('Create New Contact');
    overlay.openAjax({
      url: "{{ route('customer.overlay-contact-new', ['id'=> enc_id($cust->id)]) }}",
      data: { _token: "{{ csrf_token() }}" }
    });
  });
  $('.btn-mod-contact').click(function() {
    var $frm = $(this).closest('form');
    overlay.setTitle('Update Contact');
    overlay.openAjax({
      url: $frm.prop('action').replace('customer/contact/delete/','customer/overlay/contact/mod/'),
      data: { _token: "{{ csrf_token() }}" }
    });
  });
  $('.btn-del-contact').click(function() {
    var $frm = $(this).closest('form');
    confirmUser("Do you want to delete the contact? You cannot undo this.",
      function() {
        submitFrm($frm.get(0));
      }, "Delete Contact");
  });
}
// declare overlay as global var to be used in oVsE1()
var overlay;mPvW1();
</script>
@endsection