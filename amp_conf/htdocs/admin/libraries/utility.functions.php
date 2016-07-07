<?php

define('EOL', isset($_SERVER['REQUEST_METHOD']) ? "<br />" :  PHP_EOL);

define("FPBX_LOG_FATAL",    "FATAL");
define("FPBX_LOG_CRITICAL", "CRITICAL");
define("FPBX_LOG_SECURITY", "SECURITY");
define("FPBX_LOG_SIGNATURE_UNSIGNED", "SIGNATURE_UNSIGNED");
define("FPBX_LOG_UPDATE",   "UPDATE");
define("FPBX_LOG_ERROR",    "ERROR");
define("FPBX_LOG_WARNING",  "WARNING");
define("FPBX_LOG_NOTICE",   "NOTICE");
define("FPBX_LOG_INFO",     "INFO");
define("FPBX_LOG_PHP",      "PHP");

function SPLAutoloadBroken() {
	if(!class_exists('freepbxSPLAutoLoadTest',false)) {
		class freepbxSPLAutoLoadTest {
			public static $attempted = false;
			public static function loadClassLoader($class) {
				self::$attempted = true;
			}
		}
	}

	spl_autoload_register(array('freepbxSPLAutoLoadTest', 'loadClassLoader'));
	class_exists('freepbxSPLAutoLoadTestClassThatDoesNotExist');
	spl_autoload_unregister(array('freepbxSPLAutoLoadTest', 'loadClassLoader'));
	if(freepbxSPLAutoLoadTest::$attempted) {
		freepbxSPLAutoLoadTest::$attempted = false;
		return false;
	}
	return true;
}

/** In PHP 5.5 **/
if (!function_exists('json_last_error_msg')) {
	function json_last_error_msg() {
		static $ERRORS = array(
			JSON_ERROR_NONE => 'No error',
			JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
			JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
			JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX => 'Syntax error',
			JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
		);

		$error = json_last_error();
		return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
	}
}

/**
 * Log a message to the freepbx security file
 * @param  {string} $message the message
 */
function freepbx_log_security($txt) {
	$path = FreePBX::Config()->get('ASTLOGDIR');
	$log_file = $path.'/freepbx_security.log';

	$tz = date_default_timezone_get();
	if (!$tz) {
		$tz = 'America/Los_Angeles';
	}
	date_default_timezone_set($tz);
	$tstamp		= date("Y-m-d H:i:s");

	// Don't append if the file is greater than ~2G since some systems fail
	//
	$size = file_exists($log_file) ? sprintf("%u", filesize($log_file)) + strlen($txt) : 0;
	if ($size > 2000000000) {
		unlink($log_file);
	}
	file_put_contents($log_file, "[$tstamp] $txt\n", FILE_APPEND);
}
/**
 * FreePBX Logging facility to FILE or syslog
 * @param  string   The level/severity of the error. Valid levels use constants:
 *                  FPBX_LOG_FATAL, FPBX_LOG_CRITICAL, FPBX_LOG_SECURITY, FPBX_LOG_UPDATE,
 *                  FPBX_LOG_ERROR, FPBX_LOG_WARNING, FPBX_LOG_NOTICE, FPBX_LOG_INFO.
 * @param  string   The error message
 */
function freepbx_log($level, $message) {
	global $amp_conf;

	$php_error_handler = false;
	$bt = debug_backtrace();

	if (isset($bt[1]) && $bt[1]['function'] == 'freepbx_error_handler') {
		$php_error_handler = true;
	} elseif (isset($bt[1]) && $bt[1]['function'] == 'out' || $bt[1]['function'] == 'die_freepbx') {
		$file_full = $bt[1]['file'];
		$line = $bt[1]['line'];
	} elseif (basename($bt[0]['file']) == 'notifications.class.php') {
		$file_full = $bt[2]['file'];
		$line = $bt[2]['line'];
	} else {
		$file_full = $bt[0]['file'];
		$line = $bt[0]['line'];
	}

	if (!$php_error_handler) {
		$file_base = basename($file_full);
		$file_dir  = basename(dirname($file_full));
		$txt = sprintf("[%s] (%s/%s:%s) - %s\n", $level, $file_dir, $file_base, $line, $message);
	} else {
		// PHP Error Handler provides it's own formatting
		$txt = sprintf("[%s-%s\n", $level, $message);
	}

	// if it is not set, it's probably an initial installation so we want to log something
	if (!isset($amp_conf['AMPDISABLELOG']) || !$amp_conf['AMPDISABLELOG']) {
		$log_type = isset($amp_conf['AMPSYSLOGLEVEL']) ? $amp_conf['AMPSYSLOGLEVEL'] : 'FILE';
		switch ($log_type) {
			case 'LOG_EMERG':
			case 'LOG_ALERT':
			case 'LOG_CRIT':
			case 'LOG_ERR':
			case 'LOG_WARNING':
			case 'LOG_NOTICE':
			case 'LOG_INFO':
			case 'LOG_DEBUG':
				syslog(constant($log_type),"FreePBX - $txt");
				break;
			case 'SQL':     // Core will remove these settings once migrated,
			case 'LOG_SQL': // default to FILE during any interim steps.
			case 'FILE':
			default:
				// during initial install, there may be no log file provided because the script has not fully bootstrapped
				// so we will default to a pre-install log file name. We will make a file name mandatory with a proper
				// default in FPBX_LOG_FILE
				$log_file	= isset($amp_conf['FPBX_LOG_FILE']) ? $amp_conf['FPBX_LOG_FILE'] : '/tmp/freepbx_pre_install.log';

				// PHP Throws an error on install running of install_amp because the tiemzone isn't set. This is something that
				// should be done in the php.ini file but we will make an attempt to set it to something if we can't derive it
				// from the date_default_timezone_get() command which goes through heuristics of guessing.
				//
				$tz = date_default_timezone_get();
				if (!$tz) {
					$tz = 'America/Los_Angeles';
				}
				date_default_timezone_set($tz);
				$tstamp = date("Y-M-d H:i:s");

				// Don't append if the file is greater than ~2G since some systems fail
				//
				$size = file_exists($log_file) ? sprintf("%u", filesize($log_file)) + strlen($txt) : 0;
				if ($size < 2000000000) {
					$dn = dirname($log_file);
					if((file_exists($log_file) && is_writable($log_file)) || (!file_exists($log_file) && is_dir($dn) && is_writable($dn))) {
						file_put_contents($log_file, "[$tstamp] $txt", FILE_APPEND);
					} else {
						return false;
					}
				}
				break;
		}
	}
	return true;
}

