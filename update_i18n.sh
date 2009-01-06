#! /bin/sh
# Copyright (c) 2008 Mikael Carlsson
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

#
# The purpose of this script is to extract all text strings from all FreePBX code that can
# be translated and create template files under each modules/<module>/i18n directory.
# For this script to work you need to so svn co for for branch and for modules and 
# install this in the same tree so that the script can do all extraction at once. 
#

echo "Creating new POT template files for modules"
# go down to modules directory
cd amp_conf/htdocs/admin/modules
for modules in $(ls -d */); 
do
	echo "Checking if module ${modules%%/} has an i18n directory"
	# spit out the module.xml in a <modulename>.i18.php so that we can grab it with the find
	# all modules from svn MUST be installed as module_admin reads from 
	# the installed modules NOT from the directory itself
	if [ -d ${modules}i18n ]; then
	echo "Found directory ${modules}i18n, creating temp file"
	/var/lib/asterisk/bin/module_admin i18n ${modules%%/} > $modules${modules%%/}.i18n.php
	echo "Creating ${modules%%/}.pot file, extracting text strings"
	find ${modules%%/}/*.php | xargs xgettext -L PHP -o ${modules%%/}/i18n/${modules%%/}.pot --keyword=_ -
	echo "Removing temp file"
	rm $modules${modules%%/}.i18n.php
	fi
done
# Go back two directory levels
cd ../..
echo "Creating new POT template files for core"
# spit out the module.xml for core to amp.i18.php so that we can grab it with the find
/var/lib/asterisk/bin/module_admin i18n core > admin/modules/core/core.i18n.php
find admin/*.php admin/cdr/*.php admin/views/*.php admin/common/*.php admin/modules/core/*.php -maxdepth 0 | xargs xgettext -L PHP -o admin/i18n/amp.pot --keyword=_ -
# remove the <modulename>.i18.php
rm admin/modules/core/core.i18n.php
echo "Done, now don't forget to commit your work!"
