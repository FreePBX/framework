#!/bin/sh

VERSION=1.10.005


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

###################################
# UpdateVersion function
UpdateVersion ()
{
	echo "Updating version information in database"
	echo
	# Update voxbox version Major.asterisk.vbswver
	mysql --user=asteriskuser --password=amp109 --execute="UPDATE admin SET value = '$VERSION' WHERE variable = 'version'" asterisk
}

echo
echo "Warning!!!"
echo "This AMP upgrade will overwrite your current Asterisk configuration files."
echo "You will _not_ lose configurations you made from the AMP web admin."
echo
echo "Press enter to continue."
echo

read anything

# copy in new config
cp -rf /usr/src/AMP/amp_conf/* /
/usr/src/AMP/chown_asterisk.sh

# reload asterisk
asterisk -rx reload

echo
echo "New configuration applied ..."
echo 

UpdateVersion

echo "AMP upgrade complete.  Please see CHANGES to see what is new."