/**
 * version_compare that works with FreePBX version numbers
 *
 * @param string $version1 First version number
 * @param string $version2 Second version number
 * @param string $op If you specify the third optional operator argument,
 *                   you can test for a particular relationship.
 *                   The possible operators are:
 *                   <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne respectively.
 * @return mixed returns -1 if the first version is lower than the second,
 *               0 if they are equal, and 1 if the second is lower.
 *               When using the optional operator argument, the function will
 *               return TRUE if the relationship is the one specified by the
 *               operator, FALSE otherwise.
 */
function version_compare_freepbx($version1, $version2, $op = null) {
	$version1 = str_replace("rc","RC", strtolower($version1));
	$version2 = str_replace("rc","RC", strtolower($version2));
	if (!is_null($op)) {
		return version_compare($version1, $version2, $op);
	} else {
		return version_compare($version1, $version2);
	}
}

function compress_framework_css() {
	compress::web_files();
}

/**
 * Throw FreePBX DIE Message
 * @param string $text The message
 * @param string $extended_text The Extended Message (Optional)
 * @param string $type The message type (Optional)
 */
function die_freepbx($text, $extended_text="", $type="FATAL") {
	if(is_object($extended_text) && method_exists($extended_text,"getMessage")) {
		$e = $extended_text;
		$extended_text = htmlentities($e->getMessage());
		$code = $e->getCode();
		throw new \Exception($text . "::" . $extended_text,$code,$e);
	} else {
		$extended_text = htmlentities($extended_text);
		throw new \Exception($text . "::" . $extended_text);
	}
}

/**
 * Get the FreePBX/Framework Version
 * @param bool $cached Whether to pull from the DB or not
 * @return string The FreePBX version number
 */
function getversion($cached=true) {
	global $db;
	static $version;
	if (isset($version) && $version && $cached) {
		return $version;
	}
	$sql		= "SELECT value FROM admin WHERE variable = 'version'";
	$results	= $db->getRow($sql);
	if($db->IsError($results)) {
		die_freepbx($sql."<br>\n".$results->getMessage());
	}
	return $results[0];
}

/**
 * Get the FreePBX/Framework Version (Depreciated in favor of getversion)
 * @param bool $cached Whether to pull from the DB or not
 * @return string The FreePBX version number
 */
function get_framework_version($cached=true) {
	global $db;
	static $version;
	if (isset($version) && $version && $cached) {
		return $version;
	}
	$sql		= "SELECT version FROM modules WHERE modulename = 'framework' AND enabled = 1";
	$version	= $db->getOne($sql);
	if($db->IsError($version)) {
		die_freepbx($sql."<br>\n".$version->getMessage());
	}
	return $version;
}

/**
 * Tell the user we need to apply changes and reload Asterisk
 */
function needreload() {
	global $db;
	$sql	= "UPDATE admin SET value = 'true' WHERE variable = 'need_reload'";
	$result	= $db->query($sql);
	if($db->IsError($result)) {
		die_freepbx($sql.$result->getMessage());
	}
}

/**
 * Check to see if Apply Changes/Need Reload flag has been set
 * @return bool true if reload needed, otherwise false
 */
function check_reload_needed() {
	global $db;
	global $amp_conf;
	$sql = "SELECT value FROM admin WHERE variable = 'need_reload'";
	$row = $db->getRow($sql);
	if($db->IsError($row)) {
		die_freepbx($sql.$row->getMessage());
	}
	//check from amp user if we are allowed to execute apply changes
	if(!isset($_SESSION["AMP_user"]) || !is_object($_SESSION["AMP_user"]) || !(get_class($_SESSION["AMP_user"]) == 'ampuser') || !$_SESSION["AMP_user"]->checkSection(99)) {
		return false;
	}
	return ($row[0] == 'true' || $amp_conf['DEVELRELOAD']);
}

/**
 * Log a debug message to a debug file (Depreciated)
 * @param  string   debug message to be printed
 * @param  string   depreciated
 * @param  string   depreciated
 */
function freepbx_debug($string, $option='', $filename='') {
	dbug($string);
}

function d($var, $tags = null) {
	if($amp_conf['PHP_CONSOLE']) {
		PhpConsole\Connector::getInstance()->getDebugDispatcher()->dispatchDebug($var, $tags, 1);
	}
}

/**
 * FreePBX Debugging function
 * This function can be called as follows:
 * dbug() - will just print a time stamp to the debug log file ($amp_conf['FPBXDBUGFILE'])
 * dbug('string') - same as above + will print the string
 * dbug('string',$array) - same as above + will print_r the array after the message
 * dbug($array) - will print_r the array with no message (just a time stamp)
 * dbug('string',$array,1) - same as above + will var_dump the array
 * dbug($array,1) - will var_dump the array with no message  (just a time stamp)
 *
 * @author Moshe Brevda mbrevda => gmail ~ com
 */
function dbug(){
	global $amp_conf;

	$opts = func_get_args();
	$disc = $msg = $dump = null;

	// Check if it is set to avoid un-defined errors if using in code portions that are
	// not yet bootstrapped. Default to enabling it.
	//
	if (isset($amp_conf['FPBXDBUGDISABLE']) && $amp_conf['FPBXDBUGDISABLE']) {
		return;
	}

	$dump = 0;
	//sort arguments
	switch (count($opts)) {
		case 1:
			$msg		= $opts[0];
			break;
		case 2:
			if ( is_array($opts[0]) || is_object($opts[0]) ) {
				$msg	= $opts[0];
				$dump	= $opts[1];
			} else {
				$disc	= $opts[0];
				$msg	= $opts[1];
			}
			break;
		case 3:
			$disc		= $opts[0];
			$msg		= $opts[1];
			$dump		= $opts[2];
			break;
	}

	if (isset($disc) && $disc) {
		$disc = ' \'' . $disc . '\':';
	} else {
		$disc = '';
	}

	$bt = debug_backtrace();
	$txt = date("Y-M-d H:i:s")
		. "\t" . $bt[0]['file'] . ':' . $bt[0]['line']
		. "\n\n"
		. $disc
		. "\n"; //add timestamp + file info
	dbug_write($txt, true);
	if ($dump==1) {//force output via var_dump
		ob_start();
		var_dump($msg);
		$msg=ob_get_contents();
		ob_end_clean();
		dbug_write($msg."\n\n\n");
	} elseif(is_array($msg) || is_object($msg)) {
		dbug_write(print_r($msg,true)."\n\n\n");
	} else {
		dbug_write($msg."\n\n\n");
	}
}

