<?php
/**
* required vars
* @param $providers: available providers to choose from (present = adding quote, otherwise = update quote)
* @param $data: [provider_id, name,  default_spiff, default_residual, rates (agencies share)]
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

      @if ($data->manager_share)
    <div class="input-group">
      <label>Your Commission Share</label>
      <table class="tbl-lead-agency-rates">
        <thead>
          <tr> <th>Spiff</th> <th>Residual</th> </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              @unless ($data->manager_share->spiff_share === NULL)
              {{ number_format($data->manager_share->spiff_share, 2) }} %
              @else
              {{ number_format($data->manager_share->spiff_expect, 2) }} % (default)
              @endif
            </td>
            <td>
              @unless ($data->manager_share->resid_share === NULL)
              {{ number_format($data->manager_share->resid_share, 2) }} %
              @else
              {{ number_format($data->manager_share->resid_expect, 2) }} % (default)
              @endif
            </td>
          </tr>
          <tr>
            <td class="money">
              @unless ($data->manager_share->spiff_share === NULL)
                {{ number_format($data->manager_share->spiff_share * $quote->spiff_total /100, 2) }}
              @else
                {{ number_format($data->manager_share->resid_share * $quote->spiff_total /100, 2) }} (expected)
              @endif
            </td>
            <td class="money">
              @unless ($data->manager_share->resid_share === NULL)
                {{ number_format($data->manager_share->resid_share * $quote->resid_total /100, 2) }}
              @else
                {{ number_format($data->manager_share->resid_expect * $quote->resid_total /100, 2) }} (expected)
              @endif
            </td>
          </tr>
        </tbody>
      </table>
    </div>
      @endif

      @if ($data->rates)
    <div class="input-group">
      <label>Agency Commission Share</label>
      <table class="tbl-lead-agency-rates">
        <thead>
          <tr> <th></th> <th colspan="2">Spiff</th> <th colspan="2">Residual</th> </tr>
        </thead>
        <tbody>

        @foreach ($data->rates as $agency_rate)
          <tr>
            <td>{{ $agency_rate->agency }}</td>
            @unless ($agency_rate->spiff_share ===NULL)
            <td class="money">{{ number_format($quote->spiff_total * $agency_rate->spiff_share /100, 2) }}</td>
            <td>{{ number_format($agency_rate->spiff_share, 2) }} %</td>

            @else
            <td class="money">{{ number_format($quote->spiff_total * $agency_rate->spiff_expect /100, 2) }} (expected)</td>
            <td>{{ number_format($agency_rate->spiff_expect, 2) }} % (default)</td>

            @endif

            @unless ($agency_rate->residual_share ===NULL)
            <td class="money">{{ number_format($quote->resid_total * $agency_rate->residual_share /100, 2) }}</td>
            <td>{{ number_format($agency_rate->residual_share, 2) }} %</td>

            @else
            <td class="money">{{ number_format($quote->resid_total * $agency_rate->residual_expect /100, 2) }} (expected)</td>
            <td>{{ number_format($agency_rate->residual_expect, 2) }} % (default)</td>

            @endif
          </tr>
        @endforeach
        
        </tbody>
      </table>
      </div>
      @endif
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


<?php /*
    <div class="input-group">
      <label>Agency Spiff</label>
      <div class="wrapper-input">
        <div class="row">
          <div class="cell popup-base">
            {!! Form::number('spiff', $data->agent_spiff, ['min'=> 0, 'step'=> 0.01, 'max'=> MAX_AGENCY_RATE, 'required']) !!}
            <div class="popup-tip"><div>
              <p>Agency share of Spiff (0 - {{ MAX_AGENCY_RATE }} %)</p>
            </div></div>
          </div>
          <label class="fa-percent"></label>
        </div>
      </div>
    </div>
    <div class="input-group">
      <label>Agency Residual</label>
      <div class="wrapper-input">
        <div class="row">
          <div class="cell popup-base">
            {!! Form::number('resid', $data->agent_residual, ['min'=> 0, 'step'=> 0.01, 'max'=> MAX_AGENCY_RATE, 'required']) !!}
            <div class="popup-tip"><div>
              <p>Agency share of Residual (0 - {{ MAX_AGENCY_RATE }} %)</p>
            </div></div>
          </div>
          <label class="fa-percent"></label>
        </div>
      </div>
    </div>
*/ ?>
