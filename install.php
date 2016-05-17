<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

if (version_compare(PHP_VERSION, '5.3.3', '<')) {
	out(sprintf(_("FreePBX Requires PHP Version 5.3.3 or Higher, you have: %s"),PHP_VERSION));
	return false;
}

// HELPER FUNCTIONS:

function framework_print_errors($src, $dst, $errors) {
	out("error copying files:");
	out(sprintf(_("'cp -rf' from src: '%s' to dst: '%s'...details follow"), $src, $dst));
	freepbx_log(FPBX_LOG_ERROR, sprintf(_("framework couldn't copy file to %s"),$dst));
	foreach ($errors as $error) {
		out("$error");
		freepbx_log(FPBX_LOG_ERROR, _("cp error output: $error"));
	}
}

global $amp_conf;

// default php will check local path, or should we add that in?
//include "libfreepbx.install.php";

$debug = false;
$dryrun = false;

/** verison_compare that works with freePBX version numbers
 *  included here because there are some older versions of functions.inc.php that do not have
 *  it included as it was added during 2.3.0beta1
 */
if (!function_exists('version_compare_freepbx')) {
	function version_compare_freepbx($version1, $version2, $op = null) {
		$version1 = str_replace("rc","RC", strtolower($version1));
		$version2 = str_replace("rc","RC", strtolower($version2));
		if (!is_null($op)) {
			return version_compare($version1, $version2, $op);
		} else {
			return version_compare($version1, $version2);
		}
	}
}

/* This is here to catch some errors in an 11->12 upgrade, specifically
 * with dashboard changes. These used to be part of dashboard, but have
 * been moved to framework. As dashboard was never REMOVED, the symlinks
 * were never removed either. We'll just sneak them in now.
 */
$wr = $amp_conf['AMPWEBROOT'];
if (is_link("$wr/admin/images/notify_critical.png")) {
	unlink("$wr/admin/images/notify_critical.png");
}
if (is_link("$wr/admin/images/notify_security.png")) {
	unlink("$wr/admin/images/notify_security.png");
}