//http://php.net/manual/en/function.set-error-handler.php
function freepbx_error_handler($errno, $errstr, $errfile, $errline,  $errcontext) {
	global $amp_conf;

	//for pre 5.2
	if (!defined('E_RECOVERABLE_ERROR')) {
		define('E_RECOVERABLE_ERROR', '');
	}
	$errortype = array (
		E_ERROR => 'ERROR',
		E_WARNING => 'WARNING',
		E_PARSE => 'PARSE_ERROR',
		E_NOTICE => 'NOTICE',
		E_CORE_ERROR => 'CORE_ERROR',
		E_CORE_WARNING => 'CORE_WARNING',
		E_COMPILE_ERROR => 'COMPILE_ERROR',
		E_COMPILE_WARNING => 'COMPILE_WARNING',
		E_DEPRECATED => 'DEPRECATION_WARNING',
		E_USER_ERROR => 'USER_ERROR',
		E_USER_WARNING => 'USER_WARNING',
		E_USER_NOTICE => 'USER_NOTICE',
		E_STRICT => 'RUNTIM_NOTICE',
		E_RECOVERABLE_ERROR => 'CATCHABLE_FATAL_ERROR',
	);

	if (!isset($amp_conf['PHP_ERROR_HANDLER_OUTPUT'])) {
		$amp_conf['PHP_ERROR_HANDLER_OUTPUT'] = 'dbug';
	}

	switch($amp_conf['PHP_ERROR_HANDLER_OUTPUT']) {
		case 'freepbxlog':
			$txt = sprintf("%s] (%s:%s) - %s", $errortype[$errno], $errfile, $errline, $errstr);
			freepbx_log(FPBX_LOG_PHP,$txt);
		break;
		case 'off':
		break;
		case 'dbug':
		default:
			$errormsg = isset($errortype[$errno]) ? $errortype[$errno] : 'Undefined Error';
			$txt = date("Y-M-d H:i:s")
				. "\t" . $errfile . ':' . $errline
				. "\n"
				. '[' . $errormsg . ']: '
				. $errstr
				. "\n\n";
			dbug_write($txt, $check='');
		break;
	}
}


global $outn_function_buffer;
$outn_function_buffer='';
function out($text,$log=true) {
	global $outn_function_buffer;
	global $amp_conf;
	echo $text.EOL;
	// if not set, could be bootstrapping so default to true
	if ($log && (!isset($amp_conf['LOG_OUT_MESSAGES']) || $amp_conf['LOG_OUT_MESSAGES'])) {
		$outn_function_buffer .= $text;
		freepbx_log(FPBX_LOG_INFO,$outn_function_buffer);
		$outn_function_buffer = '';
	}
}

function outn($text,$log=true) {
	global $outn_function_buffer;
	global $amp_conf;
	echo $text;
	// if not set, could be bootstrapping so default to true
	if ($log && (!isset($amp_conf['LOG_OUT_MESSAGES']) || $amp_conf['LOG_OUT_MESSAGES'])) {
		// Don't log, just accumualte until matching out() dumps the accumulated text
		$outn_function_buffer .= $text;
	}
}

function error($text,$log=true) {
	echo "[ERROR] ".$text.EOL;
	if ($log) {
		freepbx_log(FPBX_LOG_ERROR,$text);
	}
}

// TODO: used in retrieve_conf, scan code base and remove if appropriate
//       replacing with logging and die_freepbx (which should log also)
//
function fatal($text,$log=true) {
	echo "[FATAL] ".$text.EOL;
	if ($log) {
		freepbx_log(FPBX_LOG_FATAL,$text);
	}
	exit(1);
}

// TODO: used in retrieve_conf, scan code base and remove if appropriate
//
function debug($text,$log=true) {
	global $debug;

	if ($debug) echo "[DEBUG-preDB] ".$text.EOL;
	if ($log) {
		dbug("HEY YOU!! YEAH YOU. STOP USING THIS. USE dbug() INSTEAD");
		dbug($text);
	}
}

/** like file_get_contents designed to work with url only, will try
 * wget if fails or if MODULEADMINWGET set to true. If it detects
 * failure, will set MODULEADMINWGET to true for future improvements.
 *
 * @param   mixed   url to be fetches or array of multiple urls to try
 * @return  mixed   content of first successful url, boolean false if it failed.
 */
function file_get_contents_url($file_url) {
	global $amp_conf;
	$contents = '';

	if (!is_array($file_url)) {
		$file_url = array($file_url);
	}

	foreach ($file_url as $fn) {
		if (!$amp_conf['MODULEADMINWGET']) {
			ini_set('user_agent','Wget/1.10.2 (Red Hat modified)');
			$contents = @ file_get_contents($fn);
		}
		if (empty($contents)) {
			$fn2 = str_replace('&','\\&',$fn);
			FreePBX::Curl()->setEnvVariables();
			exec("wget --tries=1 --timeout=30 -O - $fn2 2>> /dev/null", $data_arr, $retcode);
			if ($retcode) {
				// if server isn't available for some reason should return non-zero
				// so we return and we don't set the flag below
				freepbx_log(FPBX_LOG_ERROR,sprintf(_('Failed to get remote file, mirror site may be down: %s'),$fn));
				continue;

				// We are here if contents were blank. It's possible that whatever we were getting were suppose to be blank
				// so we only auto set the WGET var if we received something so as to not false trigger. If there are issues
				// with content filters that this is designed to get around, we will eventually get a non-empty file which
				// will trigger this for now and the future.
			} elseif (!empty($data_arr) && !$amp_conf['MODULEADMINWGET']) {
				$freepbx_conf =& freepbx_conf::create();
				$freepbx_conf->set_conf_values(array('MODULEADMINWGET' => true),true);

				$nt =& notifications::create($db);
				$text = sprintf(_("Forced %s to true"),'MODULEADMINWGET');
				$extext = sprintf(_("The system detected a problem trying to access external server data and changed internal setting %s (Use wget For Module Admin) to true, see the tooltip in Advanced Settings for more details."),'MODULEADMINWGET');
				$nt->add_warning('freepbx', 'MODULEADMINWGET', $text, $extext, '', false, true);
			}
			$contents = implode("\n",$data_arr);
			return $contents;
		} else {
			return $contents;
		}
		// we get here if all wget's fail
		return false;
	}
}



