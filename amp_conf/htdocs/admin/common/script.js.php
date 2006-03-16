<?php
header ("Content-type: application/x-javascript");

if (!extension_loaded('gettext')) {
       function _($str) {
               return $str;
       }
} else {
    if (isset($_COOKIE['lang'])) {
    	setlocale(LC_MESSAGES,  $_COOKIE['lang']);
    } else { 
    	setlocale(LC_MESSAGES,  'en_US');
    }
    bindtextdomain('amp','../i18n');
    textdomain('amp');
}

?>

Is_DOM = (document.getElementById) ? true : false;
Is_NS4 = (document.layers) ? true : false;
Is_IE = (document.all) ? true : false;
Is_IE4 = Is_IE && !Is_DOM;
Is_Mac = (navigator.appVersion.indexOf("Mac") != -1);
Is_IE4M = Is_IE4 && Is_Mac;

function checkForm(theForm) {
	$tech = theForm.tech.value;
	$account = theForm.account.value;
	$vmpwd = theForm.vmpwd.value;
	$email = theForm.email.value;
	$pager = theForm.pager.value;
	$context = theForm.context.value;
	
	if ($tech != "zap") {
		$secret = theForm.secret.value;
		$host = theForm.host.value;
		$type = theForm.type.value;
		$username = theForm.username.value;
		if ($username == "") {
			theForm.username.value = $account;
			$username = $account;
		}
	}

	$mailbox = theForm.mailbox.value;
	$fullname = theForm.name.value;
	$vm = theForm.vm.value;
	
	if ($tech == "zap") {
		$channel=theForm.channel.value;
	}

	
	if (($tech != "zap") && ($account == "" || $secret == "" || $context == "" || $host == "" || $type == ""  || $username == "")) {
		<?php echo "alert('"._("Please fill in all required fields.")."')"?>;
	} else if (($tech == "zap") && ( $account == "" || $context == "" || $channel=="")) {
		<?php echo "alert('"._("Please fill in all required fields.")."')"?>;
	} else if (($account.indexOf('0') == 0) && ($account.length > 1)) {
		<?php echo "alert('"._("Extensions numbers with more than one digit cannot begin with 0")."')"?>;
	} else if ($account != parseInt($account)) {
		<?php echo "alert('"._("There is something wrong with your extension number - it must be in integer")."')"?>;
	} else if ($vm == "enabled" && $fullname == "" && $vmpwd == "" && $email == "" && $pager == "") {
		<?php echo "alert('"._("You have enabled Voicemail & Directory for this extension, but have not specified any options.  Please specify options, or disable Voicemail & Directory.")."')"?>;
	} else if ((!$vmpwd.match('^[0-9]+')) && ($vmpwd != "")) {
		<?php echo "alert('"._("A voicemail passsword can only contain digits")."')"?>;
	} else {
	theForm.submit();
	}
}

function checkGeneral(theForm) {
	$RINGTIMER = theForm.RINGTIMER.value;
	$FAX_RX = theForm.FAX_RX.value;

	if ($RINGTIMER == "" || $FAX_RX == "") {
		<?php echo "alert('"._("Please fill in all required fields.")."')"?>;
	} else {
	theForm.submit();
	}
}

function checkIncoming(theForm) {
	$INCOMING = theForm.INCOMING.value;

	if ($INCOMING == "") {
		<?php echo "alert('"._("Please select where you would like to send incoming calls to.")."')"?>;
	} else {
	theForm.submit();
	}
}

