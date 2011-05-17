<?php

define('EOL', isset($_SERVER['REQUEST_METHOD']) ? "<br />" :  PHP_EOL);

define("FPBX_LOG_FATAL",    "FATAL");
define("FPBX_LOG_CRITICAL", "CRITICAL");
define("FPBX_LOG_SECURITY", "SECURITY");
define("FPBX_LOG_UPDATE",   "UPDATE");
define("FPBX_LOG_ERROR",    "ERROR");
define("FPBX_LOG_WARNING",  "WARNING");
define("FPBX_LOG_NOTICE",   "NOTICE");
define("FPBX_LOG_INFO",     "INFO");
define("FPBX_LOG_PHP",      "PHP");

/** FreePBX Logging facility to FILE or syslog
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
				$tstamp		= date("Y-M-d H:i:s");
        		file_put_contents($log_file, "[$tstamp] $txt", FILE_APPEND);
				break;
		}
	}
}

/* version_compare that works with FreePBX version numbers
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

function die_freepbx($text, $extended_text="", $type="FATAL") {
	global $amp_conf;

	$bt = debug_backtrace();
	freepbx_log(FPBX_LOG_FATAL, "die_freepbx(): ".$text);

	if (isset($_SERVER['REQUEST_METHOD'])) {
		// running in webserver
		$trace =  "<h1>".$type." ERROR</h1>\n";
		$trace .= "<h3>".$text."</h3>\n";
		if (!empty($extended_text)) {
			$trace .= "<p>".$extended_text."</p>\n";
		}
		$trace .= "<h4>"._("Trace Back")."</h4>";

		$main_fmt = "%s:%s %s()<br />\n";
		$arg_fmt = "&nbsp;&nbsp;[%s]: %s<br />\n";
		$separator = "<br />\n";
		$tail = "<br />\n";
		$f = 'htmlspecialchars';
	} else {
 		// CLI
		$trace =  "[$type] ".$text." ".$extended_text."\n\n";
		$trace .= "Trace Back:\n\n";

		$main_fmt = "%s:%s %s()\n";
		$arg_fmt = " [%s]: %s\n";
		$separator = "\n";
		$tail = "";
		$f = 'trim';
	}

	foreach ($bt as $l) {
		$cl = isset($l['class']) ? $f($l['class']) : '';
		$ty = isset($l['type']) ? $f($l['type']) : '';
		$func = $f($cl . $ty . $l['function']);
		$trace .= sprintf($main_fmt, $l['file'], $l['line'], $func);
		if (isset($l['args'])) foreach ($l['args'] as $i => $a) {
			$trace .= sprintf($arg_fmt, $i, $f($a));
		}
		$trace .= $separator;
	}
	echo $trace . $tail;

	if ($amp_conf['DIE_FREEPBX_VERBOSE']) {
		$trace = print_r($bt,true);
		if (isset($_SERVER['REQUEST_METHOD'])) {
			echo '<pre>' .$trace. '</pre>';
		} else {
			echo $trace;
		}
	}

	// Now die!
	exit(1);
}

//get the version number
function getversion($cached=true) {
	global $db;
	static $version;
	if (isset($version) && $version && $cached) {
		return $version;
	}
	$sql		= "SELECT value FROM admin WHERE variable = 'version'";
	$results	= $db->getRow($sql);
	if(DB::IsError($results)) {
		die_freepbx($sql."<br>\n".$results->getMessage());
	}
	return $results[0];
}

//get the version number
function get_framework_version($cached=true) {
	global $db;
	static $version;
	if (isset($version) && $version && $cached) {
		return $version;
	}
	$sql		= "SELECT version FROM modules WHERE modulename = 'framework' AND enabled = 1";
	$version	= $db->getOne($sql);
	if(DB::IsError($version)) {
		die_freepbx($sql."<br>\n".$version->getMessage());
	}
	return $version;
}

//tell application we need to reload asterisk
function needreload() {
	global $db;
	$sql	= "UPDATE admin SET value = 'true' WHERE variable = 'need_reload'"; 
	$result	= $db->query($sql); 
	if(DB::IsError($result)) {     
		die_freepbx($sql.$result->getMessage()); 
	}
}

function check_reload_needed() {
	global $db;
	global $amp_conf;
	$sql = "SELECT value FROM admin WHERE variable = 'need_reload'";
	$row = $db->getRow($sql);
	if(DB::IsError($row)) {
		die_freepbx($sql.$row->getMessage());
	}
	return ($row[0] == 'true' || $amp_conf['DEVELRELOAD']);
}

/** Log a debug message to a debug file
 * @param  string   debug message to be printed
 * @param  string   depreciated
 * @param  string   depreciated
 */
function freepbx_debug($string, $option='', $filename='') {
	dbug($string);
}

 /* 
  * FreePBX Debugging function
  * This function can be called as follows:
  * dbug() - will just print a time stamp to the debug log file ($amp_conf['FPBXDBUGFILE'])
  * dbug('string') - same as above + will print the string
  * dbug('string',$array) - same as above + will print_r the array after the message
  * dbug($array) - will print_r the array with no message (just a time stamp)  
  * dbug('string',$array,1) - same as above + will var_dump the array
  * dbug($array,1) - will var_dump the array with no message  (just a time stamp)
  * 	 
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
	
	if ($disc) {
		$disc = ' \'' . $disc . '\':';
	}
	
	$bt = debug_backtrace();
	$txt = date("Y-M-d H:i:s") 
		. "\t" . $bt[0]['file'] . ':' . $bt[0]['line'] 
		. "\n\n"
		. $disc 
		. "\n"; //add timestamp + file info
	dbug_write($txt);
	if ($dump==1) {//force output via var_dump
		ob_start();
		var_dump($msg);
		$msg=ob_get_contents();
		ob_end_clean();
		dbug_write($msg."\n\n\n");
	} elseif(is_array($msg)||is_object($msg)) {
		dbug_write(print_r($msg,true)."\n\n\n");
	} else {
		dbug_write($msg."\n\n\n");
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
		dbug($text);
	}
}

/** like file_get_contents designed to work with url only, will try
 * wget if fails or if MODULEADMINWGET set to true. If it detects
 * failure, will set MODULEADMINWGET to true for future improvements.
 *
 * @param   string  url to be fetches
 * @return  mixed   content of url, boolean false if it failed.
 */
function file_get_contents_url($fn) {
	global $amp_conf;
	$contents = '';

	if (!$amp_conf['MODULEADMINWGET']) {
		ini_set('user_agent','Wget/1.10.2 (Red Hat modified)');
		$contents = @ file_get_contents($fn);
	}
	if (empty($contents)) {
		$fn2 = str_replace('&','\\&',$fn);
		exec("wget -O - $fn2 2>> /dev/null", $data_arr, $retcode);
		if ($retcode) {
			return false;
	} elseif (!$amp_conf['MODULEADMINWGET']) {
		$freepbx_conf =& freepbx_conf::create();
		$freepbx_conf->set_conf_values(array('MODULEADMINWGET' => true),true);

		$nt =& notifications::create($db); 
		$text = sprintf(_("Forced %s to true"),'MODULEADMINWGET');
		$extext = sprintf(_("The system detected a problem trying to access external server data and changed internal setting %s (Use wget For Module Admin) to true, see the tooltip in Advanced Settings for more details."),'MODULEADMINWGET');
		$nt->add_warning('freepbx', 'MODULEADMINWGET', $text, $extext, '', false, true);
	}
	$contents = implode("\n",$data_arr);
	}
	return $contents;
}
