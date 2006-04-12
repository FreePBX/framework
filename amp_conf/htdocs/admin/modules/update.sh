#!/bin/sh

for modname in asterisk-cli backup callforward callwaiting conferences disa donotdisturb featurecodeadmin infoservices irc ivr music paging queues recordings ringgroups timeconditions voicemail
do
  cd $modname
  svn update
  cd ..
done
