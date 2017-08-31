/**
 * general functions
 */


/********************************************************
 * elemid
 *  shorcut to document.getElementById
 ********************************************************/
function elemid(o) {
return document.getElementById(o);
}
/********************************************************
 * alertUser
 *  custom alert function (in case customization needed)
 ********************************************************/
function alertUser(msg) {
	if (msg != null && msg != undefined && msg != '')
		console.error(msg);
	// alert(msg);
}
/********************************************************
 * validateInput
 *  validate input based on validation-type (either numeric or string)
 *  on fail to validate, alert with message
 ********************************************************/
function validateInput (elem, validateType, msg, extra) {
	var invalid = false;
	
	switch (validateType) {
		case 'tel':
		case 'fax':		
		// phone/fax number is series of digits (no length validation, no dash/hyphen allowed)
			var objRegExp  = /^(\d)*$/;
			invalid = (!objRegExp.test(elem.value));
			break;

		case 'email':
		// email validation
			invalid = (!validateEmail(elem.value, false));
			break;
			
		case 'confirmpw':
		// unconfirmed password
			invalid = (elem.value != extra.value || elem.value == "");
			break;
			
		case 'validpw':
		// match password (at least 1 letter, 1 digit, and 1 special character, 6-30 letters long): ^(?=.*[A-Za-z])(?=.*\d)(?=.*[_$@$!%*#?&])[A-Za-z\d_$@$!%*#?&]{6,}$
			var objRegExp  = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[_$@$!%*#?&])[A-Za-z\d_$@$!%*#?&]{6,30}$/;
			invalid = (!objRegExp.test(elem.value));
			break;
			
		case 'zipus':
		// not a valid US zip code
			var objRegExp  = /^\d{5}(-\d{4})?$/;
			invalid = (!objRegExp.test(elem.value));
			break;
			
		case 'alpha_num_under':
		// alphanumeric and underscore only
			var objRegExp  = /^[A-Za-z0-9]+$/;
			invalid = (!objRegExp.test(elem.value));
			break;


		case 1:
		// empty string
			invalid = (elem.value == "");
			break;
		case 4:
		// both string are empty
			invalid = (elem.value == "" && extra.value == "");
			break;
		case 5:
		// select from dropdown menu
			invalid = (elem.selectedIndex <1);
			break;
		case 6:
		// valid price
			invalid = (elem.value == "" || parseFloat(elem.value) < 0.01);
			break;
		case 7:
		// valid price, 0 acceptable
			invalid = (elem.value == "" || parseFloat(elem.value) < 0);
			break;
		case 11:
		// not a valid CANADA zip code
			var objRegExp  = /^[abceghjklmnprstvxyABCEGHJKLMNPRSTVXY]{1}\d{1}[a-zA-Z]{1} *\d{1}[a-zA-Z]{1}\d{1}$/;
			invalid = (!objRegExp.test(elem.value));
			break;
		case 12:
		// not a valid US or CANADA zip code
			var objRegExp  = /(^\d{5}(-\d{4})?$)|(^[abceghjklmnprstvxyABCEGHJKLMNPRSTVXY]{1}\d{1}[a-zA-Z]{1} *\d{1}[a-zA-Z]{1}\d{1}$)/;
			invalid = (!objRegExp.test(elem.value));
			break;
			
			
		case 20:
		// cc number (15-16 digits), allow masked XXXX1234
			var objRegExp  = /(^\d{15,16}$)|(^[X]{4}\d{4}$)/;
			invalid = (!objRegExp.test(elem.value));
			break;
		case 21:
		// cc code (3-4 digits)
			var objRegExp  = /^\d{3,4}$/;
			invalid = (!objRegExp.test(elem.value));
			break;
		case 22:
		// routing number (9 digit), allow masked XXXX1234
			var objRegExp  = /(^\d{9}$)|(^[X]{4}\d{4}$)/;
			invalid = (!objRegExp.test(elem.value));
			break;
		case 23:
		// bank account number (5-17 digit), allow masked XXXX1234
			var objRegExp  = /(^\d{5,17}$)|(^[X]{4}\d{4}$)/;
			invalid = (!objRegExp.test(elem.value));
			break;
		case 24:
		// cc expiration date (MMYY format), allow masked XXXX
			var objRegExp  = /(^\d{4}$)|(^[X]{4}$)/;
			if (!objRegExp.test(elem.value)) {
				invalid = true; break;
			}
			var mm = elem.value.substr(0,2);
			var val_f = elem.value.substr(2) + mm;
			invalid = (parseInt(extra) > parseInt(val_f) || parseInt(mm) >12 || parseInt(mm) <1);
			break;
		case 25:
		// auth no (alphanumeric only)
			var objRegExp  = /^[a-zA-Z0-9]+$/;
			invalid = (!objRegExp.test(elem.value));
			break;
			
			
		case 95:
		// codes (alphanumeric and underscore only)
			var objRegExp  = /^[a-zA-Z0-9_]+$/;
			invalid = (!objRegExp.test(elem.value));
			break;
			
		case 96:
		// valid number
			invalid = (!isNumeric(elem.value));
			break;
		case 97:
		// invoice number - int greater than 0
			invalid = (!isNumeric(elem.value) || parseInt(elem.value) <1);
			break;
			
		case 98:
		// phone number (10 digits, or ###-###-#### format)
			var objRegExp  = /(^\d{10}$)|(^\d{3}-\d{3}-\d{4}$)/;
			invalid = (!objRegExp.test(elem.value));
			break;
			
		case 99:
		// matches regular expresson
			var objRegExp  = new RegExp(extra);
			invalid = (!objRegExp.test(elem.value));
			break;
	} // END switch: validateType

	if (invalid) {
		alertUser(msg);
		elem.focus();
		return false;
	}
	return true;
}

/********************************************************
 * validateEmail
 *  argument: addr - email address, required - if empty email is NOT allowed
 *  validate email address
 ********************************************************/
function validateEmail(addr, required) {
addr = addr.toLowerCase();

// empty address is invalid
if (!required && addr == '')
   return true;

// validate: invalid characters
var invalidChars = '\/\'\\ ";:?!()[]\{\}^|';
for (i=0; i<invalidChars.length; i++) {
   if (addr.indexOf(invalidChars.charAt(i),0) > -1) {
      if (db) alert('email address contains invalid characters');
      return false;
   }
}

// validate: non-ascii characters (charCode > 127)
for (i=0; i<addr.length; i++) {
   if (addr.charCodeAt(i)>127) {
      return false;
   }
}

var atPos = addr.indexOf('@',0);
// validate: all email has @
if (atPos == -1)
   return false;
// validate: email should not start with @
if (atPos == 0)
   return false;
// validate: email has 1 and only 1 @
if (addr.indexOf('@', atPos + 1) > - 1)
   return false;

// validate: email should have . (for domain)
if (addr.indexOf('.', atPos) == -1)
   return false;
// validate: period should not follow @ (e.g. abc@.com)
if (addr.indexOf('@.',0) != -1)
   return false;
// validate: period should not lead @ (e.g. abc.@abc.com)
if (addr.indexOf('.@',0) != -1)
   return false;
// validate: email should not have 2 periods together (e.g. ab..c@abc.com)
if (addr.indexOf('..',0) != -1) 
   return false;
   
// validate: suffix/domain - length 2 or more
var suffix = addr.substring(addr.lastIndexOf('.')+1);
if (suffix.length <2)
   return false;
   
// all validation passed
return true;
}

/********************************************************
 * setSelect
 *  argument: elem - select element, val - target value
 *  search through select element's options, and set Index to target value
 ********************************************************/
function setSelect(elem, val) {
 	var opts = elem.options;
 	for (var i=0; i<opts.length; i++)
 		if (opts[i].value == val) {
			elem.selectedIndex = i;
			return false;
		}
	// if not found, reset to Index 0
	elem.selectedIndex =0;
}

/********************************************************
 * isMobile
 *  detect if the browser is mobile
********************************************************/
function isMobile() {
	var isMobile = {
		Android: function() {
			return navigator.userAgent.match(/Android/i);
		},
		BlackBerry: function() {
			return navigator.userAgent.match(/BlackBerry/i);
		},
		iOS: function() {
			return navigator.userAgent.match(/iPhone|iPad|iPod/i);
		},
		Opera: function() {
			return navigator.userAgent.match(/Opera Mini/i);
		},
		Windows: function() {
			return navigator.userAgent.match(/IEMobile/i);
		},
		any: function() {
			return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
		}
	};
	if(isMobile.any())
		return true;
	try{ document.createEvent("TouchEvent"); return true; } catch(e) { return false; }
}