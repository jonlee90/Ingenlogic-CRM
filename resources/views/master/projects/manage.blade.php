@extends('layouts.master')

@section('title', "Lead Management | ".SITE_TITLE." Control Panel v2")

@section('wrapper_type', 'lead-management')

@section('content')
<section class="lead-control-general">
  @include('leads.sub-progress-bar', [
    'progress_stat' => 'Project Opened',
    'progress_index' => 2,
  ])
</section>

<section class="lead-control-account">
  <div class="overflow-frame">
    <div class="panel expanded">
      <div class="ctrl-header">
        Location Navigation
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

    <div class="panel expanded">
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
        <div>
          <a href="{{ route('lead.manage', ['id'=> enc_id($lead->id)]) }}"><button type="button" class="btn-lead-proj">Return to Lead Management</button></a>
        </div>
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
          'route_name_master_del' => 'master.project.ajax-follower-master-delete',
          'route_name_prov_del' => 'master.project.ajax-follower-provider-delete',
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
    @include('projects.sub-location', [
      'locations' => $data->locations,
      'open_first' => TRUE,
      'is_master' => TRUE,
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
window.mProjectManage = function() {
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


  // local: location
  var fnLocationExpand = function() {
    $(this).closest('.location').toggleClass('expanded');
    resizeControlPanel();
  }
  var fnLocationFile = function() {
    overlay.setTitle('File Attachments');
    overlay.openAjax({
      url: laraRoute('project.overlay-loc-file') + $(this).closest('.location').attr('data-id'),
      method: 'GET', data: {}
    });
  }
  var fnKeepMod = function() {
    overlay.setTitle('Update Account Products');
    overlay.openAjax({
      url: laraRoute('project.overlay-keep-prod') + $(this).closest('.account').attr('data-accnt-id'),
      method: 'GET', data: {}
    });
  };
  var fnCancelMod = function() {
    overlay.setTitle('Update Dates');
    overlay.openAjax({
      url: laraRoute('project.overlay-cancel-date') + $(this).closest('.account').attr('data-accnt-id'),
      method: 'GET', data: {}
    });
  };
  var fnSignedMod = function() {
    overlay.setTitle('Update Dates');
    overlay.openAjax({
      url: laraRoute('project.overlay-sign-date') + $(this).closest('.account').attr('data-quote-id'),
      method: 'GET', data: {}
    });
  };
  var fnSignedToggleProd = function() {
    $(this).nextAll('.tbl-accnt-prods').toggleClass('expand');
  };

  var fnKeepRevert = function() {
    var frm = $(this).closest('form');
    confirmUser("<p>Do you want to revert and remove the account from the Project Management?</p><p>All updated products will be Deleted and cannot be undone.</p>",
      function() {
        submitFrm(frm);
      }, "Revert Account");
  }
  var fnCancelRevert = function() {
    var frm = $(this).closest('form');
    confirmUser("<p>Do you want to revert and remove the account from the Project Management?</p><p>All dates will be reset.</p>",
      function() {
        submitFrm(frm);
      }, "Revert Account");
  }
  var fnSignedRevert = function() {
    var frm = $(this).closest('form');
    confirmUser("<p>Do you want to revert the signed account and remove from the Project Management?</p>All dates will be reset.</p>",
      function() {
        submitFrm(frm);
      }, "Revert Account");
  }
  var fnAccountComplete = function() {
    var frm = $(this).closest('form');
    confirmUser("<p>Do you want to mark the Account Complete?</p>",
      function() {
        submitFrm(frm);
      }, "Account Complete");
  }
  var fnAccountUndoComplete = function() {
    var frm = $(this).closest('form');
    confirmUser("<p>Do you want to undo the Account Completion?</p>",
      function() {
        submitFrm(frm);
      }, "Undo Account Completion");
  }
  
  var fnCommissionEvents = function() {
    $sectionLeadControlAccount.find('.btn-assign-agency').click(function() {
      overlay.setTitle('Assign New Agency');
      overlay.openAjax({ url: laraRoute('master.project.overlay-agency-assign') + encLeadId, method: 'GET', data: {} });
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
      overlay.openAjax({ url: laraRoute('master.project.overlay-manager-assign') + encLeadId, method: 'GET', data: {} });
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
      url: laraRoute('master.project.overlay-log-mod') + $(this).closest('.log-action').attr('data-id'),
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
    $sectionLeadContent.find('.location .btn-loc-file').click(fnLocationFile);
    
    $sectionLeadContent.find('.account .btn-accnt-keep-mod').click(fnKeepMod);
    $sectionLeadContent.find('.account .btn-accnt-cancel-date').click(fnCancelMod);
    $sectionLeadContent.find('.account .btn-accnt-sign-date').click(fnSignedMod);
    $sectionLeadContent.find('.account .btn-accnt-sign-toggle').click(fnSignedToggleProd);

    $sectionLeadContent.find('.location .btn-accnt-keep-revert').click(fnKeepRevert);
    $sectionLeadContent.find('.location .btn-accnt-cancel-revert').click(fnCancelRevert);
    $sectionLeadContent.find('.location .btn-accnt-sign-revert').click(fnSignedRevert);

    $sectionLeadContent.find('.btn-accnt-complete').click(fnAccountComplete);
    $sectionLeadContent.find('.btn-undo-complete').click(fnAccountUndoComplete);
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
  
  // keep-account-prod-update: monthly rate
  window.aoKeepProdUpdate = function() {
    // product section
    $overlayPane.find('.btn-del-prod').click(function() {
      $(this).closest('tr').fadeOut({duration: 200, complete: function() { 
        $(this).remove();
        if ($overlayPane.find('.tbl-lead-prod-list tbody tr').length <= 0)
          $overlayPane.find('form.frm-prod input[type=submit]').prop('disabled', true);
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
      $overlayPane.find('.tbl-lead-prod-list tbody').append(
        $overlayPane.find('.tbl-lead-row-src tr:first').clone(true)
      );
      $overlayPane.find('form.frm-prod input[type=submit]').prop('disabled', false);
    });
    $overlayPane.find('.frm-prod').submit(function(e) {
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
          toastUser('Account Products have been updated.');
        },
      });
    });
  }
  // cancel-account/signed-account-date-update
  window.aoDateUpdate = function() {
    $overlayPane.find('.cal-date-input').on('focus click', function() {
      cal.clickDraw(this);
    });
    $overlayPane.find('.frm-update').submit(function(e) {
      e.preventDefault();
      
      var $frm = $(this);

      // check if form has signed-date -> required field
      $inputDateSign = $frm.find('input[name=sign_date]');
      if ($inputDateSign.length >0 && $inputDateSign.val() == '')
        return alertUser('Date Signed is a required field.');

      reqAjax({
        url: $frm.prop('action'),
        data: $frm.serializeArray(),
        fnSuccess: function(json) {
          fnReloadLead(json);
          overlay.close();
          toastUser('Account Dates have been updated.');
        },
      });
    });
  }
  
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

  var $sectionLeadControlAccount = $('section.lead-control-account');

  // ***** control panel: location navigation *****
  $sectionLeadControlAccount.find('.btn-ctrl-expand').click(function() { $(this).closest('.panel').toggleClass('expanded') });
  $sectionLeadControlAccount.find('.nav-location select').change(function() {
    var $locPanel = $('.location[data-id="' + this.value + '"]');
    $locPanel.addClass('expanded');
    $('html, body').animate({scrollTop: $locPanel.offset().top - 120 }, 300);
  });
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
  $sectionLeadControlAccount.find('.nav-location .btn-prev-location').click(function() { navLocation(this, false); });
  $sectionLeadControlAccount.find('.nav-location .btn-next-location').click(function() { navLocation(this, true); });

  // ***** control panel: commission, followers, customer *****
  $sectionLeadControlAccount.find('.btn-commission-update').click(function() {
    overlay.setTitle('Set Commission Rates');
    overlay.openAjax({
      url: laraRoute('master.project.overlay-commission') + encLeadId,
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
      url: laraRoute('master.project.overlay-follower-mod') + encLeadId,
      method: 'GET', data: {},
      fnSuccess: fnFillContainerChange,
      fnFail: function(json) { alertUser(json.msg); overlay.close(); },
    }); // END: reqAjax
    overlay.open();
  });

  // ***** control panel: customer  *****
  $sectionLeadControlAccount.find('.btn-customer-mod').click(function() {
    overlay.setTitle('Update Customer Information');
    overlay.openAjax({ url: laraRoute('master.project.overlay-customer-mod') + encLeadId, method: 'GET', data: {} });
  });

  // ***** control panel: lead-logs *****
  $sectionLeadControlAccount.find('.btn-log-add').click(function() {
    overlay.setTitle('Leave a Log');
    overlay.openAjax({
      url: laraRoute('master.project.overlay-log-new') + encLeadId,
      method: 'GET', data: {}
    });
  });
  $sectionLeadControlAccount.find('.btn-log-mod').click(fnLogCorrect);
  $sectionLeadControlAccount.find('.btn-log-history').click(function() {
    overlay.setTitle('Log History');
    overlay.openAjax({
      url: laraRoute('master.project.overlay-log-history') + encLeadId,
      method: 'GET', data: {}
    });
  });

  // ***** main content: location, acccounts, and quotes *****
  fnReloadLeadHandlers();
} // END: mProjectManage()
mProjectManage();
</script>
@endsection