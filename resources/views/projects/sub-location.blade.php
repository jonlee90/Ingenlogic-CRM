<?php
/**
* required vars
* @param $locations: array of location objects
* @param $open_first: set first location to "expanded" if true
* @param $is_master: if false, show quote x commission rate for the agency
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
        <div class="btns">
          <span class="popup-base">
            <i class="md md-btn btn-loc-file">attach_file</i>
            @if ($loc->file_count)
            <div class="lead-loc-file-count">{{ $loc->file_count }}</div>
            @endif
            <div class="popup-tip"><div>Attached Files</div></div>
          </span>
        </div>
        <div class="addr">{{ $loc->addr }}</div>
      </div>
      
      <?php // ****************************************** Keep Accounts ****************************************** ?>
      @if ($loc->kept_accounts)
      
      <div class="account-type-header curr">
        <div class="title">Accounts As-is</div>
      </div>
      <section class="list-account keep">

        @foreach ($loc->kept_accounts as $accnt)
        <?php
        $accnt_class = ($accnt->is_selected)?  'checked' : '';
        ?>
        <div class="account {{ $accnt_class }}" data-accnt-id="{{ enc_id($accnt->id) }}">
          <div class="accnt-info">
            <div class="accnt-control">
              <span class="popup-base">
                <i class="md btn-accnt-keep-mod">edit</i>
                <div class="popup-tip"><div>Update Account</div></div>
              </span>
              {!! Form::open(['url'=> route('project.accnt-revert', ['accnt_id'=> enc_id($accnt->id)]), 'method'=> 'DELETE', 'class'=> 'inline']) !!}
              <span class="popup-base">
                <i class="md btn-accnt-keep-revert">close</i>
                <div class="popup-tip"><div>Revert to Lead</div></div>
              </span>
              {!! Form::close() !!}
              {!! Form::open(['url'=> route('project.accnt-complete', ['accnt_id'=> enc_id($accnt->id)]), 'class'=> 'inline']) !!}
              <span class="popup-base">
                <i class="md btn-accnt-complete">done</i>
                <div class="popup-tip right"><div>Mark Account as Complete</div></div>
              </span>
              {!! Form::close() !!}
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

          @if ($accnt->is_complete)
          <div class="complete-overlay">
            <div class="popup-base">
              {!! Form::open(['url'=> route('project.accnt-complete-undo', ['accnt_id'=> enc_id($accnt->id)]), 'class'=> 'inline']) !!}
                <i class="md btn-undo-complete">undo</i>
               {!! Form::close() !!}
              <div class="popup-tip right"><div>Undo Account Completion</div></div>
            </div>
          </div>
          @endif
        </div>
        @endforeach

      </section>

      @endif
      
      <?php // ****************************************** Cancel Accounts ****************************************** ?>
      @if ($loc->cancel_accounts)

      <div class="account-type-header curr">
        <div class="title">Accounts to Cancel</div>
      </div>
      <section class="list-account cancel">

        @foreach ($loc->cancel_accounts as $accnt)
        <?php
        $accnt_class = ($accnt->is_selected)?  'checked' : '';
        ?>
        <div class="account {{ $accnt_class }}" data-accnt-id="{{ enc_id($accnt->id) }}">
          <div class="accnt-info">
            <div class="accnt-control">
              <span class="popup-base">
                <i class="md btn-accnt-cancel-date">today</i>
                <div class="popup-tip"><div>Update Dates</div></div>
              </span>
              {!! Form::open(['url'=> route('project.accnt-revert', ['accnt_id'=> enc_id($accnt->id)]), 'method'=> 'DELETE', 'class'=> 'inline']) !!}
              <span class="popup-base">
                <i class="md btn-accnt-cancel-revert">close</i>
                <div class="popup-tip"><div>Revert to Lead</div></div>
              </span>
              {!! Form::close() !!}
              {!! Form::open(['url'=> route('project.accnt-complete', ['accnt_id'=> enc_id($accnt->id)]), 'class'=> 'inline']) !!}
              <span class="popup-base">
                <i class="md btn-accnt-complete">done</i>
                <div class="popup-tip right"><div>Mark Account as Complete</div></div>
              </span>
              {!! Form::close() !!}
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
                <th>Port Out</th>
                <th>Date Cancelled</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><?=($accnt->date_portout)?  $accnt->date_portout : '-' ?></td>
                <td><?=($accnt->date_cancel)?  $accnt->date_cancel : '-' ?></td>
              </tr>
            </tbody>
          </table>

          @if ($accnt->is_complete)
          <div class="complete-overlay">
            <div class="popup-base">
              {!! Form::open(['url'=> route('project.accnt-complete-undo', ['accnt_id'=> enc_id($accnt->id)]), 'class'=> 'inline']) !!}
                <i class="md btn-undo-complete">undo</i>
               {!! Form::close() !!}
              <div class="popup-tip right"><div>Undo Account Completion</div></div>
            </div>
          </div>
          @endif
        </div>
        @endforeach
        
      </section>

      @endif
          
      <?php // ****************************************** New (Signed) Accounts ****************************************** ?>
      @if ($loc->signed_accounts)
      <div class="account-type-header new">
        <div class="title">Signed Accounts</div>
      </div>
      <section class="list-account signed">

        @foreach ($loc->signed_accounts as $quote)
        <?php
        $mrc_tbody_class = ($quote->mrc_prods && count($quote->mrc_prods) %2 == 1)? 'odd-count':'';
        $mrc_total = $nrc_total = 0;
        ?>
        <div class="account checked" data-quote-id="{{ enc_id($quote->id) }}">
          <div class="accnt-info">
            <div class="accnt-control">
              <span class="popup-base">
                <i class="md btn-accnt-sign-date">today</i>
                <div class="popup-tip"><div>Update Dates</div></div>
              </span>
              {!! Form::open(['url'=> route('project.sign-revert', ['quote_id'=> enc_id($quote->id)]), 'method'=> 'DELETE', 'class'=> 'inline']) !!}
              <span class="popup-base">
                <i class="md btn-accnt-sign-revert">close</i>
                <div class="popup-tip"><div>Revert to Lead</div></div>
              </span>
              {!! Form::close() !!}
              {!! Form::open(['url'=> route('project.sign-complete', ['quote_id'=> enc_id($quote->id)]), 'class'=> 'inline']) !!}
              <span class="popup-base">
                <i class="md btn-accnt-complete">done</i>
                <div class="popup-tip right"><div>Mark Account as Complete</div></div>
              </span>
              {!! Form::close() !!}
            </div>
            <b class="prov-name">{{ $quote->name }}</b>
            <div class="info-detail">
              <p><label>Terms</label> {{ ($quote->term >1)? $quote->term.' month' : 'Month to Month' }}</p>
              
              @if (!$is_master)
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
              @endif
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
                <td><?=($quote->date_signed)?  $quote->date_signed : '-' ?></td>
                <td>
                  {{ $quote->date_inspect }}
                  @if ($quote->inspect_done)
                    <span class="popup-base"><i class="md s">done</i> <div class="popup-tip"><div>complete</div></div></span>
                  @elseif (!$quote->date_inspect)
                    -
                  @endif
                </td>
                <td>
                  {{ $quote->date_construct }}
                  @if ($quote->construct_done)
                    <span class="popup-base"><i class="md s">done</i> <div class="popup-tip"><div>complete</div></div></span>
                  @elseif (!$quote->date_construct)
                    -
                  @endif
                </td>
                <td>
                  {{ $quote->date_install }}
                  @if ($quote->install_done)
                    <span class="popup-base"><i class="md s">done</i> <div class="popup-tip"><div>complete</div></div></span>
                  @elseif (!$quote->date_install)
                    -
                  @endif
                </td>
                <td>
                  {{ $quote->date_portin }}
                  @if ($quote->portin_done)
                    <span class="popup-base"><i class="md s">done</i> <div class="popup-tip"><div>complete</div></div></span>
                  @elseif (!$quote->date_portin)
                    -
                  @endif
                </td>
              </tr>
            </tbody>
          </table>
          
          <div class="btn-accnt-sign-toggle popup-base">
            <i class="md s">expand_more</i>
            <div class="popup-tip"><div>Toggle products</div></div>
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
              <tr> <td colspan="7" class="err">The Account has 0 products.</td> </tr>
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
          @if ($quote->is_complete)
          <div class="complete-overlay">
            <div class="popup-base">
              {!! Form::open(['url'=> route('project.sign-complete-undo', ['quote_id'=> enc_id($quote->id)]), 'class'=> 'inline']) !!}
                <i class="md btn-undo-complete">undo</i>
               {!! Form::close() !!}
              <div class="popup-tip right"><div>Undo Account Completion</div></div>
            </div>
          </div>
          @endif
        </div>
        @endforeach

      </section>

      @endif
      
    </div> <?php /* loc-content */ ?>
  </div>
@empty
@endforelse