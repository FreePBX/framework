<?php
class freepbx_conf {

  var $conf_defaults = array(
	'AMPDBENGINE'    => array('std' , 'mysql'),
	'AMPDBNAME'      => array('std' , 'asterisk'),
	'AMPENGINE'      => array('std' , 'asterisk'),
	'ASTMANAGERPORT' => array('std' , '5038'),
	'ASTMANAGERHOST' => array('std' , 'localhost'),
	'AMPDBHOST'      => array('std' , 'localhost'),
	'AMPDBUSER'      => array('std' , 'asteriskuser'),
	'AMPDBPASS'      => array('std' , 'amp109'),
	'AMPMGRUSER'     => array('std' , 'admin'),
	'AMPMGRPASS'     => array('std' , 'amp111'),
	'FOPPASSWORD'    => array('std' , 'passw0rd'),
	'FOPSORT'        => array('std' , 'extension'),
	'AMPSYSLOGLEVEL '=> array('std' , 'LOG_ERR'),
	'ARI_ADMIN_PASSWORD' => array('std' , 'ari_password'),
	'TCINTERVAL'     => array('std' , '60'),
  'CFRINGTIMERDEFAULT' => array('std' , '0'),
	'NOOPTRACE'       => array('std' , '1'),

	'ASTETCDIR'      => array('dir' , '/etc/asterisk'),
	'ASTMODDIR'      => array('dir' , '/usr/lib/asterisk/modules'),
	'ASTVARLIBDIR'   => array('dir' , '/var/lib/asterisk'),
	'ASTAGIDIR'      => array('dir' , '/var/lib/asterisk/agi-bin'),
	'ASTSPOOLDIR'    => array('dir' , '/var/spool/asterisk/'),
	'ASTRUNDIR'      => array('dir' , '/var/run/asterisk'),
	'ASTLOGDIR'      => array('dir' , '/var/log/asterisk'),
	'AMPBIN'         => array('dir' , '/var/lib/asterisk/bin'),
	'AMPSBIN'        => array('dir' , '/usr/sbin'),
	'AMPWEBROOT'     => array('dir' , '/var/www/html'),
	'FOPWEBROOT'     => array('dir' , '/var/www/html/panel'),
	'MOHDIR'         => array('dir' , 'mohmp3'),
	'FPBXDBUGFILE'	 => array('dir' , '/tmp/freepbx_debug.log'),

	'USECATEGORIES'  => array('bool' , true),
	'ENABLECW'       => array('bool' , true),
	'CWINUSEBUSY'    => array('bool' , true),
	'FOPRUN'         => array('bool' , true),
	'AMPBADNUMBER'   => array('bool' , true),
	'DEVEL'          => array('bool' , false),
	'DEVELRELOAD'    => array('bool' , false),
	'CUSTOMASERROR'  => array('bool' , true),
	'DYNAMICHINTS'   => array('bool' , false),
	'BADDESTABORT'   => array('bool' , false),
	'SERVERINTITLE'  => array('bool' , false),
	'XTNCONFLICTABORT' => array('bool' , false),
	'USEDEVSTATE'    => array('bool' , false),
	'MODULEADMINWGET'=> array('bool' , false),
	'AMPDISABLELOG'  => array('bool' , true),
	'AMPENABLEDEVELDEBUG'=> array('bool' , false),
	'AMPMPG123'       => array('bool' , true),
	'FOPDISABLE'      => array('bool' , false),
	'ZAP2DAHDICOMPAT' => array('bool' , false),
	'USEQUEUESTATE'   => array('bool' , false),
	'CHECKREFERER'    => array('bool' , true),
	'USEDIALONE'      => array('bool' , false),
	'RELOADCONFIRM'   => array('bool' , true),
	'DISABLECUSTOMCONTEXTS'   => array('bool' , false),
	'DIVERSIONHEADER' => array('bool' , false),
	'TCMAINT'         => array('bool' , true),
	'DAYNIGHTTCHOOK'  => array('bool' , false),
  );

