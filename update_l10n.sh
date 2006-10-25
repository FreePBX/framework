#! /bin/sh

echo "Creating new POT template file"
find amp_conf/htdocs/admin -name '*.php' | xargs xgettext -L PHP -o freepbx.pot --keyword=_ -


for i in amp_conf/htdocs/admin/i18n/*/LC_MESSAGES/*.po; do
	echo "Updating $i"
	msgmerge $i freepbx.pot -U
done

echo "Done, now don't forget to commit your work!"
#less amportal.pot
