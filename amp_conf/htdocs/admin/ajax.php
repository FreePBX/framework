<?php

/**
 * BMO Ajax handler. 
 *
 * Does not support older modules.
 */

if (!isset($_REQUEST['module'])) {
	$module = "framework";
} else {
	$module = $_REQUEST['module'];
}

if (isset($_REQUEST['command'])) {
	$command = $_REQUEST['command'];
} else {
	$command = "unset";
}

// No astman connection
$bootstrap_settings['skip_astman'] = true;

// No auth - we'll do that later.
$bootstrap_settings['freepbx_auth'] = false;

// No non-BMO Modules.
$restrict_mods = true;

// Bootstrap!
include '/etc/freepbx.conf';

$bmo->Ajax->doRequest($module, $command);

