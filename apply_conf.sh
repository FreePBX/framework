#!/bin/sh

ROOT_UID=0	 # root uid is 0
E_NOTROOT=67	 # Non-root exit error

# check to see if we are root
if [ "$UID" -ne "$ROOT_UID" ]
then
	echo "Sorry, you must be root to run this script."
	echo
	exit 1
fi

# check to see if we are running this from the right place
if [ ! -e "apply_conf.sh" ] 
	then
	echo;
	echo "You must run this script from AMP's source directory";
	echo;
	exit 1
fi

# make sure config file exists
if [ ! -e "/etc/amportal.conf" ]       # Check if file exists.
  then
    echo;
    echo "/etc/amportal.conf does not exist!";
	echo "Using a default amportal.conf";
	cp amportal.conf /etc/
fi
source /etc/amportal.conf

echo
echo "Warning!!!"
echo "This script will overwrite your current Asterisk configuration files."
echo "You will _not_ lose configurations you made from the AMP web admin."
echo "Changes at customization points will also be preserved"
echo "(ie: *_custom.conf files)"
echo
echo "Press enter to continue."
echo

read anything

if [ ! -d $AMPWEBROOT ] 
	then
		echo;
		echo "$AMPWEBROOT does not appear to be Apache's web root.  Please set it in /etc/amportal.conf";
		echo;
		exit 1
fi

if [ ! -d $AMPCGIBIN ] 
	then
		echo;
		echo "$AMPCGIBIN does not appear to be Apache's cgi-bin directory.  Please set it in /etc/amportal.conf";
		echo;
		exit 1
fi

#copy in the AMP config
cp -rf amp_conf/etc/* /etc/
cp -rf amp_conf/usr/* /usr/
cp -rf amp_conf/var/spool/* /var/spool/
cp -rf amp_conf/var/www/cgi-bin/* $AMPCGIBIN/
cp -rf amp_conf/var/www/html/* $AMPWEBROOT/


# Replace tokens in files that require it
sed -i -e "s/AMPMGRPASS/$AMPMGRPASS/g" -e "s/AMPMGRUSER/$AMPMGRUSER/g" /etc/asterisk/manager.conf
sed -i -e "s/AMPMGRPASS/$AMPMGRPASS/g" -e "s/AMPMGRUSER/$AMPMGRUSER/g" /var/lib/asterisk/agi-bin/dialparties.agi
sed -i -e "s/AMPMGRPASS/$AMPMGRPASS/g" -e "s/AMPMGRUSER/$AMPMGRUSER/g" -e "s/AMPWEBADDRESS/$AMPWEBADDRESS/g" -e "s/FOPPASSWORD/$FOPPASSWORD/g" -e "s,AMPWEBROOT,$AMPWEBROOT,g" $AMPWEBROOT/panel/op_server.cfg
sed -i -e "s/AMPDBPASS/$AMPDBPASS/g" -e "s/AMPDBUSER/$AMPDBUSER/g" /etc/asterisk/cdr_mysql.conf
sed -i -e "s/AMPDBPASS/$AMPDBPASS/g" -e "s/AMPDBUSER/$AMPDBUSER/g" -e "s/AMPWEBADDRESS/$AMPWEBADDRESS/g" -e "s,AMPWEBROOT,$AMPWEBROOT,g" $AMPWEBROOT/admin/cdr/lib/defines.php
sed -i -e "s/AMPDBPASS/$AMPDBPASS/g" -e "s/AMPDBUSER/$AMPDBUSER/g" $AMPWEBROOT/admin/retrieve_extensions_from_mysql.pl
sed -i -e "s/AMPDBPASS/$AMPDBPASS/g" -e "s/AMPDBUSER/$AMPDBUSER/g" $AMPWEBROOT/admin/retrieve_iax_conf_from_mysql.pl
sed -i -e "s/AMPDBPASS/$AMPDBPASS/g" -e "s/AMPDBUSER/$AMPDBUSER/g" $AMPWEBROOT/admin/retrieve_meetme_conf_from_mysql.pl
sed -i -e "s/AMPDBPASS/$AMPDBPASS/g" -e "s/AMPDBUSER/$AMPDBUSER/g" -e "s,AMPWEBROOT,$AMPWEBROOT,g" $AMPWEBROOT/admin/retrieve_op_conf_from_mysql.pl
sed -i -e "s/AMPDBPASS/$AMPDBPASS/g" -e "s/AMPDBUSER/$AMPDBUSER/g" $AMPWEBROOT/admin/retrieve_sip_conf_from_mysql.pl
sed -i -e "s/AMPDBPASS/$AMPDBPASS/g" -e "s/AMPDBUSER/$AMPDBUSER/g" $AMPWEBROOT/admin/common/db_connect.php
sed -i -e "s/AMPWEBADDRESS/$AMPWEBADDRESS/g" /etc/asterisk/vm_email.inc
sed -i -e "s,AMPWEBROOT,$AMPWEBROOT,g" -e "s,AMPCGIBIN,$AMPCGIBIN,g" /usr/sbin/amportal


./chown_asterisk.sh
asterisk -rx reload

echo
echo "New configuration applied ..."
echo 
echo "Writing FOP config"
echo

su - asterisk -c "$AMPWEBROOT/admin/retrieve_op_conf_from_mysql.pl"
su - asterisk -c "$AMPWEBROOT/admin/bounce_op.sh"

exit 0
