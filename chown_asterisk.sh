chown -R asterisk:asterisk /var/run/asterisk
chown -R asterisk:asterisk /etc/asterisk
chown -R asterisk:asterisk /var/lib/asterisk
chown -R asterisk:asterisk /var/log/asterisk
chown -R asterisk:asterisk /var/spool/asterisk
chown -R asterisk:asterisk /dev/zap
chown asterisk /dev/tty9
chown -R asterisk:asterisk /var/www
chmod u+x /var/lib/asterisk/agi-bin/*.agi
chmod u+x /var/www/cgi-bin/*.cgi
chmod u+x /var/www/html/admin/*.pl
chmod u+x /var/www/html/admin/*.sh
chmod u+x /var/www/html/panel/*.pl