/**
 * function edit crontab
 * short Add/removes stuff rom conrtab
 * long Use this function to programmatically add/remove data from the crontab
 * will always run as the asterisk user
 * @author Moshe Brevda mbrevda => gmail ~ com
 *
 * @pram string
 * @pram mixed
 * @returns bool
 */

function edit_crontab($remove = '', $add = '') {
	global $amp_conf;
	$cron_out = array();
	$cron_add = false;

	//if were running as root (i.e. uid === 0), use the asterisk users crontab. If were running as the asterisk user,
	//that will happen automatically. If were anyone else, this cron entry will go the current user
	//and run as them
	$current_user = posix_getpwuid(posix_geteuid());
	if ($current_user['uid'] === 0) {
		$cron_user = '-u' . $amp_conf['AMPASTERISKWEBUSER'] . ' ';
	} else {
		$cron_user = '';
	}

	//get all crontabs
	$exec = '/usr/bin/crontab -l ' . $cron_user;
	exec($exec, $cron_out, $ret);

	//make sure the command was executed successfully before continuing
	if ($ret > 0) {
		return false;
	}

	//remove anythign that nteeds to be removed
	foreach ($cron_out as $my => $c) {
		//ignore comments
		if (substr($c, 0, 1) == '#') {
			continue;
		}

		//remove blank lines
		if (!$c) {
			unset($cron_out[$my]);
		}

		//remove $remove
		if ($remove) {
			if (strpos($c, $remove)) {
				unset($cron_out[$my]);
			}
		}
	}

	//if we have $add and its an array, parse it & fill in the missing options
	//if its a string, add it as is

	if($add) {
		if (is_array($add)) {
			if (isset($add['command'])) {
				//see if we have a one word event such as daily, weekly, anually, etc
				if (isset($add['event'])) {
					$cron_add['event'] = '@' . trim($add['event'], '@');
				} else {
					$cron_add['minute']		= isset($add['minute']) && $add['minute'] !== ''
												? $add['minute']
												: '*';
					$cron_add['hour']		= isset($add['hour']) && $add['hour'] !== ''
												? $add['hour']
												: '*';
					$cron_add['dom']		= isset($add['dom']) && $add['dom'] !== ''
												? $add['dom']
												: '*';
					$cron_add['month']		= isset($add['month']) && $add['month']	!== ''
												? $add['month']
												: '*';
					$cron_add['dow']		= isset($add['dow']) && $add['dow'] !== ''
												? $add['dow']
												: '*';
				}
				$cron_add['command']	= $add['command'];
				$cron_add = implode(' ', $cron_add);
			} else {
				//no command? No cron!
				$cron_add = '';
			}
		} else {
			//no array? Just use the string
			$cron_add = $add;
		}
	}

	//if we have soemthing to add
	if ($cron_add) {
		$cron_out[] = $cron_add;
	}

	//write out crontab
	$exec = '/bin/echo "' . implode("\n", $cron_out) . '" | /usr/bin/crontab ' . $cron_user . '-';
	//dbug('writing crontab', $exec);
	exec($exec, $out_arr, $ret);

	return ($ret > 0 ? false : true);
}

/**
 *
 * @author Moshe Brevda mbrevda => gmail ~ com
 */
function dbug_write($txt, $check = false){
	global $amp_conf;

	// dbug can be used prior to bootstrapping and initialization, so we set
	// it if not defined here to a default.
	//
	if (!isset($amp_conf['FPBXDBUGFILE'])) {
		$amp_conf['FPBXDBUGFILE'] = '/var/log/asterisk/freepbx_debug';
	}

// If not check set max size just under 2G which is the php limit before it gets upset
	if($check) { $max_size = 52428800; } else { $max_size = 2000000000; }
	//optionaly ensure that dbug file is smaller than $max_size
	$size = file_exists($amp_conf['FPBXDBUGFILE']) ? sprintf("%u", filesize($amp_conf['FPBXDBUGFILE'])) + strlen($txt) : 0;
	if ($size > $max_size) {
		file_put_contents($amp_conf['FPBXDBUGFILE'], $txt);
	} else {
		file_put_contents($amp_conf['FPBXDBUGFILE'], $txt, FILE_APPEND);
	}

	if($amp_conf['PHP_CONSOLE']) {
		PhpConsole\Connector::getInstance()->getDebugDispatcher()->dispatchDebug($txt, 'dbug');
	}
}

/**
 * this function can print a json object in a "pretty" (i.e. human-readbale) format
 * @author Moshe Brevda mbrevda => gmail ~ com
 *
 * @pram string - json string
 * @pram string - string to use for indentation
 *
 */
function json_print_pretty($json, $indent = "\t") {
	$f			= '';
	$len		= strlen($json);
	$depth		= 0;
	$newline	= false;

	for ($i = 0; $i < $len; ++$i) {
		if ($newline) {
			$f .= "\n";
			$f .= str_repeat($indent, $depth);
			$newline = false;
		}

		$c = $json[$i];
		if ($c == '{' || $c == '[') {
			$f .= $c;
			$depth++;
			$newline = true;
		} else if ($c == '}' || $c == ']') {
			$depth--;
			$f .= "\n";
			$f .= str_repeat($indent, $depth);
			$f .= $c;
		} else if ($c == '"') {
			$s = $i;
			do {
				$c = $json[++$i];
				if ($c == '\\') {
					$i += 2;
					$c = $json[$i];
				}
			} while ($c != '"');
			$f .= substr($json, $s, $i-$s+1);
		} else if ($c == ':') {
			$f .= ': ';
		} else if ($c == ',') {
			$f .= ',';
			$newline = true;
		} else {
			$f .= $c;
		}
	}
	return $f;
}

/**
 *
 * @author Moshe Brevda mbrevda => gmail ~ com
 */
