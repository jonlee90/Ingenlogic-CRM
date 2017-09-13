<?php
/**
* required vars
* @param $lead_id: lead ID
* @param $frm_url: URL to submit form
* @param $followers: available users (follower)
* @param $alerted_followers: currently saved lead x follower-follower objects
*/

?>
<style>
  table tr:nth-child(2n) td {
    background: none;
}
</style>
<div class="overlay-form overlay-lead-product">
  <h2>Alerts</h2>

  <div class="lead-list-available follower">
    <div class='alerts-container'>
      @if(count($alerts) > 0)
        @foreach ($alerts as $alert)
          <div class='alert-container' style='background-color: {{ $alert->is_read == 0 ? "rgba(248, 246, 149, .6)" : "" }}'>
            <div class='alert-link-container'>
              <div class='outer-link'>
                <div class='inner-link'>
                  <a href='{{ route("alert.manage", ["id" => $alert->alert_type_id, "type" => $alert->alert_type, "alert" => $alert->id]) }}'><i class="fa-external-link btn-go-lead" title="Go to Lead"></i></a>
                </div>
              </div>
            </div>
            <div class='alert-content-container'>
              <div class="a_date">Date: {{ $alert->date_added }}</div>
              <div>
                <div class="a_name">Sent By: {{ $alert->name }}</div>
              </div>
              <div class="a_msg">{!! $alert->alert_msg !!}</div>
            </div>
          </div>
        @endforeach
      @else 
        <p class="not-found">* 0 Alerts found.</p>
      @endif
    </div>
  </div>
</div>

