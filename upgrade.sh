#!/bin/sh

# Update FOP config to latest format
su - asterisk -c "cd /var/www/html/panel && /var/www/html/panel/convert_config_pre_14.pl"

apply the new configuration
/usr/src/AMP/apply_conf.sh
