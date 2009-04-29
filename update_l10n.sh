#! /bin/sh
# This file is part of FreePBX.
#
#    FreePBX is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 2 of the License, or
#    (at your option) any later version.
#
#    FreePBX is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with FreePBX.  If not, see <http://www.gnu.org/licenses/>.
#
#    Copyright 2006, diego_iastrubni
#
echo "Creating new POT template file"
find amp_conf/htdocs/admin -name '*.php' | xargs xgettext -L PHP -o freepbx.pot --keyword=_ -


for i in amp_conf/htdocs/admin/i18n/*/LC_MESSAGES/*.po; do
	echo "Updating $i"
	msgmerge $i freepbx.pot -U
done

echo "Done, now don't forget to commit your work!"
#less amportal.pot
