/**
 * ingenCalendar object
 *  require: jquery, Date (default javascript library)
 * 
 *  initialize: 'var cal = new ingenCalendar(elem);' where elem is DOM element to draw calendar
 *  open calendar: 'cal.clickDraw(elemInput);' where elemInput is the date input associated with the calendar
 *  hide calendar: 'cal.hide();'
 * 
 * css style: use 'ingen.calendar.css' for styling.
 */
document.write('<link rel="StyleSheet" href="/css/ingen.calendar.css" type=text/css>');

function ingenCalendar (elem) {
	/***** variables
	 * elemCalendar: HTML element where the calendar will be drawn
	 * targetInput: HTML input element that the calendar will update value  
	 */
	this.elemCalendar = elem;
	this.targetInput = null;
	this.drawing = false;

	this.date = new Date();
	this.months = new Array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
		
	/**
	 * temporary variable used through the object
	 */
	var self = this;
	var slideSpeed = 300;

	// add handler for document.click: if outside the calendar is clicked, calendar hides
	$(document).click(function(ev) {
		// when a 'drawing' flag is on, skip calendar hiding and turn off the flag
		if (self.drawing)
			self.drawing = false;
		else {
			// hide show/hide pane if clicked outside calendar
			if (!$(self.elemCalendar).is(ev.target) && $(self.elemCalendar).has(ev.target).length ===0) {
				self.hide();
			}
		}
	});
	// add handler for window.resize: hide calendar
	$(window).resize(function(ev) { self.hide(); });

	/***** functions
	 */
	this.clickDraw = function(elemInput) {
		/**
		 * if calendar is drawn by mouse-click, document.click will also fire and will hide the calendar as soon as its drawn
		 * turn on 'drawing' flag to prevent document.click event to hide calendar
		*/
		this.drawFor(elemInput);
		this.drawing = true;
	}
	this.drawFor = function(elemInput) {
		// input element is REQUIRED for calendar
		if (elemInput == null || elemInput == undefined) {
			console.error('Unable to draw the calendar: targetInput is either null or undefined.');
			return false;
		}
		this.targetInput = elemInput;
		var date = new Date(elemInput.value);
		// if elemInput.value is invalid date, use new Date. otherwise, append ISOString.split to use correct time zone
		this.date = isNaN(date.getTime())?
			new Date() : new Date(elemInput.value + 'T' + this.date.toISOString().split('T')[1]);
		this.draw();
	}
	this.draw = function() {
		// based on the position of the input element, position the calendar
		var elem = this.targetInput;
		var x =0, y =0;
		while(elem != null) {
			x += elem.offsetLeft;
			y += elem.offsetTop;
			elem = elem.offsetParent;
			
			// special case: if calendar element is within overlay, do not apply overlay offset
			if ($(elem).is('#overlay-pane .overlay-inner'))
				break;
		}
		this.elemCalendar.style.left = (x +10) +'px';
		this.elemCalendar.style.top = (y +40) +'px';
		

		// start drawing the calendar
		this.elemCalendar.className = "ingen-calendar";
		
		var y = this.date.getFullYear();
		var m = this.date.getMonth(); // 0-11: JAN =0, DEC=11
		var wk = new Date(y,m,1).getDay(); // wk day in number: SUN=0, SAT=6
		var nDays = daysInMonth(y,m);
		var today = new Date();
		
		var code = '<div class="nav"> <div class="month"><select class="sel-month">';
		for (var i=0; i<12; i++) {
			code += '<option value="' + i + '"';
			if (m==i)
				code += " selected";
			code += '>' + this.months[i] + '</option>';
		}
		code += '</select></div>' +
			'<div class="year">' +
				'<span class="nav-btn btn-last-yr md" title="Last Year">first_page</span>' +
				'<span class="nav-btn btn-last-mo md" title="Last Month">chevron_left</span>' +
				'<span class="yr">' + y + '</span>' +
				'<span class="nav-btn btn-next-mo md" title="Next Month">chevron_right</span>' +
				'<span class="nav-btn btn-next-yr md" title="Next Year">last_page</span>' +
			'</div>' +
			
			'<div class="tbl header"> <div class="row">' +
				'<div class="cell wknd">Sun</div> <div class="cell">Mon</div> <div class="cell">Tue</div> <div class="cell">Wed</div>' +
					'<div class="cell">Thr</div> <div class="cell">Fri</div> <div class="cell wknd">Sat</div>' +
			'</div></div> </div>' +
			'<div class="tbl days"></div>' +
			'<div class="btns">' +
				'<input type="button" class="btn-clear" value="clear" /> <input type="button" class="btn-today" value="Today" /> <input type="button" class="btn-hide" value="close" />'
			'</div>';
		
		this.elemCalendar.innerHTML = code;
		
		var $calPane = $(this.elemCalendar);
		$calPane.find('.btn-last-yr').click(function() { prevYear(self); });
		$calPane.find('.btn-next-yr').click(function() { nextYear(self); });
		$calPane.find('.btn-last-mo').click(function() { prevMonth(self); });
		$calPane.find('.btn-next-mo').click(function() { nextMonth(self); });
		$calPane.find('.sel-month').change(function() { updateMonth(self, this); });
		$calPane.find('.btn-clear').click(function() { clearCalendar(self); });
		$calPane.find('.btn-today').click(function() { applyToday(self); });
		$calPane.find('.btn-hide').click(function() { closeCalendar(self); });
		
		calRefresh();

		$calPane.slideDown(slideSpeed);
		this.drawing = true;
	} // END draw()

	this.clearHide = function() {
		this.targetElem.value = '';
		$(this.elemCalendar).slideUp(slideSpeed);
	}
	this.hide = function() {
		$(this.elemCalendar).slideUp(slideSpeed);
	}
	
	
	// internal functions
	function calRefresh() {
		var y = self.date.getFullYear();
		var m = self.date.getMonth(); // 0-11: JAN =0, DEC=11
		var wk = new Date(y,m,1).getDay(); // wk day in number: SUN=0, SAT=6
		var nDays = daysInMonth(y,m);
		var today = new Date();

		var code = '<div class="row">';

		for (var i=0; i<wk; i++)
			code += '<div class="cell"></div>';
		for (var i=1; i<=nDays; i++) {
			var rowWk = (i+wk-1)%7; // day ok week: SUN=0 = SAT=6
			if (rowWk == 0)
				code += '</div> <div class="row">';
			code += (rowWk == 0 || rowWk ==6)? '<div class="cell wknd">' : '<div class="cell">';
			code += '<span class="link-day">';
			code += (today.getFullYear() ==y && today.getMonth() ==m && today.getDate() ==i)? '<span class="today">'+i+'</span>' : i;
			code += '</span></div>';
		}
		for (var i=(nDays+wk)%7; 0<i && i<7; i++)
			code += '<div class="cell"></div>';
		/**
		 * calendar is formatted to have 6 weeks (e.g. 1st SAT - 31st MON)
		 * if current year-month does not have enough days to fit 6 weeks, manually insert empty rows.
		 */
		if (wk+nDays < 29)
			code += '</div><div class="row"><div class="cell"></div>';
		if (wk+nDays < 36)
			code += '</div><div class="row"><div class="cell"></div>';

		code += '</div>';

		$(self.elemCalendar).find('.nav .year .yr').text(y);
		$(self.elemCalendar).find('.sel-month').val(m);
		$(self.elemCalendar).find('.tbl.days').html( code);
		$(self.elemCalendar).find('.link-day').click(function() { applyDate(self, this); });
	}
	function daysInMonth(yr, mon) {
		return 32 - new Date(yr, mon, 32).getDate();
	}

	function prevYear(cal) {
		if (isNaN(cal.date.getTime()))
			cal.date = new Date();
		cal.date.setFullYear(cal.date.getFullYear() -1);
		calRefresh();
	}
	function nextYear(cal) {
		if (isNaN(cal.date.getTime()))
			cal.date = new Date();
		cal.date.setFullYear(cal.date.getFullYear() +1);
		calRefresh();
	}
	function prevMonth(cal) {
		if (isNaN(cal.date.getTime()))
			cal.date = new Date();
		cal.date.setMonth(cal.date.getMonth() -1);
		calRefresh();
	}
	function nextMonth(cal) {
		if (isNaN(cal.date.getTime()))
			cal.date = new Date();
		cal.date.setMonth(cal.date.getMonth() +1);
		calRefresh();
	}
	// elem: select input with month values (JAN:0 - DEC:11)
	function updateMonth(cal, elem) {
		if (isNaN(cal.date.getTime()))
			cal.date = new Date();
		cal.date.setMonth(parseInt(elem.value));
		calRefresh();
	}

	// elem: tag enclosing date (1-31)
	function applyDate(cal, elem) {
		targetElem = cal.targetInput;
		if (targetElem !=null || targetElem !=undefined) {
			var date = new Date(cal.date.getFullYear(), cal.date.getMonth(), parseInt($(elem).text()));
			targetElem.value = date.toISOString().split('T')[0]; // ISOString formats in 2000-01-01T12:00:00Z
		}
		cal.hide();
	}
	function applyToday(cal) {
		targetElem = cal.targetInput;
		if (targetElem !=null || targetElem !=undefined) {
			var date = new Date();
			targetElem.value = date.toISOString().split('T')[0]; // ISOString formats in 2000-01-01T12:00:00Z
		}
		cal.hide();
	}

	function closeCalendar(cal) {
		cal.hide();
	}
	function clearCalendar(cal) {
		targetElem = cal.targetInput;
		if (targetElem !=null || targetElem !=undefined) 
			targetElem.value ='';
		cal.hide();
	}
}