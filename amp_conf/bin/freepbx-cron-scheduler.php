#!/usr/bin/php -q
<?php
//include bootstrap
$bootstrap_settings['freepbx_auth'] = false;
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
	include_once('/etc/asterisk/freepbx.conf');
}
// Define the notification class for logging to the dashboard
//
$nt = notifications::create($db);

// Check to see if email should be sent
//

$cm =& cronmanager::create($db);

$cm->run_jobs();

$email = $cm->get_email();
if ($email) {

	$text="";

	// clear email flag
	$nt->delete('freepbx', 'NOEMAIL');

	// set to false, if no updates are needed then it will not be
	// set to true and no email will go out even though the hash
	// may have changed.
	//
	$send_email = false;

	$security = $nt->list_security();
	if (count($security)) {
		$send_email = true;
		$text = "SECURITY NOTICE: ";
		foreach ($security as $item) {
			$text .= $item['display_text']."\n";
			$text .= $item['extended_text']."\n\n";
		}
	}
	$text .= "\n\n";

	$updates = $nt->list_update();
	if (count($updates)) {
		$send_email = true;
		$text = "UPDATE NOTICE: ";
		foreach ($updates as $item) {
			$text .= $item['display_text']."\n";
			$text .= $item['extended_text']."\n\n";
		}
	}

	if ($send_email && (! $cm->check_hash('update_email', $text))) {
		$cm->save_hash('update_email', $text);
		if (mail($email, _("FreePBX: New Online Updates Available"), $text)) {
			$nt->delete('freepbx', 'EMAILFAIL');
		} else {
			$nt->add_error('freepbx', 'EMAILFAIL', _('Failed to send online update email'), sprintf(_('An attempt to send email to: %s with online update status failed'),$email));
		}
	}
} else {
		$nt->add_notice('freepbx', 'NOEMAIL', _('No email address for online update checks'), _('You are automatically checking for online updates nightly but you have no email address setup to send the results. This can be set on the General Tab. They will continue to show up here.'), '', 'PASSIVE', false);
}
?>
