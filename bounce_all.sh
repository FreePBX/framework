#!/bin/bash
/usr/src/AMP/chown_asterisk.sh
asterisk -rx "stop when convenient"
echo PLEASE WAIT
sleep 10
/sbin/modprobe -r wcfxs
/sbin/modprobe wcfxs
su - asterisk -c "export PATH=$PATH:/usr/sbin && export LD_ASSUME_KERNEL=2.4.1 && export LD_LIBRARY_PATH=/usr/local/lib && /usr/sbin/safe_asterisk"
echo COMPLETE
