/**
 * site-specific general functions
 *  require: jQuery 
 */

/********************************************************
 * alertUser: OVERRIDE
 *  customzied alertUser - popup toast with alert flag ON
 *  if msg includes a special string: "[FORCELOGOUT]", it signifies the ajax call was made despite the user is logged out
 *   redirect to index page
 ********************************************************/
function alertUser(msg) {
    if (msg.substr(0,13) === '[FORCELOGOUT]') {
        window.location = 'logout';
        return false;
    }
    toastUser(msg, true);
}
/********************************************************
 * toastUser: friendly message show up on toast - popup toast
 *  wrap message with 'info' class name by default. if alert is on, change class name to 'err'
 ********************************************************/
function toastUser(msg, alert) {
  if (alert == undefined)
    alert = false;

	if (msg != null && msg != undefined && msg != '') {
    
    var $toast = $('#header-toast');
    var $toastMsg = $toast.find('.toast-msg');
    if ($toastMsg.length ==0)
        $toastMsg = $('<div/>', {class: 'toast-msg'});
    
    var className = (alert)?  'err':'info';
    var $msgWrapper = $('<span/>', {class: className});
    // $msgWrapper.prop('innerText', msg);
    $msgWrapper.html(msg);

    $toastMsg.html("").append($msgWrapper);

    $toast.finish().append($toastMsg);
    var toastH = $toast.height() +30;
    $toast.css({top: -toastH-10 + 'px'})
      .animate({top: '0px'}, 500, function() {
        $toast.delay(5000).animate({top: -toastH-10 + 'px'}, 300, function() { $toastMsg.remove(); });
      });
    }
}
/********************************************************
 * closeToast: force/manually close Toast container
 ********************************************************/
function closeToast() {
  var $toast = $('#header-toast');
  if ($toast.length >0) {
    var toastH = $toast.height() +30;
    $toast.clearQueue().animate({top: -toastH-10 + 'px'}, 300, function() { $toast.find('.toast-msg').remove(); });
  }
}
/********************************************************
 * confirmUser
 *  customzied confirm alert - for future updates to stylize confirm popup
 *  fnYes: callback function to execute on 'okayed'
 ********************************************************/
function confirmUser(msg, fnYes, title) {
    /*
    if (confirm(msg))
        fnYes();
    */
    // deselect any element that has the focus
    $(':focus').blur();

    var alertTitle = (title && title !="")?  title : '';
    // create new element
    var $elem = $('<div />', { id: 'alert-pane'}).hide();
    $elem.html(
        '<div class="alert-inner">' +
            '<section class="bar"><h3>' + alertTitle + '</h3>' +
                '<span class="alert-close">Close or ESC key <span class="fa-remove"></span></span>' +
            '</section>' +
            '<section class="body">' +
                '<p>' + msg + '</p>' +
            '</section>' +
            '<section class="btn-group">' +
                '<input type="button" value="yes" class="btn-yes" />' +
                '<input type="button" value="no" class="btn-close" />' +
            '</section>' +
        '</div>'
    );

    /** handlers
	 * 3 ways to close the alert box:
	 *  click outside the inner container, ESC key, click on alert-close/btn-close
     * if "yes" clicked: execute function 'fnYes'
	 */
    var alertClose = function() {
        $elem.fadeOut({complete: function() { $elem.remove(); }});
    };
    $elem.click(function(ev) {
        $inner = $elem.find('.alert-inner');
        // close if clicked outside alert-inner
        var is = $inner.is(ev.target);
        var has = $inner.has(ev.target).length;
        if (!is && has ===0)
            alertClose();
    });
    $(document).keydown(function(ev) {
        // close on ESC key (keycode = 27) if overlay is opened
        if ($('body').has($elem).length >0 && ev.keyCode == 27)
            alertClose();
    });
    $elem.find('.btn-yes').click(function() { fnYes(); alertClose(); });
    $elem.find('.alert-close, .btn-close').click(function() { alertClose(); });
    $('body').append($elem);
    $elem.fadeIn();
}
/********************************************************
 * submitFrm
 *  customized form submit: equivalent to form.submit(), but it will place loading overlay while the form is submitted
 *  @frm: DOM object of form to submit
 ********************************************************/
