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
	return new RegExp(/[a-zA-Z'\&\(\)\-\/]/).test(c);
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

/***************************************************
 *             GLOBAL JQUERY CODE                  *
 ***************************************************/
//weird name, but should be self explanitor
function bind_dests_double_selects() {
	//destination double dropdown code
	$('.destdropdown').unbind().bind('blur click change keypress', function(){
		var name=$(this).attr('name');
		var id=name.replace('goto','');
		var dest=$(this).val();
		$('[name$='+id+'].destdropdown2').hide();
		$('[name='+dest+id+'].destdropdown2').show();
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

$(document).ready(function(){
	bind_dests_double_selects();
	
	//help tags. based on: http://www.dvq.co.nz/jquery/create-a-jquery-popup-bubble-effect/
	$("a.info").hover(function(){
		var pos = $(this).offset();
    	var left = (200 - pos.left)+"px";
		$(this).find("span").css("left",left).stop(true, true).delay(500).animate({opacity: "show"}, 750);
		}, function() {
		$(this).find("span").stop(true, true).animate({opacity: "hide"}, "fast");
	});

	//module setup/tools menu
	$('#nav').tabs({cookie:{expires:30}});

	// initialize the displayed/hidden nav bar categories
	$(".category-header").each(function(){
		if ($.cookie(this.id) == 'collapsed') {
			$(".id-"+this.id).hide();
			$(this).removeClass("toggle-minus").addClass("toggle-plus")
			$.cookie(this.id,'collapsed', { expires: 365 });
		} else {
			$(".id-"+this.id).show();
			$(this).removeClass("toggle-plus").addClass("toggle-minus")
			$.cookie(this.id,'expanded', { expires: 365 });
		}
	});

  //slide open/closed each section
	$(".category-header").click(function(){
		if ($.cookie(this.id) == 'expanded') {
			$(".id-"+this.id).slideUp();
			$.cookie(this.id,'collapsed', { expires: 365 });
			$(this).removeClass("toggle-minus").addClass("toggle-plus")
	    } else {
			$(".id-"+this.id).slideDown();
			$.cookie(this.id,'expanded', { expires: 365 });
			$(this).removeClass("toggle-plus").addClass("toggle-minus")
		}
	});

	//show/hide a gui_eleements section
	$('.guielToggle').click(function() {
		var txt = $(this).find('.guielToggleBut');
		var el = $(this).data('toggleClass')
		switch(txt.text().replace(/ /g,'')) {
			case '-':
				txt.text('+ ');
				$('.'+el).hide()
				break;
			case '+':
				txt.text('-  ');
				$('.'+el).show();
				break;
		}
	})

});