function checkQ(theForm) {
        $queuename = theForm.name.value;
        var bad = "false";

        var whichitem = 0;
        while (whichitem < theForm.goto_indicate0.length) {
                if (theForm.goto_indicate0[whichitem].checked) {
                        theForm.goto0.value=theForm.goto_indicate0[whichitem].value;
                }
                whichitem++;
        }

        var gotoType = theForm.elements[ "goto0" ].value;
        if (gotoType == 'custom') {
                var gotoVal = theForm.elements[ "custom0"].value;
                if (gotoVal.indexOf('custom') == -1) {
                        bad = "true";
						<?php echo "alert('"._("Custom Goto contexts must contain the string \"custom\".  ie: custom-app,s,1")."')"?>;
                }
        }

        $account = theForm.account.value;
        if ($account == "") {
                <?php echo "alert('"._("Queue Number must not be blank")."')"?>;
                bad="true";
        }
        else if (($account.indexOf('0') == 0) && ($account.length > 1)) {
                <?php echo "alert('"._("Queue numbers with more than one digit cannot begin with 0")."')"?>;
                bad="true";
        }

        if ($queuename == "") {
                <?php echo "alert('"._("Queue name must not be blank")."')"?>;
                bad="true";
        } else if (!$queuename.match('^[a-zA-Z][a-zA-Z0-9]+$')) {
                <?php echo "alert('"._("Queue name cannot start with a number, and can only contain letters and numbers")."')"?>;
                bad="true";
        }

        if (bad == "false") {
                theForm.submit();
        }
}

function repositionTrunk(repositiondirection,repositionkey,key,direction){
	if(direction == "up"){
		document.getElementById('repotrunkdirection').value=direction;
		document.getElementById('repotrunkkey').value=key;
	}else if(direction == "down" ){
		document.getElementById('repotrunkdirection').value=direction;
		document.getElementById('repotrunkkey').value=key;
	}
	document.getElementById('routeEdit').submit();
}
function deleteTrunk(key) {
	document.getElementById('trunkpri'+key).value = '';
	document.getElementById('routeEdit').submit();
}

function repositionRoute(key,direction){
	if(direction == "up"){
		document.getElementById('reporoutedirection').value=direction;
		document.getElementById('reporoutekey').value=key;
	}else if(direction == "down" ){
		document.getElementById('reporoutedirection').value=direction;
		document.getElementById('reporoutekey').value=key;
	}
	document.getElementById('action').value='prioritizeroute';
	document.getElementById('routeEdit').submit();
}


function openWindow(url,width,height) { 
	popupWin = window.open(url, '', 'width='+width + ',height='+height)
}

function checkVoicemail(theForm) {
	$vm = theForm.elements["vm"].value;
	if ($vm == 'disabled') {
		document.getElementById('voicemail').style.display='none';
		theForm.vmpwd.value = '';
		theForm.email.value = '';
		theForm.pager.value = '';
	} else {
		document.getElementById('voicemail').style.display='block';
	}
}

function hideExtenFields(theForm) {
	if(theForm.tech.value == 'iax2') {
		document.getElementById('dtmfmode').style.display = 'none';
		document.getElementById('secret').style.display = 'inline';
		document.getElementById('channel').style.display = 'none';
		document.getElementById('dial').style.display = 'none';
	} else if (theForm.tech.value == 'sip') {
		document.getElementById('dtmfmode').style.display = 'inline';
		document.getElementById('secret').style.display = 'inline';
		document.getElementById('channel').style.display = 'none';
		document.getElementById('dial').style.display = 'none';
	} else if (theForm.tech.value == 'zap') {
		document.getElementById('dtmfmode').style.display = 'none';
		document.getElementById('secret').style.display = 'none';
		document.getElementById('channel').style.display = 'block';
		document.getElementById('dial').style.display = 'none';
	} else if (theForm.tech.value == 'custom') {
		document.getElementById('dtmfmode').style.display = 'none';
		document.getElementById('secret').style.display = 'none';
		document.getElementById('channel').style.display = 'none';
		document.getElementById('dial').style.display = 'block';
	}
}

