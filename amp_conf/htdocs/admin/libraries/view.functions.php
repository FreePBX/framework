<?php

function frameworkPasswordCheck() {
	global $amp_conf;

  $nt = notifications::create($db);

  // Moved most of the other checks to retrieve_conf to avoid running every page load. These have been left
  // here becuase both of these settings could be affected differently in the php apache related settings vs.
  // what retrieve_conf would see running the CLI version of php
  //
	
	// Check and increase php memory_limit if needed and if allowed on the system
	//
	$current_memory_limit = rtrim(ini_get('memory_limit'),'M');
	$proper_memory_limit = '100';
	if ($current_memory_limit < $proper_memory_limit) {
		if (ini_set('memory_limit',$proper_memory_limit.'M') !== false) {
			$nt->add_notice('core', 'MEMLIMIT', _("Memory Limit Changed"), sprintf(_("Your memory_limit, %sM, is set too low and has been increased to %sM. You may want to change this in you php.ini config file"),$current_memory_limit,$proper_memory_limit));
		} else {
			$nt->add_warning('core', 'MEMERR', _("Low Memory Limit"), sprintf(_("Your memory_limit, %sM, is set too low and may cause problems. FreePBX is not able to change this on your system. You should increase this to %sM in you php.ini config file"),$current_memory_limit,$proper_memory_limit));
		}
	} else {
		$nt->delete('core', 'MEMLIMIT');
	}

	// send error if magic_quotes_gpc is enabled on this system as much of the code base assumes not
	//
	if(get_magic_quotes_gpc()) {
		$nt->add_error('core', 'MQGPC', _("Magic Quotes GPC"), _("You have magic_quotes_gpc enabled in your php.ini, http or .htaccess file which will cause errors in some modules. FreePBX expects this to be off and runs under that assumption"));
	} else {
		$nt->delete('core', 'MQGPC');
	}

}

// setup locale
function set_language() {
	if (extension_loaded('gettext')) {
		if (isset($_COOKIE['lang'])) {
			setlocale(LC_ALL,  $_COOKIE['lang']);
			putenv("LANGUAGE=".$_COOKIE['lang']);
		} else {
			setlocale(LC_ALL,  'en_US');
		}
		bindtextdomain('amp','./i18n');
		bind_textdomain_codeset('amp', 'utf8');
		textdomain('amp');
	}
}

//
function fileRequestHandler($handler, $module = false, $file = false){
	global $amp_conf;
	
	switch ($handler) {
		case 'cdr':
			include('cdr/cdr.php');
			break;
		case 'cdr_export_csv':
			include('cdr/export_csv.php');
			break;
		case 'cdr_export_pdf':
			include('cdr/export_pdf.php');
			break;
		case 'reload':
			// AJAX handler for reload event
			$response = do_reload();
			header("Content-type: application/json");
			echo json_encode($response);
		break;
		case 'file':
			/** Handler to pass-through file requests 
			 * Looks for "module" and "file" variables, strips .. and only allows normal filename characters.
			 * Accepts only files of the type listed in $allowed_exts below, and sends the corresponding mime-type, 
			 * and always interprets files through the PHP interpreter. (Most of?) the freepbx environment is available,
			 * including $db and $astman, and the user is authenticated.
			 */
			if (!$module || !$file) {
				die_freepbx("unknown");
			}
			//TODO: this could probably be more efficient
			$module = str_replace('..','.', preg_replace('/[^a-zA-Z0-9-\_\.]/','',$module));
			$file = str_replace('..','.', preg_replace('/[^a-zA-Z0-9-\_\.]/','',$file));
			
			$allowed_exts = array(
				'.js' => 'text/javascript',
				'.js.php' => 'text/javascript',
				'.css' => 'text/css',
				'.css.php' => 'text/css',
				'.html.php' => 'text/html',
				'.jpg.php' => 'image/jpeg',
				'.jpeg.php' => 'image/jpeg',
				'.png.php' => 'image/png',
				'.gif.php' => 'image/gif',
			);
			foreach ($allowed_exts as $ext=>$mimetype) {
				if (substr($file, -1*strlen($ext)) == $ext) {
					$fullpath = 'modules/'.$module.'/'.$file;
					if (file_exists($fullpath)) {
						// file exists, and is allowed extension

						// image, css, js types - set Expires to 24hrs in advance so the client does
						// not keep checking for them. Replace from header.php
						if (!$amp_conf['DEVEL']) {
							@header('Expires: '.gmdate('D, d M Y H:i:s', time() + 86400).' GMT', true);
							@header('Cache-Control: max-age=86400, public, must-revalidate',true); 
							@header('Pragma: ', true); 
						}
						@header("Content-type: ".$mimetype);
						include($fullpath);
						exit();
					}
					break;
				}
			}
			die_freepbx("../view/not allowed");
		break;
    case 'api':
      if (isset($_REQUEST['function']) && function_exists($_REQUEST['function'])) {
        $function = $_REQUEST['function'];
        $args = isset($_REQUEST['args'])?$_REQUEST['args']:'';

        //currently works for one arg functions, eventually need to clean this up to except more args
        $result = $function($args);
        $jr = json_encode($result);
      } else {
        $jr = json_encode(null);
      }
      header("Content-type: application/json");
      echo $jr;
    break;
	}
	exit();
}

