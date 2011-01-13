<?php

@session_start();
if (isset($_REQUEST['logout'])) {
	// logging out..
	// remove the user
	unset($_SESSION['AMP_user']);
	// flag to prompt for pw again
	$_SESSION['logout'] = true; 

	showview('loggedout');
	exit;
}

switch (strtolower($amp_conf['AUTHTYPE'])) {
	case 'webserver':
		// handler for apache doing authentication
		if ((!isset($_SESSION['AMP_user']) || ($_SESSION['AMP_user']->username != $_SERVER['PHP_AUTH_USER'])) && !isset($_REQUEST['logout'])) {
			// not logged in, or username has changed;  and not trying to log out
			
			if (isset($_SESSION['logout']) && $_SESSION['logout']) {
				// workaround for HTTP-auth - just tried to logout, don't allow a log in (with the same credentials)
				unset($_SESSION['logout']);
				// afterwards, this falls through to the !AMP_user check below, and sends 401 header, which causes the browser to re-prompt the user
			} else {
				$_SESSION['AMP_user'] = new ampuser($_SERVER['PHP_AUTH_USER']);
				
				if ($_SESSION['AMP_user']->username == $amp_conf['AMPDBUSER']) {
					// admin user, grant full access
					$_SESSION['AMP_user']->setAdmin();
				}
			}
		}

		if (!isset($_SESSION['AMP_user'])) {
			// not logged in, send headers
			@header('WWW-Authenticate: Basic realm="'._('FreePBX Administration').'"');
			@header('HTTP/1.0 401 Unauthorized');
			showview("unauthorized");
			exit;
		}
		define('FREEPBX_IS_AUTH', 'TRUE');
	case 'none':
		if (!isset($_SESSION['AMP_user'])) {
			$_SESSION['AMP_user'] = new ampuser($amp_conf['AMPDBUSER']);
			$_SESSION['AMP_user']->setAdmin();
		}
		define('FREEPBX_IS_AUTH', 'TRUE');
	break;
	case 'database':
	default:
		if (!isset($_SESSION['AMP_user']) && isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && !isset($_REQUEST['logout'])) {
			if (isset($_SESSION['logout']) && $_SESSION['logout']) {
				// workaround for HTTP-auth - just tried to logout, don't allow a log in (with the same credentials)
				unset($_SESSION['logout']);
				// afterwards, this falls through to the !AMP_user check below, and sends 401 header, which causes the browser to re-prompt the user
			} else {
				// not logged in, and have provided a user/pass
				$_SESSION['AMP_user'] = new ampuser($_SERVER['PHP_AUTH_USER']);
				
				if (!$_SESSION['AMP_user']->checkPassword(sha1($_SERVER['PHP_AUTH_PW']))) {
					// failed, one last chance -- fallback to amportal.conf db admin user
					if (($_SERVER['PHP_AUTH_USER'] == $amp_conf['AMPDBUSER']) && ($_SERVER['PHP_AUTH_PW'] == $amp_conf['AMPDBPASS'])) {
	
						// password succesfully matched amportal.conf db admin user 
	
						// set admin access
						$_SESSION['AMP_user']->setAdmin();
						define('FREEPBX_IS_AUTH', 'TRUE');
					} else {
						// password failed and admin user fall-back failed
						unset($_SESSION['AMP_user']);
					}
				} // else, succesfully logged in
			} 
		}

		if (!isset($_SESSION['AMP_user'])) {
			// not logged in, send headers
			@header('WWW-Authenticate: Basic realm=" '._('FreePBX Administration').'"');
			@header('HTTP/1.0 401 Unauthorized');
			showview("unauthorized");
			exit;
		}
		if (!defined('FREEPBX_IS_AUTH')) {
			define('FREEPBX_IS_AUTH', 'TRUE');
		}
	break;
}


function frameworkPasswordCheck() {
	global $amp_conf;

	$freepbx_conf =& freepbx_conf::create();
  $amp_conf_defaults =& $freepbx_conf->conf_defaults;

	$nt = notifications::create($db);
	if ($amp_conf['AMPMGRPASS'] == $amp_conf_defaults['AMPMGRPASS'][1]) {
		$nt->add_warning('core', 'AMPMGRPASS', _("Default Asterisk Manager Password Used"), _("You are using the default Asterisk Manager password that is widely known, you should set a secure password"));
	} else {
		$nt->delete('core', 'AMPMGRPASS');
	}
	
	if ($amp_conf['ARI_ADMIN_PASSWORD'] == $amp_conf_defaults['ARI_ADMIN_PASSWORD'][1]) {
		$nt->add_warning('ari', 'ARI_ADMIN_PASSWORD', _("Default ARI Admin password Used"), _("You are using the default ARI Admin password that is widely known, you should change to a new password. Do this in amportal.conf"));
	} else {
		$nt->delete('ari', 'ARI_ADMIN_PASSWORD');
	}
	
	if ($amp_conf['AMPDBPASS'] == $amp_conf_defaults['AMPDBPASS'][1]) {
		$nt->add_warning('core', 'AMPDBPASS', _("Default SQL Password Used"), _("You are using the default SQL password that is widely known, you should set a secure password"));
	} else {
		$nt->delete('core', 'AMPDBPASS');
	}
	
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
/** Loads a view (from the views/ directory) with a number of named parameters created as local variables.
 * @param  string   The name of the view.
 * @param  array    The parameters to pass. Note that the key will be turned into a variable name for use by the view.
 *                  For example, passing array('foo'=>'bar'); will create a variable $foo that can be used by
 *                  the code in the view.
 */
function loadview($viewname, $parameters = false) {
	ob_start();
	showview($viewname, $parameters);
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
}
/** Outputs the contents of a view.
 * @param  string   The name of the view.
 * @param  array    The parameters to pass. Note that the key will be turned into a variable name for use by the view.
 *                  For example, passing array('foo'=>'bar'); will create a variable $foo that can be used by
 *                  the code in the view.
 */
function showview($viewname, $parameters = false) {
	global $amp_conf, $db;
	if (is_array($parameters)) {
		extract($parameters);
	}

	$viewname = str_replace('..','.',$viewname); // protect against going to subdirectories
	if (file_exists('views/'.$viewname.'.php')) {
		include('views/'.$viewname.'.php');
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
	}
	exit();
}

?>
