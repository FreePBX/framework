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
	} else {
	theForm.submit();
	}
}

function checkGeneral(theForm) {
	$DIAL_OUT = theForm.DIAL_OUT.value;
	$RINGTIMER = theForm.RINGTIMER.value;
	$FAX_RX = theForm.FAX_RX.value;

	if ($DIAL_OUT == "" || $RINGTIMER == "" || $FAX_RX == "") {
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

function openWindow(url,width,height) { 
popupWin = window.open(url, '', 'width='+width + ',height='+height)
}