/**
 * Load View
 *
 * This function is used to load a "view" file. It has two parameters:
 *
 * 1. The name of the "view" file to be included.
 * 2. An associative array of data to be extracted for use in the view.
 *
 * NOTE: you cannot use the variable $view_filename_protected in your views!
 *
 * @param	string
 * @param	array
 * @return	string
 * 
 */
function load_view($view_filename_protected, $vars = array()) {
	
	//return false if we cant find the file or if we cant open it
	if ( ! $view_filename_protected OR ! file_exists($view_filename_protected) OR ! is_readable($view_filename_protected) ) {
		return false;
	}

	// Import the view variables to local namespace
	extract($vars, EXTR_SKIP);
	
	// Capture the view output
	ob_start();
	
	// Load the view within the current scope
	include($view_filename_protected);
	
	// Get the captured output
	$buffer = ob_get_contents();
	
	//Flush & close the buffer
	@ob_end_clean();
	
	//Return captured output
	return $buffer;
}

/**
 * Show View
 *
 * This function is used to show a "view" file. It has two parameters:
 *
 * 1. The name of the "view" file to be included.
 * 2. An associative array of data to be extracted for use in the view.
 *
 * This simply echos the output of load_view() if not false.
 *
 * NOTE: you cannot use the variable $view_filename_protected in your views!
 *
 * @param	string
 * @param	array
 * @return	string
 * 
 */
function show_view($view_filename_protected, $vars = array()) {
  $buffer = load_view($view_filename_protected, $vars );
  if ($buffer !== false) {
    echo $buffer;
  }
}

/** Abort all output, and redirect the browser's location.
 *
 * Useful for returning to the user to a GET location immediately after doing
 * a successful POST operation. This avoids the "this page was sent via POST, resubmit?"
 * message in the users browser, and also overwrites the POST page as a location in 
 * the browser's URL history (eg, they can't press the back button and end up re-submitting
 * the page).
 *
 * @param string   The url to go to
 * @param bool     If execution should stop after the function. Defaults to true
 */
function redirect($url, $stop_processing = true) {
	// TODO: If I don't call ob_end_clean() then is output buffering still on? Do I need to run it off still?
	//       (note ob_end_flush() results in the same php NOTICE so not sure how to turn it off. (?ob_implicit_flush(true)?)
	//
	if (!empty($res)) {
		@ob_end_clean();
	}
	@header('Location: '.$url);
	if ($stop_processing) exit;
}

/** Abort all output, and redirect the browser's location using standard
 * FreePBX user interface variables. By default, will take POST/GET variables
 * 'type' and 'display' and pass them along in the URL. 
 * Also accepts a variable number of parameters, each being the name of a variable
 * to pass on. 
 * 
 * For example, calling redirect_standard('extdisplay','test'); will take $_REQUEST['type'], 
 * $_REQUEST['display'], $_REQUEST['extdisplay'], and $_REQUEST['test'],
 * and if any are present, use them to build a GET string (eg, "config.php?type=setup&
 * display=somemodule&extdisplay=53&test=yes", which is then passed to redirect() to send the browser
 * there.
 *
 * redirect_standard_continue does exactly the same thing but does NOT abort processing. This
 * is used when you wish to do a redirect but there is a possibility of other hooks still needing
 * to continue processing. Note that this is used in core when in 'extensions' mode, as both the
 * users and devices modules need to hook into it together.
 *
 * @param string  (optional, variable number) The name of a variable from $_REQUEST to 
 *                pass on to a GET URL.
 *
 */
function redirect_standard( /* Note. Read the next line. Variable No of Params */ ) {
	$args = func_get_Args();

        foreach (array_merge(array('type','display'),$args) as $arg) {
                if (isset($_REQUEST[$arg])) {
                        $urlopts[] = $arg.'='.urlencode($_REQUEST[$arg]);
                }
        }
        $url = $_SERVER['PHP_SELF'].'?'.implode('&',$urlopts);
        redirect($url);
}

function redirect_standard_continue( /* Note. Read the next line. Varaible No of Params */ ) {
	$args = func_get_Args();

        foreach (array_merge(array('type','display'),$args) as $arg) {
                if (isset($_REQUEST[$arg])) {
                        $urlopts[] = $arg.'='.urlencode($_REQUEST[$arg]);
                }
        }
        $url = $_SERVER['PHP_SELF'].'?'.implode('&',$urlopts);
        redirect($url, false);
}
