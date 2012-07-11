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

//weird name, but should be self explanitor
function bind_dests_double_selects() {
	//destination double dropdown code
	$('.destdropdown').unbind().bind('blur click change keypress', function(){
		var name	= $(this).attr('name');
		var id		= $(this).data('id');
		var id 		= typeof id == 'undefined' ? '' : id;//ensure id isnt set to undefined
		var dest	= $(this).val();
		$('[data-id=' + id + '].destdropdown2').hide();
		$('[name=' + dest + id + '].destdropdown2').show();
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
	var box = $('<div></div>')
		.html('<progress style="width: 100%">'
			+ 'Please wait...'
			+ '</progress>')
		.dialog({
			title: 'Reloading...',
			resizable: false,
			modal: true,
			height: 50,
			position: ['center', 50],
			close: function (e) {
				$(e.target).dialog("destroy").remove();
			}
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
$(document).ready(function(){
	bind_dests_double_selects();
	
	//help tags
	$("a.info").each(function(){
		$(this).after('<span class="help">?<span>' + $(this).find('span').html() + '</span></span>');
		$(this).find('span').remove();
		$(this).replaceWith($(this).html())
	})
	
	$(".help").live('mouseenter', function(){
			side = fpbx.conf.text_dir == 'lrt' ? 'left' : 'right';
			var pos = $(this).offset();
	    	var offset = (200 - pos.side)+"px";
			//left = left > 0 ? left : 0;
			$(this).find("span")
					.css(side, offset)
					.stop(true, true)
					.delay(500)
					.animate({opacity: "show"}, 750);
		}).live('mouseleave', function(){
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
	$('.radioset').buttonset();
	$('.menubar').menubar().hide().show();
	
	
	//show menu on hover
	//this is far from perfect, and will hopefully be depreciated soon
	$('.module_menu_button').hover(
		function(){
			$(this).click()
		},
		function(){
			
		});
		
	//show reload button if neede
	if (fpbx.conf.reload_needed) {
		toggle_reload_button('show');
	}
	
	//style all sortables as menu's
	$('.sortable').menu().find('input[type="checkbox"]').click(function(event) { 
		event.stopPropagation(); 
	});
	
	//Links are disabled in menu for now. Final release will remove that
	$('.ui-menu-item').click(function(){
		go = $(this).find('a').attr('href');
		if(go && !$(this).find('a').hasClass('ui-state-disabled')) {
			document.location.href = go;
		}
	})
	
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
	
	//ajax spinner
	$(document).ajaxStart(function(){
		$('#ajax_spinner').show()
	});
	
	$(document).ajaxStop(function(){
		$('#ajax_spinner').hide()
	});
});
