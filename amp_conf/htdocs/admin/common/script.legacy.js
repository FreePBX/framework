// ***************************************************
// ** Client-side Browser Detection                 **
// ***************************************************

Is_DOM = (document.getElementById) ? true : false;
Is_NS4 = (document.layers) ? true : false;
Is_IE = (document.all) ? true : false;
Is_IE7 = Is_IE && window.XMLHttpRequest ? true : false;

// detect for konqueror, taken from
// http://www.javascript-source.com/
var detect = navigator.userAgent.toLowerCase();
if (checkIt('konqueror'))
        Is_IE = false;

function checkIt(string)
{
	place = detect.indexOf(string) + 1;
	thestring = string;
	return place;
}

Is_IE4 = Is_IE && !Is_DOM;
Is_Mac = (navigator.appVersion.indexOf("Mac") != -1);
Is_IE4M = Is_IE4 && Is_Mac;


// override getElementsByTagName with an IE-compatible function
function ie_getElementsByTagName(str) {
 if (str=="*")
  return document.all
 else
  return document.all.tags(str)
}
if (document.all)
 document.getElementsByTagName = ie_getElementsByTagName
 
 
// ***************************************************
// ** Client-side library functions                     **
// ***************************************************

function menu_popup(mylink, windowname)
{
	if (! window.focus)return true;
	var href;
	if (typeof(mylink) == 'string')
		href=mylink;
	else
		href=mylink.href;
		window.open(href, windowname, 'scrollbars=yes, resizable');
		return false;
	}

function decision(message, url) {
	if (confirm(message))
		location.href = url;
}

//this will hide or show all the <select> elements on a page
function hideSelects(b)
{
      var allelems = document.all.tags('SELECT');
      if (allelems != null)
      {
              var i;
              for (i = 0; i < allelems.length; i++)
                      allelems[i].style.visibility = (b ? 'hidden' : 'inherit');
      }
}

// these two 'do' functions are needed to assign to the onmouse events
function doHideSelects(event)
{
      hideSelects(true);
}
function doShowSelects(event)
{
      hideSelects(false);
}

// this will setup all the 'A' tags on a page, with the 'info' class, with the
// above functions
function setAllInfoToHideSelects()
{
      if (Is_IE && !Is_IE7)
      {
              var allelems = document.all.tags('A');
              if (allelems != null)
              {
                      var i, elem;
                      for (i = 0; elem = allelems[i]; i++)
                      {
                              if (elem.className=='info' && elem.onmouseover == null && elem.onmouseout == null)
                              {
                                      elem.onmouseover = doHideSelects;
                                      elem.onmouseout = doShowSelects;
                              }
                      }
              }
      }
}