function checkAmpUser(theForm, action) {
	$username = theForm.username.value;
	$deptname = theForm.deptname.value;
	
	if ($username == "") {
		<?php echo "alert('"._("Username must not be blank")."')"?>;
	} else if (!$username.match('^[a-zA-Z][a-zA-Z0-9]+$')) {
		<?php echo "alert('"._("Username cannot start with a number, and can only contain letters and numbers")."')"?>;
	} else if ($deptname == "default") {
		<?php echo "alert('"._("For security reasons, you cannot use the department name default")."')"?>;
	} else if ($deptname != "" && !$deptname.match('^[a-zA-Z0-9]+$')) {
		<?php echo "alert('"._("Department name cannot have a space")."')"?>;
	} else {
		theForm.action.value = action;
		theForm.submit();
	}
}

function changeLang(lang) {
	document.cookie='lang='+lang;
	window.location.reload();
}

function decision(message, url){
if(confirm(message)) location.href = url;
}

//call this function from forms that include module destinations
//numForms is the number of destination forms to process (usually 1)
function setDestinations(theForm,numForms) {
	for (var formNum = 0; formNum < numForms; formNum++) {
		var whichitem = 0;
		while (whichitem < theForm['goto_indicate'+formNum].length) {
			if (theForm['goto_indicate'+formNum][whichitem].checked) {
				theForm['goto'+formNum].value=theForm['goto_indicate'+formNum][whichitem].value;
			}
			whichitem++;
		}
	}
}

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

