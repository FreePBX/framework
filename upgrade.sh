#!/bin/sh

VERSION=1.10.006


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

# check to see if we are running this from the right place
if [ ! -e "upgrade.sh" ] 
	then
	echo;
	echo "You must run this script from AMP's source directory";
	echo;
	exit;
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

###################################
# UpdateVersion function
UpdateVersion ()
{
	echo "Updating version information in database"
	echo
	# Update voxbox version Major.asterisk.vbswver
	mysql --user=AMPDBUSER --password=AMPDBPASS --execute="UPDATE admin SET value = '$VERSION' WHERE variable = 'version'" asterisk
}


# copy in new config
source apply_conf.sh

UpdateVersion

echo "AMP upgrade complete.  Please see CHANGES to see what is new."
