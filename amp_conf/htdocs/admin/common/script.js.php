<?php
if (!extension_loaded('gettext')) {
       function _($str) {
               return $str;
       }
} else {
    setlocale(LC_MESSAGES,  $_COOKIE['lang'] ? $_COOKIE['lang']:'en_US');
    bindtextdomain('amp','../i18n');
    textdomain('amp');
}

?>
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
		var gotoVal = theForm.elements[ "custom_args0"].value;
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
                var gotoVal = theForm.elements[ "custom_args0"].value;
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
	$account = theForm.account.value;

	var whichitem = 0;
	while (whichitem < theForm.goto_indicate0.length) {
		if (theForm.goto_indicate0[whichitem].checked) {
			theForm.goto0.value=theForm.goto_indicate0[whichitem].value;
		}
		whichitem++;
	}
	
	var gotoType = theForm.elements[ "goto0" ].value;
	if (gotoType == 'custom') {
		var gotoVal = theForm.elements[ "custom_args0"].value;
		if (gotoVal.indexOf('custom') == -1) {
			bad = "true";
			<?php echo "alert('"._("Custom Goto contexts must contain the string \"custom\".  ie: custom-app,s,1")."')"?>;
		}
	}
	
	if ($account == "") {
		<?php echo "alert('"._("Please enter a DID number list.")."')"?>;
	} else {
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

	if ($routename == "") {
		<?php echo "alert('"._("Route name must not be blank")."')"?>;
	} else if ( (!$routename.match($routeRegex_new)) && (!$routename.match($routeRegex_update)) ) {
		<?php echo "alert('"._("Route name cannot start with a number, and can only contain letters and numbers")."')"?>;
	} else if (!$dialpattern.match('[A-Z0-9a-z]+')) {
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
			var gotoVal = theForm.elements[ "custom_args"+formNum ].value;
			if (gotoVal.indexOf('custom') == -1) {
				bad = "true";
				item = formNum + 1;
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
	} else if (theForm.tech.value == 'sip') {
		document.getElementById('dtmfmode').style.display = 'inline';
		document.getElementById('secret').style.display = 'inline';
		document.getElementById('channel').style.display = 'none';
	} else if (theForm.tech.value == 'zap') {
		document.getElementById('dtmfmode').style.display = 'none';
		document.getElementById('secret').style.display = 'none';
		document.getElementById('channel').style.display = 'block';
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
