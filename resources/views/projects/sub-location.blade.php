<?php
/**
* required vars
* @param $locations: array of location objects
* @param $open_first: set first location to "expanded" if true
* @param $quote_requested: if false, disable adding quotes
*/
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
        <div class="addr">{{ $loc->addr }}</div>
      </div>
      
      <?php // ****************************************** Keep Accounts ****************************************** ?>
      <div class="account-type-header curr">
        <div class="title">Accounts As-is</div>
      </div>
      <section class="list-account keep">

        @forelse ($loc->kept_accounts as $accnt)
        <?php
        $accnt_class = ($accnt->is_selected)?  'checked' : '';
        ?>
        <div class="account {{ $accnt_class }}" data-accnt-id="{{ enc_id($accnt->id) }}">
          <div class="accnt-info">
            <div class="accnt-control">
              <span class="popup-base">
                <i class="md btn-accnt-curr-mod">edit</i>
                <div class="popup-tip"><div>Update Account</div></div>
              </span>
              <span class="popup-base">
                <i class="md btn-quote-del">close</i>
                <div class="popup-tip"><div>Revert to Lead</div></div>
              </span>
              <span class="popup-base">
                <i class="md btn-accnt-curr-mod">done</i>
                <div class="popup-tip right"><div>Mark Account as Complete</div></div>
              </span>
            </div>
            <b class="prov-name">{{ $accnt->name }}
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
      
      <?php // ****************************************** Cancel Accounts ****************************************** ?>
      <div class="account-type-header curr">
        <div class="title">Accounts to Cancel</div>
      </div>
      <section class="list-account cancel">

        @forelse ($loc->cancel_accounts as $accnt)
        <?php
        $accnt_class = ($accnt->is_selected)?  'checked' : '';
        ?>
        <div class="account {{ $accnt_class }}" data-accnt-id="{{ enc_id($accnt->id) }}">
          <div class="accnt-info">
            <div class="accnt-control">
              <span class="popup-base">
                <i class="md btn-quote-mod">today</i>
                <div class="popup-tip"><div>Update Dates</div></div>
              </span>
              <span class="popup-base">
                <i class="md btn-quote-del">close</i>
                <div class="popup-tip"><div>Revert to Lead</div></div>
              </span>
              <span class="popup-base">
                <i class="md btn-accnt-curr-mod">done</i>
                <div class="popup-tip right"><div>Mark Account as Complete</div></div>
              </span>
            </div>
            <b class="prov-name">{{ $accnt->name }}
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
          <table class="tbl-accnt-dates">
            <thead>
              <tr>
                <th>Port In</th>
                <th>Date Cancelled</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>2001-01-01</td>
                <td>2001-01-01 <i class="md s">done</i></td>
              </tr>
            </tbody>
          </table>
        </div>
        @empty
        @endforelse

      </section>
          
      <?php // ****************************************** New Accounts ****************************************** ?>
      <div class="account-type-header new">
        <div class="title">Signed Accounts</div>
      </div>
      <section class="list-account signed">

        @forelse ($loc->signed_accounts as $quote)
        <div class="account" data-quote-id="{{ enc_id($quote->id) }}">
          <div class="accnt-info">
            <div class="accnt-control">
              <span class="popup-base">
                <i class="md btn-quote-mod">today</i>
                <div class="popup-tip"><div>Update Dates</div></div>
              </span>
              <span class="popup-base">
                <i class="md btn-quote-del">close</i>
                <div class="popup-tip"><div>Revert to Lead</div></div>
              </span>
              <span class="popup-base">
                <i class="md btn-accnt-curr-mod">done</i>
                <div class="popup-tip right"><div>Mark Account as Complete</div></div>
              </span>
            </div>
            <b class="prov-name">{{ $quote->name }}</b>
            <div class="info-detail">
              <p><label>Terms</label> {{ ($quote->term >1)? $quote->term.' month' : 'Month to Month' }}</p>
              <div>
                <p>
                  <label>Spiff</label>
                  @unless ($quote->spiff_share === NULL)
                  $ {{ number_format($quote->total_spiff * $quote->spiff_share /100, 2) }}
                  @else
                  $ {{ number_format($quote->total_spiff * $quote->spiff_expect /100, 2) }} (expected)
                  @endif
                </p>
                <p>
                  <label>Residual</label>
                  @unless ($quote->resid_share === NULL)
                  $ {{ number_format($quote->total_resid * $quote->resid_share /100, 2) }}
                  @else
                  $ {{ number_format($quote->total_resid * $quote->resid_expect /100, 2) }} (expected)
                  @endif
                </p>
              </div>
            </div>
          </div>
          <table class="tbl-accnt-dates">
            <thead>
              <tr>
                <th>Signed</th>
                <th>Site Survey</th>
                <th>Construction</th>
                <th>Installation</th>
                <th>Port In</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>2001-01-01</td>
                <td>2001-01-01 <i class="md s">done</i></td>
                <td>2001-01-01</td>
                <td>2001-01-01</td>
                <td>2001-01-01</td>
              </tr>
            </tbody>
          </table>
        </div>
        @empty
        @endforelse

      </section>
    </div> <?php /* loc-content */ ?>
  </div>
@empty
@endforelse