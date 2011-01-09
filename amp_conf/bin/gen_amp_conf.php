#!/usr/bin/php -q
<?php
  // Generate the a list of variables that can be sourced by
  // a bash script
  $bootstrap_settings['freepbx_auth'] = false;
  $bootstrap_settings['skip_astman'] = true;//no need for astman here
  $restrict_mods = true;//no need for modules here
  if (!@include_once(getenv("FREEPBX_CONF") ? getenv("FREEPBX_CONF") : "/etc/freepbx.conf")) {
    include_once("/etc/asterisk/freepbx.conf");
  }
  foreach($amp_conf as $key => $val) {
    if (is_bool($val)) {
      echo "export " . trim($key) . "=" . ($val?"TRUE":"FALSE") ."\n";
    } else {
      echo "export " . trim($key) . "=" . escapeshellcmd(trim($val)) ."\n";
    }
  }