function submitFrm(frm) {
    var $loadingOverlay = $('<div/>', {class: 'form-submit-loading'}).hide();
    $('body').append($loadingOverlay);
    $loadingOverlay.fadeIn({complete: function() { frm.submit(); }});
}
/********************************************************
 * cleanInput
 *  modify input value based on input type specified
 ********************************************************/
function cleanInput(elem, validateType) {
    var v = elem.value;
    switch (validateType) {
        case "tel":
		    // phone/fax number is 10 digits (no dash/hyphen allowed)
            v = v.replace(/[^\d]/g, "");
            elem.value = v.replace(/^([\d]{10}).*$/, "$1");
			break;
    }
}
/********************************************************
 * preventEnter
 *  prevent 'ENTER' key from input type[text|search|email|date|tel|number|radio|checkbox]
 ********************************************************/
function preventEnterKey(ev) {
    var elem = (ev.target)? ev.target : ev.srcElement;
    if ((ev.keyCode == 13) &&
        (elem.type =='text' || elem.type =='search' || elem.type =='email' || elem.type =='date' || elem.type =='tel' ||
            elem.type =='number' || elem.type =='radio' || elem.type =='checkbox')
    ) {
        return false;
    }
}




/** ******************************************************* custom overlay object *******************************************************
 * require: jquery
 *  reserve: .btn-overlay - button to open overlay
 */
function ingenOverlay (elemID) {
  // validate container
  var elem = document.getElementById(elemID);
  if (!elem) {
      console.error('Unable to create the overlay container');
      return false;
  }

	/** variables
	 * container: HTML overlay element
	 * opened: flag if the overlay is opened or not
	 */
	this.container = elem;
  this.opened = false;

  // setup overlay-inner within container
  var $elem = $(elem);
  $elem.html(
      '<div class="overlay-inner" style="display:flex">'+
          '<section class="bar">' +
              '<h3>Overlay</h3>' + 
              '<span class="overlay-close">Close or ESC key <span class="fa-remove"></span></span>' +
          '</section>' +
          '<section class="body"></section>' +
      '</div>' +
      '<div class="overlay-load-spinner"></div>'
  );

  /** handlers
	 * 3 ways to close the overlay:
	 *  click outside the inner container, ESC key, click on overlay-close
	 */
  var self = this;
  $elem.click(function(ev) {
    $inner = $(self.container).find('.overlay-inner');
    // close if clicked outside overlay-inner
    var is = $inner.is(ev.target);
    var has = $inner.has(ev.target).length;
    if (!is && has ===0)
      self.close();
  });
  $(document).keydown(function(ev) {
    // close on ESC key (keycode = 27) if overlay is opened
    if (self.opened && ev.keyCode == 27) {
      self.close();
    }
  });
  $(this.container).find('.overlay-close').click(function() { self.close(); });
  
    
	/** public functions
   * open: open overlay (just the container, setContent is required to open inner content)
   * close: close overlay (and empty container body)
   * setTitle: set title of the overlay
   * setContent: set HTML code inside the section.body
   * openAjax: load page using jquery AJAX, input should be in JSON format { url, data }, then open overlay
	 */
	this.open = function() {
    this.opened = true;
    $(this.container).fadeIn();
	};
	this.close = function() {
    this.opened = false;
    var $overlayBody = $(this.container);
    $overlayBody.fadeOut({complete: function() {
      $overlayBody.find('.overlay-inner').hide();
      $overlayBody.find('section.body').html("");
    }});
	};
  this.setTitle = function(title) {
    $(this.container).find('section.bar h3').html(title);
  };
  this.setContent = function(html) {
    $(this.container).find('section.body').html(html);
    $(this.container).find('.overlay-inner').fadeIn();
    this.hideSpin();
  }
  this.loadSpin = function() {
    // set loading animation for 5000ms
    var $spinner = $(this.container).find('.overlay-load-spinner');
    $spinner.fadeIn().delay(5000).fadeOut();
  }
  this.hideSpin = function() {
    $(this.container).find('.overlay-load-spinner').finish().fadeOut();
  }
  this.openAjax = function(json, httpMethod) {
    // URL should not be an empty string
    if (!json || !json.url || !json.data) {
        console.error('Invalid data input');
        return false;
    }
    if (json.method == undefined || json.method != 'GET') {
      if (httpMethod == undefined || httpMethod != 'GET')
        json.method = 'POST';
    }

    /**
     * mark the overlay is opened, and fadein shaded overlay first
     *  set loading animation (will get replaced when loading completes) : in case overlay is reloaded (not initial open)
     * */
    this.loadSpin(); 
    this.open();
    
    $.ajax({url: json.url,
      type: json.method,
      data : json.data,
      success: function(res) {
        try {
          var json_res = JSON.parse(res);
        } catch (ex) {
          if (res.substr(0,9) =='<!DOCTYPE')
            alertAndClose('Unable to load Overlay: Unknown Error. Please check if you are still logged in.');
          else
            alertAndClose('Unable to load Overlay: Failed to parse Response: ' + res);
          return false;
        }
        if (json_res.success >0) {
          self.setContent(json_res.html);
          $(self.container).find(".btn-cancel").click(function() { self.close(); });

        } else {
          var err_msg = 'Unable to load to Overlay: There was an error';
          if (json_res.msg != undefined)
            err_msg = json_res.msg;
          alertAndClose(err_msg);
        }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        alertAndClose('AJAX Error: ' + xhr.statusText);
      }
    }); // END ajax
  };
	/** private functions
	 */
  var alertAndClose = function (msg) {
    alertUser(msg);
    self.close();
  }
}
// ******************************************************** END: ingenOverlay object ********************************************************




