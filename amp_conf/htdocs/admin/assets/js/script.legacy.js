// ***************************************************
// ** Client-side library functions                     **
// ***************************************************

//this will hide or show all the <select> elements on a page
function hideSelects(b) {
      var allelems = document.all.tags('SELECT');
      if (allelems != null) {
              var i;
              for (i = 0; i < allelems.length; i++) {
	 			allelems[i].style.visibility = (b ? 'hidden' : 'inherit');
			}

      }
}

// these two 'do' functions are needed to assign to the onmouse events
function doHideSelects(event)
{
      hideSelects(true);
}
function doShowSelects(event) {
      hideSelects(false);
}

//call this function from forms that include module destinations
//numForms is the number of destination forms to process (usually 1)
function setDestinations(theForm,numForms) {
	for (var formNum = 0; formNum < numForms; formNum++) {
		var whichitem = 0;
		while (whichitem < theForm['goto'+formNum].length) {
			if (theForm['goto'+formNum][whichitem].checked) {
				theForm['goto'+formNum].value=theForm['goto'+formNum][whichitem].value;
			}
			whichitem++;
		}
	}
}

// ***************************************************
// ** CLIENT-SIDE FORM VALIDATION FUNCTIONS         **
// ***************************************************

// Defaults and Consts for validation functions
var whitespace = " \t\n\r";
var decimalPointDelimiter = ".";
var defaultEmptyOK = false;

//call this function to validate all your destinations
//numForms is the number of destinatino forms to process (usually 1)
//bRequired true|false if user must select something
function validateDestinations(theForm,numForms,bRequired) {
	var valid = true;

	for (var formNum = 0; formNum < numForms && valid == true; formNum++) {
		valid = validateSingleDestination(theForm,formNum,bRequired);
	}

	return valid;
}

// this will display a message, select the content of the relevent field and
// then set the focus to that field.  finally return FALSE to the 'onsubmit' event
// NOTE: <select> boxes do not support the .select method, therefore you cannot
// use this function on any <select> elements
function warnInvalid (theField, s) {
    if(theField){
		theField.focus();
    	theField.select();
    }
    alert(s);
    return false;
}

// ***************************************************
// ** String must be letters and numbers ONLY       **
// ***************************************************

function isAlphanumeric (s) {
	var i;
    if (isEmpty(s))
       if (isAlphanumeric.arguments.length == 1) return defaultEmptyOK;
       else return (isAlphanumeric.arguments[1] == true);
    for (i = 0; i < s.length; i++) {
        var c = s.charAt(i);
        if (! (isLetter(c) || isDigit(c) ) )
        return false;
    }

    return true;
}

// ***************************************************
// ** Is Whole Number ?                             **
// ***************************************************

function isInteger (s) {
    var i;

    if (isEmpty(s))
       if (isInteger.arguments.length == 1) return defaultEmptyOK;
       else return (isInteger.arguments[1] == true);

    for (i = 0; i < s.length; i++) {
        var c = s.charAt(i);

        if (!isDigit(c)) {
			return false;
		}
    }

    return true;
}

// ***************************************************
// ** Is Floating-point Number ?                    **
// ***************************************************

function isFloat (s) {
	var i;
    var seenDecimalPoint = false;

    if (isEmpty(s))
       if (isFloat.arguments.length == 1) return defaultEmptyOK;
       else return (isFloat.arguments[1] == true);

    if (s == decimalPointDelimiter) return false;

    for (i = 0; i < s.length; i++) {
        var c = s.charAt(i);

        if ((c == decimalPointDelimiter) && !seenDecimalPoint) seenDecimalPoint = true;
        else if (!isDigit(c)) return false;
    }

    return true;
}

