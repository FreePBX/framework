<?php

function dbug_write($txt,$check=''){
	global $amp_conf;

	// dbug can be used prior to bootstrapping and initialization, so we set
	// it if not defined here to a default.
	//
	if (!isset($amp_conf['FPBXDBUGFILE'])) {
		$amp_conf['FPBXDBUGFILE'] = '/tmp/freepbx_debug.log';
	}
	$append=false;
	//optionaly ensure that dbug file is smaller than $max_size
	if($check){
		$max_size = 52428800;//hardcoded to 50MB. is that bad? not enough?
		$size = filesize($amp_conf['FPBXDBUGFILE']);
		$append = (($size > $max_size) ? false : true );
	}
	if ($append) {
		file_put_contents($amp_conf['FPBXDBUGFILE'], $txt);
	} else {
		file_put_contents($amp_conf['FPBXDBUGFILE'], $txt, FILE_APPEND);
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
		E_ERROR					=> 'ERROR',
		E_WARNING				=> 'WARNING',
		E_PARSE					=> 'PARSE_ERROR',
		E_NOTICE				=> 'NOTICE',
		E_CORE_ERROR			=> 'CORE_ERROR',
		E_CORE_WARNING			=> 'CORE_WARNING',
		E_COMPILE_ERROR			=> 'COMPILE_ERROR',
		E_COMPILE_WARNING		=> 'COMPILE_WARNING',
		E_USER_ERROR			=> 'USER_ERROR',
		E_USER_WARNING			=> 'USER_WARNING',
		E_USER_NOTICE			=> 'USER_NOTICE',
		E_STRICT				=> 'RUNTIM_NOTICE',
		E_RECOVERABLE_ERROR 	=> 'CATCHABLE_FATAL_ERROR',
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
			$txt = date("Y-M-d H:i:s")
				. "\t" . $errfile . ':' . $errline 
				. "\n"
				. '[' . $errortype[$errno] . ']: '
				. $errstr
				. "\n\n";
				dbug_write($txt, $check='');
				break;
			}
}

?>