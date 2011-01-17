<?php
define("CONF_TYPE_BOOL",   'bool');
define("CONF_TYPE_TEXT",   'text');
define("CONF_TYPE_DIR",    'dir');
define("CONF_TYPE_INT",    'int');
define("CONF_TYPE_UINT",   'uint');
define("CONF_TYPE_SELECT", 'select');

// TODO: remember to come back and replace the core functions being used (or have them go
//       through this.
//
// For translation (need to be in english in the DB, translated when pulled out
// TODO: is there a better place to put these like in install script?
//
if (false) {
  _('No Description Provided');
  _('Undefined Category');
}

// TODO: weed out some of these module only ones once modules updated
class freepbx_conf {
  // legacy until a better way
  var $legacy_conf_defaults = array(
  'AMPDBENGINE'    => array(CONF_TYPE_SELECT, 'mysql'),
  'AMPDBNAME'      => array(CONF_TYPE_TEXT, 'asterisk'),
  'AMPENGINE'      => array(CONF_TYPE_SELECT, 'asterisk'),
  'ASTMANAGERPORT' => array(CONF_TYPE_UINT, '5038'),
  'ASTMANAGERHOST' => array(CONF_TYPE_TEXT, 'localhost'),
  'AMPDBHOST'      => array(CONF_TYPE_TEXT, 'localhost'),
  'AMPDBUSER'      => array(CONF_TYPE_TEXT, 'asteriskuser'),
  'AMPDBPASS'      => array(CONF_TYPE_TEXT, 'amp109'),
  'AMPMGRUSER'     => array(CONF_TYPE_TEXT, 'admin'),
  'AMPMGRPASS'     => array(CONF_TYPE_TEXT, 'amp111'),
  'FOPPASSWORD'    => array(CONF_TYPE_TEXT, 'passw0rd'),
  'FOPSORT'        => array(CONF_TYPE_SELECT, 'extension'),
  'AMPSYSLOGLEVEL '=> array(CONF_TYPE_UINT, 'LOG_ERR'),
  'NOOPTRACE'      => array(CONF_TYPE_UINT, '1'),
  'ARI_ADMIN_PASSWORD' => array(CONF_TYPE_TEXT, 'ari_password'),
  'CFRINGTIMERDEFAULT' => array(CONF_TYPE_SELECT, '0'),

  'ASTETCDIR'      => array(CONF_TYPE_DIR, '/etc/asterisk'),
  'ASTMODDIR'      => array(CONF_TYPE_DIR, '/usr/lib/asterisk/modules'),
  'ASTVARLIBDIR'   => array(CONF_TYPE_DIR, '/var/lib/asterisk'),
  'ASTAGIDIR'      => array(CONF_TYPE_DIR, '/var/lib/asterisk/agi-bin'),
  'ASTSPOOLDIR'    => array(CONF_TYPE_DIR, '/var/spool/asterisk/'),
  'ASTRUNDIR'      => array(CONF_TYPE_DIR, '/var/run/asterisk'),
  'ASTLOGDIR'      => array(CONF_TYPE_DIR, '/var/log/asterisk'),
  'AMPBIN'         => array(CONF_TYPE_DIR, '/var/lib/asterisk/bin'),
  'AMPSBIN'        => array(CONF_TYPE_DIR, '/usr/sbin'),
  'AMPWEBROOT'     => array(CONF_TYPE_DIR, '/var/www/html'),
  'FOPWEBROOT'     => array(CONF_TYPE_DIR, '/var/www/html/panel'),
  'MOHDIR'         => array(CONF_TYPE_DIR, 'mohmp3'),
  'FPBXDBUGFILE'	 => array(CONF_TYPE_DIR, '/tmp/freepbx_debug.log'),

  'USECATEGORIES'  => array(CONF_TYPE_BOOL, true),
  'ENABLECW'       => array(CONF_TYPE_BOOL, true),
  'CWINUSEBUSY'    => array(CONF_TYPE_BOOL, true),
  'FOPRUN'         => array(CONF_TYPE_BOOL, true),
  'AMPBADNUMBER'   => array(CONF_TYPE_BOOL, true),
  'DEVEL'          => array(CONF_TYPE_BOOL, false),
  'DEVELRELOAD'    => array(CONF_TYPE_BOOL, false),
  'CUSTOMASERROR'  => array(CONF_TYPE_BOOL, true),
  'DYNAMICHINTS'   => array(CONF_TYPE_BOOL, false),
  'BADDESTABORT'   => array(CONF_TYPE_BOOL, false),
  'SERVERINTITLE'  => array(CONF_TYPE_BOOL, false),
  'USEDEVSTATE'    => array(CONF_TYPE_BOOL, false),
  'MODULEADMINWGET'=> array(CONF_TYPE_BOOL, false),
  'AMPDISABLELOG'  => array(CONF_TYPE_BOOL, true),
  'FOPDISABLE'     => array(CONF_TYPE_BOOL, false),
  'CHECKREFERER'   => array(CONF_TYPE_BOOL, true),
  'RELOADCONFIRM'  => array(CONF_TYPE_BOOL, true),
  'DIVERSIONHEADER' => array(CONF_TYPE_BOOL, false),
  'ZAP2DAHDICOMPAT' => array(CONF_TYPE_BOOL, false),
  'XTNCONFLICTABORT' => array(CONF_TYPE_BOOL, false),
  'AMPENABLEDEVELDEBUG' => array(CONF_TYPE_BOOL, false),
  'DISABLECUSTOMCONTEXTS' => array(CONF_TYPE_BOOL, false),

  // Time Conditions (2.9 New)
  'TCINTERVAL'     => array(CONF_TYPE_UINT, '60'),
  'TCMAINT'        => array(CONF_TYPE_BOOL, true),

  // Queues
  'USEQUEUESTATE'  => array(CONF_TYPE_BOOL, false),

  // Day Night (2.9 New)
  'DAYNIGHTTCHOOK' => array(CONF_TYPE_BOOL, false),

  // Music
  'AMPMPG123'      => array(CONF_TYPE_BOOL, true),
  );

