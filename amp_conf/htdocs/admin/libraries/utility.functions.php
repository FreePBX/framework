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

        // Don't append if the file is greater than ~2G since some systems fail
        //
        $size = sprintf("%u", filesize($log_file)) + strlen($txt);
        if ($size < 2000000000) {
          file_put_contents($log_file, "[$tstamp] $txt", FILE_APPEND);
        }
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

function compress_framework_css() {
	global $amp_conf;
	$mainstyle_css      = $amp_conf['BRAND_CSS_ALT_MAINSTYLE'] 
						? $amp_conf['BRAND_CSS_ALT_MAINSTYLE'] 
						: 'assets/css/mainstyle.css'; 
	$wwwroot 			= $amp_conf['AMPWEBROOT'] . "/admin";
	$mainstyle_css_gen 	= $wwwroot . '/' . $amp_conf['mainstyle_css_generated'];
	$mainstyle_css		= $wwwroot . '/' . $mainstyle_css;
	$new_css 			= file_get_contents($mainstyle_css) 
						. file_get_contents($wwwroot . '/' . $amp_conf['JQUERY_CSS']);
	$new_css 			= CssMin::minify($new_css);
	$new_md5 			= md5($new_css);
	$gen_md5 			= file_exists($mainstyle_css_gen) ? md5(file_get_contents($mainstyle_css_gen)) : '';

	
	//regenerate if hashes dont match
	if ($new_md5 != $gen_md5) {
		$ms_path = dirname($mainstyle_css);

		// it's important for filename tp unique 
		//because that will force browsers to reload vs. caching it
		$mainstyle_css_generated = $ms_path.'/mstyle_autogen_' . time() . '.css';
		$ret = file_put_contents($mainstyle_css_generated, $new_css);

		// Now assuming we write something reasonable, we need to save the generated file name and mtimes so
		// next time through this ordeal, we see everything is setup and skip all of this.
		// 
		// we skip this all this if we get back false or 0 (nothing written) in which case we will use the original
		// We need to set the value in addition to defining the setting 
		//since if already defined the value won't be reset.
		if ($ret) {
			$freepbx_conf =& freepbx_conf::create();
			$val_update['mainstyle_css_generated'] = str_replace($wwwroot . '/', '', $mainstyle_css_generated);
			
			// Update the values (in case these are new) and commit
			$freepbx_conf->set_conf_values($val_update, true, true);


			// If it is a regular file (could have been first time and previous was blank then delete old
			if (is_file($mainstyle_css_gen) && !unlink($mainstyle_css_gen)) {
				freepbx_log(FPBX_LOG_WARNING,
							sprintf(_('failed to delete %s from assets/css directory after '
									. 'creating updated CSS file.'), 
									$mainstyle_css_generated_full_path));
			}
		}
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
	if($db->IsError($results)) {
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
	if($db->IsError($version)) {
		die_freepbx($sql."<br>\n".$version->getMessage());
	}
	return $version;
}

//tell application we need to reload asterisk
function needreload() {
	global $db;
	$sql	= "UPDATE admin SET value = 'true' WHERE variable = 'need_reload'"; 
	$result	= $db->query($sql); 
	if($db->IsError($result)) {     
		die_freepbx($sql.$result->getMessage()); 
	}
}

function check_reload_needed() {
	global $db;
	global $amp_conf;
	$sql = "SELECT value FROM admin WHERE variable = 'need_reload'";
	$row = $db->getRow($sql);
	if($db->IsError($row)) {
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
                E_ERROR                                 => 'ERROR',
                E_WARNING                               => 'WARNING',
                E_PARSE                                 => 'PARSE_ERROR',
                E_NOTICE                                => 'NOTICE',
                E_CORE_ERROR                    => 'CORE_ERROR',
                E_CORE_WARNING                  => 'CORE_WARNING',
                E_COMPILE_ERROR                 => 'COMPILE_ERROR',
                E_COMPILE_WARNING               => 'COMPILE_WARNING',
                E_USER_ERROR                    => 'USER_ERROR',
                E_USER_WARNING                  => 'USER_WARNING',
                E_USER_NOTICE                   => 'USER_NOTICE',
                E_STRICT                                => 'RUNTIM_NOTICE',
                E_RECOVERABLE_ERROR     => 'CATCHABLE_FATAL_ERROR',
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
						$errormsg = isset($errortype[$errno])
									? $errortype[$errno]
									: 'Undefined Error';
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
			exec("wget -O - $fn2 2>> /dev/null", $data_arr, $retcode);
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
 * function generate_module_repo_url
 * short create array of full URLs to get a file from repo
 * use this function to generate an array of URLs for all configured REPOs
 * @author Philippe Lindheimer
 *
 * @pram string
 * @returns string
 */
function generate_module_repo_url($path) {
	global $amp_conf;
	$urls = array();

	$repos = explode(',', $amp_conf['MODULE_REPO']);
	foreach ($repos as $repo) {
		$urls[] = $repo . $path;
	}
	return $urls;
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
					$cron_add['minute']		= isset($add['minute'])	? $add['minute']	: '*';
					$cron_add['hour']		= isset($add['hour'])	? $add['hour']		: '*';
					$cron_add['dom']		= isset($add['dom'])	? $add['dom']		: '*';
					$cron_add['month']		= isset($add['month'])	? $add['month']		: '*';
					$cron_add['dow']		= isset($add['dow'])	? $add['dow']		: '*';
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
	$size = sprintf("%u", filesize($amp_conf['FPBXDBUGFILE'])) + strlen($txt);
	if ($size > $max_size) {
		file_put_contents($amp_conf['FPBXDBUGFILE'], $txt);
	} else {
		file_put_contents($amp_conf['FPBXDBUGFILE'], $txt, FILE_APPEND);
	}
}

/**
 * this function can print a json object in a "pretty" (i.e. human-readbale) format
 * @author Moshe Brevda mbrevda => gmail ~ com
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
 * returns the absolute path to a system application
 * 
 * @author Moshe Brevda mbrevda => gmail ~ com
 * @pram string
 * @retruns string
 */
function fpbx_which($app) {
	// don't know if we will always have an open class and not even sure if
	// $amp_conf will be set so to be safe deal with it all here.
	//
	$freepbx_conf =& freepbx_conf::create();
 	$which = $freepbx_conf->get_conf_setting('WHICH_' . $app);

	//if we have the location cached return it
	if ($which) {
		return $which;
		
		//otherwise, search for it
	} else {
		//ist of posible plases to find which
		
		$which = array(
				'which',
				'/usr/bin/which' //centos/mac osx
		);
		
		foreach ($which as $w) {
			exec($w . ' ' . $app, $path, $ret);
			
			//exit if we have a posotive find
			if ($ret === 0) {
				break;
			}
		}
		
		if($path[0]) {
			//if we have a path add it to freepbx settings
			$set = array(
					'value'			=> $path[0],
					'defaultval'	=> $path[0],
					'readonly'		=> 1,
					'hidden'		=> 0,
					'level'			=> 2,
					'module'		=> '',
					'category'		=> 'System Apps',
					'emptyok'		=> 1,
					'name'			=> 'Path for ' . $app,
					'description'	=> 'The path to ' . $app 
									. ' as auto-determined by the system.'
									. ' Overwrite as necessary.',
					'type'			=> CONF_TYPE_TEXT
			);
			$freepbx_conf->define_conf_setting('WHICH_' . $app, $set);
			$freepbx_conf->commit_conf_settings();
			
			//return the path
			return $path[0];
		} else {
			return false;
		}
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
function _getopt() {
	if (func_num_args() == 1) {
		$flag = $flag_array = $GLOBALS['argv'];
		$short_option		= func_get_arg(0);
		$long_option		= array();
	} elseif (func_num_args() == 2) {
		if (is_array(func_get_arg(1))) {
			$flag = $GLOBALS['argv'];
			$short_option	= func_get_arg(0);
			$long_option	= func_get_arg(1);
		} else {
			$flag			= func_get_arg(0);
			$short_option	= func_get_arg(1);
			$long_option	= array ();
		}
	} else if ( func_num_args() == 3 ) {
		$flag				= func_get_arg(0);
		$short_option		= func_get_arg(1);
		$long_option		= func_get_arg(2);
	} else {
		exit ( "wrong options\n" );
	}
	if (PHP_VERSION_ID >= 50300) {
		return getopt($short_option, $long_option);
	}
	$short_option			= trim ( $short_option );
	$short_no_value			= array();
	$short_required_value	= array();
	$short_optional_value	= array();
	$long_no_value			= array();
	$long_required_value	= array();
	$long_optional_value	= array();
	$options				= array();

	for ($i = 0; $i < strlen ($short_option);) {
		if ($short_option{$i} != ":") {
			if ($i == strlen ($short_option) - 1) {
				$short_no_value[] = $short_option{$i};
				break;
			} else if ($short_option{$i+1} != ":") {
				$short_no_value[] = $short_option{$i};
				$i++;
				continue;
			} elseif ($short_option{$i+1} == ":" && $short_option{$i+2} != ":") {
				$short_required_value[] = $short_option{$i};
				$i += 2;
				continue;
				} elseif ($short_option{$i+1} == ":" && $short_option{$i+2} == ":") {
				$short_optional_value[] = $short_option{$i};
				$i += 3;
				continue;
			}
		} else {
			continue;
		}
	}

	foreach ($long_option as $a) {
		if ( substr( $a, -2 ) == "::" ) {
			$long_optional_value[] = substr($a, 0, -2);
			continue;
		} elseif (substr( $a, -1 ) == ":") {
			$long_required_value[] = substr($a, 0, -1 );
			continue;
		} else {
			$long_no_value[] = $a;
			continue;
		}
	}

	if (is_array ($flag)) {
		$flag_array = $flag;
	} else {
		$flag = "- $flag";
		$flag_array = split_para($flag);
	}

	for ($i = 0; $i < count($flag_array);) {

		if ($i >= count ($flag_array) )
			break;

		if (!$flag_array[$i] || $flag_array[$i] == "-") {
			$i++;
			continue;
		}

		if ($flag_array[$i]{0} != "-") {
			$i++;
			continue;
		}

		if (substr( $flag_array[$i], 0, 2 ) == "--") {
			if (strpos($flag_array[$i], '=') != false) {
				list($key, $value) = explode('=', substr($flag_array[$i], 2), 2);
				if (in_array($key, $long_required_value) || in_array($key, $long_optional_value)) {
					$options[$key][] = $value;
				}
				$i++;
				continue;
			}
			if (strpos($flag_array[$i], '=') == false) {
				$key = substr( $flag_array[$i], 2 );
				if ( in_array( substr( $flag_array[$i], 2 ), $long_required_value ) ) {
					$options[$key][] = $flag_array[$i+1];
					$i += 2;
					continue;
				} elseif (in_array(substr($flag_array[$i], 2), $long_optional_value)) {
					if ($flag_array[$i+1] != "" && $flag_array[$i+1]{0} != "-") {
						$options[$key][] = $flag_array[$i+1];
						$i += 2;
					} else {
						$options[$key][] = FALSE;
						$i ++;
					}
					continue;
				} else if (in_array(substr( $flag_array[$i], 2 ), $long_no_value ) ) {
					$options[$key][] = FALSE;
					$i++;
					continue;
				} else {
					$i++;
					continue;
				}
			}
		} else if ( $flag_array[$i]{0} == "-" && $flag_array[$i]{1} != "-" ) {
			for ( $j=1; $j < strlen($flag_array[$i]); $j++ ) {
				if ( in_array( $flag_array[$i]{$j}, $short_required_value ) || in_array( $flag_array[$i]{$j}, $short_optional_value )) {

					if ( $j == strlen($flag_array[$i]) - 1  ) {
						if ( in_array( $flag_array[$i]{$j}, $short_required_value ) ) {
							$options[$flag_array[$i]{$j}][] = $flag_array[$i+1];
							$i += 2;
						} else if (in_array($flag_array[$i]{$j}, $short_optional_value ) && $flag_array[$i+1] != "" && $flag_array[$i+1]{0} != "-" ) {
							$options[$flag_array[$i]{$j}][] = $flag_array[$i+1];
							$i += 2;
						} else {
							$options[$flag_array[$i]{$j}][] = FALSE;
							$i ++;
						}
						$plus_i = 0;
						break;
					} else {
						$options[$flag_array[$i]{$j}][] = substr ( $flag_array[$i], $j + 1 );
							$i ++;
						$plus_i = 0;
						break;
					}
				} else if(in_array($flag_array[$i]{$j}, $short_no_value)) {
					$options[$flag_array[$i]{$j}][] = FALSE;
					$plus_i = 1;
					continue;
				}
			}
			$i += $plus_i;
			continue;
		}
		$i++;
		continue;
	}

	foreach ($options as $key => $value) {
		if (count ( $value ) == 1) {
			$options[ $key ] = $value[0];
		}
	}

	return $options;

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
 * returns the absolute path to a system application
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
 * @retruns array - details about the pdf. 
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
