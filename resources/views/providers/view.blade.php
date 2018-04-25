<?php
/**
* required vars
* @param $prov: provider object
* @param $data->row_contacts: array of contacts and products
*/
?>
@extends('layouts.app')

@section('title', "Provider Overview | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Service Provider Information</h2>
  
  <div class='input-group'>
    <label>Company Name</label>
    <div class="output">{{ $prov->name }}</div>
  </div>
  <div class='input-group'>
    <label>Address</label>
    <div class="output">{{ $prov->addr }}</div>
  </div>
  <div class='input-group'>
    <label>Address 2</label>
    <div class="output">{{ $prov->addr2 }}</div>
  </div>
  <div class='input-group'>
    <label>City/State/Zip</label>
    <div class="output">{{ format_city_state_zip($prov->city, $prov->state_code, $prov->zip) }}</div>
  </div>
  <div class='input-group'>
    <label>Phone Number</label>
    <div class="output">{{ format_tel($prov->tel) }}</div>
  </div>
  
  <div class='btn-group'>
    <a href="{{ route('provider.list') }}"><button>Return to List</button></a>
  </div>
</div>

<div class="panel">
  <h2>Contacts</h2>

  @if (count($data->row_contacts) <1)
    <div class="err">There is No Available Contact.</div>
  @else
    <table>
      <thead>
        <th>Name</th>
        <th>Title</th>
        <th>Email</th>
        <th>Phone</th>
      </thead>

      <tbody>
      
      @foreach ($data->row_contacts as $contact)
        <tr>
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

<div class="panel">
  <h2>Provided Products</h2>

  @if (count($data->row_products) <1)
    <div class="err">There is No Assigned Products</div>
  @else
    <table>
      <thead>
        <th>Name</th>
        <th>Price</th>
      </thead>

      <tbody>
      
      @foreach ($data->row_products as $prod)
        <tr>
          <td>{{ $prod->p_name }}</td>
          <td>$ {{ number_format($prod->price, 2) }}</td>
        </tr>
      @endforeach

      </tbody>
    </table>
  @endif
@endsection