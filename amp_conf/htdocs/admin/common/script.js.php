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

function checkGRP(theForm,action) {
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
	
	$grplist = theForm.grplist.value;
	if ($grplist == "") {
		<?php echo "alert('"._("Please enter an extension list.")."')"?>;
		bad="true";
	} 
	
	$account = theForm.account.value;
	if (($account.indexOf('0') == 0) && ($account.length > 1)) {
		<?php echo "alert('"._("Group numbers with more than one digit cannot begin with 0")."')"?>;
		bad="true";
	}
	
	$grppre = theForm.grppre.value;
	if (!$grppre.match('^[a-zA-Z0-9:_\-]*$')) {
		<?php echo "alert('"._("Invalid prefix. Valid characters: a-z A-Z 0-9 : _ -")."')"?>;
		bad = "true";
	}
	
	$grptime = theForm.grptime.value;
	if (!$grptime.match('^[1-9][0-9]*$')) {
		<?php echo "alert('"._("Invalid time specified")."')"?>;
		bad = "true";
	}
	
	if (bad == "false") {
		theForm.action.value = action;
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

function checkDID(theForm) {
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
	
	if (bad == "false") {
		theForm.submit();
	}
}

function checkTrunk(theForm, action) {
	$tech = theForm.tech.value;
	if ($tech != "enum") {
		$channelid = theForm.channelid.value;
		$usercontext = theForm.usercontext.value;
	
		if ($channelid == "") {
			<?php echo "alert('"._("Missing required field: trunk name")."')"?>;
		} else if ($channelid == $usercontext) {
			<?php echo "alert('"._("Trunk Name and User Context cannot be set to the same value")."')"?>;
		} else {
			theForm.action.value = action;
			theForm.submit();
		}
	} else {
		theForm.action.value = action;
		theForm.submit();
	}
}

function checkRoute(theForm, action) {
	$routename = theForm.routename.value;
	$dialpattern = theForm.dialpattern.value;
	$trunkpriority = document.getElementById('trunkpri0').value;

	$routeRegex_update = /^\d{3}-[a-zA-Z0-9]+$/;
	$routeRegex_new = /^[a-zA-Z0-9]+$/;

	// routename checks
	// we don't really care about the name on edit!
	if (action == "addroute") {
		if ($routename == "") {
			<?php echo "alert('"._("Route name must not be blank")."')"?>;
			return false;
		}
		if ( !$routename.match($routeRegex_new) ) {
			<?php echo "alert('"._("Route name is invalid, please try again")."')"?>;
			return false;
		}
	}

	// dialpattern checks
	if (!$dialpattern.match('[A-Z0-9a-z]+')) {
		<?php echo "alert('"._("Dial pattern cannot be blank")."')"?>;
	} else if ($trunkpriority == '') {
		//TODO this doesn't account for other items besides the first being filled in'
		<?php echo "alert('"._("At least one trunk must be picked")."')"?>;
	} else {
		theForm.action.value = action;
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

function checkIVR(theForm,ivr_num_options) {
	var bad = "false";
	for (var formNum = 0; formNum < ivr_num_options; formNum++) {
		
		var whichitem = 0;
		
		while (whichitem < theForm['goto_indicate'+formNum].length) {
			if (theForm['goto_indicate'+formNum][whichitem].checked) {
				theForm['goto'+formNum].value=theForm['goto_indicate'+formNum][whichitem].value;
			}
			whichitem++;
		}
	
		var gotoType = theForm.elements[ "goto"+formNum ].value;
		if (gotoType == 'custom') {
			var gotoVal = theForm.elements[ "custom"+formNum ].value;
			if (gotoVal.indexOf('custom') == -1) {
				bad = "true";
				var item = formNum + 1;
				<?php echo "alert('"._("There is a problem with option number")?> '+item+'.\n\n<?php echo _("Custom Goto contexts must contain the string \"custom\".  ie: custom-app,s,1")."')"?>;
			}
		}
	}
	if (bad == "false") {
		theForm.submit();
	}
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

function checkConf(theForm)
{
	if (theForm.account.value == "")
	{
		<?php echo "alert('" . _("Please enter the Conference Number.") . "')" ?>;
		return false;
	}
	
	// update $options
	var theOptionsFld = theForm.options;
	theOptionsFld.value = "";
	for (var i = 0; i < theForm.elements.length; i++)
	{
		var theEle = theForm.elements[i];
		var theEleName = theEle.name;
		if (theEleName.indexOf("#") > 1)
		{
			var arr = theEleName.split("#");
			if (arr[0] == "opt")
				theOptionsFld.value += theEle.value;
		}
	}

	// not possible to have a 'leader' conference with no adminpin
	if (theForm.options.value.indexOf("w") > -1 && theForm.adminpin.value == "")
	{
		<?php echo "alert('" . _("You must set an admin PIN for the Conference Leader when selecting the leader wait option.") . "')" ?>;
		return false;
	}
		
	return true;
}