function astdb_get($exclude = array()) {
	global $astman;
	$db			= $astman->database_show();
	$astdb		= array();

	foreach ($db as $k => $v) {
		if (!in_array($k, $exclude)) {
			$key = explode('/', trim($k, '/'), 2);
			//dbug($k, $key[1]);
			$astdb[$key[0]][$key[1]] = $v;
		}
	}

	return $astdb;
}

/**
 *
 * @author Moshe Brevda mbrevda => gmail ~ com
 */
function astdb_put($astdb, $exclude = array()) {
	global $astman;
	$db	= $astman->database_show();

	foreach ($astdb as $family => $key) {

		if ($family && !in_array($family, $exclude)) {
			$astman->database_deltree($family);
		}

		foreach($key as $k => $v) {
			//if ($k == 'Array' && $v == '<bad value>') continue;
			$astman->database_put($family, $k, $v);
		}

	}
	return true;
}

/**
 * function scandirr
 * scans a directory just like scandir(), only recursively
 * returns a hierarchical array representing the directory structure
 *
 * @pram string - directory to scan
 * @pram strin - retirn absolute paths
 * @returns array
 *
 * @author Moshe Brevda mbrevda => gmail ~ com
 */
function scandirr($dir, $absolute = false) {
	$list = array();
	if ($absolute) {
		global $list;
	}


	//get directory contents
	foreach (scandir($dir) as $d) {

		//ignore any of the files in the array
		if (in_array($d, array('.', '..'))) {
			continue;
		}

		//if current file ($d) is a directory, call scandirr
		if (is_dir($dir . '/' . $d)) {
			if ($absolute) {
				scandirr($dir . '/' . $d, $absolute);
			} else {
				$list[$d] = scandirr($dir . '/' . $d, $absolute);
			}


			//otherwise, add the file to the list
		} elseif (is_file($dir . '/' . $d) || is_link($dir . '/' . $d)) {
			if ($absolute) {
				$list[] = $dir . '/' . $d;
			} else {
				$list[] = $d;
			}

		}
	}

	return $list;
}

/**
 * Prints an array as a "tree" of data
 */
function dbug_printtree($dir, $indent = "\t") {
	static $t = 0;
	$foo = '';
	foreach ($dir as $key => $val) {
		//if this item is an array, its probobly a direcotry
		if (is_array($val)) {
			for ($i = 0; $i < $t; $i++) {
				$foo .= $indent;
			}
			//return the directory name
			$foo .= '[' . $key . ']' . "\n";
			++$t;
			printtree($val, $indent);
			--$t;
		} else {
			for ($i = 0; $i < $t; $i++) {
				$foo .= $indent;
			}
			//return file name
			$foo .= $val . "\n";
		}
	}
}

/**
 * Returns the absolute path to a system application
 *
 * @param string
 * @return string
 */
function fpbx_which($app) {
	$freepbx_conf = freepbx_conf::create();
	$which = $freepbx_conf->get_conf_setting('WHICH_' . $app);

	//if we have the location cached return it
	if (!empty($which) && file_exists($which) && is_executable($which)) {
		return $which;
	}

	//otherwise, search for it
	//ist of posible plases to find which

	$which = array(
			'which',
			'/usr/bin/which' //centos/mac osx
	);

	//TODO: Remove which in 14 no needed - Rob Thomas
	$location = '';
	foreach ($which as $w) {
		exec($w . ' ' . $app . ' 2>&1', $path, $ret);

		//exit if we have a positive find
		if ($ret === 0) {
			$location = $path[0];
			break;
		}
	}

	if(empty($location)) {
		$paths = array(
			"/usr/local/sbin",
			"/usr/local/bin",
			"/sbin",
			"/bin",
			"/usr/sbin",
			"/usr/bin",
			"/root/bin"
		);
		foreach($paths as $path) {
			if (file_exists($path."/".$app) && is_executable($path."/".$app)) {
				$location = $path."/".$app;
				break;
			}
		}
	}

	if(!empty($location) && $freepbx_conf->conf_setting_exists('WHICH_' . $app)) {
		$freepbx_conf->set_conf_values(array('WHICH_' . $app => $location), true,true);
		return $location;
	} elseif(!empty($location) && !$freepbx_conf->conf_setting_exists('WHICH_' . $app)) {
		//if we have a path add it to freepbx settings
		$set = array(
				'value'			=> $location,
				'defaultval'	=> $location,
				'readonly'		=> 1,
				'hidden'		=> 0,
				'level'			=> 2,
				'module'		=> '',
				'category'		=> 'System Apps',
				'emptyok'		=> 1,
				'name'			=> 'Path for ' . $app,
				'description'	=> 'The path to ' . $app . ' as auto-determined by the system. Overwrite as necessary.',
				'type'			=> CONF_TYPE_TEXT
		);
		$freepbx_conf->define_conf_setting('WHICH_' . $app, $set);
		$freepbx_conf->commit_conf_settings();

		//return the path
		return 	$location;
	} elseif(empty($location) && !$freepbx_conf->conf_setting_exists('WHICH_' . $app)) {
		$set = array(
				'value'			=> "",
				'defaultval'	=> "",
				'readonly'		=> 1,
				'hidden'		=> 0,
				'level'			=> 2,
				'module'		=> '',
				'category'		=> 'System Apps',
				'emptyok'		=> 1,
				'name'			=> 'Path for ' . $app,
				'description'	=> 'The path to ' . $app . ' as auto-determined by the system. Overwrite as necessary.',
				'type'			=> CONF_TYPE_TEXT
		);
		$freepbx_conf->define_conf_setting('WHICH_' . $app, $set);
		$freepbx_conf->commit_conf_settings();
		return false;
	}
}


/**
 * http://php.net/manual/en/function.getopt.php
 * temporary polyfill for proper working of getopt()
 * will revert to the native function if php >= 5.3.0
 *
 *
 * ===============================================================
 * THIS FUNCTION SHOULD NOT BE RELIED UPON AS IT WILL REMOVED
 * ONCE THE PROJECT REQUIRES PHP 5.3.0
 * if you must, call like:
 * $getopts = (function_exists('_getopt') ? '_' : '') . 'getopt';
 * $vars = $getopts($short = '', $long = array('id::'));
 * ===============================================================
 *
 *
 * http://www.ntu.beautifulworldco.com/weblog/?p=526
 */
function _getopt($short_option, $long_option = array()) {
		return getopt($short_option, $long_option);
}