  var $db_store;

  var $conf = array();

  var $asterisk_conf = array();

  var $parsed_from_db = false;
  var $amportal_canwrite;

  function &create() {
    static $obj;
    global $db;
    if (!isset($obj)) {
      $obj = new freepbx_conf();
    }
    return $obj;
  }

  function freepbx_conf($var) {
    $this->__construct();
  }
  function __construct() {
    $db_raw = sql('SELECT * from freepbx_settings', 'getAll', DB_FETCHMODE_ASSOC);
    foreach($db_raw as $setting) {
      $this->db_store[$setting['keyword']] = $setting;
      $this->db_store[$setting['keyword']]['modified'] = false;
      // setup the conf array also
      $this->conf[$setting['keyword']] = $setting['value'];
    }
    unset($db_raw);
  }

  function parse_amportal_conf($filename, $bootstrap_conf = array()) {
	  global $db;

    // if we have loaded for the db, then just return what we already have
    if ($this->parsed_from_db) {
	    return $this->conf;
    }

	  /* defaults
	  * This defines defaults and formating to assure consistency across the system so that
	  * components don't have to keep being 'gun shy' about these variables.
	  * 
	  * we will read these settings out of the db, but only when $filename is writeable
	  * otherwise, we read the $filename
	  */
    // If conf file is not writable, then we use it as the master so parse it.
	  if (!is_writable($filename)) {
		  $file = file($filename); 
		  if (is_array($file)) { 
        $write_back = false;
			  foreach ($file as $line) { 
				  if (preg_match("/^\s*([a-zA-Z0-9_]+)=([a-zA-Z0-9 .&-@=_!<>\"\']+)\s*$/",$line,$matches)) { 
            // overrite anything that was initialized from the db with the conf file authoritative source
            // if different from the db value then let's write it back to the db
            if (!isset($this->conf[$matches[1]]) || $this->conf[$matches[1]] != $matches[2]) {
					    $this->conf[ $matches[1] ] = $matches[2]; 
              if (isset($this->db_store[$matches[1]])) {
                $this->db_store[$matches[1]]['value'] = $matches[2];
                $this->db_store[$matches[1]]['modified'] = true;
                $write_back = true;
              }
            }
				  } 
 			  } 
			  $this->conf['amportal_canwrite'] = false;
        $this->amportal_canwrite = false;
        if ($write_back) {
          $this->commit_settings();
        }
		  } else { 
			  die_freepbx("<h1>".sprintf(_("Missing or unreadable config file (%s)...cannot continue"), $filename)."</h1>"); 
		  }
      // Need to handle transitionary period where modules are adding new settings. So once we parsed the file
      // we still go read from the database and add anything that isn't there from the conf file.
      //
	  } else {
			$this->conf['amportal_canwrite'] = true;
      $this->amportal_canwrite = true;
      $this->parsed_from_db = true;
    }
    // TODO: am I correct to NOT write these back to the db_store?
    foreach ($bootstrap_conf as $key => $value) {
      $this->conf['key'] = $value;
    }

    // TODO: have a closer look at these in light of default values in the db.
    //       this does set some minimum settings that should be there but the db
    //       could have different defaults right? 
    //
	  // set defaults
	  foreach ($this->conf_defaults as $key=>$arr) {

		  switch ($arr[0]) {
			  // for type dir, make sure there is no trailing '/' to keep consistent everwhere
			  //
			  case 'dir':
				  if (!isset($this->conf[$key]) || trim($this->conf[$key]) == '') {
					  $this->conf[$key] = $arr[1];
				  } else {
					  $this->conf[$key] = rtrim($this->conf[$key],'/');
				  }
				  break;
			  // booleans:
			  // "yes", "true", "on", true, 1 (case-insensitive) will be treated as true, everything else is false
			  //
			  case 'bool':
				  if (!isset($this->conf[$key])) {
					  $this->conf[$key] = $arr[1];
				  } else {
					  $this->conf[$key] = ($this->conf[$key] === true || strtolower($this->conf[$key]) == 'true' || $this->conf[$key] === 1 || $this->conf[$key] == '1' 
					                                      || strtolower($this->conf[$key]) == 'yes' ||  strtolower($this->conf[$key]) == 'on');
				  }
				  break;
			  default:
				  if (!isset($this->conf[$key])) {
					  $this->conf[$key] = $arr[1];
				  } else {
					  $this->conf[$key] = trim($this->conf[$key]);
				  }
		  }
	  }

	  $convert = array(
		  'astetcdir'    => 'ASTETCDIR',
		  'astmoddir'    => 'ASTMODDIR',
		  'astvarlibdir' => 'ASTVARLIBDIR',
		  'astagidir'    => 'ASTAGIDIR',
		  'astspooldir'  => 'ASTSPOOLDIR',
		  'astrundir'    => 'ASTRUNDIR',
		  'astlogdir'    => 'ASTLOGDIR'
	  );

	  $file = file($this->conf['ASTETCDIR'].'/asterisk.conf');
	  foreach ($file as $line) {
		  if (preg_match("/^\s*([a-zA-Z0-9]+)\s* => \s*(.*)\s*([;#].*)?/",$line,$matches)) { 
			  $this->asterisk_conf[ $matches[1] ] = rtrim($matches[2],"/ \t");
		  }
	  }

	  // Now that we parsed asterisk.conf, we need to make sure $amp_conf is consistent
	  // so just set it to what we found, since this is what asterisk will use anyhow.
	  //
	  foreach ($convert as $ast_conf_key => $amp_conf_key) {
		  if (isset($this->conf[$ast_conf_key])) {
			  $this->conf[$amp_conf_key] = $this->asterisk_conf[$ast_conf_key];
		  }
	  }

	  return $this->conf;
  }