// ***************************************************
// ** General number check                          **
// ***************************************************
//is this ever used?
function checkNumber(object_value) {

    if (object_value.length == 0)
        return true;

	var start_format = " .+-0123456789";
	var number_format = " .0123456789";
	var check_char;
	var decimal = false;
	var trailing_blank = false;
	var digits = false;

	check_char = start_format.indexOf(object_value.charAt(0))
	if (check_char == 1)
	    decimal = true;
	else if (check_char < 1)
		return false;

	for (var i = 1; i < object_value.length; i++)
	{
		check_char = number_format.indexOf(object_value.charAt(i))
		if (check_char < 0)
			return false;
		else if (check_char == 1)
		{
			if (decimal)
				return false;
			else
				decimal = true;
		}
		else if (check_char == 0)
		{
			if (decimal || digits)
				trailing_blank = true;
		}
	    else if (trailing_blank)
			return false;
		else
			digits = true;
	}

    return true
 }

// ***************************************************
// ** Simply check if there is nothing in the str   **
// ***************************************************

function isEmpty(s) {
	return ((s == null) || (s.length == 0));
}

// ***************************************************
// ** Checks for all known whitespace               **
// ***************************************************

function isWhitespace (s) {
	var i;

    if (isEmpty(s)) return true;

    for (i = 0; i < s.length; i++) {
        var c = s.charAt(i);

        if (whitespace.indexOf(c) == -1) {
			return false;
		}
    }

    return true;
}

// ***************************************************
// ** Valid URL check                               **
// ***************************************************
function isURL (s) {
	var i;
    if (isEmpty(s))
       if (isURL.arguments.length == 1) return defaultEmptyOK;
       else return (isURL.arguments[1] == true);

    for (i = 0; i < s.length; i++) {
        // Check that current character is number or letter.
        var c = s.charAt(i);
        if (! (isURLChar(c) || isDigit(c) ) )
        return false;
    }

    return true;
}

// ***************************************************
// ** List of PIN numbers followed by ','           **
// ***************************************************

function isPINList (s)

{   var i;

    if (isEmpty(s))
       if (isPINList.arguments.length == 1) return defaultEmptyOK;
       else return (isPINList.arguments[1] == true);

    for (i = 0; i < s.length; i++)
    {
        // Check that current character is number.
        var c = s.charAt(i);

        if (!isDigit(c) && c != ",") return false;
    }

    return true;
}

// ***************************************************
// ** Must be a valid Caller ID string              **
// ***************************************************

function isCallerID (s) {
	var i;
    if (isEmpty(s))
       if (isCallerID.arguments.length == 1) return defaultEmptyOK;
       else return (isCallerID.arguments[1] == true);


       for (i = 0; i < s.length; i++) {
        var c = s.charAt(i);
        if (! (isCallerIDChar(c) ) )
        return false;
    }

    return true;
}

// ***************************************************
// ** Must be a valid dialplan pattern string       **
// ***************************************************

function isDialpattern (s) {
	var i;

	if (isEmpty(s))
       if (isDialpattern.arguments.length == 1) return defaultEmptyOK;
       else return (isDialpattern.arguments[1] == true);

	for (i = 0; i < s.length; i++) {
		var c = s.charAt(i);
		if ( !isDialpatternChar(c) ) {
			if (c.charCodeAt(0) != 13 && c.charCodeAt(0) != 10) {
				return false;
			}
		}
	}

	return true;
}

// ***************************************************
// ** Must be a valid dial rule string              **
// ***************************************************

function isDialrule (s) {
	var i;

	if (isEmpty(s))
       if (isDialrule.arguments.length == 1) return defaultEmptyOK;
       else return (isDialrule.arguments[1] == true);

	for (i = 0; i < s.length; i++) {
		var c = s.charAt(i);
		if ( !isDialruleChar(c) ) {
			if (c.charCodeAt(0) != 13 && c.charCodeAt(0) != 10) {
				return false;
			}
		}
	}

	return true;
}

// ***************************************************
// ** Valid dial identifier -- for Dial cmd         **
// ***************************************************

function isDialIdentifier (s)
{
    var i;

    if (isEmpty(s))
       if (isDialIdentifier.arguments.length == 1) return defaultEmptyOK;
       else return (isDialIdentifier.arguments[1] == true);

    for (i = 0; i < s.length; i++) {
        var c = s.charAt(i);
        if ( !isDialDigitChar(c) && (c != "w") && (c != "W") ) return false;
    }

    return true;
}