/** ******************************************************
 * reqAjax
 * 
 * @param reqURL: URL to request via AJAX
 * @param postData: ajax request data
 * @param fnSuccess: additional function to execute on success, will pass 'json_res' as argument
 * @param fnFail: function to execute on fail, will pass 'json_res' as argument. if undefined, use 'alertUser' by default
 * @param httpMethod (optional): HTTP method - default to POST, or GET
 ******************************************************* */
function reqAJAX(reqURL, postData, fnSuccess, fnFail, httpMethod) {
  if (httpMethod == undefined || httpMethod !='GET')
    httpMethod = 'POST';
  var errHandler = function(jsonVar) {
    if (fnFail !=undefined)
      fnFail(jsonVar);
    else
      alertUser(jsonVar.msg);
    $loadingOverlay.fadeOut({complete: function() { $(this).remove(); }});
  };

  var $loadingOverlay = $('<div/>', {class: 'form-submit-loading'}).hide();
  $('body').append($loadingOverlay);
  $loadingOverlay.fadeIn({complete: function() { 
    $.ajax({url: reqURL,
      type: httpMethod,
      data : postData,
      success: function(res) {
        try {
          var json_res = JSON.parse(res);
        } catch (ex) {
          if (res.substr(0,9) =='<!DOCTYPE')
            errHandler({msg: 'Unable to load Overlay: Unknown Error. Please check if you are still logged in.'});
          else
            errHandler({msg: 'Unable to parse Response: ' + res});
          return false;
        }
        if (json_res.success >0) {
          fnSuccess(json_res);
          $loadingOverlay.fadeOut({complete: function() { $(this).remove(); }});
        } else
          errHandler(json_res);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        errHandler({msg: 'AJAX Error: ' + xhr.statusText});
      }
    }); // END ajax
  }});
  
}
/** ******************************************************
 * reqAjax (rewritten function - will replace reqAJAX)
 * 
 * @param json: {
 *  url: URL to request via AJAX
 *  data: ajax request data
 *  fnSuccess: additional function to execute on success, will pass 'json_res' as argument
 *  fnFail (optional): function to execute on fail, will pass 'json_res' as argument. if undefined, use 'alertUser' by default
 *  method (optional): HTTP method - default to POST, or GET
 * }
 ******************************************************* */
