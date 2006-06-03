#!/bin/sh

. ./modlist.sh

for modname in $FREEPBX_MODLIST
do
  echo $modname
  rm -fr $modname
done
