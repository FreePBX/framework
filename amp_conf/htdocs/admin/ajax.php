<?php

/**
 * BMO Ajax handler. 
 *
 * Does not support older modules.
 */

$_REQUEST['module']="sipsettings";
$_REQUEST['command']="foo";

// No astman connection
$bootstrap_settings['skip_astman'] = true;

// No auth - we'll do that later.
$bootstrap_settings['freepbx_auth'] = false;

// No non-BMO Modules.
$restrict_mods = true;

// Bootstrap!
include '/etc/freepbx.conf';

if (isset($_REQUEST['module'])) {
	$module = $_REQUEST['module'];
} else {
	$bmo->Ajax->ajaxError(409, 'Module not specified');
}

if (isset($_REQUEST['command'])) {
	$command = $_REQUEST['command'];
} else {
	$bmo->Ajax->ajaxError(404, 'Command not found');
}

$bmo->Ajax->doRequest($module, $command);