/**
 * returns a rounded string representation of a byte size
 *
 * @author http://us2.php.net/manual/en/function.memory-get-usage.php#96280
 * @pram int
 * @retruns string
 */
function bytes2string($size){
		$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
		return round($size / pow(1024, ($i = floor(log($size, 1024))))) . ' ' . $unit[$i];
 }

/**
 * returns a rounded byte size representation of a string
 *
 * @author Moshe Brevda mbrevda => gmail ~ com
 * @pram string
 * @pram string, optional
 * @returns string
 */
function string2bytes($str, $type = ''){
	if (!$type) {
		$str	= explode(' ', $str);
		$type	= strtolower($str[1]);
		$str	= $str[0];
	}

		$units	= array(
					'b'		=> 1,
					'kb'	=> 1024,
					'mb'	=> 1024 * 1024,
					'gb'	=> 1024 * 1024 * 1024,
					'tb'	=> 1024 * 1024 * 1024 * 1024,
					'pb'	=> 1024 * 1024 * 1024 * 1024 * 1024
			);

		return isset($str, $units[$type])
			? round($str * $units[$type])
			: false;
 }

/**
 * downloads a file to the browser (i.e. sends the file to the browser)
 *
 * @author Moshe Brevda mbrevda => gmail ~ com
 * @pram string - absolute path to file
 * @pram string, optional - file name as it will be downloaded
 * @pram string, optional - content mime type
 * @pram bool, optional - true will force the file to be download.
 *						False allows the browser to attempt to display the file
 * 						Correct mime type ($type) snesesary for proper broswer interpretation!
 *
 */
function download_file($file, $name = '', $type = '', $force_download = false) {
	if (file_exists($file)) {
		//set the filename to the current filename if no name is specified
		$name = $name ? $name : basename($file);

		//sanitize filename
		$name = preg_replace('/[^A-Za-z0-9_\.-]/', '', $name);

		//attempt to set file mime type if it isn't already set
		if (!$type) {
			if (class_exists('finfo')) {
				$finfo = new finfo(FILEINFO_MIME);
				$type = $finfo->file($file);
			} else {
				exec(fpbx_which('file') . ' -ib ' . $file, $res);
				$type = $res[0];
			}
		}

		//failsafe for false or blank results
		$type = $type ? $type : 'application/octet-stream';

		$disposition = $force_download ? 'attachment' : 'inline';

		//send headers
		header('Content-Description: File Transfer');
		header('Content-Type: ' . $type);
		header('Content-Disposition: ' . $disposition . '; filename=' . $name);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));

		//clear all buffers
		while (ob_get_level()) {
			ob_end_clean();
		}
		flush();

		//send the file!
		readfile($file);

		//return immediately
		exit;
	} else {
		return false;
	}
}


/**
 * Get data from a pdf file. Requires pdfinfo
 *
 * @author Moshe Brevda mbrevda => gmail ~ com
 * @pram string - /path/to/file
 * @returns array - details about the pdf.
 * The following details are returned
 *		(values returned are depndant on the pdfinfo binary)
 *		author
 *		creationdate
 *		creator
 *		encrypted
 *		filesize
 *		moddate
 *		optimized
 *		pages
 *		pagesize
 *		pdfversion
 *		producer
 *		tagged
 *		title
 */
function fpbx_pdfinfo($pdf) {
	$pdfinfo = array();
	exec(fpbx_which('pdfinfo') . ' ' . $pdf, $pdf_details, $ret_code);

	if($ret_code !== 0) {
		return false;
	}

	foreach($pdf_details as $detail) {
		list($key, $value) = preg_split('/:\s*/', $detail, 2);
		$pdfinfo[strtolower(preg_replace('/[^A-Za-z]/', '', $key))] = $value;
	}
	ksort($pdfinfo);
	return $pdfinfo;
}

/**
 * Generate Message Banner(s)
 *
 * @param string $message Primary Message to display
 * @param string $type Type of message, can be info,danger,warning,success
 * @param array $details Details to show, array, each item is a new line
 * @param string $link link for "What does this mean?"
 * @param bool $closeable If true then the user can close this message,
 *						Flag will be stored in cookie against hash of message
 * @return string, the generated banner
 */
function generate_message_banner($message,$type='info',$details=array(),$link='',$closeable = false) {
	$full_hash = sha1($message.json_encode($details));
	// We have to ensure that Cookies don't exceed 'a small number' of bytes.
	// I randomly am picking 'the last 5' to keep, and discard any others.

	if ($closeable && isset($_COOKIE['bannerMessages'])) {
		$cookie = json_decode($_COOKIE['bannerMessages'],TRUE);

		if(is_array($cookie)) {
			while (count($cookie) > 5) {
				array_shift($cookie);
			}
			setcookie('bannerMessages', json_encode($cookie), strtotime("+1 year"));
		}
	}

	if($closeable && !empty($cookie)) {
		if(in_array($full_hash,$cookie)) {
			// This exact alert has previously been closed.
			return '';
		}
	}

	$ts = rand();
	if(empty($message)) {
		return '';
	}
	switch($type) {
		case 'danger':
		case 'warning':
				$message = '<i class="fa fa-exclamation-triangle"></i> '.$message;
		case 'info':
		case 'success':
		break;
		default:
				$type = 'info';
		break;
	}
	if(!empty($details)) {
			$dt = $details;
			$details = '<div class="panel-group" id="message-'.$ts.'" data-toggle="collapse" data-parent="#message-'.$ts.'" href="#collapseOne-'.$ts.'">
		<div class="panel panel-default">
			<div class="panel-heading">
				<span class="panel-title">
						Details
				</span>
			</div>
			<div id="collapseOne-'.$ts.'" class="panel-collapse collapse">
				<div class="panel-body">';
				foreach($dt as $d) {
						$details .= $d . "<br>";
				}
				$details .= '</div>
			</div>
		</div>
	</div>';
	} else {
		$details = '';
	}
	$link = !empty($link) ? " <a class='alert-link' href='".$link."' target='_blank'>("._('What Does this Mean?').")</a>" : '';
	$close = ($closeable) ? '<i class="fa fa-times close" data-hash="'.$full_hash.'" data-dismiss="alert" aria-hidden="true"></i>' : '';
	return '<div class="global-message-banner alert signature alert-'.$type.' alert-dismissable text-center">'.$close.'<h2><strong>'.$message.'</strong></h2>'.$details.$link.'</div>';
}

