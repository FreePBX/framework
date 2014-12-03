<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
//
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

// I think we'll default to having astman connected,
// it adds a REALLY minor startup penalty, and saves
// work in the modules. Feel free to revisit later and
// yell at me if you disagree.
//
// $bootstrap_settings['skip_astman'] = true;

// No auth - we'll do that later.
$bootstrap_settings['freepbx_auth'] = false;

// Throw verbose exceptions, no matter what.
$bootstrap_settings['freepbx_error_handler'] = false;

// No non-BMO Modules.
$restrict_mods = true;

// Bootstrap!
include '/etc/freepbx.conf';

// We may remove this, but for the moment, ajax should be
// 100% error and warning free.
error_reporting(-1);

$bmo->Ajax->doRequest($module, $command);
