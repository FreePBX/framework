#!/bin/bash

ROOT_UID=0	 # root uid is 0
E_NOTROOT=67	 # Non-root exit error


echo
# check to see if we are root
if [ "$UID" -ne "$ROOT_UID" ]
then
	echo "Sorry, you must be root to run this script."
	echo
	exit $E_NOTROOT
fi

# make sure config file exists
if [ ! -e "/etc/amportal.conf" ]       # Check if file exists.
  then
    echo;
    echo "/etc/amportal.conf does not exist!";
	echo "Have you installed the AMP configuration?";
	exit;
fi
source /etc/amportal.conf

chown -R asterisk:asterisk /var/run/asterisk
chown -R asterisk:asterisk /etc/asterisk
chown -R asterisk:asterisk /var/lib/asterisk
chown -R asterisk:asterisk /var/log/asterisk
chown -R asterisk:asterisk /var/spool/asterisk
chown -R asterisk:asterisk /dev/zap
chown asterisk /dev/tty9
chown -R asterisk:asterisk $AMPWEBROOT
chown -R asterisk:asterisk $AMPCGIBIN
chmod u+x /var/lib/asterisk/agi-bin/*.agi
chmod u+x $AMPCGIBIN/vmail.cgi
chmod u+x $AMPWEBROOT/admin/*.pl
chmod u+x $AMPWEBROOT/admin/*.sh
chmod u+x $AMPWEBROOT/panel/*.pl
chmod 755 /usr/sbin/amportal
