#!/bin/bash

OLDPASS=amp109

if [ -z "$1" ]
then
	echo "Usage: "
	echo "   "$0" newpass [username]"
	echo
	echo "the Username is only changed if specified"
	echo
	exit
fi

NEWPASS=$1

# check for username specified
if [ -n "$2" ]
then
	echo "Got new username $USERNAME"
	USERNAME=$2
fi


echo "Changing passwords..."

echo "/etc/asterisk/cdr_mysql.conf"
sed -r -i "s/password=[a-zA-Z0-9]+/password=$NEWPASS/" /etc/asterisk/cdr_mysql.conf

echo "/var/www/html/admin/cdr/lib/defines.php"
sed -r -i "s/define \(\"PASS\", \"[a-zA-Z0-9]+\"\);/define \(\"PASS\", \"$NEWPASS\"\);/" /var/www/html/admin/cdr/lib/defines.php

# do a bunch at once here
find /var/www/html/admin/ -name retrieve\*.pl
sed -r -i "s/password = \"[a-zA-Z0-9]+\";/password = \"$NEWPASS\";/" `find /var/www/html/admin/ -name retrieve\*.pl`

echo "/var/www/html/admin/common/db_connect.php"
sed -r -i "s/db_pass = '[a-zA-Z0-9]+';/db_pass = '$NEWPASS';/" /var/www/html/admin/common/db_connect.php

if [ -n "$USERNAME" ]
then
	echo "Changing database usernames..."
	
	echo "/etc/asterisk/cdr_mysql.conf"
	sed -r -i "s/username=[a-zA-Z0-9]+/username=$USERNAME/" /etc/asterisk/cdr_mysql.conf

	echo "/var/www/html/admin/cdr/lib/defines.php"
	sed -r -i "s/define \(\"USER\", \"[a-zA-Z0-9]+\"\);/define \(\"USER\", \"$USERNAME\"\);/" /var/www/html/admin/cdr/lib/defines.php

	# do a bunch at once here
	find /var/www/html/admin/ -name retrieve\*.pl
	sed -r -i "s/username = \"[a-zA-Z0-9]+\";/username = \"$USERNAME\";/" `find /var/www/html/admin/ -name retrieve\*.pl`

	echo "/var/www/html/admin/common/db_connect.php"
	sed -r -i "s/db_user = '[a-zA-Z0-9]+';/db_user = '$USERNAME';/" /var/www/html/admin/common/db_connect.php
	
fi


echo "Done"
echo

exit

# amp_conf/etc/asterisk/cdr_mysql.conf: password=amp109
# amp_conf/var/www/html/admin/cdr/lib/defines.php.template:define ("PASS", "amp109");
# amp_conf/var/www/html/admin/retrieve_extensions_from_mysql.pl:$password = "amp109";
# amp_conf/var/www/html/admin/retrieve_iax_conf_from_mysql.pl:$password = "amp109";
# amp_conf/var/www/html/admin/retrieve_meetme_conf_from_mysql.pl:$password = "amp109";
# amp_conf/var/www/html/admin/retrieve_op_conf_from_mysql.pl:$password = "amp109";
# amp_conf/var/www/html/admin/retrieve_sip_conf_from_mysql.pl:$password = "amp109";
# amp_conf/var/www/html/admin/common/db_connect.php:$db_pass = 'amp109';

