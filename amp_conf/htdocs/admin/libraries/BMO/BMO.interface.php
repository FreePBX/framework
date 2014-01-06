<?php

// Define and document the BMO Interface

include "DB_Helper.class.php";

interface BMO {
	// ///////////////////////////////// //
	// Installing/Upgrading/Uninstalling //
	// ///////////////////////////////// //
	//
	// Process to run when you're installed.
	public function install();
	// Note that __construct will be called with ($BMO, true) before install is to be run.
	// If an exception is thrown in here, the module will NOT be marked as installed, and
	// won't be accessable.
	//
	// Process to run when you're being uninstalled.
	public function uninstall();
	// If an exception is thrown in here, the module WILL BE marked as uninstalled, but a
	// warning will be displayed to the end user with the text of the Exception.
	//
	// Optional
	// public function upgrade()
	// Is called when an Upgrade is run on the module. If this doesn't exist, install()
	// will be called.

	// ////////// //
	// FreePBX UI //
	// ////////// //
	// This is called from config.php?display=thismodulename and the entire $_REQUEST is
	// passed to it. For compatibility, you can print/echo things here, and they will 
	// appear in the right place, however, it should be returning an array, or an Object.
	public function showPage($request);

	// ////////////////// //
	// Backup and Restore //
	// ////////////////// //
	// These functions are called when the Backup/Restore module runs.
	public function backup(); 
	// When called, return a string or array that will allow the module to rebuild its
	// configuration. 
	//
	public function restore($backup);
	// When called, assume that your existing database is in an indeterminate state, and
	// process the information handed to it. Note that you should never assume that the
	// information handed to restore is the latest version. This may be a restore from
	// several versions ago, so ensure that you verify that it's up to date with your
	// current schema.

	// ////////// //
	// AJAX Calls //
	// ////////// //
	//
	// These are called from ajax.php.
	// public function ajaxCall($_REQUEST);

	// ////////////////////////// //
	// Hooking into other modules //
	// ////////////////////////// //
	//
	// public function getPageHooks();
	// public function getConfigHooks();
	//
	// public function pageHook($page);
	// public function configHook($module, $config);

	// ////////////////////// //
	// Dialplan Modifications //
	// ////////////////////// //

	// //////////////////////////// //
	// Asterisk Configuration Files //
	// //////////////////////////// //
	// When the 'reload' button is clicked, getConfig will be called, the output will
	// be given to any modules that requested it, and what they return will then be 
	// given to writeConfig.
	public function getConfig();
	//
	// writeConfig should use $this->FreePBX->WriteConfig($config) which will do all
	// the actual writing of files for it.
	// See BMO/WriteConfig.class.php
	public function writeConfig($config);
}
