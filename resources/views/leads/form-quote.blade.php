<?php
/**
* required vars
* @param $providers: available providers to choose from
* @param $data: [provider_id, name,  default_spiff, default_residual, agent_spiff, agent_residual]
* @param $quote: location x quote object
*/
?>
<div class="overlay-form <?=($providers)? 'overlay-lead-quote':'' ?>">

  @if($providers)
  <h2>Available Service Providers</h2>
  
  <div class="lead-list-available">
    <table id="tbl-lead-provider-available">
      <thead><tr> <th></th> <th>Name</th> <th>Default Spiff</th> <th>Default Residual</th> </tr></thead>
      <tbody>
      @foreach ($providers as $prov)
        <tr data-id="{{ enc_id($prov->id) }}" class="btn-prov-select">
          <td><i class="md md-18" title="Select Provider">done</i></td>
          <td>{{ $prov->name }}</td>
          <td>{{ number_format($prov->default_spiff, 2) }} %</td>
          <td>{{ number_format($prov->default_residual, 2) }} %</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif

  <div class="form-input">
    {!! Form::hidden('prov_id') !!}

    <div class="input-group">
      <label>Service Provider Name</label>
      <div class="output prov-name"><?=($data->provider_id >0)?  $data->name : '<span class="err">Please select a Service Provider</span>' ?></div>
    </div>
    
    @unless($providers)
    <div class="input-group">
      <label>Default Spiff</label>
      <div class="output">{{ number_format($data->default_spiff, 2) }} %</div>
    </div>
    <div class="input-group">
      <label>Default Residual</label>
      <div class="output">{{ number_format($data->default_residual, 2) }} %</div>
    </div>
    <div class="input-group">
      <label>Commission Share for Your Agency</label>
      <table class="tbl-lead-agency-rates">
        <thead>
          <tr> <th>Spiff</th> <th>Residual</th> </tr>
        </thead>
        <tbody>
          <tr>
            <td><?=($quote->spiff_share)? number_format($quote->spiff_share, 2).' %' : number_format($quote->spiff_expect, 2).' % (default)' ?></td>
            <td><?=($quote->resid_share)? number_format($quote->resid_share, 2).' %' : number_format($quote->resid_expect, 2).' % (default)' ?></td>
          </tr>
          <tr>
            <td class="money">
              @unless ($quote->spiff_share === NULL)
                {{ number_format($quote->spiff_share * $quote->spiff_total /100, 2) }}
              @else
                {{ number_format($quote->spiff_expect * $quote->spiff_total /100, 2).' (expected)' }}
              @endif
            </td>
            <td class="money">
              @unless ($quote->resid_share === NULL)
                {{ number_format($quote->resid_share * $quote->resid_total /100, 2) }}
              @else
                {{ number_format($quote->resid_expect * $quote->resid_total /100, 2).' (expected)' }}
              @endif
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    @endunless

    <div class="input-group">
      <label>Terms</label>
      <div class="wrapper-input">
        <div class="row">
          <div class="cell">
            {!! Form::number('term', $quote->term, ['min'=> 1, 'step'=> 1, 'max'=> 500, 'pattern'=> '^\d+$', 'title'=> 'Enter the terms in months.']) !!}
          </div>
          <label><p>month</p></label>
        </div>
      </div>
    </div>
  </div>
</div>
