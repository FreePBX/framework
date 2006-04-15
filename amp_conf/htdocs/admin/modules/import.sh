#!/bin/sh

for modname in asterisk-cli backup callforward callwaiting conferences disa donotdisturb featurecodeadmin infoservices irc ivr music paging queues recordings ringgroups timeconditions voicemail miscdests
do
  echo $modname
  svn co https://svn.sourceforge.net/svnroot/amportal/modules/branches/2.1/$modname
done
