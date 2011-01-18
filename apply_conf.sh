#!/bin/bash

if [ -z "$FREEPBX_CONF" ]; then
	if [ -e "/etc/freepbx.conf" ]; then
		FREEPBX_CONF="/etc/freepbx.conf"
	elif [ -e "/etc/asterisk/freepbx.conf" ]; then
		FREEPBX_CONF="/etc/asterisk/freepbx.conf"
	else
		FREEPBX_CONF="/etc/freepbx.conf"
	fi
fi

if [ "$1" == "-h" ]
then
	echo "Usage: "
	echo "   "$0" [config]"
	echo
	echo "If config file is not specified, FreePBX must be installed and"
	echo "the script will bootstrap the information"
	echo
	exit
fi

if [ -n "$1" ]
then
	AMPCONFIG=$1
	if [ ! -e $AMPCONFIG ]
	then
		echo "Cannot find $AMPCONFIG"
		exit
	fi
	# include config file
	echo "Reading $AMPCONFIG"
else
	echo "Bootstrapping Configuration Settings"
fi

# get settings from db/config file
if [ -n "$AMPCONFIG" ]; then # If told to parse out of file, go for it
	. $AMPCONFIG
elif [[ -e $FREEPBX_CONF ]]; then

	# get the path of this file to call the gen_amp_conf.php script which will
	# generate all the amp_conf variables that can be exported
	#
	progdir=`dirname $0`
	sv_pwd=$PWD
        cd $progdir
        gen_path=$PWD
        cd $sv_pwd
	`$gen_path/amp_conf/bin/gen_amp_conf.php`
else 
	echo;
	echo "FreePBX config file not found!";
	echo "specificy amportal.conf as argument if FreePBX is not yet installed";
	exit;
fi

echo "Updating configuration..."

echo "$ASTETCDIR/cdr_mysql.conf"
sed -i.bak "s/user\s*=.*$/user = $AMPDBUSER/" $ASTETCDIR/cdr_mysql.conf
sed -i.bak "s/password\s*=.*$/password = $AMPDBPASS/" $ASTETCDIR/cdr_mysql.conf
sed -i.bak "s/hostname\s*=.*$/hostname = $AMPDBHOST/" $ASTETCDIR/cdr_mysql.conf

echo "$ASTETCDIR/manager.conf"
sed -i.bak "s/secret\s*=.*$/secret = $AMPMGRPASS/" $ASTETCDIR/manager.conf
sed -i.bak "s/\s*\[general\].*$/TEMPCONTEXT/;s/\[.*\]/\[$AMPMGRUSER\]/;s/^TEMPCONTEXT$/\[general\]/" $ASTETCDIR/manager.conf

echo "$ASTETCDIR/vm_email.inc"
if [ "xx$AMPWEBADDRESS" = "xx" ]; then
	echo "You might need to modify /etc/asterisk/vm_email.inc manually"
else
	sed -i.bak "s!http://.*/recordings!http://$AMPWEBADDRESS/recordings!" $ASTETCDIR/vm_email.inc
fi


if [ -x $AMPSBIN/amportal ]; then 
	echo "Adjusting File Permissions.."
	$AMPSBIN/amportal chown
fi

echo "Done"