function reqAjax(json) {
  var httpMethod = (json.method == undefined) ? 'POST' : json.method;
  // check if page already has loading screen on: avoid "double" loading screen
  var alreadyLoading = ($('body > div.form-submit-loading').length >0);
  var $loadingOverlay = $('<div/>', {class: 'form-submit-loading'}).hide();

  var errHandler = function(jsonVar) {
    if (json.fnFail != undefined)
      json.fnFail(jsonVar);
    else
      alertUser(jsonVar.msg);
    if (!alreadyLoading)
      $loadingOverlay.fadeOut({complete: function() { $(this).remove(); }});
  };
  var fnRequest = function() { 
    $.ajax({url: json.url,
      type: httpMethod,
      data : json.data,
      success: function(res) {
        try {
          var json_res = JSON.parse(res);
        } catch (ex) {
          if (res.substr(0,9) =='<!DOCTYPE')
            errHandler({msg: 'Unable to load Overlay: Unknown Error. Please check if you are still logged in.'});
          else
            errHandler({msg: 'Unable to parse Response: ' + res});
          return false;
        }
        if (json_res.success >0) {
          json.fnSuccess(json_res);
          if (!alreadyLoading)
            $loadingOverlay.fadeOut({complete: function() { $(this).remove(); }});
        } else
          errHandler(json_res);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        errHandler({msg: 'AJAX Error: ' + xhr.statusText});
      }
    }); // END ajax
  };
  if (alreadyLoading)
    fnRequest();
  else {
    $('body').append($loadingOverlay);
    $loadingOverlay.fadeIn({complete: fnRequest });
  }
}
/** ******************************************************
 * openDataTable
 *  require: DataTable.js
 * 
 * @param tblSelector: jQuery selector of the table
 * @param srcUrl: url to get data from server-side 
 * @param addData: additional data to send to server-side
 * @param drawCallbackFn (optional): additional script to run
 * ***************************************************** */
function openDataTable(tblSelector, srcUrl, addData, drawCallbackFn) {
  var $loadingOverlay;
  
  $(tblSelector).DataTable({
    serverSide: true,
    pageLength: 30,
		ordering: false,
    processing: true,
    autoWidth: false,
    ajax: {
      url: srcUrl,
      type: "GET",
      data: addData,
      error: function() {
        $('.form-submit-loading').fadeOut({complete: function() { $(this).remove(); }});
        alertUser('The system was unable to load the table.');
      }
    },
		dom: '<tp>',

    preDrawCallback: function(settings) {
      if ( !$('body').has('.form-submit-loading').length ) {
        $loadingOverlay = $('<div/>', {class: 'form-submit-loading'}).hide();
        $('body').append($loadingOverlay);
        $loadingOverlay.fadeIn();
      }
    },
    drawCallback: function(settings) {
      if ($loadingOverlay)
        $loadingOverlay.fadeOut({complete: function() { $(this).remove(); }});
      $(this).closest('.dataTables_wrapper').find('.dataTables_paginate').toggle(this.api().page.info().pages > 1);
      
    }
  });
  
  // execute additional scripts to run (if exists)
  if (drawCallbackFn != undefined)
    drawCallbackFn();
}
/** ********************************************************
 * scrollToTop
 *  function used in footer: scroll back to top
********************************************************/
function scrollToTop() {
  $('html, body').animate({scrollTop: $('body').offset().top}, 300, 'linear');
}
/** ********************************************************
 * toggleBackToTop
 *  show/hide #back-to-top button
********************************************************/
function toggleBackToTop() {
  var $elem = $('#back-to-top');
    if($(window).scrollTop() >100)
		$elem.fadeIn();
  else
		$elem.fadeOut();
}
/** ******************************************************
 * laraRoute
 * 
 * @param routeName: Laravel web route name
 * @return: URL corresponds to route name
 * ***************************************************** */