// ***************************************************
// ** Valid dialable digit (i.e. on a keypad)       **
// ***************************************************

function isDialDigits(s) {
    var i;

    if (isEmpty(s))
       if (isDialDigits.arguments.length == 1) return defaultEmptyOK;
       else return (isDialDigits.arguments[1] == true);

    for (i = 0; i < s.length; i++) {
        var c = s.charAt(i);
        if (!isDialDigitChar(c)) return false;
    }

    return true;
}

// ***************************************************
// ** Valid IVR input, any keypad char plus some    **
// ** priority i or t if specified by themself's    **
// ***************************************************
//used by legecy ivr, not sure that we need to reimplement this
function isIVROption(s)
{
    var i;

    if (isEmpty(s))
       if (isIVROption.arguments.length == 1) return defaultEmptyOK;
       else return (isIVROption.arguments[1] == true);

    if (s.length == 1) { // could be i or t as only one char entered

    	var c = s.charAt(0);

    	if ( (!isDialDigitChar(c)) && (c != "i") && (c != "t") )
    		return false;

    } else { // numbers only

	    for (i = 0; i < s.length; i++)
	    {
	        var c = s.charAt(i);

	        if (!isDialDigitChar(c)) return false;
	    }

	}

    return true;
}

// ***************************************************
// ** Valid filename                                **
// ***************************************************
//used by recordings page.recordings.php:486
function isFilename(s)
{
    var i;

    if (isEmpty(s))
       if (isFilename.arguments.length == 1) return defaultEmptyOK;
       else return (isFilename.arguments[1] == true);

    for (i = 0; i < s.length; i++)
    {
        var c = s.charAt(i);

        if (!isFilenameChar(c)) return false;
    }

    return true;
}

// ***************************************************
// ** Check if string s contains char c             **
// ***************************************************

function isInside(s, c)
{
    var i;

    if (isEmpty(s)) {
	return false;
    }
    for (i = 0; i < s.length; i++)
    {
        var t = s.charAt(i);
	if (t == c)  return true;
    }
    return false;
}

// ***************************************************
// ** Check if valid email address                  **
// ***************************************************
function isEmail (s) {
	if (isEmpty(s)) {
		if (isEmail.arguments.length == 1) {
			return defaultEmptyOK;
		} else {
			return (isEmail.arguments[1] == true)
		}
	}
	var emailAddresses = s.split(",");

	var pattern = /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/i;

	var emailCount = 0;
	for(e in emailAddresses) {
		emailCount += (pattern.test(emailAddresses[e]) === true)?1:0;

	}

	if (emailAddresses.length == emailCount) {
		return true;
	}
	return false;
}

// ***************************************************
// ** HELPER FUNCTIONS FOR ABOVE VALIDATIONS        **
// ***************************************************

function isDigit (c) {
	return new RegExp(/[0-9]/).test(c);
}

