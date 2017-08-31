<?php
/**
* required vars
* @param $locations: array of location objects
* @param $open_first: set first location to "expanded" if true
* @param $quote_requested: if false, disable adding quotes
*/

$iteration_accnt = 1;
$iteration_quote = 1;
?>
@forelse ($locations as $loc)
  <?php
  $loc_class = ($loop->first && $open_first)?  'expanded' : '';
  ?>
  <div class="location {{ $loc_class }}" data-id="{{ enc_id($loc->id) }}">
    <h2 class="btn-loc-expand popup-base">
      <span class="loc-name">{{ $loc->name }}</span>
      <div class="float-r">
        <i class="md">expand_more</i>
        <div class="popup-tip right"><div>Toggle Location</div></div>
      </div>
    </h2>
    <div class="loc-content">
      <div class="loc-preview">
        <div class="btns">
          <span class="popup-base">
            <i class="md md-btn btn-loc-mod">edit_location</i>
            <div class="popup-tip"><div>Update Location</div></div>
          </span>
          <span class="popup-base">
            <i class="md md-btn btn-del-location">close</i>
            <div class="popup-tip right"><div>Delete Location</div></div>
          </span>
        </div>
        <div class="addr">{{ $loc->addr }}</div>
      </div>
      
      <?php // ****************************************** Current Accounts ****************************************** ?>
      <div class="account-type-header curr">
        <div class="title">Current Accounts</div>
        <div class="popup-base">
          <i class="md btn-accnt-curr-add">add_box</i>
          <div class="popup-tip"><div>Add Current Account</div></div>
        </div>
      </div>
      <section class="list-account curr">

        @forelse ($loc->curr_accounts as $accnt)
        <?php
        $accnt_class = ($accnt->is_selected)?  'checked' : '';
        ?>
        <div class="account {{ $accnt_class }}" data-accnt-id="{{ enc_id($accnt->id) }}">
          <div class="accnt-info">
            <span class="wrapper-checker popup-base">
              <i class="md btn-accnt-checker"></i>
              <div class="popup-tip"><div></div></div>
            </span>
            <div class="accnt-control">
              <span class="popup-base">
                <i class="md btn-accnt-curr-mod">edit</i>
                <div class="popup-tip"><div>Update Account</div></div>
              </span>
              <span class="popup-base">
                <i class="md btn-accnt-curr-del">close</i>
                <div class="popup-tip"><div>Remove Account</div></div>
              </span>
            </div>
            <b class="prov-name">{{ $iteration_accnt++ }}. {{ $accnt->name }}
              @if($accnt->memo)
              <span class="popup-base">
                <i class="md s">feedback</i>
                <div class="popup-help">
                  {!! nl2br($accnt->memo) !!}
                </div>
              </span>
              @endif
            </b>
            <div class="info-detail">
              <div>
                <p><label>Account #</label> {{ $accnt->accnt_no }}</p>
                @if($accnt->passcode)
                <p><label>Passcode</label> {{ $accnt->passcode }}</p>
                @endif
              </div>
              <p><label>Terms</label> {{ ($accnt->term >1)? $accnt->term.' month' : 'Month to Month' }}</p>
              <?=($accnt->date_end)?  '<p><label>Contract End</label> '.format_date($accnt->date_end).'</p>' : '' ?> 
              <?=($accnt->etf >0)?  '<p><label>ETF</label> $ '.number_format($accnt->etf, 2).'</p>' : '' ?> 
            </div>
          </div>
          <table class="tbl-accnt-prods">
            <thead>
              <tr>
                <th>Service</th>
                <th>Product</th>
                <th>Note</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
            <?php
            // calculate MRC total if product(s) exist
            $mrc_total = 0;

            ?>
            @forelse ($accnt->products as $prod)
              <?php
                $prod_subtotal = $prod->price * $prod->qty;
                $mrc_total += $prod_subtotal;
              ?>
              <tr>
                <td>{{ $prod->svc_name }}</td>
                <td>{{ $prod->prod_name }}</td>
                <td>{{ $prod->memo }}</td>
                <td>{{ number_format($prod->price, 2) }}</td>
                <td>{{ number_format($prod->qty) }}</td>
                <td>{{ number_format($prod_subtotal, 2) }}</td>
              </tr>
            @empty
              <tr> <td colspan="7" class="err">The account has 0 products. Please add products for the Account</td> </tr>
            @endforelse
            </tbody>
            
            <tfoot>
              <tr>
                <td colspan="5">Total</td>
                <td><label class="fa-usd"></label> <i class="cell-mrc">{{ number_format($mrc_total, 2) }}</i></td>
              </tr>
            </tfoot>
          </table>
        </div>
        @empty
        @endforelse

      </section>
          
      <?php // ****************************************** Quotes ****************************************** ?>
      <div class="account-type-header quote">
        <div class="title">quotes</div>
        <div class="popup-base">
          <i class="md btn-quote-add">add_box</i>
          <div class="popup-tip"><div>Add Quote</div></div>
        </div>
      </div>
      <section class="list-account quote">

        @forelse ($loc->quotes as $quote)
        <?php
        $quote_class = ($quote->is_selected)?  'checked' : '';
        $mrc_tbody_class = ($quote->mrc_prods && count($quote->mrc_prods) %2 == 1)? 'odd-count':'';
        $mrc_total = $nrc_total = 0;
        ?>
        <div class="account {{ $quote_class }}" data-quote-id="{{ enc_id($quote->id) }}">
          <div class="accnt-info">
            <span class="wrapper-checker popup-base">
              <i class="md btn-accnt-checker"></i>
              <div class="popup-tip"><div></div></div>
            </span>
            <div class="accnt-control">
              <span class="popup-base">
                <i class="md btn-quote-mod">edit</i>
                <div class="popup-tip"><div>Update Quote</div></div>
              </span>
              <span class="popup-base">
                <i class="md btn-quote-del">close</i>
                <div class="popup-tip"><div>Remove Account</div></div>
              </span>
            </div>
            <b class="prov-name">{{ $iteration_quote++ }}. {{ $quote->name }}</b>
            <div class="info-detail">
              <p><label>Terms</label> {{ ($quote->term >1)? $quote->term.' month' : 'Month to Month' }}</p>
            </div>
          </div>
          
          <table class="tbl-accnt-prods">
            <thead>
              <tr>
                <th>Service</th>
                <th>Product</th>
                <th>Note</th>
                <th>Price</th>
                <th>Qty</th>
                <th>NRC Subtotal</th>
                <th>MRC Subtotal</th>
              </tr>
            </thead>

            <tbody class="mrc {{ $mrc_tbody_class }}">
            @forelse ($quote->mrc_prods as $prod)
              <?php
                // calculate MRC/NRC total if product(s) exist
                $prod_subtotal = $prod->price * $prod->qty;
                $mrc_total += $prod_subtotal;
              ?>
              <tr {!! ($prod->valid) ? '' : 'class="grayed"' !!}>
                <td>{{ $prod->svc_name }}
                  {!! ($prod->valid)?
                      '' :
                      '<span class="popup-base">
                        <i class="md s danger">report_problem</i>
                        <div class="popup-tip"><div><p>Product has Invalid Association with:</p><p>Providers, Services, or Products in the system.</p></div></div>
                      </span>'
                  !!}
                </td>
                <td>{{ $prod->prod_name }}</td>
                <td>{{ $prod->memo }}</td>
                <td>{{ number_format($prod->price, 2) }}</td>
                <td>{{ number_format($prod->qty) }}</td>
                <td></td>
                <td>{{ number_format($prod_subtotal, 2) }}</td>
              </tr>
            @empty
            @endforelse
            </tbody>

            <tbody class="nrc">
            @forelse ($quote->nrc_prods as $prod)
              <?php
                $prod_subtotal = $prod->price * $prod->qty;
                $nrc_total += $prod_subtotal;
              ?>
              <tr {!! ($prod->valid) ? '' : 'class="grayed"' !!}>
                <td>{{ $prod->svc_name }}
                  {!! ($prod->valid)?
                      '' :
                      '<span class="popup-base">
                        <i class="md s danger">report_problem</i>
                        <div class="popup-tip"><div><p>Product has Invalid Association with:</p><p>Providers, Services, or Products in the system.</p></div></div>
                      </span>'
                  !!}
                </td>
                <td>{{ $prod->prod_name }}</td>
                <td>{{ $prod->memo }}</td>
                <td>{{ number_format($prod->price, 2) }}</td>
                <td>{{ number_format($prod->qty) }}</td>
                <td>{{ number_format($prod_subtotal, 2) }}</td>
                <td></td>
              </tr>
            @empty
            @endforelse

            @if(!count($quote->mrc_prods) && !count($quote->nrc_prods))
              <tr> <td colspan="7" class="err">The quote has 0 products. Please add products for the Quote</td> </tr>
            @endif
            </tbody>

            <tfoot>
              <tr>
                <td colspan="5">Total</td>
                <td><label class="fa-usd"></label> <i class="cell-nrc">{{ number_format($nrc_total, 2) }}</i></td>
                <td><label class="fa-usd"></label> <i class="cell-mrc">{{ number_format($mrc_total, 2) }}</i></td>
              </tr>
            </tfoot>
          </table>
        </div>
        @empty
        @endforelse

      </section>
    </div> <?php /* loc-content */ ?>
  </div>
@empty
@endforelse