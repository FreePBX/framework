#!/bin/bash
# use this script like:
# md5-amp_conf.sh 1.10.00X

case "$1" in
	?*)

cd ../amp_conf

find var etc usr -type f \! -name 'vm_email.inc' \! -name 'defines.php' \! -name 'op_server.cfg' \! -name 'dialparties.agi' \! -name 'manager.conf' \! -name '*.pl' \! -name 'cdr_mysql.conf' \! -name 'voicemail.conf' | xargs md5sum > ../upgrades/$1.md5

	;;
	*)

echo "usage: md5-amp_conf <version>";

	;;
esac