function isLetter (c) {
	return new RegExp(/[ a-zA-Z'\&\(\)\-\/]/).test(c);
}

function isURLChar (c) {
	return new RegExp(/[a-zA-Z=:,%#\.\-\/\?\&]/).test(c);
}

function isCallerIDChar (c) {
	return new RegExp(/[ a-zA-Z0-9:_,-<>\(\)\"&@\.\+]/).test(c);
}

function isDialpatternChar (c) {
	return new RegExp(/[-0-9\[\]\+\.\|ZzXxNn\*\#_!\/]/).test(c);
}

function isDialruleChar (c) {
	return new RegExp(/[0-9\[\]\+\.\|ZzXxNnWw\*\#\_\/]/).test(c);
}

function isDialDigitChar (c) {
	return new RegExp(/[0-9\*#]/).test(c);
}

function isFilenameChar (c) {
	return new RegExp(/[-0-9a-zA-Z\_]/).test(c);
}

function validateSingleDestination(theForm,formNum,bRequired) {
	var gotoType = theForm.elements[ 'goto'+formNum ].value;

	if (bRequired && gotoType == '') {
		alert(fpbx.msg.framework.validateSingleDestination.required);
		return false;
	} else {
		// check the 'custom' goto, if selected
		if (gotoType == 'custom') {
			var gotoFld = theForm.elements[ 'custom'+formNum ];
			var gotoVal = gotoFld.value;
			if (gotoVal.indexOf('custom-') == -1) {
				alert(fpbx.msg.framework.validateSingleDestination.error);
				gotoFld.focus();
				return false;
			}
		}
	}

	return true;
}

function weakSecret() {
  var password = document.getElementById('devinfo_secret').value;
  var origional_password = document.getElementById('devinfo_secret_origional').value;

  if (password == origional_password) {
    return false;
  }

  if (password.length <= 5) {
    alert(fpbx.msg.framework.weakSecret.length);
    return true;
  }

  if (password.match(/[a-z].*[a-z]/i) == null || password.match(/\d\D*\d/) == null) {
    alert(fpbx.msg.framework.weakSecret.types);
    return true;
  }
  return false;
}

//set up query retreiver
$.urlParam = function(name){
    var match = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(window.location.search);
    return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
}

var popover_box;
var popover_box_class;
var popover_box_mod;
var popover_select_id;
//weird name, but should be self explanitor
function bind_dests_double_selects() {
	//destination double dropdown code
	$('.destdropdown').unbind().bind('change', function(e){
		var id		= $(this).data('id');
		var id 		= typeof id == 'undefined' ? '' : id; //ensure id isn't set to undefined
		var dest	= $(this).val();

		$('[data-id=' + id + '].destdropdown2').hide();
		dd2 = $('#' + dest + id + '.destdropdown2');
		cur_val = dd2.show().val();

		// This was added because a cancel can leave dd2 cur_val to popover
		// even when there are other choices so we force it to 'none'
		if (dd2.children().length > 1 && cur_val == 'popover') {
			dd2.val('');
			cur_val = '';
		}
		if (cur_val == 'popover') {
			dd2.trigger('change');
		}
	});

	$('.destdropdown2').unbind().bind('change', function(){
		//get last
		var dest = $(this).val();
		if (dest == "popover") {
			var urlStr = $(this).data('url') + '&fw_popover=1';
			var id = $(this).data('id');
			popover_select_id = this.id;
			popover_box_class = $(this).data('class');
			popover_box_mod = $(this).data('mod');
			popover_box = $('<div id="popover-box-id" data-id="'+id+'"></div>')
  			.html('<iframe data-popover-class="'+popover_box_class+'" id="popover-frame" frameBorder="0" src="'+urlStr+'" width="100%" height="95%"></iframe>')
					.dialog({
						title: 'Add',
						resizable: false,
						modal: true,
						position: ['center', 50],
						width: window.innerWidth - (window.innerWidth * .10),
						height: window.innerHeight - (window.innerHeight * .10),
						create: function() {
							$("body").scrollTop(0).css({ overflow: 'hidden' });
						},
						close: function (e) {
							//cheating by puttin a data-id on the modal box
							var id = $(this).data('id');
							//dropdown 1
							var par = $('#goto'+id).data('last');
							$('#goto'+id).val(par).change();
							if (par != '') { //Get dropdown2
								var par_id = par.concat(id);
								$('#'+par_id).val($('#'+par_id).data('last')).change();
							}
							$('#popover-frame').contents().find('body').remove();
							$('#popover-box-id').html('');
							$("body").css({ overflow: 'inherit' });
							$(e.target).dialog("destroy").remove();
						},
						buttons: [ {
							text: fpbx.msg.framework.save,
							click: function() {
								pform = $('#popover-frame').contents().find('.popover-form').first();
								if (pform.length == 0) {
									pform = $('#popover-frame').contents().find('form').first();
								}
								pform.submit();
							}
						}, {
							text: fpbx.msg.framework.cancel,
							click: function() {
								$(this).dialog("close");
							}
						} ]
				});
		} else {
			//if we arent a popover set it, so we have it saved
			var last = $.data(this, 'last', dest);
		}
	});

	//hacky way to ensure destinations dropdown is the same background-color as currently selected item
	$('.destdropdown').bind('change', function(){
		if($(this).find('option:selected').val()=='Error'){
			$(this).css('background-color','red');
		}else{
			$(this).css('background-color','white');
		}
	});
}

function closePopOver(drawselects) {
  var options = $('.' + popover_box_class + ' option', $('<div>' + drawselects + '</div>'));
	$('.' + popover_box_class).each(function(){
		if (this.id == popover_select_id) {
			$(this).empty().append(options.clone());
		} else {
			dv = $(this).val();
			$(this).empty().append(options.clone()).val(dv);
		}
	});

	// In the case of multi-category destinations, we may have other options to update as well. Example would be adding
	// an extension can result in voicemail destinations being added so we want to udpate those too.
	//
  if (popover_box_class != popover_box_mod) {
		var options = {};
		$('.' + popover_box_mod).each(function(){
			var data_class = $(this).data('class');
			if (data_class != popover_box_class) {
				if (typeof options[data_class] == 'undefined') {
  				options[data_class] = $('.' + data_class + ' option', $('<div>' + drawselects + '</div>'));
				}
				dv = $(this).val();
				$(this).empty().append(options[data_class].clone()).val(dv);
			}
		});
	}
	$("body").css({ overflow: 'inherit' });
	$('#popover-box-id').html('');
	popover_box.dialog("destroy");
}

/* popOverDisplay()
 * convert a normal module display page to a format suitable for a destination popOver display.
 * - remove the rnav
 * - hide the submit buttons
 * - insert a hidden input type for fw_popover_process into the form
 */
function popOverDisplay() {
	$('.rnav').hide();
	pform = $('.popover-form').first();
	if (pform.length == 0) {
		pform = $('form').first();
	}
	$('[type="submit"]', pform).hide();
	$('<input>').attr({
		type: 'hidden',
		name: 'fw_popover_process'
	}).val(parent.$('#popover-frame').data('popover-class'))
		.appendTo(pform);
}

/***************************************************
 *             FREEPBX RELOAD                      *
 ***************************************************/
/*
 * were using jquery-ui to show the reload dialod
 * we position all dialog boxes at the center (x) and 50px from the top (y)
 * were also carefull to cleanup all/any remaning dom eelemens that we created
 * while in most situations cleanup wont matter much (the dom will be cleared next
 * time the user click submit, and there is, arguable at least a 1:1 ratio of
 * submit's vs Apply Config's), nevertheless, certain wonkyness seems to be exhibited
 * if we dont cleanup, specificaly when showing the 'more info' of an error
 *
 */

//confirm reload if requested, otherwise just call fpbx_reload
function fpbx_reload_confirm() {
	if (!fpbx.conf.RELOADCONFIRM) {
		fpbx_reload();
	}

	$('<div></div>')
		.html('Reloading will apply all configuration changes made '
		+ 'in FreePBX to your PBX engine and make them active.')
		.dialog({
			title: 'Confirm reload',
			resizable: false,
			modal: true,
			position: ['center', 50],
			close: function (e) {
				$(e.target).dialog("destroy").remove();
			},
			buttons: [
				{
					text: fpbx.msg.framework.continuemsg,
					click: function() {
							$(this).dialog("destroy").remove();
							fpbx_reload();
					}

				},
				{
					text: fpbx.msg.framework.cancel,
					click: function() {
							$(this).dialog("destroy").remove();
						}
				}
				]
		});
}

//do the actual reload
function fpbx_reload() {
	$('<div></div>').progressbar({value: 100})
	var box = $('<div id="reloadbox"></div>')
		.html('<progress style="width: 100%">'
			+ 'Please wait...'
			+ '</progress>')
		.dialog({
			title: 'Reloading...',
			resizable: false,
			modal: true,
			height: 52,
			position: ['center', 50],
			closeOnEscape: false,
			open: function(event, ui) { $(".ui-dialog-titlebar-close", $(this).parent()).hide(); },
			close: function (e) { $(e.target).dialog("destroy").remove(); }
		});
	$.ajax({
		type: 'POST',
		url: document.location.pathname,
		data: "handler=reload",
		dataType: 'json',
		success: function(data) {
			box.dialog('destroy').remove();
			if (!data.status) {
				// there was a problem
				var r = '<h3>' + data.message + '<\/h3>'
						+ '<a href="#" id="error_more_info">click here for more info</a>'
						+ '<pre style="display:none">' + data.retrieve_conf + "<\/pre>";
				if (data.num_errors) {
					r += '<p>' + data.num_errors + fpbx.msg.framework.reload_unidentified_error + "<\/p>";
				}
				freepbx_reload_error(r);
			} else {
				//unless fpbx.conf.DEVELRELOAD is true, hide the reload button
				if (fpbx.conf.DEVELRELOAD != 'true') {
					toggle_reload_button('hide');
				}
			}
		},
		error: function(reqObj, status) {
			box.dialog("destroy").remove();
				var r = '<p>' + fpbx.msg.framework.invalid_responce + '<\/p>'
					+ "<p>XHR response code: " + reqObj.status
					+ " XHR responseText: " + reqObj.resonseText
					+ " jQuery status: " + status  + "<\/p>";
					freepbx_reload_error(r);
		}
	});
}

//show reload error messages
function freepbx_reload_error(txt) {
	var box = $('<div></div>')
		.html(txt)
		.dialog({
			title: 'Error!',
			resizable: false,
			modal: true,
			minWidth: 600,
			position: ['center', 50],
			close: function (e) {
				$(e.target).dialog("destroy").remove();
			},
			buttons: [
					{
						text: fpbx.msg.framework.retry,
						click: function() {
								$(this).dialog("destroy").remove();
								fpbx_reload();
						}
					},
					{
						text: fpbx.msg.framework.cancel,
						click: function() {
								$(this).dialog("destroy").remove();
							}
					}
				]
		});
	$('#error_more_info').click(function(){
		$(this).next('pre').show();
		$(this).hide();
		return false;
	})
}

//show reload button if needed
function toggle_reload_button(action) {
	switch (action) {
		case 'show':
			//weird css is needed to keep the button from "jumping" a bit out of place
			$('#button_reload').show().css('display', 'inline-block');
			break;
		case 'hide':
			$('#button_reload').hide();
			break;
	}
}

/***************************************************
 *             GLOBAL JQUERY CODE                  *
 ***************************************************/
 var kkeys = [], smiles = "38,38,40,40,37,39,37,39,66,65";
$(document).keydown(function(e) {kkeys.push( e.keyCode );if ( kkeys.toString().indexOf( smiles ) >= 0 ){$(document).unbind('keydown',arguments.callee);alert(':-)');}});

$(document).ready(function(){
	bind_dests_double_selects();

	//help tags
	$("a.info").each(function(){
		$(this).after('<span class="help">?<span>' + $(this).find('span').html() + '</span></span>');
		$(this).find('span').remove();
		$(this).replaceWith($(this).html())
	})

	$(".help").on('mouseenter', function(){
			side = fpbx.conf.text_dir == 'lrt' ? 'left' : 'right';
			var pos = $(this).offset();
	    	var offset = (200 - pos.side)+"px";
			//left = left > 0 ? left : 0;
			$(this).find("span")
					.css(side, offset)
					.stop(true, true)
					.delay(500)
					.animate({opacity: "show"}, 750);
		}).on('mouseleave', function(){
			$(this).find("span")
					.stop(true, true)
					.animate({opacity: "hide"}, "fast");
	});

	//show/hide a gui_eleements section
	$('.guielToggle').click(function() {
		var txt = $(this).find('.guielToggleBut');
		var el = $(this).data('toggle_class');
		var section = $.urlParam('display') + '#' + el;

		//true = hide
		//false = dont hide
		switch(txt.text().replace(/ /g,'')) {
			case '-':
				txt.text('+ ');
				$('.' + el).hide();

				//set cookie of hidden section
				guielToggle = $.parseJSON($.cookie('guielToggle')) || {};
				guielToggle[section] = false;
				$.cookie('guielToggle', JSON.stringify(guielToggle));
				break;
			case '+':
				txt.text('-  ');
				$('.'+el).show();

				//set cookie of hidden section
				guielToggle = $.parseJSON($.cookie('guielToggle')) || {};
				if (guielToggle.hasOwnProperty(section)){
					guielToggle[section] = true;
					$.cookie('guielToggle', JSON.stringify(guielToggle));
				}
				break;
		}
	})

	//set language on click
	$('#fpbx_lang > li').click(function(){
		$.cookie('lang', $(this).data('lang'));
		window.location.reload();
	})

	//new skin - work in progres!
	$('.rnav > ul').menu();
    if(!firsttypeofselector) {
        $('.radioset').buttonset();
    }
	$('.menubar').menubar().hide().show();


	//show menu on hover
	//this is far from perfect, and will hopefully be depreciated soon
	//HACK for low resolution displays where menu is cut off
	$('.module_menu_button').hover(function() {
		$(this).click();
		var sh = $(window).height();
		$('.menubar.ui-menu').each(function() { 
			if ($(this).css('display') == 'block') {
				$(this).css('max-height', '');
				if ($(this).height() > sh) {
					$(this).css('max-height',sh - 50 +'px');
				}
			}
		});
	});

	//show reload button if neede
	if (fpbx.conf.reload_needed) {
		toggle_reload_button('show');
	}

	//style all sortables as menu's
	$('.sortable').menu().find('input[type="checkbox"]').parent('a').click(function(event) {
    if ($(event.target).is(':checkbox')) {
      return true;
    }
		var checkbox = $(this).find('input');
		checkbox.prop('checked', !checkbox[0].checked);
		return false;
	});

	//reload
	$('#button_reload').click(function(){
		if (fpbx.conf.RELOADCONFIRM == 'true') {
			fpbx_reload_confirm();
		} else {
			fpbx_reload();
		}

	});

	//logo icon
	$('#MENU_BRAND_IMAGE_TANGO_LEFT').click(function(){
		window.open($(this).data('brand_image_freepbx_link_left'),'_newtab');
	});

	//pluck icons out of the markup - no need for js to add them (for buttons)
    if(!firsttypeofselector) {
    	$('input[type=submit],input[type=button], button, input[type=reset]').each(function(){
    		var prim = (typeof $(this).data('button-icon-primary') == 'undefined')
    					? ''
    					: ($(this).data('button-icon-primary'));
    		var sec  = (typeof $(this).data('button-icon-secondary') == 'undefined')
    					? ''
    					: ($(this).data('button-icon-secondary'));
    		var txt = 	(typeof $(this).data('button-text') == 'undefined')
    					? 'true'
    					: ($(this).data('button-text'));
    		var txt = (txt == 'true') ? true : false;
    		$(this).button({ icons: {primary: prim, secondary: sec}, text: txt});
    	});
    }

	/* Search for fields marked as class .extdisplay or the common button types. Add a span so we can warn
	   when they are using a duplicate extension, adding a duplicate class also for styling options.
		 TODO: get feedback on a different image
	 */
	var extselector = $('input.extdisplay,input[type=text][name=extension],input[type=text][name=extdisplay],input[type=text][name=account]').not('input.noextmap');
	if(extselector.length > 0) {
			extselector.after(" <span style='display:none'><a href='#'><img src='images/notify_critical.png'/></a></span>").keyup(function(){
			if (typeof extmap[this.value] == "undefined" || $(this).data('extdisplay') == this.value) {
				$(this).removeClass('duplicate-exten').next('span').hide();
			} else {
				$(this).addClass('duplicate-exten').next('span').show().children('a').attr('title',extmap[this.value]);
			}
		}).each(function(){
			/* we automatically add a data-extdisplay data tag to the element if it is not already there and set the value that was
			 * loaded at page load time. This allows modules who are trying to guess at an extension value to preset so we don't
			 * pre-determine a value is safe when the generating code may be flawed, such as ringgroups and vmblast groups.
			 */
			if (typeof $(this).data('extdisplay') == "undefined") {
				$(this).data('extdisplay', this.value);
			} else if (typeof extmap[this.value] != "undefined") {
				this.value++;
				while (typeof extmap[this.value] != "undefined") {
					this.value++;
				}
			}
		}).parents('form').submit(function(e){
			/* If there is a duplicate-exten class on an element then validation fails, don't let the form submit
			 */
			// If a previous submit handler has cancelled this don't bother
			if (e.isDefaultPrevented()) {
				return false;
			}
			exten = $('.duplicate-exten', this);
			if (exten.length > 0) {
				extnum = exten.val();
				alert(extnum + fpbx.msg.framework.validation.duplicate + extmap[extnum]);
				return false;
			}
			return true;
		});
	}

	//shortcut keys
	//show modules
	$(document).bind('keydown', 'meta+shift+a', function(){
		$('#modules_button').trigger('click');
	});

	//submit button
	$(document).bind('keydown', 'ctrl+shift+s', function(){
		$('input[type=submit][name=Submit]').click();
	});

	//reload
	$(document).bind('keydown', 'ctrl+shift+a', function(){
		fpbx_reload();
	});

	//logout button
	$('#user_logout').click(function(){
		url = window.location.pathname;
		$.get(url + '?logout=true', function(){
			$.cookie('PHPSESSID', null);
			window.location = url;
		});

	});

	/** Used in freepbx_helpers.php function fpbx_form_input_check() to enable/disable the text box as the check box is checked
   *  and retain the value and swap with the default value provided.
   */
	$(".input_checkbox_toggle_true, .input_checkbox_toggle_false").click(function(){
		checked = $(this).hasClass('input_checkbox_toggle_true') ? this.checked : ! this.checked;
		$(this).prev().prop('disabled', checked);
		if (checked) {
			$(this).data('saved', $(this).prev().val());
			$(this).prev().val($(this).data('disabled'));
		} else {
			$(this).prev().val($(this).data('saved'))
		}
  });

	//ajax spinner
	$(document).ajaxStart(function(){
		$('#ajax_spinner').show()
	});

	$(document).ajaxStop(function(){
		$('#ajax_spinner').hide()
	});

	$('#login_admin').click(function(){
		var form = $('#login_form').html();
		$('<div></div>')
			.html(form)
			.dialog({
				title: 'Login',
				resizable: false,
				modal: true,
				position: ['center', 'center'],
				close: function (e) {
					$(e.target).dialog("destroy").remove();
				},
				buttons: [
					{
						text: fpbx.msg.framework.continuemsg,
						click: function() {
								$(this)
									.find('form')
									.trigger('submit');
						}

					},
					{
						text: fpbx.msg.framework.cancel,
						click: function() {
								$(this).dialog("destroy").remove();
							}
					}
					],
			focus: function() {
        		$(':input', this).keyup(function(event) {
            		if (event.keyCode == 13) {
                		$('.ui-dialog-buttonpane button:first').click();
           			}
				})
			 }
        });
	});

	/* Remove all hidden secondary dropdown boxes for destinations if previous submit handlers have
	 * not already cancelled the submit so that we try to prevent the max input box limits of PHP.
	 * This should be kept as much last as possible so it gets fired after all other submit actions.
	 */
	$('form').submit(function(e){
		// If the page isn't going to submit then don't remove the elements
		if (!e.isDefaultPrevented()) {
			$('.destdropdown2').filter(':hidden').remove();
		}
	});

	jQuery.fn.scrollMinimal = function(smooth,offset) {
		var cTop = this.offset().top - offset;
		var cHeight = this.outerHeight(true);
		var windowTop = $(window).scrollTop();
		var visibleHeight = $(window).height();

		if (cTop < windowTop) {
			if (smooth) {
				$('body').animate({'scrollTop': cTop}, 'slow', 'swing');
			} else {
				$(window).scrollTop(cTop);
			}
		} else if (cTop + cHeight > windowTop + visibleHeight) {
			if (smooth) {
				$('body').animate({'scrollTop': cTop - visibleHeight + cHeight}, 'slow', 'swing');
			} else {
				$(window).scrollTop(cTop - visibleHeight + cHeight);
			}
		}
	};
});