function updateInfoTargets() {
	links = document.getElementsByTagName('a');
	var i, elem;
	for (i = 0; elem = links[i]; i++) {
		if (elem.className == 'info' && elem.href.charAt(elem.href.length-1) == '#') {
			elem.href = 'javascript:void(null)';
		}
	}
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

function body_loaded() {
	setAllInfoToHideSelects(); 
	updateInfoTargets();
}

function amp_apply_changes() {
	if (confirm("About to reload backend configuration. This applies all outstanding changes to the live server.")) {
		
		var newlocation;
		if (document.all) {
			// need this for IE : http://support.microsoft.com/kb/q190244/
			window.event.returnValue = false;
			// IE also uses window.location.href instead of location.href
			newlocation = window.location.href;
		} else {
			newlocation = location.href;
		}

		if (location.href.indexOf('?') == -1) {
			newlocation = location + '?clk_reload=true';
		} else {
			newlocation = location + '&clk_reload=true';
		}
		location = newlocation.replace(/action/,"var-disabled");
		
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
// ** Checks for a valid Email address              **
// ***************************************************

function isEmail (s) {
	if (isEmpty(s)) 
       if (isEmail.arguments.length == 1) return defaultEmptyOK;
       else return (isEmail.arguments[1] == true);

    if (isWhitespace(s)) return false;
    var i = 1;
    var sLength = s.length;
    // look for @
    while ((i < sLength) && (s.charAt(i) != "@")) {
		i++;
    }

    if ((i >= sLength) || (s.charAt(i) != "@")) return false;
    else i += 2;

    // make sure there is something after the '@'
    if (i > sLength) return false;

//    // look for .
//    while ((i < sLength) && (s.charAt(i) != ".")) {
//		i++;
//    }
//	if ((i >= sLength - 1) || (s.charAt(i) != ".")) return false;
//    else 
        return true;
}

// ***************************************************
// ** String must contain Alphabetic letters ONLY   **
// ***************************************************

function isAlphabetic (s) {
	var i;
    if (isEmpty(s)) 
       if (isAlphabetic.arguments.length == 1) return defaultEmptyOK;
       else return (isAlphabetic.arguments[1] == true);
    for (i = 0; i < s.length; i++) {   
        var c = s.charAt(i);

        if (!isLetter(c))
        return false;
    }

    return true;
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

    for (i = 0; i < s.length; i++)
    {   
        var c = s.charAt(i);

        if (!isDigit(c)) return false;
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

function isEmpty(s)
{   return ((s == null) || (s.length == 0));
}

// ***************************************************
// ** Checks for all known whitespace               **
// ***************************************************

function isWhitespace (s)
{   var i;

    if (isEmpty(s)) return true;

    for (i = 0; i < s.length; i++)
    {   
        var c = s.charAt(i);

        if (whitespace.indexOf(c) == -1) return false;
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

    for (i = 0; i < s.length; i++)
    {   
        var c = s.charAt(i);

        if ( !isDialDigitChar(c) && (c != "w") && (c != "W") ) return false;
    }

    return true;
}

// ***************************************************
// ** Valid dialable digit (i.e. on a keypad)       **
// ***************************************************

function isDialDigits(s)
{
    var i;

    if (isEmpty(s)) 
       if (isDialDigits.arguments.length == 1) return defaultEmptyOK;
       else return (isDialDigits.arguments[1] == true);

    for (i = 0; i < s.length; i++)
    {   
        var c = s.charAt(i);

        if (!isDialDigitChar(c)) return false;
    }

    return true;
}

// ***************************************************
// ** Valid IVR input, any keypad char plus some    **
// ** priority i or t if specified by themself's    **
// ***************************************************

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

function isDigit (c)
{   return ((c >= "0") && (c <= "9"))
}

function isLetter (c)
{   return ( ((c >= "a") && (c <= "z")) || ((c >= "A") && (c <= "Z")) || (c == " ") || (c == "&") || (c == "'") || (c == "(") || (c == ")") || (c == "-") || (c == "/"))
}

function isURLChar (c)
{   return ( ((c >= "a") && (c <= "z")) || ((c >= "A") && (c <= "Z")) || (c == ":") || (c == ",") || (c == ".") || (c == "%") || (c == "#") || (c == "-") || (c == "/") || (c == "?") || (c == "&") || (c == "=") )
}

function isCallerIDChar (c)
{   return ( ((c >= "a") && (c <= "z")) || ((c >= "A") && (c <= "Z")) || ((c >= "0") && (c <= "9")) || (c == ":") || (c == "_") || (c == ",") || (c == "-") || (c == "<") || (c == ">") || (c == "(") || (c == ")") || (c == " ") || (c == "\"") || (c == "&") || (c == "@") || (c == ".") || (c == "+") )
}

function isDialpatternChar (c)
{   return ( ((c >= "0") && (c <= "9")) || (c == "[") || (c == "]") || (c == "-") || (c == "+") || (c == ".") || (c == "|") || (c == "Z" || c == "z") || (c == "X" || c == "x") || (c == "N" || c == "n") || (c == "*") || (c == "#" ) || (c == "_") || (c == "!") || (c == "/") )
}

function isDialruleChar (c)
{   return ( ((c >= "0") && (c <= "9")) || (c == "[") || (c == "]") || (c == "-") || (c == "+") || (c == ".") || (c == "|") || (c == "Z" || c == "z") || (c == "X" || c == "x") || (c == "N" || c == "n") || (c == "*") || (c == "#" ) || (c == "_") || (c == "!") || (c == "w") || (c == "W") )
}

function isDialDigitChar (c)
{   return ( ((c >= "0") && (c <= "9")) || (c == "*") || (c == "#" ) )
}

function isFilenameChar (c)
{   return ( ((c >= "0") && (c <= "9")) || ((c >= "a") && (c <= "z")) || ((c >= "A") && (c <= "Z")) || (c == "_") || (c == "-") )
}

/***************************************************
 *             GLOBAL JQUERY CODE                  *
 ***************************************************/

$(document).ready(function(){

		//destination double dropdown code
		$('.destdropdown').bind('blur click change keypress', function(){
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

});