/**
 * Update AMI credentials in manager.conf
 *
 * @author Philippe Lindheimer
 * @pram mixed $user false means don't change
 * @pram mixed $pass password false means don't change
 * @pram mixed $writetimeout false means don't change
 * @returns boolean
 *
 * allows FreePBX to update the manager credentials primarily used by Advanced Settings and Backup and Restore.
 */
function fpbx_ami_update($user=false, $pass=false, $writetimeout = false) {
	global $amp_conf, $astman, $db, $bootstrap_settings;
	$conf_file = $amp_conf['ASTETCDIR'] . '/manager.conf';
	$ret = $ret2 = $ret3 = 0;
	$output = array();

	if(strpos($amp_conf['ASTETCDIR'],"..") === false && !file_exists($conf_file)) {
		return;
	}

	if ($user !== false && $user != '') {
		$sed_arg = escapeshellarg('s/\s*\[general\].*$/TEMPCONTEXT/;s/\[.*\]/\[' . $amp_conf['AMPMGRUSER'] . '\]/;s/^TEMPCONTEXT$/\[general\]/');
		exec("sed -i.bak $sed_arg $conf_file", $output, $ret);
		if ($ret) {
			freepbx_log(FPBX_LOG_ERROR,sprintf(_("Failed changing AMI user to [%s], internal failure details follow:"),$amp_conf['AMPMGRUSER']));
			foreach ($output as $line) {
				freepbx_log(FPBX_LOG_ERROR,sprintf(_("AMI failure details:"),$line));
			}
		}
	}

	if ($pass !== false && $pass != '') {
		unset($output);
		$sed_arg = escapeshellarg('s/secret\s*=.*$/secret = ' . $amp_conf['AMPMGRPASS'] . '/');
		exec("sed -i.bak $sed_arg $conf_file", $output2, $ret2);
		if ($ret2) {
			freepbx_log(FPBX_LOG_ERROR,sprintf(_("Failed changing AMI password to [%s], internal failure details follow:"),$amp_conf['AMPMGRPASS']));
			foreach ($output as $line) {
				freepbx_log(FPBX_LOG_ERROR,sprintf(_("AMI failure details:"),$line));
			}
		}

		// We've changed the password, let's update the notification
		//
		$nt = notifications::create($db);
		$freepbx_conf =& freepbx_conf::create();
		if ($amp_conf['AMPMGRPASS'] == $freepbx_conf->get_conf_default_setting('AMPMGRPASS')) {
			if (!$nt->exists('core', 'AMPMGRPASS')) {
				$nt->add_warning('core', 'AMPMGRPASS', _("Default Asterisk Manager Password Used"), _("You are using the default Asterisk Manager password that is widely known, you should set a secure password"));
			}
		} else {
			$nt->delete('core', 'AMPMGRPASS');
		}
	}

	//attempt to set writetimeout
	unset($output);
	if ($writetimeout) {
		$sed_arg = escapeshellarg('s/writetimeout\s*=.*$/writetimeout = ' . $amp_conf['ASTMGRWRITETIMEOUT'] . '/');
		exec("sed -i.bak $sed_arg $conf_file", $output3, $ret3);
		if ($ret3) {
			freepbx_log(FPBX_LOG_ERROR,sprintf(_("Failed changing AMI writetimout to [%s], internal failure details follow:"),$amp_conf['ASTMGRWRITETIMEOUT']));
			foreach ($output as $line) {
				freepbx_log(FPBX_LOG_ERROR,sprintf(_("AMI failure details:"),$line));
			}
		}
	}
	if ($ret || $ret2 || $ret3) {
		dbug("aborting early because previous errors");
		return false;
	}
	if (is_object($astman) && method_exists($astman, "connected") && $astman->connected()) {
		$ast_ret = $astman->Command('module reload manager');
	} else {
		unset($output);
		dbug("no astman connection so trying to force through linux command line");
		exec(fpbx_which('asterisk') . " -rx 'module reload manager'", $output, $ret2);
		if ($ret2) {
			freepbx_log(FPBX_LOG_ERROR,_("Failed to reload AMI, manual reload will be necessary, try: [asterisk -rx 'module reload manager']"));
		}
	}
	if (is_object($astman) && method_exists($astman, "connected") && $astman->connected()) {
		$astman->disconnect();
	}
	if (!is_object($astman) || !method_exists($astman, "connected")) {
		//astman isn't initiated so escape
		return true;
	}
	if (!$res = $astman->connect($amp_conf["ASTMANAGERHOST"] . ":" . $amp_conf["ASTMANAGERPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"], $bootstrap_settings['astman_events'])) {
		// couldn't connect at all
		freepbx_log(FPBX_LOG_CRITICAL,"Connection attmempt to AMI failed");
	} else {
		$bmo = FreePBX::create();
		$bmo->astman = $astman;
	}
	return true;
}

/**
 * Outbound Callerid Sanatizer
 * @author mbrevda@gmail.com
 * @param string
 * @return string
 *
 * Bell Canada BID-0011, Enhanced Call Management Service, May, 1992
 * 5.2.7: "The field can contain any displayable ASCII character"
 * http://www.bell.cdn-telco.com/bid/bid-0011.pdf
 * referencing Bellcore TR-TSY-000031, which I could not find -MB
 *
 * Please note: instead of using this all over the place, it would
 * make much more sense to do sanitization one time in the dial plan
 * just before a call is sent out a trunk. Hoever, there doesnt seem
 * to be a simple way to do this in asterisk.
 *
 */
function sanitize_outbound_callerid($cid) {
	return preg_replace('/[^[:print:]]/', '', $cid);
}

/**
 * Recursively remove a directory
 * @param string - dirname
 *
 * @return bool
 */
function rrmdir($dir) {
	foreach(glob($dir . '/*') as $file) {
		if(is_dir($file)) {
			rrmdir($file);
		} else {
			unlink($file);
		}
	}
	rmdir($dir);

	return !is_dir($dir);
}

/**
 * Run bootstrap hooks as provided by module.xml
 *
 * We currently support hooking at two points: before modules are loaded and after modules are loaded
 * Before we load ANY modules, we will include all "all_mods" hooks
 * Before we load an indevidual module, we will load there specifc hook
 *
 * @param string - hook type
 * @param string - module name
 *
 */