  // memory resident copy of freepbx_settings
  var $db_conf_store;
  // key=>value has equivalent to amp_conf and reference accessed into db_conf_store
  var $conf = array();
  // legacy $asterisk_conf that we need to obsolete
  var $asterisk_conf = array();
  var $parsed_from_db = false;
  var $amportal_canwrite;

  // access should always be through create so only one copy is ever running
  function &create() {
    static $obj;
    global $db;
    if (!isset($obj) || !is_object($obj)) {
      $obj = new freepbx_conf();
    }
    return $obj;
  }

  // php4 constructor
  function freepbx_conf() {
    $this->__construct();
  }
  // TODO: do we stay 'super' effecient and not validate settings upon read (we protect them upon write), or do we
  //       re-massage them every time we read them? (which would protect us from someone on the outside changing
  //       a db value incorrectly.
  function __construct() {
    global $db;
    $sql = 'SELECT * FROM freepbx_settings ORDER BY category, module, level, keyword';
    $db_raw = $db->getAll($sql, DB_FETCHMODE_ASSOC);
    if(DB::IsError($db_raw)) {
      die_freepbx(_('fatal error reading freepbx_settings'));	
    }
    foreach($db_raw as $setting) {
      $this->db_conf_store[$setting['keyword']] = $setting;
      $this->db_conf_store[$setting['keyword']]['modified'] = false;
      // setup the conf array also
      // note the reference assignment, if it's actually the authoritative source
      $this->conf[$setting['keyword']] =& $this->db_conf_store[$setting['keyword']]['value'];
      if (!$setting['emptyok'] && $setting['value'] == '') {
        $this->db_conf_store[$setting['keyword']]['value'] = $setting['defaultval'];
      }
    }
    unset($db_raw);
  }

