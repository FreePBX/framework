#!/bin/bash
# use this script like:
# md5-amp_conf.sh 1.10.00X

case "$1" in
	?*)

cd ../amp_conf

find agi-bin  astetc  bin  cgi-bin htdocs  htdocs_panel  mohmp3  sbin sounds -type f | xargs md5sum | grep -v .svn > ../upgrades/$1.md5

	;;
	*)

echo "usage: md5-amp_conf <version>";

	;;
esac