//this is called from validateDestinations to check each set
//you can call this directly if you have multiple sets and only
//require one to be selected, for example.
//formNum is the set number (0 indexed)
//bRequired true|false if user must select something
function validateSingleDestination(theForm,formNum,bRequired) {
	var gotoType = theForm.elements[ 'goto'+formNum ].value;
	
	if (bRequired && gotoType == '') {
		alert('<?php echo _("Please select a \"Destination\""); ?>');
		return false;
	} else {
		// check the 'custom' goto, if selected
		if (gotoType == 'custom') {
			var gotoFld = theForm.elements[ 'custom'+formNum ];
			var gotoVal = gotoFld.value;
			if (gotoVal.indexOf('custom-') == -1) {
				alert('<?php echo _("Custom Goto contexts must contain the string \"custom-\".  ie: custom-app,s,1"); ?>');
				gotoFld.focus();
				return false;
			}
		}
	}
	
	return true;
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
      if (Is_IE)
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

// Various form checking helper functions -- very useful
var whitespace = " \t\n\r";
var decimalPointDelimiter = ".";
var defaultEmptyOK = false;

function isEmail (s) {
	if (isEmpty(s)) 
       if (isEmail.arguments.length == 1) return defaultEmptyOK;
       else return (isEmail.arguments[1] == true);
    // is s whitespace?
    if (isWhitespace(s)) return false;
    // there must be >= 1 character before @, so we
    // start looking at character position 1 
    // (i.e. second character)
    var i = 1;
    var sLength = s.length;
    // look for @
    while ((i < sLength) && (s.charAt(i) != "@")) {
		i++;
    }

    if ((i >= sLength) || (s.charAt(i) != "@")) return false;
    else i += 2;

    // look for .
    while ((i < sLength) && (s.charAt(i) != ".")) {
		i++;
    }
    // there must be at least one character after the .
    if ((i >= sLength - 1) || (s.charAt(i) != ".")) return false;
    else return true;
}

function isAlphabetic (s) {
	var i;
    if (isEmpty(s)) 
       if (isAlphabetic.arguments.length == 1) return defaultEmptyOK;
       else return (isAlphabetic.arguments[1] == true);
    // Search through string's characters one by one
    // until we find a non-alphabetic character.
    // When we do, return false; if we don't, return true.
    for (i = 0; i < s.length; i++) {   
        // Check that current character is letter.
        var c = s.charAt(i);

        if (!isLetter(c))
        return false;
    }
    // All characters are letters.
    return true;
}

function isAlphanumeric (s) {
	var i;
    if (isEmpty(s)) 
       if (isAlphanumeric.arguments.length == 1) return defaultEmptyOK;
       else return (isAlphanumeric.arguments[1] == true);
    // Search through string's characters one by one
    // until we find a non-alphanumeric character.
    // When we do, return false; if we don't, return true.
    for (i = 0; i < s.length; i++) {   
        // Check that current character is number or letter.
        var c = s.charAt(i);
        if (! (isLetter(c) || isDigit(c) ) )
        return false;
    }
    // All characters are numbers or letters.
    return true;
}

function isPrefix (s) {
	var i;
    if (isEmpty(s)) 
       if (isPrefix.arguments.length == 1) return defaultEmptyOK;
       else return (isPrefix.arguments[1] == true);
    // Search through string's characters one by one
    // until we find a non-prefix character.
    // When we do, return false; if we don't, return true.
    for (i = 0; i < s.length; i++) {   
        // Check that current character is number or letter.
        var c = s.charAt(i);
        if (! (isPrefixChar(c) ) )
        return false;
    }
    // All characters are numbers or letters.
    return true;
}

function isCallerID (s) {
	var i;
    if (isEmpty(s)) 
       if (isCallerID.arguments.length == 1) return defaultEmptyOK;
       else return (isCallerID.arguments[1] == true);
    // Search through string's characters one by one
    // until we find a non-prefix character.
    // When we do, return false; if we don't, return true.
    for (i = 0; i < s.length; i++) {   
        // Check that current character is number or letter.
        var c = s.charAt(i);
        if (! (isCallerIDChar(c) ) )
        return false;
    }
    // All characters are numbers or letters.
    return true;
}

function isDialpattern (s) {
	var i;
    if (isEmpty(s)) 
       if (isDialpattern.arguments.length == 1) return defaultEmptyOK;
       else return (isDialpattern.arguments[1] == true);
    // Search through string's characters one by one
    // until we find a non-prefix character.
    // When we do, return false; if we don't, return true.
    for (i = 0; i < s.length; i++) {   
        // Check that current character is number or letter.
        var c = s.charAt(i);
        if ( !isDialpatternChar(c) ) {
		if (c.charCodeAt(0) != 13 && c.charCodeAt(0) != 10) {
			//alert(c.charCodeAt(0));
			return false;
		}
	}
    }
    // All characters are numbers or letters.
    return true;
}

function isAddress (s) {
	var i;
    if (isEmpty(s)) 
       if (isAddress.arguments.length == 1) return defaultEmptyOK;
       else return (isAddress.arguments[1] == true);
    // Search through string's characters one by one
    // until we find a non-alphanumeric character.
    // When we do, return false; if we don't, return true.
    for (i = 0; i < s.length; i++) {   
        // Check that current character is number or letter.
        var c = s.charAt(i);
        if (! (isAddrLetter(c) || isDigit(c) ) )
        return false;
    }
    // All characters are numbers or letters.
    return true;
}

function isPhone (s) {
	var i;
    if (isEmpty(s)) 
       if (isPhone.arguments.length == 1) return defaultEmptyOK;
       else return (isPhone.arguments[1] == true);
    // Search through string's characters one by one
    // until we find a non-alphanumeric character.
    // When we do, return false; if we don't, return true.
    for (i = 0; i < s.length; i++) {   
        // Check that current character is number or letter.
        var c = s.charAt(i);
        if (!isPhoneDigit(c))
        return false;
    }
    // All characters are numbers or letters.
    return true;
}

function isURL (s) {
	var i;
    if (isEmpty(s)) 
       if (isURL.arguments.length == 1) return defaultEmptyOK;
       else return (isURL.arguments[1] == true);
    // Search through string's characters one by one
    // until we find a non-alphanumeric character.
    // When we do, return false; if we don't, return true.
    for (i = 0; i < s.length; i++) {   
        // Check that current character is number or letter.
        var c = s.charAt(i);
        if (! (isURLLetter(c) || isDigit(c) ) )
        return false;
    }
    // All characters are numbers or letters.
    return true;
}
function isInteger (s)

{   var i;

    if (isEmpty(s)) 
       if (isInteger.arguments.length == 1) return defaultEmptyOK;
       else return (isInteger.arguments[1] == true);

    // Search through string's characters one by one
    // until we find a non-numeric character.
    // When we do, return false; if we don't, return true.

    for (i = 0; i < s.length; i++)
    {   
        // Check that current character is number.
        var c = s.charAt(i);

        if (!isDigit(c)) return false;
    }

    // All characters are numbers.
    return true;
}

function isFloat (s) {
	var i;
    var seenDecimalPoint = false;

    if (isEmpty(s)) 
       if (isFloat.arguments.length == 1) return defaultEmptyOK;
       else return (isFloat.arguments[1] == true);

    if (s == decimalPointDelimiter) return false;

    // Search through string's characters one by one
    // until we find a non-numeric character.
    // When we do, return false; if we don't, return true.

    for (i = 0; i < s.length; i++) {   
        // Check that current character is number.
        var c = s.charAt(i);

        if ((c == decimalPointDelimiter) && !seenDecimalPoint) seenDecimalPoint = true;
        else if (!isDigit(c)) return false;
    }

    // All characters are numbers.
    return true;
}

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


function isWhitespace (s)

{   var i;

    // Is s empty?
    if (isEmpty(s)) return true;

    // Search through string's characters one by one
    // until we find a non-whitespace character.
    // When we do, return false; if we don't, return true.

    for (i = 0; i < s.length; i++)
    {   
        // Check that current character isn't whitespace.
        var c = s.charAt(i);

        if (whitespace.indexOf(c) == -1) return false;
    }

    // All characters are whitespace.
    return true;
}
function isEmpty(s)
{   return ((s == null) || (s.length == 0));
}

function isLetter (c)
{   return ( ((c >= "a") && (c <= "z")) || ((c >= "A") && (c <= "Z")) || (c == " ") || (c == "&") || (c == "'") || (c == "(") || (c == ")") || (c == "-") || (c == "/"))
}

function isAddrLetter (c)
{   return ( ((c >= "a") && (c <= "z")) || ((c >= "A") && (c <= "Z")) || (c == " ") || (c == "&") || (c == ",") || (c == ".") || (c == "(") || (c == ")") || (c == "-") || (c == "'") || (c == "/") )
}

function isURLLetter (c)
{   return ( ((c >= "a") && (c <= "z")) || ((c >= "A") && (c <= "Z")) || (c == ":") || (c == ",") || (c == ".") || (c == "%") || (c == "#") || (c == "-") || (c == "/") || (c == "?") || (c == "&") || (c == "=") )
}

function isDigit (c)
{   return ((c >= "0") && (c <= "9"))
}

function isPhoneDigit (c)
{   return ( ((c >= "0") && (c <= "9")) || (c == " ") || (c == "-") || (c == "(") || (c == ")") ) 
}

function isPrefixChar (c)
{   return ( ((c >= "a") && (c <= "z")) || ((c >= "A") && (c <= "Z")) || ((c >= "0") && (c <= "9")) || (c == ":") || (c == "_") || (c == "-") )
}

function isCallerIDChar (c)
{   return ( ((c >= "a") && (c <= "z")) || ((c >= "A") && (c <= "Z")) || ((c >= "0") && (c <= "9")) || (c == "<") || (c == ">") || (c == " ") || (c == "\"") )
}

function isDialpatternChar (c)
{   return ( ((c >= "0") && (c <= "9")) || (c == "[") || (c == "]") || (c == "-") || (c == "+") || (c == ".") || (c == "|") || (c == "Z" || c == "z") || (c == "X" || c == "x") || (c == "N" || c == "n") || (c == "*") )
}

function warnInvalid (theField, s) {
    theField.focus();
    theField.select();
    alert(<?php echo _(s); ?>);
    return false;
}

