@extends('layouts.app')

@section('title', "Quote Summary Report | ".SITE_TITLE." Control Panel v2")

@section('wrapper_type', 'print-friendly')

@section('content')
<section class="print-hide">
  <a href="{{ route('lead.manage', ['id'=> enc_id($lead->id)]) }}">
    <button class="btn-icon">
      <i class="md">arrow_back</i>
      Return to Lead Management
    </button>
  </a>
  <button class="btn-icon" onclick="print()">
    <i class="md">print</i>
    Print Report
  </button>
</section>

<section>
  <table>
    <thead>
      <tr>
        <th colspan="6">Location</th>
        <th colspan="4">Service Provider</th>
        <th colspan="5">Detail</th>
      </tr>
      <tr>
        <th></th>
        <th>Address</th>
        <th>Ste</th>
        <th>city</th>
        <th>st</th>
        <th>zip</th>
        
        <th>name</th>
        <th>nrc</th>
        <th>mrc</th>
        <th>term</th>
        
        <th>service</th>
        <th>product</th>
        <th>one time price</th>
        <th>recurring price</th>
        <th>qty</th>
      </tr>
    </thead>

    <tbody>
    <?php
    $rpt_mrc_total = $rpt_nrc_total = 0;
    ?>  
    @forelse ($data->locations as $loc)
      <?php
      $loc_cells = '
        <td>'.$loc->addr.'</td>
        <td>'.$loc->addr2.'</td>
        <td>'.$loc->city.'</td>
        <td>'.$loc->state_code.'</td>
        <td>'.$loc->zip.'</td>
      ';
      ?>
      @forelse ($loc->quotes as $quote)
        <?php
        // calculate MRC/NRC total if product(s) exist
        $mrc_total = $nrc_total = 0;
        if ($quote->prods) {
          foreach ($quote->prods as $prod) {
            if ($prod->is_mrc >0)
              $mrc_total += $prod->price * $prod->qty;
            else
              $nrc_total += $prod->price * $prod->qty;
          }
        }
        $rpt_mrc_total += $mrc_total;
        $rpt_nrc_total += $nrc_total;
        
        $o_name = ($quote->is_curr >0)?  $quote->name.' (current)' : $quote->name;
        $o_term = ($quote->term >0)?  $quote->term.' month' : 'M2M';
        $prov_cells = '
          <td>'.$o_name.'</td>
          <td>$ '.number_format($nrc_total, 2).'</td>
          <td>$ '.number_format($mrc_total, 2).'</td>
          <td>'.$o_term.'</td>
        ';
        ?>
        @forelse ($quote->prods as $prod)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <?=($loop->parent->first && $loop->first)?  $loc_cells : '<td></td> <td></td> <td></td> <td></td> <td></td>' ?>
          <?=($loop->first)?  $prov_cells : '<td></td> <td></td> <td></td> <td></td>' ?>
          <td>{{ $prod->svc_name }}</td>
          <td>{{ $prod->prod_name }}</td>
          <td><?=($prod->is_mrc) ? '-' : '$ '.number_format($prod->price, 2) ?></td>
          <td><?=($prod->is_mrc) ? '$ '.number_format($prod->price, 2) : '-' ?></td>
          <td>{{ number_format($prod->qty) }}</td>
        </tr>
        @empty
        <tr>
          <td>a</td>
          <?=($loop->first)?  $loc_cells : '<td></td> <td></td> <td></td> <td></td> <td></td>' ?>

          {!! $prov_cells !!}
          <td></td><td></td><td></td><td></td><td></td>
        </tr>
        @endforelse
      @empty
        <tr>
          <td>b</td>
          {!! $loc_cells !!}
          <td></td><td></td><td></td>
          <td></td><td></td><td></td><td></td><td></td>
        </tr>
      @endforelse
    @empty
    @endforelse
    </tbody>

    <tfoot>
      <tr>
        <th colspan="6"></th>
        <th>Total</th>
        <th>$ {{ number_format($rpt_nrc_total, 2) }}</th>
        <th>$ {{ number_format($rpt_mrc_total, 2) }}</th>
        <th colspan="6"></th>
      </tr>
    </tfoot>
  </table>
</section>
@endsection