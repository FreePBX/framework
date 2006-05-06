#!/bin/sh

. ./modlist.sh

for modname in $FREEPBX_MODLIST
do
  echo $modname
  svn co https://svn.sourceforge.net/svnroot/amportal/modules/branches/2.1/$modname
done