function laraRoute(routeName) {
  switch (routeName) {
    case 'datatables.leads':
      return '/datatables/leads';
    case 'datatables.projects-sign':
      return '/datatables/projects/signed';
    case 'datatables.projects-keep':
      return '/datatables/projects/keep';
    case 'datatables.projects-cancel':
      return '/datatables/projects/cancel';
    case 'master.provider.overlay-prod-new':
      return '/provider/json/product/new/'; // + provider-id
    case 'master.provider.overlay-prod-mod':
      return '/provider/json/product/mod/'; // + product-id
    case 'master.lead.overlay-commission':
      return '/lead/json/commission/'; // + lead-id
    case 'master.lead.overlay-agency-assign':
      return '/lead/json/agency/assign/'; // + lead-id
    case 'master.lead.overlay-manager-assign':
      return '/lead/json/manager/assign/'; // + lead-id
    case 'master.project.overlay-commission':
      return '/project/json/commission/'; // + lead-id
    case 'master.project.overlay-agency-assign':
      return '/project/json/agency/assign/'; // + lead-id
    case 'master.project.overlay-manager-assign':
      return '/project/json/manager/assign/'; // + lead-id
      
    /* */
    case 'lead.overlay-customer-list':
      return '/lead/json/customers';
    case 'lead.overlay-customer-new':
      return '/lead/json/customer/new';
    /* */
    case 'master.lead.overlay-customer-mod':
    case 'lead.overlay-customer-mod':
      return '/lead/json/customer/mod/'; // + lead-id
    /* */
    case 'lead.overlay-salesperson-list':
      return '/lead/json/salespersons';
    case 'lead.overlay-salesperson-new':
      return '/lead/json/salesperson/new';
    case 'lead.overlay-salesperson-mod':
      return '/lead/json/salesperson/mod/'; // + lead-id
    /* */
    case 'master.lead.overlay-follower-mod':
    case 'lead.overlay-follower-mod':
      return '/lead/json/follower/mod/'; // + lead-id
    case 'lead.overlay-alert-mod':
      return '/lead/json/alert/mod/'; // + lead-id
    case 'master.lead.overlay-log-new':
    case 'lead.overlay-log-new':
      return '/lead/json/log/new/'; // + lead-id
    case 'master.lead.overlay-log-mod':
    case 'lead.overlay-log-mod':
      return '/lead/json/log/mod/'; // + log-id
    case 'master.lead.overlay-log-history':
    case 'lead.overlay-log-history':
      return '/lead/json/log/history/'; // + lead-id

    case 'master.lead.overlay-loc-new':
    case 'lead.overlay-loc-new':
      return '/lead/json/location/new/'; // + lead-id
    case 'master.lead.overlay-loc-mod':
    case 'lead.overlay-loc-mod':
      return '/lead/json/location/mod/'; // + location-id
    case 'lead.overlay-loc-file':
      return '/lead/json/location/file/'; // + location-id
    case 'master.lead.overlay-accnt-new':
    case 'lead.overlay-accnt-new':
      return '/lead/json/account/new/'; // + location-id
    case 'master.lead.overlay-accnt-mod':
    case 'lead.overlay-accnt-mod':
      return '/lead/json/account/mod/'; // + account-id
    case 'lead.overlay-accnt-svc':
      return '/lead/json/account/services/'; // + account-id
    case 'master.lead.overlay-quote-new':
    case 'lead.overlay-quote-new':
      return '/lead/json/quote/new/'; // + location-id
    case 'master.lead.overlay-quote-mod':
    case 'lead.overlay-quote-mod':
      return '/lead/json/quote/mod/'; // + quote-id
      
    case 'master.project.overlay-customer-mod':
    case 'project.overlay-customer-mod':
      return '/project/json/customer/mod/'; // + lead-id
    case 'master.project.overlay-follower-mod':
    case 'project.overlay-follower-mod':
      return '/project/json/follower/mod/'; // + lead-id
    case 'master.project.overlay-log-new':
    case 'project.overlay-log-new':
      return '/project/json/log/new/'; // + lead-id
    case 'master.project.overlay-log-mod':
    case 'project.overlay-log-mod':
      return '/project/json/log/mod/'; // + log-id
    case 'master.project.overlay-log-history':
    case 'project.overlay-log-history':
      return '/project/json/log/history/'; // + lead-id
      
    case 'project.overlay-loc-file':
      return '/project/json/location/file/'; // + location-id
    case 'project.overlay-keep-prod':
      return '/project/json/keep/product/'; // + account-id
    case 'project.overlay-cancel-date':
      return '/project/json/cancel/date/'; // + account-id
    case 'project.overlay-sign-date':
      return '/project/json/signed/date/'; // + quote-id
      
    case 'master.lead.ajax-reload':
    case 'lead.ajax-reload':
      return '/lead/json/reload/'; // + lead-id
    case 'lead.ajax-customer-select':
      return '/lead/json/customer/select/'; // + customer-id
    case 'lead.ajax-customer-create':
      return '/lead/json/customer/create';
    case 'lead.ajax-customer-update':
      return '/lead/json/customer/update/'; // + lead-id
    case 'lead.ajax-salesperson-select':
      return '/lead/json/salesperson/select/'; // + salesperson-id
    case 'lead.ajax-salesperson-create':
      return '/lead/json/salesperson/create';
    case 'master.lead.ajax-loc-delete':
    case 'lead.ajax-loc-delete':
      return '/lead/json/location/delete/'; // + location-id
    case 'master.lead.ajax-accnt-toggle':
    case 'lead.ajax-accnt-toggle':
      return '/lead/json/account/toggle/'; // + account-id
    case 'master.lead.ajax-accnt-delete':
    case 'lead.ajax-accnt-delete':
      return '/lead/json/account/delete/'; // + account-id
    case 'lead.ajax-accnt-svc':
      return '/lead/json/account/services/update/'; // + account-id
    case 'master.lead.ajax-quote-toggle':
    case 'lead.ajax-quote-toggle':
      return '/lead/json/quote/toggle/'; // + quote-id
    case 'master.lead.ajax-quote-delete':
    case 'lead.ajax-quote-delete':
      return '/lead/json/quote/delete/'; // + quote-id
    case 'lead.ajax-quote-svc':
      return '/lead/json/quote/services/update/'; // + quote-id
  }
  return false;
}


