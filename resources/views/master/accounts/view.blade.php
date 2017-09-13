<?php
/**
* required vars
* @param $account: commission-account object (+ agencies, managers, mrc_prods, nrc_prods)
*/
$mrc_total = $nrc_total = $residual_total =0;
if ($account->mrc_prods) {
  foreach ($account->mrc_prods as $prod) {
    $subtotal = $prod->price * $prod->qty;
    $mrc_total += $subtotal;
    $residual_total += $subtotal * $prod->spiff /100;
  }
}
if ($account->nrc_prods) {
  foreach ($account->nrc_prods as $prod) 
    $nrc_total += $prod->price * $prod->qty;
}
?>
@extends('layouts.master')

@section('title', "Commission Account | ".SITE_TITLE." Control Panel v2")

@section('wrapper_type', 'tbl-1200')

@section('content')
<div class="panel panel-comm-accnt-view">
  <h2>Commission Share
    <span class="popup-base">
      <i class="md s btn-comm-update">edit</i>
      <div class="popup-tip"><div>Update Commission</div></div>
    </span>
  </h2>
  
  <table class="tbl-comm-share">
    <thead>
      <tr>
        <th>Name</th>
        <th>Position</th>
        <th>Spiff</th>
        <th>Residual</th>
        <th>Monthly Residual</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($account->agencies as $agency)
      <tr>
        <td>{{ $agency->agency }}</td>
        <td>Agency</td>
        <td>{{ number_format($agency->spiff, 2) }} %</td>
        <td>{{ number_format($agency->residual, 2) }} %</td>
        <td class="money">{{ number_format($agency->residual * $residual_total /100, 2) }}</td>
      </tr>
      @endforeach
      
      @foreach ($account->managers as $manager)
      <tr>
        <td>{{ $manager->manager }}</td>
        <td>Channel Manager</td>
        <td>{{ number_format($manager->spiff, 2) }} %</td>
        <td>{{ number_format($manager->residual, 2) }} %</td>
        <td class="money">{{ number_format($manager->residual * $residual_total /100, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="spacer-h"></div>


  <h2>Account Information
    <span class="popup-base">
      <i class="md s btn-prov-mod">edit</i>
      <div class="popup-tip"><div>Update Provider</div></div>
    </span>
  </h2>
  
  <div class='input-group'>
    <label>Provider</label>
    <div class="output">{{ $account->provider }}</div>
  </div>
  
  <div class='input-group'>
    <label>Term</label>
    <div class="output"><?=($account->term >1)?  $account->term.' months' : 'Month to Month' ?></div>
  </div>

  <div class="spacer-h"></div>

  <div class="group-col comm-accnt-address">
    <?php /* ***** Billing Information ***** */ ?>
    <div class="col-2 billing">
      <h3>Billing Address
        <span class="popup-base">
          <i class="md s">edit</i>
          <div class="popup-tip"><div>Update Address</div></div>
        </span>
      </h3>
      
      <div class='input-group'>
        <label>Address</label>
        <div class="output">{{ $account->bill_addr }}</div>
      </div>
      <div class='input-group'>
        <label>Address 2</label>
        <div class="output">{{ $account->bill_addr2 }}</div>
      </div>
      <div class='input-group'>
        <label>City/State/Zip</label>
        <div class="output">{{ format_city_state_zip($account->bill_city, $account->bill_state, $account->bill_zip) }}</div>
      </div>
      <div class='input-group'>
        <label>Phone Number</label>
        <div class="output">{{ format_tel($account->bill_tel) }}</div>
      </div>
    </div>

    <?php /* ***** Shipping Information ***** */ ?>
    <div class="col-2 shipping">
      <h3>Shipping Address
        <span class="popup-base">
          <i class="md s">edit</i>
          <div class="popup-tip"><div>Update Address</div></div>
        </span>
      </h3>
      
      <div class='input-group'>
        <label>Address</label>
        <div class="output">{{ $account->ship_addr }}</div>
      </div>
      <div class='input-group'>
        <label>Address 2</label>
        <div class="output">{{ $account->ship_addr2 }}</div>
      </div>
      <div class='input-group'>
        <label>City/State/Zip</label>
        <div class="output">{{ format_city_state_zip($account->ship_city, $account->ship_state, $account->ship_zip) }}</div>
      </div>
      <div class='input-group'>
        <label>Phone Number</label>
        <div class="output">{{ format_tel($account->ship_tel) }}</div>
      </div>
    </div>
  </div>

  <div class="spacer-h"></div>


  <h2>Project Dates
    <span class="popup-base">
      <i class="md s">edit</i>
      <div class="popup-tip"><div>Update Dates</div></div>
    </span>
  </h2>

  <div class='input-group'>
    <label>Date Signed</label>
    <div class="output">{{ format_date($account->date_signed) }}</div>
  </div>

  <div class="group-col">
    <div class="col-2">
      <div class='input-group'>
        <label>Date Contract Begin</label>
        <div class="output">{{ format_date($account->date_contract_begin) }}</div>
      </div>
    </div>
    <div class="col-2">
      <div class='input-group'>
        <label>Date Contract End</label>
        <div class="output">{{ format_date($account->date_contract_end) }}</div>
      </div>
    </div>
  </div>
  
  <div class="group-col">
    <div class="col-2">
      <div class='input-group'>
        <label>Date Site Survey</label>
        <div class="output">{{ format_date($account->date_inspect) }}</div>
      </div>
      <div class='input-group'>
        <label>Date Construction</label>
        <div class="output">{{ format_date($account->date_construct) }}</div>
      </div>
    </div>
    <div class="col-2">
      <div class='input-group'>
        <label>Date Installation</label>
        <div class="output">{{ format_date($account->date_install) }}</div>
      </div>
      <div class='input-group'>
        <label>Date Port-In</label>
        <div class="output">{{ format_date($account->date_portin) }}</div>
      </div>
    </div>
  </div>

  <div class="spacer-h"></div>


  <h2>Service Products</h2>
  
  <table class="tbl-comm-accnt-prods">
    <thead>
      <tr>
        <th>
          <span class="popup-base">
            <i class="md btn-prod-add">add_box</i>
            <div class="popup-tip"><div>Add Product</div></div>
          </span>
        </th>
        <th>Service</th>
        <th>Product</th>
        <th>Note</th>
        <th>Spiff</th>
        <th>Residual</th>
        <th>Price</th>
        <th>Qty</th>
        <th>NRC Subtotal</th>
        <th>MRC Subtotal</th>
      </tr>
    </thead>

    <tbody>
    @forelse ($account->mrc_prods as $prod)
      <tr class="mrc">
        <td>
          {!! Form::hidden('prod_id[]', enc_id($prod->product_id)) !!}
          {!! Form::hidden('is_mrc[]', 1) !!}
          <span class="popup-base">
            <i class="md s btn-prod-update">edit</i>
            <div class="popup-tip"><div>Update Product</div></div>
          </span>
          <span class="popup-base">
            <i class="md s btn-del-prod">close</i>
            <div class="popup-tip"><div>Remove Product</div></div>
          </span>
        </td>
        <td class="svc-name">{{ $prod->svc_name }}</td>
        <td class="prod-name">{{ $prod->prod_name }}</td>
        <td class="cell-input">{!! Form::text('prod_memo[]', $prod->memo, ['maxlength'=> 100, 'readonly' ]) !!}</td>
        <td class="cell-input commission">{!! Form::number('prod_spiff[]', number_format($prod->spiff, 2, '.',''), ['min'=> 0, 'step'=> '0.01', 'readonly' ]) !!}</td>
        <td class="cell-input commission">{!! Form::number('prod_resid[]', number_format($prod->residual, 2, '.',''), ['min'=> 0, 'step'=> '0.01', 'readonly' ]) !!}</td>
        <td class="cell-input money">{!! Form::number('prod_price[]', number_format($prod->price, 2, '.',''), ['min'=> 0, 'step'=> '0.01', 'readonly' ]) !!}</td>
        <td class="cell-input">{!! Form::number('prod_qty[]', number_format($prod->qty, 0, '.',''), ['min'=> 0, 'step'=> '1', 'readonly' ]) !!}</td>
        <td class="nrc-subtotal"></td>
        <td class="mrc-subtotal">{{ number_format($prod->price * $prod->qty, 2, '.','') }}</td>
      </tr>
    @empty
    @endforelse
    
    @forelse ($account->nrc_prods as $prod)
      <tr class="nrc">
        <td>
          {!! Form::hidden('prod_id[]', enc_id($prod->product_id)) !!}
          {!! Form::hidden('is_mrc[]', 0) !!}
          <span class="popup-base">
            <i class="md s btn-prod-update">edit</i>
            <div class="popup-tip"><div>Update Product</div></div>
          </span>
          <span class="popup-base">
            <i class="md s btn-del-prod">close</i>
            <div class="popup-tip"><div>Remove Product</div></div>
          </span>
        </td>
        <td class="svc-name">{{ $prod->svc_name }}</td>
        <td class="prod-name">{{ $prod->prod_name }}</td>
        <td class="cell-input">{!! Form::text('prod_memo[]', $prod->memo, ['maxlength'=> 100, 'readonly' ]) !!}</td>
        <td class="cell-input commission">{!! Form::number('prod_spiff[]', 0, ['min'=> 0, 'step'=> '0.01', 'readonly' ]) !!}</td>
        <td class="cell-input commission">{!! Form::number('prod_resid[]', 0, ['min'=> 0, 'step'=> '0.01', 'readonly' ]) !!}</td>
        <td class="cell-input money">{!! Form::number('prod_price[]', number_format($prod->price, 2, '.',''), ['min'=> 0, 'step'=> '0.01', 'readonly' ]) !!}</td>
        <td class="cell-input">{!! Form::number('prod_qty[]', number_format($prod->qty, 0, '.',''), ['min'=> 0, 'step'=> '1', 'readonly' ]) !!}</td>
        <td class="nrc-subtotal">{{ number_format($prod->price * $prod->qty, 2, '.','') }}</td>
        <td class="mrc-subtotal"></td>
      </tr>
    @empty
    @endforelse
    </tbody>

    <tfoot>
      <tr>
        <td colspan="8">Total</td>
        <td><label class="fa-usd"></label> <i class="cell-nrc">{{ number_format($nrc_total, 2, '.','') }}</i></td>
        <td><label class="fa-usd"></label> <i class="cell-mrc">{{ number_format($mrc_total, 2, '.','') }}</i></td>
      </tr>
    </tfoot>
  </table>

  
  <div class='btn-group'>
    <a href="{!! URL::previous() !!}"><input type="button" value="return to list" /></a>
  </div>
</div>
@endsection

@section('end_of_body')
<div id="cal-pane" class="ingen-calendar"></div>
@endsection

@section('post_content_script')
<script src="/js/ingen.calendar.js"></script>
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function mAccountNew() {
  var overlay = new ingenOverlay('overlay-pane');

  var cal = new ingenCalendar( $('#cal-pane').get(0) );

  $('.btn-comm-update').click(function() {
    overlay.setTitle('Update Comission Share');
    overlay.openAjax({
      url: '{{ route('master.account.overlay-commission-mod', ['accnt_id'=> enc_id($account->id)]) }}',
      method: 'GET', data: {}
    });
  });

  // window.moAccountCommission(): script for overlay
  window['moAccountCommission'] = function() {
    var $overlay = $('#overlay-pane');
    var $tblShare = $overlay.find('.tbl-comm-accnt-share tbody');
    var maxParty = 3 // 3 agency or 3 managers

    $('#tbl-accnt-agency-available').DataTable({
      autoWidth: false,
      ordering: false, paging: false,
      language: { search: '', searchPlaceholder: 'Search Agency'},
      dom: '<ft>',
    });
    $('#tbl-accnt-manager-available').DataTable({
      autoWidth: false,
      ordering: false, paging: false,
      language: { search: '', searchPlaceholder: 'Search Providers'},
      dom: '<ft>',
    });

    $overlay.find('.btn-agency').click(function() {
      $overlay.find('.container-change').removeClass('manager').addClass('agency');
    });
    $overlay.find('.btn-manager').click(function() {
      $overlay.find('.container-change').removeClass('agency').addClass('manager');
    });

    var fnCommissionDel = function() {
      var $tr = $(this).closest('tr');
      $tr.fadeOut({complete: function() { $tr.remove(); }});
    }
    $overlay.find('.btn-del-party').click(fnCommissionDel);
    
    
    // event handler for: select agency/manager
    $('.btn-agency-select').click(function() {
      var agencyId = $(this).attr('data-id');

      var $agencies = $tblShare.find('input[name*="spiff_share[agency]"]');
      if ($agencies.length >= maxParty)
        return alertUser('Total number of Agencies cannot exceed ' + maxParty + '.');

      if ($tblShare.find('input[name="spiff_share[agency][' + agencyId + ']"]').length >0)
        return alertUser('Duplicate Agency cannot be assigned.');

      var $btn = $('<i class="md s btn-del-party">close</i>');
      $btn.click(fnCommissionDel);

      var $tr = $('<tr/>')
        .append(
          $('<td/>').append(
            $('<span class="popup-base">')
              .append($btn)
              .append('<div class="popup-tip left"><div>Remove Agency</div></div>')
          )
        )
        .append('<td>' + $(this).find('.agency-name').text() + '</td>')
        .append('<td>Agency</td>')
        .append('<td><input min="0" step="0.01" max="100" required="" name="spiff_share[agency][' + agencyId + ']" type="number" value="0.00"></td>')
        .append('<td><input min="0" step="0.01" max="100" required="" name="resid_share[agency][' + agencyId + ']" type="number" value="0.00"></td>');

      $('.tbl-comm-accnt-share tbody').append($tr);
    });
    $('.btn-manager-select').click(function() {
      var managerId = $(this).attr('data-id');

      var $managers = $tblShare.find('input[name*="spiff_share[manager]"]');
      if ($managers.length >= maxParty)
        return alertUser('Total number of Managers cannot exceed ' + maxParty + '.');

      if ($tblShare.find('input[name="spiff_share[manager][' + managerId + ']"]').length >0)
        return alertUser('Duplicate Channel Manager cannot be assigned.');

      var $btn = $('<i class="md s btn-del-party">close</i>');
      $btn.click(fnCommissionDel);

      var $tr = $('<tr/>')
        .append(
          $('<td/>').append(
            $('<span class="popup-base">')
              .append($btn)
              .append('<div class="popup-tip left"><div>Remove Manager</div></div>')
          )
        )
        .append('<td>' + $(this).find('.manager-name').text() + '</td>')
        .append('<td>Channel Manager</td>')
        .append('<td><input min="0" step="0.01" max="100" required="" name="spiff_share[manager][' + managerId + ']" type="number" value="0.00"></td>')
        .append('<td><input min="0" step="0.01" max="100" required="" name="resid_share[manager][' + managerId + ']" type="number" value="0.00"></td>');

      $('.tbl-comm-accnt-share tbody').append($tr);
    });
    $overlay.find('form').submit(function(e) {
      e.preventDefault();

      var $elemsSpiff = $tblShare.find('input[name^="spiff_share"]');
      var $elemsResid = $tblShare.find('input[name^="resid_share"]');
      if ($elemsSpiff.length <= 0 && $elemsResid.length <= 0)
        return alertUser('Please assign at least one Agency or Channel Manager.');
      if ($elemsSpiff.length > maxParty || $elemsResid.length > maxParty)
        return alertUser('Total number of Agency/Channel Manager cannot exceed ' + maxParty + '.');
      var totalShare = 0;
      $elemsSpiff.each(function() {
        var share = parseFloat(this.value);
        if (share >=0)
          totalShare += share;
      });
      if (totalShare > 100) {
        $elemsSpiff.get(0).focus();
        return alertUser('Total Commission Share should not exceed 100%.');
      }
      totalShare = 0;
      $elemsResid.each(function() {
        var share = parseFloat(this.value);
        if (share >=0)
          totalShare += share;
      });
      if (totalShare > 100) {
        $elemsResid.get(0).focus();
        return alertUser('Total Commission Share should not exceed 100%.');
      }

      submitFrm(this);
    });
  }; // END: window.moAccountCommission()
  
  $('.btn-prov-mod').click(function() {
    overlay.setTitle('Update Provider');
    overlay.openAjax({
      url: '{{ route('master.account.overlay-provider-mod', ['accnt_id'=> enc_id($account->id)]) }}',
      method: 'GET', data: {}
    });
  });

  // window.moAccountProvider(): script for overlay
  window['moAccountProvider'] = function() {
    var $overlay = $('#overlay-pane');

    $('#tbl-accnt-provider-available').DataTable({
      autoWidth: false,
      ordering: false, paging: false,
      language: { search: '', searchPlaceholder: 'Search Providers'},
      dom: '<ft>',
    });
    
    // event handler for: select provider
    $overlay.find('.btn-prov-select').click(function() {
      $(this).find('input[type=radio]').prop('checked', true);
    });

    $overlay.find('form').submit(function(e) {
      e.preventDefault();

      if ()
      submitFrm(this);
    });
  }; // END: window.moAccountProvider()

  $('.comm-accnt-address .tel').on('input blur', function() {
    cleanInput(this, 'tel');
  });
  $('.comm-accnt-address .btn-copy-ship, .comm-accnt-address .btn-copy-bill').click(function() {
    var $srcAddr, $dstAddr;
    if ($(this).hasClass('btn-copy-ship')) {
      $srcAddr = $('.comm-accnt-address .shipping');
      $dstAddr = $('.comm-accnt-address .billing');
    } else {
      $srcAddr = $('.comm-accnt-address .billing');
      $dstAddr = $('.comm-accnt-address .shipping');
    }
    $dstAddr.find('input.addr').val(  $srcAddr.find('input.addr').val() );
    $dstAddr.find('input.addr2').val( $srcAddr.find('input.addr2').val() );
    $dstAddr.find('input.city').val(  $srcAddr.find('input.city').val() );
    $dstAddr.find('select.state').val( $srcAddr.find('select.state').val() );
    $dstAddr.find('input.zip').val(   $srcAddr.find('input.zip').val() );
    $dstAddr.find('input.tel').val(   $srcAddr.find('input.tel').val() );
  });

  var fnCalcTotal = function() {
    var mrcSubtotal =0;
    var nrcSubtotal =0;

    var $tblProd = $('.tbl-comm-accnt-prods');
    $tblProd.find('.mrc-subtotal').each(function() {
      var subtotal = parseFloat($(this).text());
      if (subtotal >= 0)
        mrcSubtotal += subtotal;
    });
    $tblProd.find('.nrc-subtotal').each(function() {
      var subtotal = parseFloat($(this).text());
      if (subtotal >= 0)
        nrcSubtotal += subtotal;
    });
    $tblProd.find('.cell-mrc').text(mrcSubtotal.toFixed(2));
    $tblProd.find('.cell-nrc').text(nrcSubtotal.toFixed(2));
  };
  var $srcProdTr = null;
  var fnProdUpdate = function() {
    var provId = $elemProvId.val();
    if (provId =='')
      return alertUser('Please select a Service Provider first.');

    $srcProdTr = $(this).closest('tr');

    overlay.setTitle('Update Product Information');
    overlay.openAjax({
      url: '{{ route('master.account.overlay-prod', ['prov_id'=> '']) }}' + '/' + provId,
      method: 'GET', data: {}
    });
  }
  var fnProdDel = function() {
    var $tr = $(this).closest('tr');
    $tr.fadeOut({complete: function() { $tr.remove(); }});
    fnCalcTotal();
  }

  // window.moAccountProduct(): script for overlay
  window['moAccountProduct'] = function() {    
    var $overlayContainer = $('#overlay-pane .frm-overlay');
    var $elemSvc = $overlayContainer.find('.output.svc-name');
    var $elemName = $overlayContainer.find('.output.prod-name');

    var $elemId = $overlayContainer.find('input[name=prod_id]');
    var $elemMemo = $overlayContainer.find('input[name=memo]');
    var $elemPrice = $overlayContainer.find('input[name=price]');
    var $elemQty = $overlayContainer.find('input[name=qty]');
    var $elemMRC = $overlayContainer.find('input[name=mrc]');
    var $elemSpiff = $overlayContainer.find('input[name=spiff]');
    var $elemResidual = $overlayContainer.find('input[name=resid]');

    $('#tbl-accnt-product-available').DataTable({
      autoWidth: true,
      ordering: false, paging: false,
      scrollY: '160px', scrollCollapse: true,
      language: { search: '', searchPlaceholder: 'Search Products'},
      dom: '<ft>',
    });
    
    var $tr;
    if ($srcProdTr) 
      $tr = $srcProdTr;
    else {
      var $btnEdit = $('<i class="md s btn-prod-update">edit</i>');
      $btnEdit.click(fnProdUpdate);
      var $btnDel = $('<i class="md s btn-del-prod">close</i>');
      $btnDel.click(fnProdDel);

      $tr = $('<tr class="mrc"/>');
      $tr.append(
        $('<td/>').append('<input name="prod_id[]" type="hidden" />')
          .append('<input name="is_mrc[]" type="hidden" value="1">')
          .append( $('<span class="popup-base"></span>').append($btnEdit).append('<div class="popup-tip"><div>Update Product</div></div>') )
          .append( $('<span class="popup-base"></span>').append($btnDel).append('<div class="popup-tip"><div>Remove Product</div></div>') )
      )
        .append('<td class="svc-name"></td>')
        .append('<td class="prod-name"></td>')
        .append( $('<td class="cell-input" />').append('<input name="prod_memo[]" maxlength="100" type="text" readonly>') )
        .append( $('<td class="cell-input commission" />').append('<input name="prod_spiff[]" type="number" value="0.00" min="0" step="0.01" readonly>') )
        .append( $('<td class="cell-input commission" />').append('<input name="prod_resid[]" type="number" value="0.00" min="0" step="0.01" readonly>') )
        .append( $('<td class="cell-input money" />').append('<input name="prod_price[]" type="number" value="0.00" min="0" step="0.01" readonly>') )
        .append( $('<td class="cell-input" />').append('<input name="prod_qty[]" type="number" value="1" min="0" step="1" readonly>') )
        .append('<td class="nrc-subtotal"></td>')
        .append('<td class="mrc-subtotal"></td>');
    }
    var $trSvc = $tr.find('.svc-name');
    var $trName = $tr.find('.prod-name');

    var $trId = $tr.find('input[name="prod_id[]"]');
    var $trMemo = $tr.find('input[name="prod_memo[]"]');
    var $trPrice = $tr.find('input[name="prod_price[]"]');
    var $trQty = $tr.find('input[name="prod_qty[]"]');
    var $trMRC = $tr.find('input[name="is_mrc[]"]');
    var $trSpiff = $tr.find('input[name="prod_spiff[]"]');
    var $trResidual = $tr.find('input[name="prod_resid[]"]');
  
    if ($trId.val() != '') {
      $elemId.val( $trId.val() );
      $elemSvc.text( $trSvc.text() );
      $elemName.text( $trName.text() );
    } else {
      $elemId.val('');
      $elemSvc.html('<span class="err">(Please select a Product from the Available Products)</span>');
      $elemName.html('<span class="err">(Please select a Product from the Available Products)</span>');
    }
    $elemMemo.val( $trMemo.val() );
    $elemPrice.val($trPrice.val());
    $elemQty.val($trQty.val());
    $elemMRC.prop('checked', ($trMRC.val() == '1'));
    $elemSpiff.val($trSpiff.val());
    $elemResidual.val($trResidual.val());
    
    // event handler for: select product
    $('.btn-prod-select').click(function() {
      var $prodTr = $(this);

      var price = parseFloat( $prodTr.find('.prod-price').text().replace(/[^\d\.]/g, '') );
      price = (price >= 0)?  price : 0;
      var spiff = parseFloat( $prodTr.find('.prod-spiff').text().replace(/[^\d\.]/g, '') );
      spiff = (spiff >= 0)?  spiff : 0;
      var resid = parseFloat( $prodTr.find('.prod-resid').text().replace(/[^\d\.]/g, '') );
      resid = (resid >= 0)?  resid : 0;

      $elemId.val( $prodTr.attr('data-id') );
      $elemSvc.text( $prodTr.find('.prod-service').text() );
      $elemName.text( $prodTr.find('.prod-name').text() );
      $elemPrice.val( price );
      $elemSpiff.val( spiff );
      $elemResidual.val( resid );
    });

    // event handler for: save change
    $('#overlay-pane .frm-overlay').submit(function(e) {
      e.preventDefault();

      var price = parseFloat($elemPrice.val());
      price = (price >= 0)?  price : 0;
      var qty = parseInt($elemQty.val());
      qty = (qty >= 0)?  qty : 0;
      var subtotal = price * qty;

      $trId.val($elemId.val());
      $trSvc.text($elemSvc.text());
      $trName.text($elemName.text());
      $trMemo.val($elemMemo.val());
      $trPrice.val(price.toFixed(2));
      $trQty.val(qty);

      if ( $elemMRC.prop('checked') ) {
        var spiff = parseFloat($elemSpiff.val());
        spiff = (spiff >= 0)?  spiff : 0;
        var resid = parseFloat($elemResidual.val());
        resid = (resid >= 0)?  resid : 0;
        
        $tr.addClass('mrc').removeClass('nrc');
        $trMRC.val(1);
        $trSpiff.val(spiff.toFixed(2));
        $trResidual.val(resid.toFixed(2));
        $tr.find('.nrc-subtotal').text('');
        $tr.find('.mrc-subtotal').text( subtotal.toFixed(2) );

      } else {
        $tr.addClass('nrc').removeClass('mrc');
        $trMRC.val(0);
        $tr.find('.nrc-subtotal').text( subtotal.toFixed(2) );
        $tr.find('.mrc-subtotal').text('');
      }
      if (!$srcProdTr)
        $('.tbl-comm-accnt-prods tbody').append($tr);
      fnCalcTotal();

      // remove src product, and close overlay
      $srcProdTr = null;
      overlay.close();
    })
  }; // END: window.moAccountProduct()

  $('.btn-prod-add').click(function() {
    var provId = $elemProvId.val();
    if (provId =='')
      return alertUser('Please select a Service Provider first.');

    overlay.setTitle('Add Product');
    overlay.openAjax({
      url: '{{ route('master.account.overlay-prod', ['prov_id'=> '']) }}' + '/' + provId,
      method: 'GET', data: {}
    });
  });
  $('.btn-prod-update').click(fnProdUpdate);
  $('.btn-del-prod').click(fnProdDel);

  $('form.frm-accnt').submit(function(e) {
    e.preventDefault();

    var $elemsSpiff = $('.tbl-comm-accnt-share tbody input[name^="spiff_share');
    var $elemsResid = $('.tbl-comm-accnt-share tbody input[name^="resid_share');
    if ($elemsSpiff.length <= 0 && $elemsResid.length <= 0)
      return alertUser('Please assign at least one Agency or Channel Manager.');
    if ($elemsSpiff.length + $elemsResid.length > maxParty)
      return alertUser('Total number of Agency plus Channel Manager cannot exceed ' + maxParty + '.');
    var totalShare = 0;
    $elemsSpiff.each(function() {
      var share = parseFloat(this.value);
      if (share >=0)
        totalShare += share;
    });
    if (totalShare > 100) {
      $elemsSpiff.get(0).focus();
      return alertUser('Total Commission Share should not exceed 100%.');
    }
    totalShare = 0;
    $elemsResid.each(function() {
      var share = parseFloat(this.value);
      if (share >=0)
        totalShare += share;
    });
    if (totalShare > 100) {
      $elemsResid.get(0).focus();
      return alertUser('Total Commission Share should not exceed 100%.');
    }

    if (this.date_sign.value == '') {
      this.date_sign.focus();
      return alertUser('Date Signed is a required field.');
    }

    var $elemsProdId = $('.tbl-comm-accnt-prods tbody input[name="prod_id[]"]');
    if ($elemsProdId.length <= 0)
      return alertUser('Account must have at least one product.');

    submitFrm(this);
  })
}
mAccountNew();
</script>
@endsection
