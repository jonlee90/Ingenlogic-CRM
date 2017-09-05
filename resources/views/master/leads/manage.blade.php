@extends('layouts.master')

@section('title', "Lead Management | ".SITE_TITLE." Control Panel v2")

@section('wrapper_type', 'lead-management')

@section('content')
<section class="lead-control-general">
<?php
$progress_stat = 'New Lead';
if ($lead->quote_requested)
  $progress_stat = 'Quote Requested';
?>
  @include('leads.sub-progress-bar', [
    'progress_stat' => 'New Lead',
    'progress_index' => 1,
  ])
</section>

<section class="lead-control-account">
  <div class="overflow-frame">
    <div class="panel expanded">
      <div class="ctrl-header">
        Location Navigation
        <?php /*
        <div class="ctrl-actions">
          <span class="popup-base">
            <i class="md btn-ctrl-expand">expand_more</i>
            <div class="popup-tip right"><div>Toggle Panel</div></div>
          </span>
        </div>
        */ ?>
      </div>
      <div class="ctrl-content">
        <div class="nav-location">
          <select>
          @forelse ($data->locations as $loc)
            <option value="{{ enc_id($loc->id) }}">{{ $loc->name }}</option>
          @empty
            <option>There is No Location</option>
          @endforelse
          </select>
          <span class="popup-base">
            <i class="md btn-add-location">add_location</i>
            <div class="popup-tip"><div>Next Location</div></div>
          </span>
          <span class="popup-base">
            <i class="md btn-prev-location">chevron_left</i>
            <div class="popup-tip"><div>Previous Location</div></div>
          </span>
          <span class="popup-base">
            <i class="md btn-next-location">chevron_right</i>
            <div class="popup-tip"><div>Next Location</div></div>
          </span>
        </div>
        <?php /* ****** collapse and expand all ******
        <div>
          <i class="md">layers</i>
          <i class="md">layers_clear</i>
        </div>
        */ ?>
      </div>
    </div> <?php // END: control-panel location ?>

    <div class="panel ctrl-commission expanded">
      <div class="ctrl-header">
        Commission
        <div class="ctrl-actions">
          @if ($data->permissions->commission)
          <span class="popup-base">
            <i class="fa-usd btn-commission-update"></i>
            <div class="popup-tip"><div>Set Rates</div></div>
          </span>
          @endif

          <span class="popup-base">
            <i class="md btn-ctrl-expand">expand_more</i>
            <div class="popup-tip right"><div>Toggle Panel</div></div>
          </span>
        </div>
      </div>
      
      <div class="ctrl-content">
        @include('master.leads.sub-commission', [
          'lead_id' => $lead->id,
          'permissions' => $data->permissions,
          'agencies' => $data->agencies,
          'managers' => $data->managers,
        ])
      </div>
    </div> <?php // END: control-panel agency ?>

    <div class="panel">
      <div class="ctrl-header">
        Lead Summary
        <div class="ctrl-actions">
          <span class="popup-base">
            <i class="md btn-ctrl-expand">expand_more</i>
            <div class="popup-tip right"><div>Toggle Panel</div></div>
          </span>
        </div>
      </div>
      <div class="ctrl-content">        
        @if (count($data->locations) && $lead->project_open)
        <a href="{{ route('master.project.manage', ['id'=> enc_id($lead->id)]) }}">
          <button type="button" class="btn-lead-proj">Proceed to Project Management</button>
        </a>
        @endif

        <h3>store 1st</h3>
        <br>

        <p><b>Current MRC</b> <span class="float-r">$ 79.97</span></p>
        <p><b>New MRC</b> <span class="float-r">$ 20.00</span></p>
        <p><b>Monthly Saving</b> <span class="float-r">$ 59.97</span></p>
        <p><b>ETF</b> <span class="float-r">$ 1,200.00</span></p>
        <p><b>New NRC</b> <span class="float-r">$ 0.00</span></p>
        <p><b>One-Time Setup Fee</b> <span class="float-r">$ 1,200.00</span></p>
        <br>

        <table>
          <thead>
            <tr> <th>Provider</th> <th>MRC</th> <th>NRC</th> </tr>
          </thead>
          <tbody>
            <tr> <td>Broadvoice <i class="md s float-r secondary">done</i></td> <td>$ 0.00</td> <td>$ 0.00</td> </tr>
            <tr class="grayed"> <td>Service not in List</td> <td>$ 79.97</td> <td>$ 0.00</td> </tr>
          </tbody>
          <tbody>
            <tr> <td>Spectrum VoIP <i class="md s float-r secondary">done</i></td> <td>$ 20.00</td> <td>$ 0.00</td> </tr>
            <tr class="grayed"> <td>Broadview</td> <td>$ 4650.00</td> <td>$ 450.00</td> </tr>
            <tr class="grayed"> <td>AT&T</td> <td>$ 0.00</td> <td>$ 0.00</td> </tr>
          </tbody>
          <tfoot>
            <tr> <th>Total</th> <th>$ 20.00</th> <th>$ 0.00</th> </tr>
          </tfoot>
        </table>
        <div class="spacer-h"></div>
        
        <h3>store 3</h3>
        <table>
          <thead>
            <tr> <th>Provider</th> <th>MRC</th> <th>NRC</th> </tr>
          </thead>
          <tbody>
            <tr> <td colspan="3" class="err">Please add account(s)</td> </tr>
          </tbody>
          <tbody>
          </tbody>
          <tfoot>
            <tr> <th>Total</th> <th>$ 20.00</th> <th>$ 0.00</th> </tr>
          </tfoot>
        </table>
        <div class="spacer-h"></div>
        
        <h3>store 5th</h3>
        <table>
          <thead>
            <tr> <th>Provider</th> <th>MRC</th> <th>NRC</th> </tr>
          </thead>
          <tbody>
            <tr> <td colspan="3" class="err">Please add account(s)</td> </tr>
          </tbody>
          <tbody>
          </tbody>
          <tfoot>
            <tr> <th>Total</th> <th>$ 20.00</th> <th>$ 0.00</th> </tr>
          </tfoot>
        </table>
        <div class="spacer-h"></div>
        
        <h3>new loc</h3>
        <table>
          <thead>
            <tr> <th>Provider</th> <th>MRC</th> <th>NRC</th> </tr>
          </thead>
          <tbody>
            <tr> <td>Net 2 Phone <i class="md s float-r secondary">done</i></td> <td>$ 0.00</td> <td>$ 0.00</td> </tr>
          </tbody>
          <tbody>
            <tr> <td>Broadvoice <i class="md s float-r secondary">done</i></td> <td>$ 0.00</td> <td>$ 0.00</td> </tr>
          </tbody>
          <tfoot>
            <tr> <th>Total</th> <th>$ 0.00</th> <th>$ 0.00</th> </tr>
          </tfoot>
        </table>
      </div>
    </div> <?php // END: control-panel summary ?>

    <div class="panel ctrl-follower expanded">
      <div class="ctrl-header">
        Followers
        <div class="ctrl-actions">
          <span class="popup-base">
            <i class="md btn-follow-mod">edit</i>
            <div class="popup-tip"><div>Update Followers</div></div>
          </span>
          <span class="popup-base">
            <i class="md btn-ctrl-expand">expand_more</i>
            <div class="popup-tip right"><div>Toggle Panel</div></div>
          </span>
        </div>
      </div>
      <div class="ctrl-content">
        @include('master.leads.sub-follower', [
          'lead_id' => $lead->id,
          'followers' => $data->followers,
          'route_name_master_del' => 'master.lead.ajax-follower-master-delete',
          'route_name_prov_del' => 'master.lead.ajax-follower-provider-delete',
        ])
      </div>
    </div> <?php // END: control-panel follower ?>

    <div class="panel expanded">
      <div class="ctrl-header">
        Customer
        <div class="ctrl-actions">
          <span class="popup-base">
            <i class="md btn-customer-mod">edit</i>
            <div class="popup-tip"><div>Update Customer</div></div>
          </span>
          <span class="popup-base">
            <i class="md btn-ctrl-expand">expand_more</i>
            <div class="popup-tip right"><div>Toggle Panel</div></div>
          </span>
        </div>
      </div>
      <div class="ctrl-content">
        <h2>Customer Information</h2>

        <div id="selected-customer">
          <?php
          $city_state = $lead->city;
          $city_state .= ($city_state && $lead->state_code)?  ', '.$lead->state_code : $lead->state_code;
        ?>
          <div class="input-group">
            <label>Name</label>
            <div class="output">{{ $lead->cust_name }}</div>
          </div>
          <div class="input-group">
            <label>Phone Number</label>
            <div class="output">{{ format_tel($lead->tel) }}</div>
          </div>
          <div class="input-group">
            <label>Tax ID</label>
            <div class="output">{{ $lead->tax_id }}</div>
          </div>
          <div class="input-group">
            <label>Email Address</label>
            <div class="output">{{ $lead->email }}</div>
          </div>
          <div class="input-group">
            <label>Address</label>
            <div class="output">
              <p>{{ $lead->addr }}</p>
              <p>{{ $lead->addr2 }}</p>
              <p>{{ trim($city_state.' '.$lead->zip) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div> <?php // END: control-panel customer ?>

    <div class="panel ctrl-logs expanded">
      <div class="ctrl-header">
        Latest Logs
        <div class="ctrl-actions">
          <span class="popup-base">
            <i class="md btn-log-add">chat</i>
            <div class="popup-tip right"><div>Leave a Log</div></div>
          </span>
          <span class="popup-base">
            <i class="md btn-log-history">schedule</i>
            <div class="popup-tip right"><div>Log History</div></div>
          </span>
        </div>
      </div>
      <div class="ctrl-content lead-log-history">
        @include('leads.sub-log', [
          'show_detail' => 0,
          'logs' => $data->logs,
        ])
      </div>
    </div> <?php // END: control-panel log ?>

  </div><?php /* overflow-frame */ ?>
</section>

<section class="lead-frame-content">
  <div class="panel block err" style="<?=(count($data->locations) >0)? 'display: none':'' ?>">
    You do not have any location yet. Please add a location from the Control Panel
  </div>

  <div class="list-locations">
    @include('master.leads.sub-location', [
      'locations' => $data->locations,
      'open_first' => TRUE,
      'quote_requested' => $lead->quote_requested,
    ])
  </div>
</section>

<section class="data-group">
  <data data-key="enc-lead-id">{{ enc_id($lead->id) }}</data>
</section>
@endsection

@section('post_content_script')
<script src="/js/ingen.calendar.js"></script>
<script src="/js/jquery.dataTables.min.js"></script>
<script>
window.mLeadManage = function() {
  var $win = $(window);
  var resizeControlPanel = function() {
    var minHeight = $win.height() -220; // 230 = top nav (nav-mm) height + lead-control-general height + footer height + margin/padding = 50 +40 +80 +50
    var maxHeight = minHeight +90; // 90 = footer height + margin/padding = 80 +10
    var height = $('.lead-frame-content').height() - $win.scrollTop();
    if (height > maxHeight)
      height = maxHeight;
    else if (height < minHeight)
      height = minHeight;
    var $controlPanel = $('.lead-control-account');
    $controlPanel.height(height);

    var domContainer = $controlPanel.find('> .overflow-frame').get(0);
    var width = $controlPanel.width();

    // if overflowing AND browser is webkit (chrome, safari): .container-flex-v width is 410, otherwise 424 -> will hide thick scrollbar partially and will look thinner
    if (domContainer.offsetHeight < domContainer.scrollHeight && !/(safari|chrome|webkit)/.test(navigator.userAgent.toLowerCase()) )
      width += 14;
    $(domContainer).width(width);
  }
  $(document).scroll(resizeControlPanel);
  $win.resize(resizeControlPanel);
  $win.on('load',resizeControlPanel);

  var overlay = new ingenOverlay('overlay-pane');
  var encLeadId = $('.data-group data[data-key=enc-lead-id]').text();

  var $overlayPane = $('#overlay-pane');
  $overlayPane.find('.overlay-inner').append( $('<div/>', {class: 'cal-pane'}) );
  var cal = new ingenCalendar( $overlayPane.find('.cal-pane').get(0) );

  var $sectionLeadControlAccount = $('section.lead-control-account');
  

  // local functions
  var fnFillContainerChange = function(json) {
    $('#overlay-pane .container-change').html(json.html);
    $("#overlay-pane .btn-cancel").click(function() { overlay.close(); });
  };
  var updateTextareaChr = function(elem) {
    var $elem = $(elem);
    var max = parseInt($elem.prop('maxlength'));
    // if maxlength is not a number, skip the rest
    if (max >0)
      $elem.closest('.wrapper-textarea').find('.chr-left').text(elem.value.length + ' / ' + max);
  }
  /**
  * @param forward: true if navigating forward, false for backward
  */
  var navLocation = function(elem, forward) { 
    var $navLoc = $(elem).closest('.nav-location');
    var elemSelect = $navLoc.find('select').get(0);
    var $selOpts = $navLoc.find('select option');
      
    if (forward && $selOpts.length >1 && $selOpts.length -1 > elemSelect.selectedIndex)
      elemSelect.selectedIndex += 1;
    else if (!forward && $selOpts.length >1 && elemSelect.selectedIndex > 0)
      elemSelect.selectedIndex -= 1;
    else if ($selOpts.length < 1)
      return false; // cannot navigate forward or backward
      
    // scrollTop for location = offset - 120 (<main> element's padding-top = 120)
    var $locPanel = $('.location[data-id="' + elemSelect.value + '"]');
    $locPanel.addClass('expanded');
    $('html, body').animate({scrollTop: $locPanel.offset().top - 120 }, 300);
  }


  // local: location
  var fnLocationExpand = function() {
    $(this).closest('.location').toggleClass('expanded');
    resizeControlPanel();
  }
  var fnLocationMod = function() {
    overlay.setTitle('Update Location');
    overlay.openAjax({
      url: laraRoute('master.lead.overlay-loc-mod') + $(this).closest('.location').attr('data-id'),
      method: 'GET', data: {}
    });
  }
  var fnLocationFile = function() {
    overlay.setTitle('File Attachments');
    overlay.openAjax({
      url: laraRoute('lead.overlay-loc-file') + $(this).closest('.location').attr('data-id'),
      method: 'GET', data: {}
    });
  }
  var fnLocationDel = function() {
    var $containerLoc = $(this).closest('.location');
    confirmUser("Do you want to delete the Location? All related information will be Deleted and cannot be undone.",
      function() {
        reqAjax({
          url: laraRoute('master.lead.ajax-loc-delete') + $containerLoc.attr('data-id'),
          data: {_method: 'DELETE'},
          fnSuccess: function(json) {
            $containerLoc.fadeOut({complete: function() { fnReloadLead(json); }});
            toastUser('Location has been deleted.');
          },
        });
      }, "Delete Location");
  }
  // local: current account
  var fnAccountNew = function() {
    var locId = $(this).closest('.location').attr('data-id');
    overlay.setTitle('Add Current Account');
    overlay.openAjax({ url: laraRoute('master.lead.overlay-accnt-new') + locId, method: 'GET', data: {} });
  }
  var fnAccountMod = function(encAccntId) {
    var $btnProv = $('<button/>')
      .text('Service Providers')
      .click(function() {
        $('#overlay-pane .overlay-container-change-wrapper .container-change').removeClass('mrc').addClass('accnt');
      });
    var $btnMRC = $('<button/>')
      .text('Monthly Rate')
      .click(function() {
        $('#overlay-pane .overlay-container-change-wrapper .container-change').removeClass('accnt').addClass('mrc');
      });
    overlay.setTitle('Update Current Account');
    overlay.setContent(
      $('<div/>', {class: 'overlay-container-change-wrapper'})
        .append($('<div/>', {class: 'btn-group'}).append($btnProv).append(' ').append($btnMRC).append(' '))
        .append($('<div/>', {class: 'container-change accnt'}))
    );
    // by default open 'update provider'
    reqAjax({
      url: laraRoute('master.lead.overlay-accnt-mod') + encAccntId,
      method: 'GET', data: {},
      fnSuccess: fnFillContainerChange,
      fnFail: function(json) { alertUser(json.msg); overlay.close(); },
    });
    overlay.open();
  };
  var fnAccountCheck = function() {
    reqAjax({
      url: laraRoute('master.lead.ajax-accnt-toggle') + $(this).closest('.account').attr('data-accnt-id'),
      data: {},
      fnSuccess: fnReloadLead,
    });
  }
  var fnAccountProceed = function() {
    var frm = $(this).closest('form');
    confirmUser("Do you want to add the Account to Project Management?",
      function() {
        submitFrm(frm);
      }, "Proceed to Project Management");
  }
  var fnAccountDel = function() {
    var $containerAccnt = $(this).closest('.account');
    confirmUser("Do you want to delete the account? All associated products with the account will be Deleted and cannot be undone.",
      function() {
        reqAjax({
          url: laraRoute('master.lead.ajax-accnt-delete') + $containerAccnt.attr('data-accnt-id'),
          data: {_method: 'DELETE'},
          fnSuccess: function(json) {
            $containerAccnt.fadeOut({complete: function() { fnReloadLead(json); } });
            toastUser('Current Account has been deleted.');
          }
        });
      }, "Delete Account");
  }
  // local: quotes
  var fnQuoteNew = function() {
    var locId = $(this).closest('.location').attr('data-id');
    overlay.setTitle('Add Quote');
    overlay.openAjax({ url: laraRoute('master.lead.overlay-quote-new') + locId, method: 'GET', data: {} });
  }
  var fnQuoteCheck = function() {
    reqAjax({
      url: laraRoute('master.lead.ajax-quote-toggle') + $(this).closest('.account').attr('data-quote-id'),
      data: {},
      fnSuccess: fnReloadLead,
    });
  }
  var fnQuoteSign = function() {
    var frm = $(this).closest('form');
    confirmUser("Do you want to mark the Quote as Signed and proceed to Project Management?",
      function() {
        submitFrm(frm);
      }, "Mark Quote Signed");
  }
  var fnQuoteDel = function() {
    var $containerAccnt = $(this).closest('.account');
    confirmUser("Do you want to delete the quote? All associated products with the quote will be Deleted and cannot be undone.",
      function() {
        reqAjax({
          url: laraRoute('master.lead.ajax-quote-delete') + $containerAccnt.attr('data-quote-id'),
          data: {_method: 'DELETE'},
          fnSuccess: function(json) {
            $containerAccnt.fadeOut({complete: function() { fnReloadLead(json); } });
            toastUser('Quote has been deleted.');
          }
        });
      }, "Delete Quote");
  }
  var fnQuoteMod = function(encQuoteId) {
    var $btnProv = $('<button/>')
      .text('Service Providers')
      .click(function() {
        $('#overlay-pane .overlay-container-change-wrapper .container-change').removeClass('mrc').removeClass('nrc').addClass('quote');
      });
    var $btnMRC = $('<button/>')
      .text('Monthly Rate')
      .click(function() {
        $('#overlay-pane .overlay-container-change-wrapper .container-change').removeClass('quote').removeClass('nrc').addClass('mrc');
      });
    var $btnNRC = $('<button/>')
      .text('Non-Recurring')
      .click(function() {
        $('#overlay-pane .overlay-container-change-wrapper .container-change').removeClass('quote').removeClass('mrc').addClass('nrc');
      });
    overlay.setTitle('Update Quote');
    overlay.setContent(
      $('<div/>', {class: 'overlay-container-change-wrapper'})
        .append($('<div/>', {class: 'btn-group'}).append($btnProv).append(' ').append($btnMRC).append(' ').append($btnNRC))
        .append($('<div/>', {class: 'container-change quote'}))
    );
    // by default open 'mrc products'
    reqAjax({
      url: laraRoute('master.lead.overlay-quote-mod') + encQuoteId,
      method: 'GET', data: {},
      fnSuccess: fnFillContainerChange,
      fnFail: function(json) { alertUser(json.msg); overlay.close(); },
    });
    overlay.open();
  };
  var fnCommissionEvents = function() {
    $sectionLeadControlAccount.find('.btn-assign-agency').click(function() {
      overlay.setTitle('Assign New Agency');
      overlay.openAjax({ url: laraRoute('master.lead.overlay-agency-assign') + encLeadId, method: 'GET', data: {} });
    });
    $sectionLeadControlAccount.find('.frm-agency-remove').submit(function(e) { e.preventDefault(); });
    $sectionLeadControlAccount.find('.btn-agency-del').click(function() {
      var frm = $(this).closest('form.frm-agency-remove').get(0);
      confirmUser("<p>Do you want to remove the Agency?</p><p>Any Commission Rates saved for the Lead x Agency association would be removed.</p>",
        function() {
          submitFrm(frm);
        }, "Remove Agency");
    });
    $sectionLeadControlAccount.find('.btn-assign-manager').click(function() {
      overlay.setTitle('Assign New Manager');
      overlay.openAjax({ url: laraRoute('master.lead.overlay-manager-assign') + encLeadId, method: 'GET', data: {} });
    });
    $sectionLeadControlAccount.find('.frm-manager-action').submit(function(e) { e.preventDefault(); });
    $sectionLeadControlAccount.find('.btn-manager-primary').click(function() {
      var frm = $(this).closest('form.frm-manager-action').get(0);
      confirmUser("<p>Do you want to set this Manager as a Primary Manager?</p>",
        function() {
          submitFrm(frm);
        }, "Change Primary Manager");
    });
    $sectionLeadControlAccount.find('.btn-manager-del').click(function() {
      var frm = $(this).closest('form.frm-manager-action').get(0);
      confirmUser("<p>Do you want to remove the Manager?</p><p>Any Commission Rates saved for the Lead x Manager association would be removed.</p>",
        function() {
          submitFrm(frm);
        }, "Remove Manager");
    });
  }
  var fnFollowerDel = function() {
    var $frm = $(this).closest('form');
    var $li = $(this).closest('li');
    confirmUser("Do you want to remove the Follower?",
      function() {
        reqAjax({
          url: $frm.prop('action'), data: {_method: 'DELETE'},
          fnSuccess: function(json) {
            $li.fadeOut({complete: function() { fnReloadLead(json); }});
            toastUser('Follower has been removed.');
          }
        });
      }, "Remove Follower");
  };
  var fnLogCorrect = function() {
    overlay.setTitle('Log Correction');
    overlay.openAjax({
      url: laraRoute('master.lead.overlay-log-mod') + $(this).closest('.log-action').attr('data-id'),
      method: 'GET', data: {}
    });
  }
  
  var fnReloadLeadHandlers = function() {
    fnCommissionEvents();
    $sectionLeadControlAccount.find('.btn-follow-del').click(fnFollowerDel);
    $sectionLeadControlAccount.find('.btn-log-mod').click(fnLogCorrect);

    // reload location, accounts, quotes handlers
    var $sectionLeadContent = $('section.lead-frame-content');
    $sectionLeadContent.find('.location .btn-loc-expand').click(fnLocationExpand);
    $sectionLeadContent.find('.location .btn-loc-mod').click(fnLocationMod);
    $sectionLeadContent.find('.location .btn-loc-file').click(fnLocationFile);
    $sectionLeadContent.find('.location .btn-del-location').click(fnLocationDel);

    $sectionLeadContent.find('.list-account.curr .account .btn-accnt-checker').click(fnAccountCheck);
    $sectionLeadContent.find('.account .btn-accnt-curr-mod').click(function() { fnAccountMod($(this).closest('.account').attr('data-accnt-id')); });
    $sectionLeadContent.find('.location .btn-accnt-curr-del').click(fnAccountDel);
    $sectionLeadContent.find('.location .btn-accnt-curr-add').click(fnAccountNew);
    $sectionLeadContent.find('.location form.accnt-proceed .btn-accnt-curr-proceed').click(fnAccountProceed);

    $sectionLeadContent.find('.list-account.quote .account .btn-accnt-checker').click(fnQuoteCheck);
    $sectionLeadContent.find('.account .btn-quote-mod').click(function() { fnQuoteMod($(this).closest('.account').attr('data-quote-id')); });
    $sectionLeadContent.find('.location .btn-quote-del').click(fnQuoteDel);
    $sectionLeadContent.find('.location .btn-quote-add').click(fnQuoteNew);
    $sectionLeadContent.find('.location form.quote-sign .btn-quote-sign').click(fnQuoteSign);
  };
  /**
  * @param json: {
  *   locId (optional): location ID encoded - if exists, expand the location and scroll 
  * }
  */
  var fnReloadLead = function(json) {
    var $locSelect = $sectionLeadControlAccount.find('.nav-location select');

    $sectionLeadControlAccount.find('.ctrl-commission .ctrl-content').html('').html(json.commissionHTML);
    $sectionLeadControlAccount.find('#selected-customer').html('').html(json.custHTML);
    $sectionLeadControlAccount.find('.ctrl-follower .ctrl-content').html('').html(json.followerHTML);
    $locSelect.html('').html(json.locOptHTML);
    $sectionLeadControlAccount.find('.ctrl-logs .ctrl-content ul').html('').html(json.logHTML);

    var $locList = $('section.lead-frame-content .list-locations');
    $locList.html('').html(json.locHTML);
    if (json.locHTML !='')
      $('section.lead-frame-content > .block.err').fadeOut();
    else
      $('section.lead-frame-content > .block.err').fadeIn();
    fnReloadLeadHandlers();
    
    // check for location ID parameter, if NOT found, scroll to first location
    var $locs = $locList.find('.location');
    if ($locs.length < 1)
      return false;
      
    var $targetLoc = $locs.eq(0);
    if ($locs.length > 1 && json != undefined && json.locId != undefined && json.locId != null) {
      $locSelect.val(json.locId);
      $targetLoc = $locs.filter('[data-id="' + json.locId + '"]');
    }
    if ($targetLoc) 
      $targetLoc.addClass('expanded');
      
    // whenever content is reloaded, resize control panel accordingly
    resizeControlPanel();
  };
  
  // customer-update
  window.moCustomerUpdate = function() {
    $('#overlay-pane form.frm-update').submit(function(e) {
      e.preventDefault();
      reqAjax({
        url: this.action,  data: $(this).serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          overlay.close();
          toastUser('The Lead Customer has been updated.');
        },
      });
    });
  }
  // location-add
  window.moLocationNew = function() {
    $('#overlay-pane .frm-add').submit(function(e) {
      e.preventDefault();
      var $frm = $(this);

      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          overlay.close();
          toastUser('New Location has been added to the Lead.');
        },
      });
    });
  }
  // location-update
  window.moLocationMod = function() {
    $('#overlay-pane .frm-update').submit(function(e) {
      e.preventDefault();
      var $frm = $(this);

      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          overlay.close();
          toastUser('Location has been updated.');
        },
      });
    });
  }
  // location-file-attach/delete
  window.moLocationFiles = function() {
    var $frmFile = $('#overlay-pane .frm-file');
    $frmFile.find('input[type=file]').change(function () {
      var $wrapper = $(this).closest('.file-wrapper');
      var $preview = $wrapper.find('.preview');
      
      var clearFile = function(msg) {
        if (msg != undefined && msg !='')
          alertUser(msg);

        $frmFile.get(0).reset();
        $wrapper.find('label.file-label').addClass('empty');
        $preview.html("");
        return false;
      };
      
      if (this.files.length >0) {
        // validate: file size must be greater than 0 byte, 10 MB limit
        var size_limit = 10; // MB
        var total_size = parseInt( $('#overlay-pane .lead-loc-list-files').attr('data-size') );
        total_size = (total_size >0)?  total_size : 0;

        // validate: valid image types
        for (var i=0; i<this.files.length; i++) {
          var f = this.files[i];
          
          if (f.size <= 0)
            return clearFile('File size must be greater than 0 byte.');

          total_size += f.size;
          if (total_size > size_limit  *1048576)
            return clearFile("Total File size is limited to " + size_limit + " MB.");
        }
        // add preview of uploaded files
        $wrapper.find('label.file-label').removeClass('empty');

        var previewHTML = '';
        for (var i=0; i<this.files.length; i++)
          previewHTML += '<p>' + this.files[i].name + '</p>';
        $preview.hide().html(previewHTML).fadeIn();
      } else
        return clearFile();
    });
    $frmFile.submit(function(e) {
      e.preventDefault();
      if ($(this).find('input[type=file]').get(0).files.length <1) {
        alertUser("Please select at least 1 file to upload.");
      } else
        submitFrm(this);
    });
    $('#overlay-pane .lead-loc-list-files .btn-del-file').click(function() {
      var $frm = $(this).closest('form');

      confirmUser("<p>Do you want to Delete the attached File?</p>",
        function() {
          reqAjax({
            url: $frm.prop('action'), data: $frm.serializeArray(),
            fnSuccess: function(json) {
              fnReloadLead(json);
              toastUser('Attached File has been removed.');
              $frm.fadeOut({ complete: function() { $(this).closest('li').remove(); }});
            },
          });
        }, "Delete Attached File");
    });
  } // END moLocationFiles()

  // current-accont-create
  window.moAccountNew = function() {
    var $elemDateEnd = $overlayPane.find('.frm-add .cal-date-end');
    $elemDateEnd.on('focus click', function() {
      cal.clickDraw(this);
    });
    $overlayPane.find('.wrapper-textarea textarea').on('input blur', function() { updateTextareaChr(this); });
  
    $overlayPane.find('.frm-add').submit(function(e) {
      e.preventDefault();
      var $frm = $(this);
      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          fnAccountMod(json.accntId);
          $overlayPane.find('.overlay-container-change-wrapper .container-change').removeClass('accnt').addClass('mrc');
          toastUser('Current Account has been added to the Lead.');
        },
      });
    });
  }
  // current-accont-update: provider, monthly rate
  window.moAccountMod = function() {
    // provider section
    $overlayPane.find('.frm-accnt .cal-date-end').on('focus click', function() {
      cal.clickDraw(this);
    });
    $overlayPane.find('.wrapper-textarea textarea').on('input blur', function() { updateTextareaChr(this); });

    $overlayPane.find('.frm-accnt').submit(function(e) {
      e.preventDefault();
      var $frm = $(this);

      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          overlay.close();
          toastUser('Current Account has been updated.');
        },
      });
    });
    // product section
    $overlayPane.find('.btn-del-prod').click(function() {
      $(this).closest('tr').fadeOut({duration: 200, complete: function() { 
        $(this).remove();
        if ($overlayPane.find('.tbl-lead-prod-list.mrc tbody tr').length <= 0)
          $overlayPane.find('form.frm-mrc input[type=submit]').prop('disabled', true);
      }});
    });
    $overlayPane.find('input[name="price[]"], input[name="qty[]"]').on('input blur', function() {
      var $tr = $(this).closest('tr');
      var price = parseFloat( $tr.find('input[name="price[]"]').val().replace(/,/g,'') );
      var qty = parseFloat( $tr.find('input[name="qty[]"]').val() );
      var subtotal = ($.isNumeric(price) && $.isNumeric(qty) )?  (price * qty).toFixed(2) : '0.00';
      $tr.find('.subtotal').text(subtotal);
    });
    $overlayPane.find('.btn-prod-add').click(function() {
      $overlayPane.find('.tbl-lead-prod-list.mrc tbody').append(
        $overlayPane.find('.tbl-lead-row-src tr:first').clone(true)
      );
      $overlayPane.find('form.frm-mrc input[type=submit]').prop('disabled', false);
    });
    $overlayPane.find('.frm-mrc').submit(function(e) {
      e.preventDefault();

      var $frm = $(this);
      var $tr = $frm.find('.tbl-lead-prod-list tbody tr');
      if ($tr.length <= 0) {
        alertUser("At least one product is required to continue.");
        return false;
      }
      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          overlay.close();
          toastUser('Current Account Products have been updated.');
        },
      });
    });
  }
  // quote-create
  window.moQuoteNew = function() {
    var $elemDateEnd = $('#overlay-pane .frm-add .cal-date-end');
    $elemDateEnd.on('focus click', function() {
      cal.clickDraw(this);
    });
    $('#tbl-lead-provider-available').DataTable({
      autoWidth: true,
      ordering: false,
      paging: false,
      scrollY: '100%',
      scrollCollapse: true,
      language: { search: '', searchPlaceholder: 'Search Providers'},
      dom: '<ft>',
    });
    $('.btn-prov-select').click(function() {
      var provId = $(this).attr('data-id');
      var $cells = $(this).closest('tr').find('td');

      var $inputPane = $(this).closest('form').find('.form-input');
      $inputPane.find('input[name=prov_id]').val(provId);
      $inputPane.find('.output.prov-name').text($cells.eq(1).text());
    });
    $('#overlay-pane .frm-add').submit(function(e) {
      e.preventDefault();

      var $frm = $(this);
      if ($frm.find('input[name=prov_id]').val() =='') {
        alertUser("Please select a Service Provider.");
        return false;
      }
      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          fnQuoteMod(json.quoteId);
          $overlayPane.find('.overlay-container-change-wrapper .container-change').removeClass('quote').removeClass('nrc').addClass('mrc');
          toastUser('Quote has been added to the Lead.');
        },
      });
    });
  }
  // quote-update: quote-info, mrc, nrc
  window.moQuoteMod = function() {
    // quote-info section
    $overlayPane.find('.form-input input[name=prov_id]').remove();
    $overlayPane.find('.frm-quote').submit(function(e) {
      e.preventDefault();
      
      var $frm = $(this);
      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          overlay.close();
          toastUser('Quote has been updated.');
        },
      });
    });
    // quote x product section (MRC and NRC)
    $('#tbl-lead-product-available').DataTable({
      autoWidth: false,
      ordering: false,
      paging: false,
      scrollY: '160px',
      scrollCollapse: true,
      language: { search: '', searchPlaceholder: 'Search Products'},
      dom: '<ft>',
    });
    $overlayPane.find('.btn-del-prod').click(function() {
      $(this).closest('tr').fadeOut({duration: 200, complete: function() {
        var $frmProd = $(this).closest('form');
        $(this).remove();
        if ($frmProd.hasClass('frm-mrc') && $frmProd.find('.tbl-lead-prod-list tbody tr').length <= 0)
          $frmProd.find('input[type=submit], .btn-save-close').prop('disabled', true);
      }});
    });
    $overlayPane.find('input[name="price[]"], input[name="qty[]"]').on('input blur', function() {
      var $tr = $(this).closest('tr');
      var price = parseFloat( $tr.find('input[name="price[]"]').val().replace(/,/g,'') );
      var qty = parseFloat( $tr.find('input[name="qty[]"]').val() );
      var subtotal = ($.isNumeric(price) && $.isNumeric(qty) )?  (price * qty).toFixed(2) : '0.00';
      $tr.find('.subtotal').text(subtotal);
    });
    $overlayPane.find('.btn-prod-add').click(function() {
      var prodId = $(this).attr('data-id');
      var $tr = $(this).closest('tr');
      var price = parseFloat( $tr.find('.prod-price').text().replace(/,/g,'') );
      price = (price >0)?  price : 0;

      var $newRow = $overlayPane.find('.tbl-lead-row-src tr:first').clone(true); // copy with events
      $newRow.find('input[name="prod_id[]"]').val(prodId);
      $newRow.find('.prod-service').text( $tr.find('.prod-service').text() );
      $newRow.find('.prod-name').text( $tr.find('.prod-name').text() );
      $newRow.find('input[name="price[]"]').val(price);
      $newRow.find('.subtotal').text(price.toFixed(2));

      var prodSelector = ($overlayPane.find('.container-change').hasClass('mrc'))? 'mrc':'nrc';
      if (prodSelector == 'mrc') {
        var spiffVal = parseFloat( $tr.find('.prod-spiff').text().replace(/,/g,'') );
        spiffVal = (spiffVal >0)?  spiffVal : 0;
        var residVal = parseFloat( $tr.find('.prod-resid').text().replace(/,/g,'') );
        residVal = (residVal >0)?  residVal : 0;
        
        $newRow.find('input[name="spiff[]"]').val(spiffVal);
        $newRow.find('input[name="resid[]"]').val(residVal);
      } else {
        $newRow.find('td').eq(4).text('-');
        $newRow.find('td').eq(5).text('-');
      }
      $overlayPane.find('.tbl-lead-prod-list.' + prodSelector + ' tbody').append($newRow);
      $overlayPane.find('form.frm-' + prodSelector).find('input[type=submit], .btn-save-close').prop('disabled', false);
    });
    var fnQuoteProdSubmit = function($frm, closeOverlay) {
      var isMrc = ($frm.hasClass('frm-mrc'));
      if (isMrc && $frm.find('.tbl-lead-prod-list tbody tr').length <= 0) {
        alertUser("At least one product is required to continue.");
        return false;
      }
      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          if (closeOverlay)
            overlay.close();
          toastUser('Quote Products have been updated.');
        },
      });
    }
    $overlayPane.find('.frm-mrc, .frm-nrc').submit(function(e) {
      e.preventDefault();
      fnQuoteProdSubmit($(this), false);
    });
    $overlayPane.find('.btn-save-close').click(function() {
      fnQuoteProdSubmit($(this).closest('form'), true);
    });
  } // END: oMlQ2() -> quote-update overlay script
  // agency-change
  window.moAgencyChange = function() {
    $('#tbl-lead-agency-available').DataTable({
      autoWidth: false,
      ordering: false,
      paging: false,
      scrollY: '100%',
      scrollCollapse: true,
      language: { search: '', searchPlaceholder: 'Search Agency'},
      dom: '<ft>',
    });
    $('.btn-agency-select').click(function() {
      var agencyId = $(this).attr('data-id');
      var $frm = $overlayPane.find('form.frm-agency');

      confirmUser("<p>Do you want to assign the selected Agency?</p>",
        function() {
          $frm.find('input[name=agency_id]').val(agencyId);
          submitFrm($frm.get(0));
        }, "Assign Agency");
    });
  }
  // manager-change
  window.moManagerChange = function() {
    $('#tbl-lead-manager-available').DataTable({
      autoWidth: false,
      ordering: false,
      paging: false,
      scrollY: '100%',
      scrollCollapse: true,
      language: { search: '', searchPlaceholder: 'Search Manager'},
      dom: '<ft>',
    });
    $('.btn-manager-select').click(function() {
      var managerId = $(this).attr('data-id');
      var $frm = $overlayPane.find('form.frm-manager');

      confirmUser("<p>Do you want to assign the selected Manager?</p>",
        function() {
          $frm.find('input[name=manager_id]').val(managerId);
          submitFrm($frm.get(0));
        }, "Assign Manager");
    });
  }
  // follower-update
  window.moFollower = function() {
    var fnDelRow = function() { $(this).closest('tr').fadeOut({complete: function() { $(this).remove(); }}); };
    
    $overlayPane.find('.btn-row-add').click(function() {
      var $frmFollower = $overlayPane.find('form.frm-follower');
      var $newRow = $(this).clone().removeClass('btn-row-add');
      
      // follower cannot have duplicate contacts
      if ($(this).closest('.container-change').hasClass('master'))
        var dupe = ($frmFollower.find('tbody input[name="user_id[]"][value="' + $newRow.find('input[name="user_id[]"]').val() + '"]').length >0);
      else
        var dupe = ($frmFollower.find('tbody input[name="contact_id[]"][value="' + $newRow.find('input[name="contact_id[]"]').val() +'"]').length >0);
      if (dupe) {
        alertUser("Followers cannot have duplicate contacts.");
        return false;
      }
      
      var $btnDel = $('<i/>', {class: 'md s btn-del-row', title: 'Remove Follower'})
        .text('close')
        .click(fnDelRow);
      $newRow.find('td:first').html('').append($btnDel);
      
      $overlayPane.find('.tbl-lead-follower-list tbody').append($newRow);
    });
    $overlayPane.find('.btn-del-row').click(fnDelRow);

    $overlayPane.find('#tbl-lead-master-available').DataTable({
      autoWidth: false,
      ordering: false,
      paging: false,
      scrollY: '180px',
      scrollCollapse: true,
      language: { search: '', searchPlaceholder: 'Search Users'},
      dom: '<ft>',
    });
    $overlayPane.find('#tbl-lead-contact-available').DataTable({
      autoWidth: false,
      ordering: false,
      paging: false,
      scrollY: '180px',
      scrollCollapse: true,
      language: { search: '', searchPlaceholder: 'Search Contacts'},
      dom: '<ft>',
    });
    $overlayPane.find('.frm-follower').submit(function(e) {
      e.preventDefault();

      var $frm = $(this);
      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          overlay.close();
          toastUser('List of Followers has been updated.');
        },
      });
    });
  }
  // log-new AND mod (=add new, corrected log)
  window.aoLogAdd = function() {
    $overlayPane.find('.wrapper-textarea textarea').on('input blur', function() { updateTextareaChr(this); });
    $overlayPane.find('.frm-log').submit(function(e) {
      e.preventDefault();
      var $frm = $(this);
      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          overlay.close();
          toastUser('Log has been recorded.');
        },
      });
    });
  }
  // overlay open: log-history
  window.aoLogHistory = function() {
    $overlayPane.find('.btn-log-mod').click(fnLogCorrect);
  }
  // END: window.(function name)



  // ***** control panel: location navigation *****
  $sectionLeadControlAccount.find('.btn-ctrl-expand').click(function() { $(this).closest('.panel').toggleClass('expanded') });

  $sectionLeadControlAccount.find('.btn-add-location').click(function() {
    overlay.setTitle('Add New Location');
    overlay.openAjax({ url: laraRoute('master.lead.overlay-loc-new') + encLeadId, method: 'GET', data: {} });
  });
  $sectionLeadControlAccount.find('.nav-location select').change(function() {
    var $locPanel = $('.location[data-id="' + this.value + '"]');
    $locPanel.addClass('expanded');
    $('html, body').animate({scrollTop: $locPanel.offset().top - 120 }, 300);
  });
  $sectionLeadControlAccount.find('.nav-location .btn-prev-location').click(function() { navLocation(this, false); });
  $sectionLeadControlAccount.find('.nav-location .btn-next-location').click(function() { navLocation(this, true); });

  // ***** control panel: commission, followers, customer *****
  $sectionLeadControlAccount.find('.btn-commission-update').click(function() {
    overlay.setTitle('Set Commission Rates');
    overlay.openAjax({
      url: laraRoute('master.lead.overlay-commission') + encLeadId,
      method: 'GET', data: {}
    });
  });
  fnCommissionEvents();
  
  $sectionLeadControlAccount.find('.btn-follow-mod').click(function() {
    var $btnMaster = $('<button/>')
      .text('Master Agents')
      .click(function() {
        $('#overlay-pane .overlay-container-change-wrapper .container-change').removeClass('provider').addClass('master');
      });
    var $btnProv = $('<button/>')
      .text('Service Providers')
      .click(function() {
        $('#overlay-pane .overlay-container-change-wrapper .container-change').removeClass('master').addClass('provider');
      });
    overlay.setTitle('Update Follower');
    overlay.setContent(
      $('<div/>', {class: 'overlay-container-change-wrapper'})
        .append($('<div/>', {class: 'btn-group'}).append($btnMaster).append(' ').append($btnProv))
        .append($('<div/>', {class: 'container-change master'}))
    );
    reqAjax({
      url: laraRoute('master.lead.overlay-follower-mod') + encLeadId,
      method: 'GET', data: {},
      fnSuccess: fnFillContainerChange,
      fnFail: function(json) { alertUser(json.msg); overlay.close(); },
    }); // END: reqAjax
    overlay.open();
  });
  $sectionLeadControlAccount.find('.btn-customer-mod').click(function() {
    overlay.setTitle('Update Customer Information');
    overlay.openAjax({
      url: laraRoute('master.lead.overlay-customer-mod') + encLeadId,
      method: 'GET', data: {}
    });
  });

  // ***** control panel: lead-logs *****
  $sectionLeadControlAccount.find('.btn-log-add').click(function() {
    overlay.setTitle('Leave a Log');
    overlay.openAjax({
      url: laraRoute('master.lead.overlay-log-new') + encLeadId,
      method: 'GET', data: {}
    });
  });
  $sectionLeadControlAccount.find('.btn-log-mod').click(fnLogCorrect);
  $sectionLeadControlAccount.find('.btn-log-history').click(function() {
    overlay.setTitle('Log History');
    overlay.openAjax({
      url: laraRoute('master.lead.overlay-log-history') + encLeadId,
      method: 'GET', data: {}
    });
  });

  // ***** main content: location, acccounts, and quotes *****
  fnReloadLeadHandlers();
} // END: mLeadManage()
mLeadManage();
</script>
@endsection