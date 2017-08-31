<?php
/**
* required vars
* @param $agencies: list of assigned agencies
* @param $managers: list of assigned channel-managers
* @param $locations: lead x location x quotes x commission rates
*/

$n_cells = count($agencies) + count($managers);
$iteration_quote = 1;
?>
<table class="tbl-lead-commission">
  <thead>
    <tr class="title">
      <th></th>
      @forelse ($agencies as $agency)
      <th colspan="2">{{ $agency->name }}</th>
      @empty
      @endforelse
      @forelse ($managers as $manager)
      <th colspan="2">{{ trim($manager->fname.' '.$manager->lname) }}</th>
      @empty
      @endforelse
    </tr>
    <tr>
      <th></th>
      @for($i=0; $i<$n_cells; $i++)
      <th>Spiff</th><th>Residual</th>
      @endfor
    </tr>
  </thead>

  <tbody class="tbody-fill">
    <tr>
      <td>Fill Values</td>
      @forelse ($agencies as $agency)
      <td>
        <input data-id="{{ enc_id($agency->id) }}" type="number" min="0" step="0.01" max="100" value="{{ $agency->spiff }}" class="fill-val agency spiff">
        <label>%</label>
      </td>
      <td>
        <input data-id="{{ enc_id($agency->id) }}" type="number" min="0" step="0.01" max="100" value="{{ $agency->residual }}" class="fill-val agency resid">
        <label>%</label>
      </td>
      @empty
      @endforelse
      @forelse ($managers as $manager)
      <td>
        <input data-id="{{ enc_id($manager->id) }}" type="number" min="0" step="0.01" max="100" value="{{ $manager->spiff }}" class="fill-val manager spiff">
        <label>%</label>
      </td>
      <td>
        <input data-id="{{ enc_id($manager->id) }}" type="number" min="0" step="0.01" max="100" value="{{ $manager->residual }}" class="fill-val manager resid">
        <label>%</label>
      </td>
      @empty
      @endforelse
    </tr>
    <tr class="btns">
      <td colspan="{{ ($n_cells *2 +1) }}">
        <span class="popup-base">
          <button type="button" class="btn-commission-fill">fill all</button>
          <div class="popup-tip"><div>Apply Fill Values to All Quotes</div></div>
        </span>
        <span class="popup-base">
          <button type="button" class="btn-commission-reset">reset fill values</button>
          <div class="popup-tip"><div>Reset Fill Values back to Default</div></div>
        </span>
      </td>
    </tr>
  </tbody>

  <?php
  $iteration_quote = 1;
  ?>
  @foreach ($locations as $loc)
  <tbody>
    <tr class="title">
      <th>{{ $loc->name }}</th>
      @for($i=0; $i<$n_cells; $i++)
      <th colspan="2"></th>
      @endfor
    </tr>
    @foreach ($loc->quotes as $quote)
    <tr>
      <td>{{ ($iteration_quote ++).'. '.$quote->prov_name }}</td>
      @forelse ($quote->rate_agencies as $rate)
      <td>
        {!! Form::number('agency_rate['.enc_id($rate->agency_id).']['.enc_id($quote->id).'][spiff]', $rate->spiff, [
          'min'=> 0, 'step'=> 0.01, 'max'=> 100, 'class'=> 'agency spiff'
        ]) !!}
        <label>%</label>
      </td>
      <td>
        {!! Form::number('agency_rate['.enc_id($rate->agency_id).']['.enc_id($quote->id).'][resid]', $rate->residual, [
          'min'=> 0, 'step'=> 0.01, 'max'=> 100, 'class'=> 'agency resid'
        ]) !!}
        <label>%</label>
      </td>
      @empty
      @endforelse
      @forelse ($quote->rate_managers as $rate)
      <td>
        {!! Form::number('manager_rate['.enc_id($rate->user_id).']['.enc_id($quote->id).'][spiff]', $rate->spiff, [
          'min'=> 0, 'step'=> 0.01, 'max'=> 100, 'class'=> 'manager spiff'
        ]) !!}
        <label>%</label>
      </td>
      <td>
        {!! Form::number('manager_rate['.enc_id($rate->user_id).']['.enc_id($quote->id).'][resid]', $rate->residual, [
          'min'=> 0, 'step'=> 0.01, 'max'=> 100, 'class'=> 'manager resid'
        ]) !!}
        <label>%</label>
      </td>
      @empty
      @endforelse
    </tr>
    @endforeach
  </tbody>
  @endforeach
</table>
<script>
window.moCommissionUpdate = function() {
  var $frm = $('#overlay-pane .frm-commission');
  var fnRateTotal = function($inputs) {
    var total = 0;
    for (var i =0; i < $inputs.length; i++) {
      var rate = parseFloat($inputs.get(i).value);
      if (rate >=0)
        total += rate;
      else {
        alertUser('Please enter a valid numeric Rate.'); $inputs.get(i).focus();
        return false;
      }
    }
    return total;
  }
  $frm.find('.btn-commission-fill').click(function() {
    var total = 0;
    total = fnRateTotal( $frm.find('.fill-val.spiff') );
    if (total === false)
      return false;
    if (Math.ceil(total) > 100) {
      alertUser('The total sum of Spiff cannot exceed 100%');
      return false;
    }
    total = fnRateTotal( $frm.find('.fill-val.resid') );
    if (total === false)
      return false;
    if (Math.ceil(total) > 100) {
      alertUser('The total sum of Residual cannot exceed 100%');
      return false;
    }
    
    $frm.find('.tbody-fill .agency.spiff').each(function() {
      $frm.find('input[name^="agency_rate[' + $(this).attr('data-id') + ']["].spiff').val(this.value);
    });
    $frm.find('.tbody-fill .agency.resid').each(function() {
      $frm.find('input[name^="agency_rate[' + $(this).attr('data-id') + ']["].resid').val(this.value);
    });
    $frm.find('.tbody-fill .manager.spiff').each(function() {
      $frm.find('input[name^="manager_rate[' + $(this).attr('data-id') + ']["].spiff').val(this.value);
    });
    $frm.find('.tbody-fill .manager.resid').each(function() {
      $frm.find('input[name^="manager_rate[' + $(this).attr('data-id') + ']["].resid').val(this.value);
    });
  })
  $frm.find('.btn-commission-reset').click(function() {
    $frm.find('.tbody-fill .fill-val').each(function() { this.value = this.defaultValue; });
  })
  $frm.submit(function(e) {
    e.preventDefault();

    var $trs = $frm.find('tbody:not(.tbody-fill) tr:not(.title)');
    for (var i =0; i< $trs.length; i++) {
      var $inputs = $trs.eq(i).find('input.spiff');
      var total = fnRateTotal($inputs);
      if (total === false)
        return false;
      if (Math.ceil(total) > 100) {
        alertUser('The total sum of Spiff cannot exceed 100%');
        $inputs.get(0).focus();
        return false;
      }
      $inputs = $trs.eq(i).find('input.resid');
      total = fnRateTotal($inputs);
      if (total === false)
        return false;
      if (Math.ceil(total) > 100) {
        alertUser('The total sum of Residual cannot exceed 100%');
        $inputs.get(0).focus();
        return false;
      }
    };
    var frm = this;
    confirmUser("Do you want to update Commission share for All assigned agency(s) and manager(s) ?",
      function() {
        submitFrm(frm);
      }, "Update Commission");
  });
}
</script>
