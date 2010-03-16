<?php
global $amp_conf;
global $db;

/*  fix manager.conf settings for older manager.conf files being upgraded as new permissions are needed for later releases of Asterisk
 *  in english, this is limited to everything between the AMPMGRUSER section and any new section the user may have edited. It replaces
 *  everything to the right of a 'read =' or 'write =' permission line with the full set of permissoins Asterisk offers.
 */
exec('sed -i.2.8.0.bak "/^\['.$amp_conf['AMPMGRUSER'].'\]/,/^\[.*\]/s/^\(\s*read\s*=\|\s*write\s*=\).*/\1 system,call,log,verbose,command,agent,user,config,command,dtmf,reporting,cdr,dialplan,originate/" '.$amp_conf['ASTETCDIR'].'/manager.conf',$outarr,$ret);

$autoincrement = (($amp_conf["AMPDBENGINE"] == "sqlite") || ($amp_conf["AMPDBENGINE"] == "sqlite3")) ? "AUTOINCREMENT":"AUTO_INCREMENT";

$dadhi_table = "
CREATE TABLE `dadhi` (
  `id` varchar(20) NOT NULL default '-1',
  `keyword` varchar(30) NOT NULL default '',
  `data` varchar(255) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`)
);
";

outn("Create dahdi table..");
$result = $db->query($dahdi_table);
if (DB::IsError($result) && $result->getCode() == DB_ERROR_ALREADY_EXISTS ) {
  out("already exists");
} elseif (DB::IsError($result)) {
  out("FAILED..not created");
	out($result->getMessage());
} else {
  out("ok");
}

$trunk_dialpatterns = "
CREATE TABLE `trunk_dialpatterns` 
( 
	`trunkid` INTEGER,
  `match_pattern_prefix` VARCHAR(50) NULL, 
  `match_pattern_pass` VARCHAR(50) NULL, 
  `prepend_digits` VARCHAR(50) NULL,
	`seq` INTEGER,
	PRIMARY KEY  (`trunkid`, `match_pattern_prefix`, `match_pattern_pass`, `prepend_digits`, `seq`) 
) 
";
outn(_("Checking if trunk_dialpatterns table exists.."));
$check = $db->query($trunk_dialpatterns);
if(DB::IsError($check) && $check->getCode() != DB_ERROR_ALREADY_EXISTS) {
	die_freepbx(_("Can not create trunk_dialpatterns table").$check->getDebugInfo());
} else if(DB::IsError($check) && $check->getCode() == DB_ERROR_ALREADY_EXISTS) {
	out(_("already exists"));
} else {
	out(_("created"));
  outn(_("migrating rules.."));
  $patterns = sql('SELECT * FROM trunks_dialpatterns','getAll',DB_FETCHMODE_ASSOC);
  $patterns_insert = array();
  foreach ($patterns as $key => $pattern_rec) {
    $pattern = $pattern_rec['rule'];
    $trunkid = $pattern_rec['trunkid'];
    $seq = $pattern_rec['seq'];

    // convert x n and z to uppercase
    $regex = str_replace(array('x','n','z'), array('X','N','Z'), $pattern);
    // sanitize the pattern - remove any non-pattern chars
    $regex = preg_replace("/[^0-9XNZwW#*\.\[\]\-\+\|]/", "", $regex);
    // Also kill the '-' characters outside of groups
    $regex = preg_replace("/((?:\[[^\]]*\])*)([^\[\]\-]*)-?/", "$1$2", $regex);

    if (preg_match('/^(([0-9XNZwW#*\.\[\]\-]+)\|)?(([0-9XNZwW#*\.\[\]\-]+)\+)?([0-9XNZwW#*\.\[\]\-]*)$/', $regex, $matches)) {
      // one of NXXXXXX, 613|NXXXXXX   1+NXXXXXX    613|1+NXXXXXX,  
      // matches[2] = drop (eg 613),  matches[4] = prepend_digits (eg 1),  matches[5] = rest of number (eg NXXXXX)

      $match_pattern_prefix = $matches[2];
      $prepend_digits = $matches[4];
      $match_pattern_pass = $matches[5];
    } else if (preg_match('/^(([0-9XNZwW#*\.\[\]\-]+)\+)?(([0-9XNZwW#*\.\[\]\-]+)\|)?([0-9XNZwW#*\.\[\]\-]*)$/', $regex, $matches)) {
      // one of NXXXXXX,  613|NXXXXXX   1+NXXXXXX    1+613|NXXXXXX
      // matches[2] = prepend_digits (eg 1),  matches[4] = drop (eg 613),  matches[5] = rest of number (eg NXXXXX)

      $match_pattern_prefix = $matches[4];
      $prepend_digits = $matches[2];
      $match_pattern_pass = $matches[5];
    } else {
      // UNRECOGNIZED PATTERN
      out(sprintf(_("unrecognized rule: %s discarding"),$pattern));
      outn(_("continuing.."));
    }
    $patterns[$key]['match_pattern_prefix'] = $match_pattern_prefix;
    $patterns[$key]['match_pattern_pass'] = $match_pattern_pass;
    $patterns[$key]['prepend_digits'] = $prepend_digits;

    $patterns_insert[] = array($trunkid,$match_pattern_prefix,$match_pattern_pass,$prepend_digits,$seq);
  }
  $compiled = $db->prepare('INSERT INTO `trunk_dialpatterns` 
    (`trunkid`, `match_pattern_prefix`, `match_pattern_pass`, `prepend_digits`, `seq`) 
    VALUES (?,?,?,?,?)'
  );
  $result = $db->executeMultiple($compiled,$patterns_insert);
  if(DB::IsError($result)) {
    out(_("FATAL, migration failed"));
    die_freepbx($result->getDebugInfo());
  }
  out(_('ok'));
  unset($pattern_insert);
  outn(_('dropping old trunks_dialpatterns table..'));
  $check = $db->query('DROP TABLE `trunks_dialpatterns`');
  if(DB::IsError($check)) {
    out(_('ERROR - could not drop table').$check->getDebugInfo());
  } else {
    out(_('ok'));
  }
}