function bootstrap_include_hooks($hook_type, $module) {
	global $amp_conf;
	//first parse and load all hook info
	if (!isset($hooks)) {
		static $hooks = '';
		$hooks = _bootstrap_parse_hooks();

	}

	if (isset($hooks[$hook_type][$module])) {
		foreach ($hooks[$hook_type][$module] as $hook) {
			if (file_exists($hook)) {
				require_once($hook);
			} elseif(file_exists($amp_conf['AMPWEBROOT'] . '/admin/' . $hook)) {
				require_once($amp_conf['AMPWEBROOT'] . '/admin/' . $hook);
			}
		}
	}

	return true;
}

/**
 * Helper function to laod hooks for bootstrap_include_hooks()
 */
function _bootstrap_parse_hooks() {
	$hooks		= array();

	$modulef = module_functions::create();
	$modules	= $modulef->getinfo(false, MODULE_STATUS_ENABLED);
	foreach ($modules as $mymod => $mod) {
		if (isset($mod['bootstrap_hooks'])) {
			foreach ($mod['bootstrap_hooks'] as $type => $type_mods) {
				switch ($type) {
					case 'pre_module_load':
					case 'post_module_load':
						//first get all_mods
						if (isset($type_mods['all_mods'])) {

							$hooks[$type]['all_mods'] = isset($hooks[$type]['all_mods'])
														? array_merge($hooks[$type]['all_mods'],
														(array)$type_mods['all_mods'])
														: (array)$type_mods['all_mods'];
							unset($type_mods['all_mods']);
						}
						if (!isset($type_mods)) {
							break;//break if there are no more hooks to include
						}
						//now load all remaining modules
						foreach ($type_mods as $type_mod) {
							$hooks[$type][$mymod] = isset($hooks[$type][$mymod])
													? array_merge($hooks[$type][$mymod],
													(array)$type_mod)
													: (array)$type_mod;
						}
						break;
					default:
						break;
				}
			}
		}
	}
	return $hooks;
}

/**
 * do variable substitution
 * @param string - string to check for replacements
 * @param string - option delimiter, defautls to $
 * @returns string - the new string, with replacements - if any
 * @auther Moshe Brevda mbrevda => gmail ! com
 */
function varsub($string, $del = '$') {
	global $amp_conf;
	/*
	 * substitution string can look like: $delSTRING$del
	 */
	$regex = '/'
		. preg_quote($del)
		. '([a-zA-Z0-9_-]*)'
		. preg_quote($del)
		.  '/';

	//if we have matches
	if (preg_match_all($regex, $string, $matches)) {
		$vars = $matches[1];
		$find = $matches[0];
		//iterate over them
		foreach ($vars as $count => $var) {
			if (isset($amp_conf[$var])) {
				$once = 1;
				//and replace them, one at a time
				$string = str_replace($find[$count],
					$amp_conf[$var],
					$string,
					$once);
			}
		}
	}

	return $string;
}

function getSystemMemInfo() {
	$meminfo = array();
	if (PHP_OS == "FreeBSD") {
		$bytes = shell_exec("sysctl -n hw.usermem 2>/dev/null");
		$meminfo["MemTotal"] = $bytes / 1024 ;
		$bytes = shell_exec("sysctl -n vm.swap_total 2>/dev/null");
		$meminfo["SwapTotal"] = $bytes / 1024 ;
	} else {
		$data = explode("\n", file_get_contents("/proc/meminfo"));
		foreach ($data as $line) {
			if (strpos($line, ":")) {
				list($key, $val) = explode(":", $line);
				$meminfo[$key] = trim($val);
			}
		}
	}
	return $meminfo;
}

/**
 * Check filetype of files.
 * PHP Built in functions fail on files over 2G in size on 32-bit machines
 * @param  string $file file/dir path
 * @return string or bool       Returns file type if known ot false
 */
function freepbx_filetype($file){
	if(PHP_INT_SIZE == 8) { //64bit machine
		return filetype($file);
	}
	// > 2GB files not affected
	if(is_link($file)) {
		return 'link';
	}
	// > 2GB files not affected
	if(is_dir($file)) {
		return 'dir';
	}
	$size = freepbx_filesize($file);
	//If less than 1.9 gig run normal filetype
	if($size < 1.9e+9) {
		return filetype($file);
	}
	$file = escapeshellarg($file);
	$command = 'stat -c %f '.$file;
	if (in_array(PHP_OS, array('FreeBSD','Darwin','NetBSD'))){
		$command = 'stat -f %Xp '.$file;
	}
	$hex = hexdec(trim(`$command`));
	$S_FMT = 0170000;
	$S_IFLNK = 0120000;
	$S_IFREG = 0100000;
	$S_IFBLK = 0060000;
	$S_IFDIR = 0040000;
	$S_IFCHR = 0020000;
	$S_IFIFO = 0010000;
	$type = false;
	switch (($hex & $S_FMT)) {
		case $S_IFLNK:
			$type = 'link';
		break;
		case $S_IFREG:
			$type = 'file';
		break;
		case $S_IFBLK:
			$type = 'block';
		break;
		case $S_IFDIR:
			$type = 'dir';
		break;
		case $S_IFCHR:
			$type = 'char';
		break;
		case $S_IFIFO:
			$type = 'fifo';
		break;
		default:
			$type = false;
		break;
	}
	return $type;
}

/**
 * Return semi-accurate filesize for files
 * PHP Built in functions fail on files over 2G in size on 32-bit machines
 * @param  string $file file/dir path
 * @return string or bool       Returns file size
 */
function freepbx_filesize($file) {
	if(PHP_INT_SIZE == 8) { //64bit machine
		return filesize($file);
	}
	//https://github.com/jkuchar/BigFileTools/blob/master/src/Driver/CurlDriver.php
	//https://github.com/jkuchar/BigFileTools#drivers
	// ^--- cURL is faster than stat, really what?
	if (!function_exists("curl_init")) {
		throw new Exception("32-bit PBX systems require the cURL extension to be loaded in PHP");
	}
	$ch = curl_init("file://" . rawurlencode($file));
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	$data = curl_exec($ch);
	curl_close($ch);
	if ($data !== false && preg_match('/Content-Length: (\d+)/', $data, $matches)) {
		return $matches[1];
	}
	return 0; //unknown
}
