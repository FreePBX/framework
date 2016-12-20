#!/usr/bin/env php
<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013-2016 Schmooze Com Inc.
//
$bootstrap_settings['freepbx_auth'] = false;
include '/etc/freepbx.conf';

// TODO: Remove run_jobs
$cm = cronmanager::create($db);
$cm->run_jobs();

// The following is exactly the same as this, but without the startup cost
// of forking ANOTHER php binary.
//
// exec("$ampsbin/fwconsole sendemails", $out, $ret);
// $ampsbin = FreePBX::Config()->get('AMPSBIN');

use Symfony\Component\Console\Application;
$fwc = new Application();
$class = 'FreePBX\\Console\\Command\\Sendemails';
$fwc->add(new $class);
$fwc->setDefaultCommand("sendemails");
$fwc->run();
