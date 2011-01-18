<?php

if (! function_exists("out")) {
	function out($text) {
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

global $db, $amp_conf;

outn(_("Checking for freepbx_settings table.."));
$sql = 'SELECT count(*) FROM freepbx_settings';
$result = $db->query($sql);
unset($sql);
if(DB::IsError($result)){
	$sql[] = "CREATE TABLE `freepbx_settings` (
	  `keyword` varchar(50) default NULL,
	  `value` varchar(255) default NULL,
	  `level` tinyint(1) default 0,
	  `description` text default NULL,
	  `type` varchar(25) default NULL,
	  `options` text default NULL,
	  `defaultval` varchar(255) default NULL,
	  `readonly` tinyint(1) default 0,
	  `hidden` tinyint(1) default 0,
	  `category` varchar(50) default NULL,
	  `module` varchar(25) default NULL,
	  `emptyok` tinyint(1) default 1,
	  PRIMARY KEY  (`keyword`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1";

	foreach ($sql as $q) {
		$result = $db->query($q);
		if(DB::IsError($result)){
			die_freepbx($result->getDebugInfo());
		}
	}
  out(_("created"));
} else {
  out(_("exists"));
}
	
// Make sure we save the settings as they are currenlty parsed
//
$current_amp_conf = $amp_conf;

// Now let's initialize all the settings, if they happen to already exist for
// some reason, this is ok as the define_conf_settings() method does not save the
// values back once defined, you must explicitly set them. Don't commit to db,
// that is down a little further down anyhow.
//
outn(_("Initialize freepbx_conf settings.."));
freepbx_settings_init(false);
out(_("ok"));

// Now we will set the current value of all settings
//
$freepbx_conf =& freepbx_conf::create();
$update_arr = array();
out(_("Migrate current values into freepbx_conf.."));
foreach ($current_amp_conf as $key => $val) {
  outn(sprintf(_("checking %s .."),$key));
  if (!$freepbx_conf->conf_setting_exists($key)) {
    out(_("not in freepbx_conf, skipping"));
    continue;
  }
  // Make sure that all "false" values are going to be interpreted as false, the trues will be converted.
  switch (strtolower($val)) {
  case 'false':
  case 'no':
  case 'off':
    $val = false;
    break;
  }
  $update_arr[$key] = $val;
  out(_("preparing for update"));
}
unset($current_amp_conf);
if (count($update_arr)) {
  outn(_("Updating prepared settings.."));
  $ret = $freepbx_conf->set_conf_values($update_arr, true, true);
  out(sprintf(_("changed %s settings"),$ret));
} else {
  out(_("There were no settings to update"));
}

// build freepbx.conf if it doesnt already exists
outn(_("checking for freepbx.conf.."));

$freepbx_conf = getenv('FREEPBX_CONF');
if ($freepbx_conf && file_exists($freepbx_conf)) {
  out(sprintf(_("%s already exists"),$freepbx_conf));
} else if (file_exists('/etc/freepbx.conf')) {
  out(_("/etc/freepbx.conf already exists"));
} else if (file_exists('/etc/asterisk/freepbx.conf')) {
  out(_("/etc/asterisk/freepbx.conf already exists"));
} else {

  if ($freepbx_conf) {
    $filename = $freepbx_conf;
  } else {
	  $filename = is_writable('/etc') ? '/etc/freepbx.conf' : '/etc/asterisk/freepbx.conf';
  }
	
	$txt = '';
	$txt .= '<?php' . "\n";
	$txt .= '$amp_conf[\'AMPDBUSER\']	= "' . $amp_conf['AMPDBUSER'] . '";' . "\n";
	$txt .= '$amp_conf[\'AMPDBPASS\']	= "' . $amp_conf['AMPDBPASS'] . '";' . "\n";
	$txt .= '$amp_conf[\'AMPDBHOST\']	= "' . $amp_conf['AMPDBHOST'] . '";' . "\n";
	$txt .= '$amp_conf[\'AMPDBNAME\']	= "' . $amp_conf['AMPDBNAME'] . '";' . "\n";
	$txt .= '$amp_conf[\'AMPDBENGINE\']	= "' . $amp_conf['AMPDBENGINE'] . '";' . "\n";
	$txt .= '$amp_conf[\'datasource\']	= "' . $amp_conf['datasource'] . '";' . "\n";
	$txt .= 'require_once(\'' . $amp_conf['AMPWEBROOT'] . '/admin/bootstrap.php\');' . "\n";

  // don't use file_put_contents re php4 compatibility for framework/core
  // or TODO: do we have install_amp include the compatibility library which we have installed by now?
  //
  $fh = fopen($filename,'w');
  if ($fh === false || (fwrite($fh,$txt) === false)) {
    out(sprintf(_("FATAL error writing  %s"),$filename));
    die_freepbx(_("You must have a proper freepbx.conf file to proceed"));
  }
  fclose($fh);
  out(sprintf(_("created %s"),$filename));
}
?>
