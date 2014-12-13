<?php
global $db;

$sql = 'UPDATE cronmanager SET command = "/var/lib/asterisk/bin/module_admin listonline > /dev/null 2>&1" WHERE module = "module_admin"';
$db->query($sql);
