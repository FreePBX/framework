#!/usr/bin/php -q
<?php

/** setup_svn.php
 *
 *  The purpose of this function is to install all the modules associated with a particular FreePBX version
 *  under the same path as install_amp. The setup will be similar to how modules are packaged with the
 *  tarballs when distributed. There are a handful of files that disapear and a few that appear as a
 *  result of this technique, but all of those files are associated with the packaging of distros and
 *  not the actual functioning of FreePBX.
 *
 *  You can not publish modules from a tree like this. However, you can do svn udpates from the top level
 *  and then reinstall, and you can make changes in place and do 'svn ci' commands to get them back into
 *  svn. You will require a 'properly' setup svn pull of the module tree in order to do the final publishing.
 *
 *  however, you can use this mode in conjunction with the --make-links-devel install option to get all of your
 *  modules sym-linked to their final place to make development easier.
 *
 */
$VERSION = "2.3";

$NORMAL_URL =  "https://amportal.svn.sourceforge.net/svnroot/amportal/freepbx/branches/$VERSION/amp_conf/htdocs/admin/modules";
$MODULE_URL  = "https://amportal.svn.sourceforge.net/svnroot/amportal/modules/branches/$VERSION";
$MODULE_PATH = "./amp_conf/htdocs/admin/modules";

if (isset($argv[1]) && strtolower($argv[1]) == "restore") {
	system("svn switch $NORMAL_URL $MODULE_PATH");
} else {
	system("svn switch $MODULE_URL $MODULE_PATH");
}


/*
exec("svn list $MODULE_URL", $modules, $ret);

if ($ret) {
	echo "ERROR: svn list returned: $ret\n";
	exit;
} else {

	foreach ($modules as $module) {
		echo "Checking out $module to $MODULE_PATH/$module\n";
		system("svn co $MODULE_URL/$module $MODULE_PATH/$module");
	}
}
*/

?>