/********************************************************
 * general handlers
 *  applies to ALL pages
 * ********************************************************/
$(window).on('load',function() {
  // ***** hide toast if opened *****
  var $toast = $('#header-toast');
  if ($toast.length >0) {
    var toastH = $toast.height() +30;
    $toast.delay(10000).animate({top: -toastH-10 + 'px'}, 300, function() { $toast.find('.toast-msg').remove(); });
  }
  // ***** close toast if btn-toast-close button is clicked *****
  $toast.find('.btn-toast-close').click(closeToast);
  // ***** confirm on logout *****
  $('header .btn-header-logout').click(function(ev) {
    ev.preventDefault();
    var $href = $(this).prop("href");

    confirmUser("Do you want to log out?",
      function() {
        location.href = $href;
      }, "Log Out");
  });
  // ***** toggle side nav bar *****
  $('.nav-mm .btn-nav-expand').click(function() {
    $(this).closest('.nav-mm').toggleClass('expanded');
  });
  // ***** for mobile view: click to open nav-menu and sub-menu *****
  $('header .btn-header-menu').click(function() {
    $('.nav-mm > ul > li').removeClass('open');
    $('.nav-mm').slideToggle();
  });
  $('.nav-mm > ul > li > a').click(function(ev) {    
    // function is for mobile-view only
    if ($(window).width() > 700)
        return true;
        
    ev.preventDefault();
    var $href = $(this).prop("href");

    // if main menu with sub-menu (.header-nav-submenu) is clicked, open sub-menu
    var $li = $(this).closest('li');
    if ($li.find('li').length >0)
      $li.toggleClass('open');
    else if ($href !='')
      location.href = $href;
  });
  
  // ***** back-to-top scroll bar appears if main page is scrolled down *****
  $('#back-to-top').click(scrollToTop);
  $(document).scroll(toggleBackToTop);
  
  $(window).resize(function() {
    // if window is resized to non-mobile-view, reset 'display' for header nav
    if ($(window).width() > 700) {
      $('.nav-mm').css('display','');
    }
  });
  $(document).click(function(ev) {
    // function is for mobile-view only
    if ($(window).width() <= 700) { // if clicked outside header nav, hide navigation (for mobile view)
      var $nav = $('nav.nav-mm');
      var $btnMenu = $('header .btn-header-menu');
      if (!$nav.is(ev.target) && $nav.has(ev.target).length ===0 && !$btnMenu.is(ev.target) && $btnMenu.has(ev.target).length ===0)
        $nav.slideUp();
    }       
  });

  // ***** specific for Laravel: use 'csrf-token' saved in meta tags to protect ajax calls with CSRF token *****
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
// END: $(window).load
});