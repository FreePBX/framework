#!/bin/sh

# copy in new config
cp -rf /usr/src/AMP/amp_conf/* /
/usr/src/AMP/chown_asterisk.sh

# reload asterisk
asterisk -rx reload

# Update FOP config to latest format
su - asterisk -c "cd /var/www/html/panel && /var/www/html/panel/convert_config_pre_14.pl"

# apply the new configuration
asterisk -rx reload
su - asterisk -c "/var/www/html/admin/retrieve_op_conf_from_mysql.pl"
su - asterisk -c "/var/www/html/admin/bounce_op.sh"

