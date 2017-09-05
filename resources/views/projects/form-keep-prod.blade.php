<?php
/**
* required vars
* @param $accnt: location x account object + currently saved account x products
* @param $services: name list of services
*/
?>
<div class="overlay-form overlay-lead-product">
  {!! Form::open(['url'=> route('project.ajax-keep-update', ['accnt_id'=> enc_id($accnt->id)]), 'class'=>'frm-prod']) !!}
    <table class="tbl-lead-prod-list curr-accnt">
      <thead>
        <tr>
          <th>
            <span class="popup-base">
              <i class="md btn-prod-add">add_box</i>
              <div class="popup-tip left"><div>Add Product</div></div>
            </span>
          </th>
          <th>Service</th> <th>Product</th> <th>Note</th> <th>Price</th> <th>Qty</th> <th>Subtotal</th>
        </tr>
      </thead>
      
      <tbody>
      @forelse ($accnt->products as $prod)
        <tr>
          <td><i class="md s btn-del-prod" title="Remove Product">close</i></td>
          <td>{!! Form::text('svc[]', $prod->svc_name, ['list'=> 'overlay-list-services', 'maxlength'=> 50, 'required']) !!}</td>
          <td>{!! Form::text('prod[]', $prod->prod_name, ['maxlength'=> 50, 'required']) !!}</td>
          <td>{!! Form::text('memo[]', $prod->memo, ['maxlength'=> 100]) !!}</td>
          <td>
            <label class="fa-usd"></label>
            {!! Form::number('price[]', round($prod->price,2), ['step'=> 0.01, 'required']) !!}
          </td>
          <td>{!! Form::number('qty[]', $prod->qty, ['step'=> 1, 'required',]) !!}</td>
          <td>
            <label class="fa-usd"></label>
            <div class="subtotal">{{ number_format($prod->price * $prod->qty, 2, '.','') }}</div>
          </td>
        </tr>
      @empty
        <?php // ***** empty row if no product is currently saved ***** ?>
        <tr>
          <td><i class="md s btn-del-prod" title="Remove Service">close</i></td>
          <td>{!! Form::text('svc[]', '', ['list'=> 'overlay-list-services', 'maxlength'=> 50, 'required']) !!}</td>
          <td>{!! Form::text('prod[]', '', ['maxlength'=> 50, 'required']) !!}</td>
          <td>{!! Form::text('memo[]', '', ['maxlength'=> 100]) !!}</td>
          <td>
            <label class="fa-usd"></label>
            {!! Form::number('price[]', 0, ['step'=> 0.01, 'required']) !!}
          </td>
          <td>{!! Form::number('qty[]', 1, ['step'=> 1, 'required',]) !!}</td>
          <td>
            <label class="fa-usd"></label>
            <div class="subtotal">0.00</div>
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>

    <div class="btn-group">
      {!! Form::submit('Save Products').' '.Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
    </div>
  {!! Form::close() !!}

  <?php // ***** source row to clone for additional rows of products ***** ?>
  <table class="tbl-lead-row-src">
    <tr>
      <td><i class="md s btn-del-prod" title="Remove Service">close</i></td>
      <td>{!! Form::text('svc[]', '', ['list'=> 'overlay-list-services', 'maxlength'=> 50, 'required']) !!}</td>
      <td>{!! Form::text('prod[]', '', ['maxlength'=> 50, 'required']) !!}</td>
      <td>{!! Form::text('memo[]', '', ['maxlength'=> 100]) !!}</td>
      <td>
        <label class="fa-usd"></label>
        {!! Form::number('price[]', 0, ['step'=> 0.01, 'required']) !!}
      </td>
      <td>{!! Form::number('qty[]', 1, ['step'=> 1, 'required',]) !!}</td>
      <td>
        <label class="fa-usd"></label>
        <div class="subtotal">0.00</div>
      </td>
    </tr>
  </table>
  <datalist id="overlay-list-services">
    @forelse ($services as $svc)
    <option value="{{ $svc->name }}"></option>
    @empty  
    @endforelse
  </datalist>
</div>
