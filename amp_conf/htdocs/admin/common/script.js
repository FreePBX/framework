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
	
	if ($account == "" || $secret == "" || $vmpwd == "" || $context == "" || $dtmfmode == "" || $host == "" || $type == "" || $mailbox == "" || $username == "" || $fullname == "") {
		alert('Please fill out all forms.');
	} else if (($account.indexOf('0') == 0) || ($account.indexOf('8') == 0)) {
		alert('Extensions cannot begin with 0 or 8');
	} else if ($account != parseInt($account)) {
		alert('There is something wrong with your extension number - it must be in integer');
	} else {
	theForm.submit();
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
