function checkForm(theForm) {
	$account = theForm.account.value;
	$secret = theForm.secret.value;
	$vmpwd = theForm.vmpwd.value;
	$context = theForm.context.value;
	$dtmfmode = theForm.dtmfmode.value;
	$host = theForm.host.value;
	$type = theForm.type.value;
	$mailbox = theForm.mailbox.value;
	$username = theForm.username.value;
	$fullname = theForm.name.value;
	
	if ($username == "") {
		theForm.username.value = $account;
		$username = $account;
	}
	if ($mailbox == "") {
		theForm.mailbox.value = $account;
		$mailbox = $account;
	}
	
	if ($account == "" || $secret == "" || $context == "" || $dtmfmode == "" || $host == "" || $type == "" || $mailbox == "" || $username == "" || $fullname == "") {
		alert('Please fill out all forms.');
	} else if (($account.indexOf('0') == 0) || ($account.indexOf('8') == 0)) {
		alert('Extensions cannot begin with 0 or 8');
	} else if ($account != parseInt($account)) {
		alert('There is something wrong with your extension number - it must be in integer');
	} else {
	theForm.submit();
	}
	if ($vmpwd == "") {
		alert('A voicemail account has _not_ been created.  Please set the "mailbox" setting for this extension to an existing voicemail account number. To do this, click this extension on the right and change "mailbox" under "Advanced Edit"');	
	}
}

function checkGeneral(theForm) {
	$RINGTIMER = theForm.RINGTIMER.value;
	$FAX_RX = theForm.FAX_RX.value;

	if ($RINGTIMER == "" || $FAX_RX == "") {
		alert('Please fill out all forms.');
	} else {
	theForm.submit();
	}
}

function checkIncoming(theForm) {
	$INCOMING = theForm.INCOMING.value;

	if ($INCOMING == "") {
		alert('Please select where you would like to send incoming calls to.');
	} else {
	theForm.submit();
	}
}

function checkGRP(theForm) {
	$grplist = theForm.grplist.value;

	var whichitem = 0;
	while (whichitem < theForm.goto_indicate.length) {
		if (theForm.goto_indicate[whichitem].checked) {
			theForm.goto0.value=theForm.goto_indicate[whichitem].value;
		}
		whichitem++;
	}
	
	if ($grplist == "") {
		alert('Please enter an extension list.');
	} else {
	theForm.submit();
	}
}

function checkDID(theForm) {
	$account = theForm.account.value;

	var whichitem = 0;
	while (whichitem < theForm.goto_indicate.length) {
		if (theForm.goto_indicate[whichitem].checked) {
			theForm.goto0.value=theForm.goto_indicate[whichitem].value;
		}
		whichitem++;
	}
	
	if ($account == "") {
		alert('Please enter a DID number list.');
	} else {
	theForm.submit();
	}
}

function checkTrunk(theForm) {
	$dialprefix = theForm.dialprefix.value;
	$channelid = theForm.channelid.value;
	$usercontext = theForm.usercontext.value;

	if ($dialprefix == "" || $channelid == "") {
		alert('Please fill out all forms');
	} else if ($channelid == $usercontext) {
		alert('Trunk Name and User Context cannot be set to the same value');
	} else {
	theForm.submit();
	}
}

function openWindow(url,width,height) { 
	popupWin = window.open(url, '', 'width='+width + ',height='+height)
}

function checkIVR(theForm,ivr_num_options) {
	var bad = "false";
	for (var formNum = 0; formNum < ivr_num_options; formNum++) {
		var gotoType = theForm.elements[ "goto"+formNum ].value;
		if (gotoType == 'custom') {
			var gotoVal = theForm.elements[ "custom"+formNum ].value;
			if (gotoVal.indexOf('custom') == -1) {
				bad = "true";
				item = formNum + 1;
				alert('There is a problem with option number '+item+'.\n\nCustom Goto contexts must contain the string "custom".  ie: custom-app,s,1');
			}
		}
	}
	if (bad == "false") {
		theForm.submit();
	}
}