  function get_asterisk_conf() {
	  return $this->asterisk_conf;
  }

  // Commit back any dirty settings to the database, if they are not modified then
  // don't bother.
  function commit_settings() {
    // TODO: foreach db_store that modified === true, package up to do an update
    //       then mark clean
  }

  function get_setting($keyword) {
    if (isset($this->db_store[$keyword])) {
      return $this->db_store[$keyword];
    } else {
      return false;
    }
  }

  // TODO: used to insert or update an existing setting such as in an install
  //       script. $vars will include some required fields and we will be strict
  //       with a die_freebpx() if they are missing. Some fields are optional
  //       and we will set default values also if appropriate.
  //
  function update_setting($keyword,$vars,$commit=false) {
  }

  // Used to remove settings from the database that are no longer needed like with an
  // uninstall script.
  // TODO: remove from db_store, from conf, delete from table directly
  function del_setting($keyword) {
  }

}

//TODO: if running in crippled mode, then at retrieve_conf time I think we should write to
//      the notification systems that they need to make the file writable to us since
//      we need to live with this transition at least for a while.

/** Replaces variables in a string with the values from ampconf
 * eg, "%AMPWEBROOT%/admin" => "/var/www/html/admin"
 */
function ampconf_string_replace($string) {
	$freepbx_conf =& freepbx_conf::create();
	
	$target = array();
	$replace = array();
	
	foreach ($freepbx_conf->conf as $key=>$value) {
		$target[] = '%'.$key.'%';
		$replace[] = $value;
	}
	
	return str_replace($target, $replace, $string);
}

/** Expands variables from amportal.conf 
 * Replaces any variables enclosed in percent (%) signs with their value
 * eg, "%AMPWEBROOT%/admin/functions.inc.php"
 */
//TODO: seems this the exact same as the above function. Should either be removed?
function expand_variables($string) {
	return ampconf_string_replace($string);
}
?>