/*
 * Framework install script
 */

	$base_source = dirname(__FILE__) . "/amp_conf";
	$htdocs_source = $base_source . "/htdocs/*";
	$bin_source = $base_source . "/bin/*";
	$agibin_source = $base_source . "/agi-bin/*";

	if (!file_exists(dirname($htdocs_source))) {
		out(sprintf(_("No directory %s, install script not needed"),dirname($htdocs_source)));
		return true;
	}

	// These are required by libfreepbx.install.php library for upgrade routines
	//
	define("UPGRADE_DIR", dirname(__FILE__)."/upgrades/");
	define("MODULE_DIR",  $amp_conf['AMPWEBROOT'].'/modules/');

	$htdocs_dest = $amp_conf['AMPWEBROOT'];
	$bin_dest    = isset($amp_conf['AMPBIN']) ? $amp_conf['AMPBIN'] : '/var/lib/asterisk/bin';
	$agibin_dest = isset($amp_conf['ASTAGIDIR']) ? $amp_conf['ASTAGIDIR']:'/var/lib/asterisk/agi-bin';

	$msg = _("installing files to %s..");

	$out = array();
	outn(sprintf($msg, $htdocs_dest));
	exec("cp -rf $htdocs_source $htdocs_dest 2>&1",$out,$ret);
	if ($ret != 0) {
		framework_print_errors($htdocs_source, $htdocs_dest, $out);
		out(_("done, see errors below"));
	} else {
		out(_("done"));
	}


	unset($out);
	outn(sprintf($msg, $bin_dest));
	exec("cp -rf $bin_source $bin_dest 2>&1",$out,$ret);
	if ($ret != 0) {
		framework_print_errors($bin_source, $bin_dest, $out);
		out(_("done, see errors below"));
	} else {
		exec("chmod +x $bin_dest/*");
		out(_("done"));
	}

	unset($out);
	outn(sprintf($msg, $agibin_dest));
	exec("cp -rf $agibin_source $agibin_dest 2>&1",$out,$ret);
	if ($ret != 0) {
		framework_print_errors($agibin_source, $agibin_dest, $out);
		out(_("done, see errors below"));
	} else {
		exec("chmod +x $agibin_dest/*");
		out(_("done"));
	}

	/*TODO: (Requirment for #4733)
	 *
	 * 1. Update publish.pl to grab a copy of amportal and put it somehwere.
	 * 2. If we have access to do an md5sum on AMPSBIN/amportal do it and
	 *    compare to the local copy.
	 * 3. If the md5sum is different or we couldn't check, put amportal in AMPBIN
	 * 4. If we decided they need a new one, then write out a message that they
	 *    should run amportal to update it.
	 */

	require_once(__DIR__.'/installlib/installer.class.php');
	$installer = new \FreePBX\Install\Installer();
	$installer->install_upgrades(getversion());
	// We run this each time so that we can add settings if need be
	// without requiring a major version bump
	//
	$installer->freepbx_settings_init(true);

	// We now delete the files, this makes sure that if someone had an unprotected system where they have not enabled
	// the .htaccess files or otherwise allowed direct access, that these files are not around to possibly cause problems
	//
	out(_("framework file install done, removing packages from module"));

	$rem_files[] = $base_source;
	$rem_files[] = dirname(__FILE__) . "/upgrades";
	$rem_files[] = dirname(__FILE__) . "/start_asterisk";
	$rem_files[] = dirname(__FILE__) . "/install";
	$rem_files[] = dirname(__FILE__) . "/installlib";

	foreach ($rem_files as $target) {
		unset($out);
		exec("rm -rf $target 2>&1",$out,$ret);
		if ($ret != 0) {
			out(sprintf(_("an error occured removing the packaged file/directory: %s"), $target));
		} else {
			out(sprintf(_("file/directory: %s removed successfully"), $target));
		}
	}

	//This seems like a really freaky race condition because we have previously called the out function
	//But I digress, just reinclude the file
	if (!$amp_conf['DISABLE_CSS_AUTOGEN'] && !function_exists('compress_framework_css')) {
		if(!class_exists('compress')) {
			require_once($dirname . '/libraries/compress.class.php');
		}
		compress::web_files();
	}

	if (!$amp_conf['DISABLE_CSS_AUTOGEN'] && function_exists('compress_framework_css')) {
		compress_framework_css();
	}

	if(!file_exists(dirname(__FILE__).'/module.xml')) {
		out(_('Cant Find Framework XML'));
		return false;
	}

	//This is also run in moduleadmin class
	//why? well because in developer mode this file doesnt exist, only the
	//module.xml exists so we have to do it in multiple places. yaaaaay :-|
	$fwxml = simplexml_load_file(dirname(__FILE__).'/module.xml');
	//setversion to whatever is in framework.xml forever for here on out.
	$fwver = (string)$fwxml->version;
	if(!empty($fwver)) {
		$installer->set_version($fwver);
		if($installer->get_version() != $fwver) {
			out(_('Internal Error. Function install_getversion did not match the Framework version, even after it was suppose to be applied'));
			return false;
		}
	} else {
		out(_('Version from Framework was empty, cant continue'));
		return false;
	}

	//sbin is not set correctly starting in 2.9, this is a stop-gap till we fix the installer in 13
	exec('export PATH=/usr/local/sbin:$PATH && which -a amportal',$output, $return_var);
	$file = null;
	foreach($output as $f) {
		if(!is_link($f)) {
			$file = $f;
			break;
		}
	}
	if(!empty($file)) {
		$sbin = dirname($file);
		if(is_dir($sbin) && ($amp_conf['AMPSBIN'] !== $sbin)) {
			$freepbx_conf =& freepbx_conf::create();
			out(sprintf(_("Setting sbin to the correct location of: %s"),$sbin));
			$freepbx_conf->set_conf_values(array("AMPSBIN" => $sbin), true,true);
		}
	}

	exec($amp_conf['AMPBIN'] . '/retrieve_conf 2>&1', $ret);

//need to invalidate module_xml at this point
if(function_exists("sql")) {
	sql("DELETE FROM module_xml WHERE id = 'modules'");
}

// Make sure our GPG keys are up to date
try {
	\FreePBX::GPG()->refreshKeys();
} catch (\Exception $e) {
	out(sprintf(_("Error updating GPG Keys: %s"), $e->getMessage()));
}

