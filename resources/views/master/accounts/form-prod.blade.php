<?php
/**
* required vars
* @param $products: array of provider x product object
*/
?>
<div class="overlay-comm-accnt-prod">
  <h2>Available Products</h2>

  @if(!$products)
  <div class="err no-provider-prod">Selected Provider does not have any products available.</div>

  @else
  {!! Form::open(['class'=> 'frm-overlay']) !!}
    {!! Form::hidden('prod_id', '') !!}
    
    <div class="overlay-list-frame">
      <table id="tbl-accnt-product-available">
        <thead><tr> <th></th> <th>Services</th> <th>Name</th> <th>Default Spiff</th> <th>Default Residual</th> <th>Price</th> </tr></thead>
        <tbody>
        @forelse ($products as $prod)
          <tr data-id="{{ enc_id($prod->id) }}" class="btn-prod-select">
            <td><i class="md s">done</i></td>
            <td class="prod-service">{{ $prod->svc_name }}</td>
            <td class="prod-name">{{ $prod->p_name }}</td>
            <td><span class="prod-spiff">{{ number_format($prod->rate_spiff, 2) }}</span> %</td>
            <td><span class="prod-resid">{{ number_format($prod->rate_residual, 2) }}</span> %</td>
            <td>$ <span class="prod-price">{{ number_format($prod->price, 2) }}</span></td>
          </tr>
        @empty
            
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="spacer-h"></div>

    <div class="input-group">
      <label>Service</label>
      <div class="output svc-name"><span class="err">(Please select a Product from the Available Products)</span></div>
    </div>
    <div class="input-group">
      <label>Product</label>
      <div class="output prod-name"><span class="err">(Please select a Product from the Available Products)</span></div>
    </div>

    <div class="group-col">
      <div class="col-2">
        <div class="input-group">
          <label>Note</label>
          {!! Form::text('memo', '', ['maxlength'=> 100, ]) !!}
        </div>
        <div class="input-group">
          <label>Price</label>
          <div class="wrapper-input">
            <div class="row">
              <label><i class="fa-usd"></i></label>
              <div class="cell">{!! Form::number('price', '', ['min'=> 0, 'step'=> 0.01, 'required']) !!}</div>
            </div>
          </div>
        </div>
        <div class="input-group">
          <label>Quantity</label>
          {!! Form::number('qty', 1, ['min'=> 0, 'step'=> 1, 'required']) !!}
        </div>
      </div>
      <div class="col-2">
        {!! Form::checkbox('mrc', 1, TRUE, ['id'=> 'k-mrc']) !!}
        <label for="k-mrc">Recurring Product</label>

        <div class="commission-rates">
          <div class="input-group">
            <label>Spiff Rate</label>
            <div class="wrapper-input">
              <div class="row">
                <div class="cell">{!! Form::number('spiff', 0.00, ['min'=> 0, 'step'=> 0.01, 'required']) !!}</div>
                <label>%</label>
              </div>
            </div>
          </div>
          <div class="input-group">
            <label>Residual Rate</label>
            <div class="wrapper-input">
              <div class="row">
                <div class="cell">{!! Form::number('resid', 0.00, ['min'=> 0, 'step'=> 0.01, 'required']) !!}</div>
                <label>%</label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class='btn-group'>
      {!! Form::submit('update information') !!}
      {!! Form::button('cancel', ['class'=> 'btn-cancel']) !!}
    </div>
    
  {!! Form::close() !!}
  <script>moAccountProduct()</script>
  @endif
</div>
