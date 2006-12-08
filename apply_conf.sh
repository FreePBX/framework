#!/usr/bin/env bash

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

echo "$ASTETCDIR/cdr_mysql.conf"
sed -i.bak "s/user=[a-zA-Z0-9]*/user=$AMPDBUSER/" $ASTETCDIR/cdr_mysql.conf
sed -i.bak "s/password=[a-zA-Z0-9]*/password=$AMPDBPASS/" $ASTETCDIR/cdr_mysql.conf
sed -i.bak "s/hostname=[a-zA-Z0-9.-]*/hostname=$AMPDBHOST/" $ASTETCDIR/cdr_mysql.conf

echo "$ASTETCDIR/manager.conf"
sed -i.bak "s/secret = [a-zA-Z0-9]*/secret = $AMPMGRPASS/" $ASTETCDIR/manager.conf
sed -i.bak "s/\[AMPMGRUSER\]/\[$AMPMGRUSER\]/" $ASTETCDIR/manager.conf

echo "$ASTETCDIR/vm_email.inc"
if [ "xx$AMPWEBADDRESS" = "xx" ]; then
	echo "You might need to modify /etc/asterisk/vm_email.inc manually"
else
	sed -i.bak "s!http://.*/recordings!http://$AMPWEBADDRESS/recordings!" $ASTETCDIR/vm_email.inc
fi


if [ -x /usr/sbin/amportal ]; then 
	echo "Adjusting File Permissions.."
	/usr/sbin/amportal chown
fi

echo "Done"
