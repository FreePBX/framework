#!/bin/bash

if [ "$1" == "-h" ]
then
	echo "Usage: "
	echo "   "$0" [config]"
	echo
	echo "If config file is not specified, default is /etc/amportal.conf"
	echo
	exit
fi

if [ -n "$1" ]
then
	AMPCONFIG=$1
else
	AMPCONFIG=/etc/amportal.conf
fi

if [ ! -e $AMPCONFIG ]
then
	echo "Cannot find $AMPCONFIG"
	exit
fi

# include config file
echo "Reading $AMPCONFIG"
source $AMPCONFIG


echo "Updating configuration..."

echo "/etc/asterisk/cdr_mysql.conf"
sed -r -i "s/user=[a-zA-Z0-9]*/user=$AMPDBUSER/" /etc/asterisk/cdr_mysql.conf
sed -r -i "s/password=[a-zA-Z0-9]*/password=$AMPDBPASS/" /etc/asterisk/cdr_mysql.conf

# do a bunch at once here
find $AMPWEBROOT/admin/ -name retrieve\*.pl
sed -r -i "s/username = \"[a-zA-Z0-9]*\";/username = \"$AMPDBUSER\";/" `find $AMPWEBROOT/admin/ -name retrieve\*.pl`
sed -r -i "s/password = \"[a-zA-Z0-9]*\";/password = \"$AMPDBPASS\";/" `find $AMPWEBROOT/admin/ -name retrieve\*.pl`

sed -r -i "s!op_conf = \"[a-zA-Z0-9_-\.\/\\]*\";!op_conf = \"$AMPWEBROOT\/panel\/op_buttons_additional.cfg\";!" $AMPWEBROOT/admin/retrieve_op_conf_from_mysql.pl

echo "/etc/asterisk/manager.conf"
sed -r -i "s/secret = [a-zA-Z0-9]*/secret = $AMPMGRPASS/" /etc/asterisk/manager.conf
sed -r -i "/\[general\]/!s/\[[a-zA-Z0-9]+\]/[$AMPMGRUSER]/" /etc/asterisk/manager.conf

echo "/var/lib/asterisk/agi-bin/dialparties.agi"
sed -r -i "s/mgrUSERNAME='[a-zA-Z0-9]*';/mgrUSERNAME='$AMPMGRUSER';/" /var/lib/asterisk/agi-bin/dialparties.agi
sed -r -i "s/mgrSECRET='[a-zA-Z0-9]*';/mgrSECRET='$AMPMGRPASS';/" /var/lib/asterisk/agi-bin/dialparties.agi

echo $AMPWEBROOT"/panel/op_server.cfg"
sed -r -i "s/manager_user=[a-zA-Z0-9]*/manager_user=$AMPMGRUSER/" $AMPWEBROOT/panel/op_server.cfg
sed -r -i "s/manager_secret=[a-zA-Z0-9]*/manager_secret=$AMPMGRPASS/" $AMPWEBROOT/panel/op_server.cfg
sed -r -i "s/web_hostname=[a-zA-Z0-9_\-\.]*/web_hostname=$AMPWEBADDRESS/" $AMPWEBROOT/panel/op_server.cfg
sed -r -i "s/security_code=[a-zA-Z0-9]*/security_code=$FOPPASSWORD/" $AMPWEBROOT/panel/op_server.cfg
sed -r -i "s!flash_dir=[a-zA-Z0-9_\-\.\/\\]*!flash_dir=$AMPWEBROOT\/panel!" $AMPWEBROOT/panel/op_server.cfg
sed -r -i "s!web_hostname=[a-zA-Z0-9\.]*!web_hostname=$AMPWEBADDRESS!" $AMPWEBROOT/panel/op_server.cfg
sed -r -i "s!web_hostname=[a-zA-Z0-9\.]*!web_hostname=$AMPWEBADDRESS!" $AMPWEBROOT/panel/op_server.cfg

echo "/etc/asterisk/vm_email.inc (may require manual check)"
sed -i -e "s/AMPWEBADDRESS/$AMPWEBADDRESS/g" /etc/asterisk/vm_email.inc

echo "Done"
echo
echo "Adjusting File Permissions.."
/usr/sbin/amportal chown
echo "Done"
exit