  function parse_amportal_conf($filename, $bootstrap_conf = array(), $file_is_authority=false) {
	  global $db;

    // if we have loaded for the db, then just return what we already have
    if ($this->parsed_from_db && !$file_is_authority) {
	    return $this->conf;
    }

	  /* defaults
	  * This defines defaults and formatting to assure consistency across the system so that
	  * components don't have to keep being 'gun shy' about these variables.
	  * 
	  * we will read these settings out of the db, but only when $filename is writeable
	  * otherwise, we read the $filename
	  */
    // If conf file is not writable, then we use it as the master so parse it.
	  if (!is_writable($filename) || $file_is_authority) {
		  $file = file($filename); 
		  if (is_array($file)) { 
        $write_back = false;
			  foreach ($file as $line) { 
				  if (preg_match("/^\s*([a-zA-Z0-9_]+)=([a-zA-Z0-9 .&-@=_!<>\"\']+)\s*$/",$line,$matches)) { 
            // overrite anything that was initialized from the db with the conf file authoritative source
            // if different from the db value then let's write it back to the db
            // TODO: massage any data we read from the conf file with _preapre_conf_value since it is
            //       written back to the DB here if different from the DB.
            // TODO: investigate if this can be used for the actual migration code eliminating the need
            //       to do it there (since I think a lot is missing there including validation that is
            //       getting into the DB. Thinking more ... probably just translates into using the 
            //       define_conf_setting method?
            //
            if (!isset($this->conf[$matches[1]]) || $this->conf[$matches[1]] != $matches[2]) {
              if (isset($this->db_conf_store[$matches[1]])) {
                $this->db_conf_store[$matches[1]]['value'] = $matches[2];
                $this->db_conf_store[$matches[1]]['modified'] = true;
					      $this->conf[$matches[1]] =& $this->db_conf_store[$matches[1]]['value'];
                $write_back = true;
              } else {
					      $this->conf[$matches[1]] = $matches[2]; 
              }
            }
				  } 
 			  } 
			  $this->conf['amportal_canwrite'] = false;
        $this->amportal_canwrite = false;
        if ($write_back) {
          $this->commit_conf_settings();
        }
		  } else { 
			  die_freepbx(sprintf(_("Missing or unreadable config file (%s)...cannot continue"), $filename)); 
		  }
      // Need to handle transitionary period where modules are adding new settings. So once we parsed the file
      // we still go read from the database and add anything that isn't there from the conf file.
      //
	  } else {
			$this->conf['amportal_canwrite'] = true;
      $this->amportal_canwrite = true;
      $this->parsed_from_db = true;
    }
    // If boostrap_conf settings are passed in, add them to the class
    //
    // TODO: am I correct to NOT write these back to the database ?
    foreach ($bootstrap_conf as $key => $value) {
      $this->conf[$key] = $value;
    }

    // We set defaults above from the database so anything that needed a default
    // and had one available was set there. The only conflict here is if we did
    // not specify emptyok and yet the legacy ones do have a default.
    //
    // it looks like the only ones that don't accept an empty but set variable are directories
    //
	  // set defaults
    // TODO: change this to use _prepare_conf_value ?
    // TODO: beware that these are all free-form entered (e.g. booleans) need pre-conditioning if from conf file
	  foreach ($this->legacy_conf_defaults as $key=>$arr) {

		  switch ($arr[0]) {
			  // for type dir, make sure there is no trailing '/' to keep consistent everwhere
			  //
        case CONF_TYPE_DIR:
				  if (!isset($this->conf[$key]) || trim($this->conf[$key]) == '') {
					  $this->conf[$key] = $arr[1];
				  } else {
					  $this->conf[$key] = rtrim($this->conf[$key],'/');
				  }
				  break;
			  // booleans:
			  // "yes", "true", "on", true, 1 (case-insensitive) will be treated as true, everything else is false
			  //
        case CONF_TYPE_BOOL:
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

  // Check if a setting exists
  //
  function conf_setting_exists($keyword) {
    return isset($this->db_conf_store[$keyword]);
  }

  // TODO: no way to differentiate between a bad setting and boolean false
  //       but throwing an error on a bad keyword seems harsh? Maybe I should
  //       write out to the debug log or something?
  function get_conf_setting($keyword) {
    if (isset($this->db_conf_store[$keyword])) {
      return $this->db_conf_store[$keyword]['value'];
    } else {
      return false;
    }
  }

  // used to insert or update an existing setting such as in an install
  // script. $vars will include some required fields and we are strict
  // with a die_freebpx() if they are missing.
  //
  // we will not change the value if it exists.
  //
  // TODO - should I remove (or ignore) need for value. Is it always the default value
  //        or is there a scenario where this could be created with a default value set
  //        different then the initial value???
  //
  function define_conf_setting($keyword,$vars,$commit=false) {
    global $amp_conf;
    $attributes = array(
	    'keyword' => '',
	    'value' => '',
	    'level' => 0,
	    'description' => 'No Description Provided', // Don't gettext this
	    'type' => '',
	    'options' => '',
	    'defaultval' => '',
	    'readonly' => 0,
	    'hidden' => 0,
	    'category' => 'Undefined Category', // Don't gettext this
	    'module' => '',
	    'emptyok' => 1,
	    'modified' => false, // set to false to compare against existing array
      );
    // Got to have a type and value, if no type, _prepared_conf_value will throw an error
    $new_setting = !isset($this->db_conf_store[$keyword]);
    if (!$new_setting && $vars['type'] != $this->db_conf_store[$keyword]['type']) {
      die_freepbx(sprintf(_("you can't convert an existing type, keyword %s"),$keyword));
    }
    if (!isset($vars['value']) || !isset($vars['defaultval'])) {
      die_freepbx(sprintf(_("missing value and/or defaultval required for %s"),$keyword));
    } else {
      // validate even if already set, catches coding errors early even though we don't use it
      $value = $this->_prepare_conf_value($vars['value'], $vars['type']);
      $attributes['value'] = $new_setting ? $value : $this->db_conf_store[$keyword];
      $attributes['defaultval'] = $this->_prepare_conf_value($vars['defaultval'], $vars['type']);
      $attributes['keyword'] = $keyword;
      $attributes['type'] = $vars['type'];
    }
    if (isset($vars['category']) && $vars['category'] == '' && (!isset($vars['module']) || $vars['module'] == '')) {
      die_freepbx(_("You must set either a category or a module attribute"));
    }
    if ($vars['type'] == CONF_TYPE_SELECT) {
      if (!isset($vars['options']) || $vars['options'] == '') { 
        die_freepbx(sprintf(_("missing options for % required if type is select"),$keyword));
      } else {
        $attributes['options'] = is_array($vars['options']) ? implode(',',$vars['options']) : $vars['options'];
      }
    }
    if (isset($vars['level'])) {
      $attributes['level'] = (int) $vars['level'];
    }
    $optional = array('readonly', 'hidden', 'emptyok');
    foreach ($optional as $atrib) {
      if (isset($vars[$atrib])) {
        $attributes[$atrib] = $vars[$atrib] ? '1' : '0';
      }
    }
    $optional = array('description', 'category', 'module');
    foreach ($optional as $atrib) {
      if (isset($vars[$atrib])) {
        $attributes[$atrib] = $vars[$atrib];
      }
    }
    if ($new_setting || $attributes != $this->db_conf_store[$keyword]) {
      if (!$new_setting) {
        unset($attributes['keyword']);
        unset($attributes['value']);
        unset($attributes['type']);
        unset($attributes['modified']);
      }
      foreach ($attributes as $key => $val) {
        $this->db_conf_store[$keyword][$key] = $val;
      }
      if ($new_setting) {
        $this->conf[$keyword] =& $this->db_conf_store[$keyword]['value'];
        $amp_conf[$keyword] =& $this->conf[$keyword];
      }
      $this->db_conf_store[$keyword]['modified'] = true;
    }
    if ($commit) {
      $this->commit_conf_settings();
    }

    function get_conf_settings() {
      $this->db_conf_store;
    }
  }

  function set_conf_values($update_arr, $commit=false, $override_readonly=false) {
    $cnt = 0;
    if (!is_array($update_arr)) {
      die_freepbx(_("called set_conf_values with a non-array"));
    }
    foreach($update_arr as $keyword => $value) {
      if (!isset($this->db_conf_store[$keyword])) {
        die_freepbx(sprintf(_("trying to set keyword %s to %s on unitialized setting"),$keyword, $value));
      } 
      $prep_value = $this->_prepare_conf_value($value, $this->db_conf_store[$keyword]['type']);
      if ($prep_value != $this->db_conf_store[$keyword]['value'] && ($prep_value !== '' || $this->db_conf_store[$keyword]['emptyok']) && ($override_readonly || !$this->db_conf_store[$keyword]['readonly'])) {
        $this->db_conf_store[$keyword]['value'] = $prep_value;
        $this->db_conf_store[$keyword]['modified'] = true;
        $cnt++;
      }
    }
    if ($commit) {
      $this->commit_conf_settings();
    }
    return $cnt;
  }

  // TODO: need to address emptyok situation with INT and UINT at least
  //
  function _prepare_conf_value($value, $type) {
    switch ($type) {
    case CONF_TYPE_BOOL:
      $ret = $value ? 1 : 0;
      break;
    case CONF_TYPE_TEXT:
    case CONF_TYPE_SELECT:
      $ret = $value;
      break;
    case CONF_TYPE_DIR:
      $ret = rtrim($value,'/');
      break;
    case CONF_TYPE_INT:
      $ret = (int) $value;
      break;
    case CONF_TYPE_UINT:
      $ret = (int) $value < 0 ? 0 : (int) $value;
      break;
    default:
      die_freepbx(sprintf(_("unknown type: %s"),$type));
      break;
    }
    return $ret;
  }

  // same as remove_conf_settings
  function remove_conf_setting($setting) {
    return $this->remove_conf_settings($setting);
  }
  // Used to remove settings from the database that are no longer needed like with an
  // uninstall script.
  //
  function remove_conf_settings($settings) {
    global $db,$amp_conf;
    if (!is_array($settings)) {
      $settings = array($settings);
    }
    foreach ($settings as $setting) {
      if (isset($this->db_conf_store[$setting]) ) {
        unset($this->db_conf_store[$setting]);
      }
      if (isset($this->conf[$setting])) {
        unset($this->conf[$setting]);
      }
      //for legacy sakes
      if (isset($amp_conf[$setting])) {
        unset($amp_conf[$setting]);
      }
    }
    $sql = "DELETE FROM freepbx_settings WHERE keyword in ('".implode("','",$settings)."')";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
      die_freepbx(_('fatal error deleting rows from freepbx_settings: ').$sql);	
    }
  }

  // Commit back any dirty settings to the database, if they are not modified then
  // don't bother.
  function commit_conf_settings() {
    global $db;
    $update_array = array();
    foreach ($this->db_conf_store as $keyword => $atrib) {
      if (!$atrib['modified']) {
        continue;
      }
      //TODO: confirm that prepare with ? does an escapeSimple() or equiv, the docs say so
      $update_array[] = array(
        $keyword,
        $atrib['value'],
        $atrib['level'],
        $atrib['description'],
        $atrib['type'],
        $atrib['options'],
        $atrib['defaultval'],
        $atrib['readonly'],
        $atrib['hidden'],
        $atrib['category'],
        $atrib['module'],
        $atrib['emptyok'],
      );
      $this->db_conf_store[$keyword]['modified'] = false;
    }
    if (empty($update_array)) {
      return 0;
    }
    $sql = 'REPLACE INTO freepbx_settings 
      (keyword, value, level, description, type, options, defaultval, readonly, hidden, category, module, emptyok)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
    $compiled = $db->prepare($sql);
    $result = $db->executeMultiple($compiled,$update_array);
    if(DB::IsError($result)) {
      die_freepbx(_('fatal error updating freepbx_settings table'));	
    }
    return count($update_array);
  }
}

//TODO: if running in crippled mode, then at retrieve_conf time I think we should write to
//      the notification systems that they need to make the file writable to us since
//      we need to live with this transition at least for a while. (done by freepbx_engine)

// LEFT OVER Legacy, only used in a few places, we could add them as class functions or ???

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
