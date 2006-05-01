#!/bin/sh

for modname in asterisk-cli backup callforward callwaiting conferences disa donotdisturb featurecodeadmin infoservices irc ivr music paging queues recordings ringgroups timeconditions voicemail miscdests callback findmefollow javassh logfiles
do
  echo $modname
  cd $modname
  svn status
  cd ..
done
