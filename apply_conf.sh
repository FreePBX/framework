cp -rf /usr/src/AMP/amp_conf/* /
/usr/src/AMP/chown_asterisk.sh
asterisk -rx reload
su - asterisk -c /var/www/html/admin/retrieve_op_conf_from_mysql.pl
su - asterisk -c /var/www/html/admin/bounce_op.sh
