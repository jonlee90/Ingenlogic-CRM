<?php
/**
* required vars
* @param $products: available products to choose from
* @param $quote: location x quote object + currently saved quote x MRC products + currently saved quote x NRC products
*/
?>
<div class="overlay-form overlay-lead-product">
  <h2>Available Products</h2>

  @if(!$products)
  <div class="err no-provider-prod">Selected Provider does not have any products available.</div>
  @else
  <?php
  // ********** show the rest of form only if there are products ********** 
  ?>
  <div class="lead-list-available">
    <table id="tbl-lead-product-available">
      <thead><tr> <th></th> <th>Services</th> <th>Name</th> <th>Default Spiff</th> <th>Default Residual</th> <th>Price</th> </tr></thead>
      <tbody>
      @forelse ($products as $prod)
        <tr data-id="{{ enc_id($prod->id) }}" class="btn-prod-add">
          <td><i class="fa-plus-square" title="Add Service"></i></td>
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

  <?php // ******************************* Monthly Recurring products table form ******************************* ?>

  {!! Form::open(['url'=> route('lead.ajax-quote-mrc', ['quote_id'=> enc_id($quote->id)]), 'class'=>'frm-mrc']) !!}
    <h3>Monthly-Recurring Products</h3>

    <table class="tbl-lead-prod-list mrc">
      <thead>
        <tr> <th></th> <th>Service</th> <th>Product</th> <th>Note</th> <th>Spiff</th> <th>Residual</th> <th>Price</th> <th>Qty</th> <th>Subtotal</th> </tr>
      </thead>
      
      <tbody>
      @forelse ($quote->mrc_prods as $prod)
        @if($prod->valid)
        <tr>
          <td><i class="md s btn-del-prod" title="Remove Product">close</i></td>
          <td class="prod-service">{{ $prod->svc_name }}</td>
          <td>
            <span class="prod-name">{{ $prod->prod_name }}</span>
            {!! Form::hidden('prod_id[]', enc_id($prod->product_id)) !!}
          </td>
          <td>{!! Form::text('memo[]', $prod->memo, ['maxlength'=> 100]) !!}</td>
          <td>{!! Form::number('spiff[]', round($prod->spiff_rate,2), ['required', 'readonly']) !!}<label>%</label></td>
          <td>{!! Form::number('resid[]', round($prod->residual_rate,2), ['required', 'readonly']) !!}<label>%</label></td>
          <td>
            <label class="fa-usd"></label>
            {!! Form::number('price[]', round($prod->price,2), ['required', 'readonly']) !!}
          </td>
          <td>{!! Form::number('qty[]', $prod->qty, ['step'=> 1, 'required',]) !!}</td>
          <td>
            <label class="fa-usd"></label>
            <div class="subtotal">{{ number_format($prod->price * $prod->qty, 2, '.','') }}</div>
          </td>
        </tr>
        @else
        <tr class="grayed">
          <td>
            <span class="popup-base">
              <i class="md s danger">report_problem</i>
              <div class="popup-tip left"><div>
                <p>Product has Invalid Association with:</p>
                <p>Providers, Services, or Products in the system.</p>
                <p>The product will be removed on Update.</p>
              </div></div>
            </span>
          </td>
          <td>{{ $prod->svc_name }}</td>
          <td>{{ $prod->prod_name }}</td>
          <td>{{ $prod->memo }}</td>
          <td>{{ number_format($prod->spiff_rate, 2) }}<label>%</label></td>
          <td>{{ number_format($prod->residual_rate, 2) }}<label>%</label></td>
          <td><label class="fa-usd"></label> {{ round($prod->price,2) }} </td>
          <td>{{ $prod->qty }}</td>
          <td><label class="fa-usd"></label> {{ number_format($prod->price * $prod->qty, 2, '.','') }} </td>
        </tr>
        @endif
      @empty
      @endforelse
      </tbody>
    </table>

    <div class="btn-group">
      @if($quote->mrc_prods)
      {!! Form::submit('Save Products') !!}
      {!! Form::button('Save and Close', ['class'=> 'btn-save-close']) !!}
      @else
      {!! Form::submit('Save Products', ['disabled']) !!}
      {!! Form::button('Save and Close', ['class'=> 'btn-save-close', 'disabled']) !!}
      @endif
      {!! Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
    </div>
  {!! Form::close() !!}
  

  <?php // ******************************* Non-Recurring products table form ******************************* ?>

  {!! Form::open(['url'=> route('lead.ajax-quote-nrc', ['quote_id'=> enc_id($quote->id)]), 'class'=>'frm-nrc']) !!}
    <h3>Non-Recurring Products</h3>

    <table class="tbl-lead-prod-list nrc">
      <thead>
        <tr> <th></th> <th>Service</th> <th>Product</th> <th>Note</th> <th>Spiff</th> <th>Residual</th> <th>Price</th> <th>Qty</th> <th>Subtotal</th> </tr>
      </thead>
      
      <tbody>
      @forelse ($quote->nrc_prods as $prod)
        @if($prod->valid)
        <tr>
          <td><i class="md s btn-del-prod" title="Remove Product">close</i></td>
          <td class="prod-service">{{ $prod->svc_name }}</td>
          <td>
            <span class="prod-name">{{ $prod->prod_name }}</span>
            {!! Form::hidden('prod_id[]', enc_id($prod->product_id)) !!}
          </td>
          <td>{!! Form::text('memo[]', $prod->memo, ['maxlength'=> 100]) !!}</td>
          <td>-</td>
          <td>-</td>
          <td>
            <label class="fa-usd"></label>
            {!! Form::number('price[]', round($prod->price,2), ['step'=> 0.01, 'required', 'readonly']) !!}
          </td>
          <td>{!! Form::number('qty[]', $prod->qty, ['step'=> 1, 'required',]) !!}</td>
          <td>
            <label class="fa-usd"></label>
            <div class="subtotal">{{ number_format($prod->price * $prod->qty, 2, '.','') }}</div>
          </td>
        </tr>
        @else
        <tr class="grayed">
          <td>
            <span class="popup-base">
              <i class="md s danger">report_problem</i>
              <div class="popup-tip left"><div>
                <p>Product has Invalid Association with:</p>
                <p>Providers, Services, or Products in the system.</p>
                <p>The product will be removed on Update.</p>
              </div></div>
            </span>
          </td>
          <td>{{ $prod->svc_name }}</td>
          <td>{{ $prod->prod_name }}</td>
          <td>{{ $prod->memo }}</td>
          <td>-</td>
          <td>-</td>
          <td><label class="fa-usd"></label> {{ round($prod->price,2) }} </td>
          <td>{{ $prod->qty }}</td>
          <td><label class="fa-usd"></label> {{ number_format($prod->price * $prod->qty, 2, '.','') }} </td>
        </tr>
        @endif
      @empty
      @endforelse
      </tbody>
    </table>

    <div class="btn-group">
      {!! Form::submit('Save Products') !!}
      {!! Form::button('Save and Close', ['class'=> 'btn-save-close']) !!}
      {!! Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
    </div>
  {!! Form::close() !!}


  <?php // ******************************* source table to clone product rows ******************************* ?>
  <table class="tbl-lead-row-src">
    <tr>
      <td><i class="md s btn-del-prod" title="Remove Service">close</i></td>
      <td class="prod-service"></td>
      <td>
        <span class="prod-name"></span>
        {!! Form::hidden('prod_id[]', '') !!}
      </td>
      <td>{!! Form::text('memo[]', '', ['maxlength'=> 100]) !!}</td>
      <td>{!! Form::number('spiff[]', 0, ['required', 'readonly']) !!}<label>%</label></td>
      <td>{!! Form::number('resid[]', 0, ['required', 'readonly']) !!}<label>%</label></td>
      <td>
        <label class="fa-usd"></label>
        {!! Form::number('price[]', 0, ['required', 'readonly']) !!}
      </td>
      <td>{!! Form::number('qty[]', 1, ['step'=> 1, 'required',]) !!}</td>
      <td>
        <label class="fa-usd"></label>
        <div class="subtotal">100.00</span>
      </td>
    </tr>
  </table>
    
  <?php // ********** END: if there are products ********** ?>
  @endif
</div>
