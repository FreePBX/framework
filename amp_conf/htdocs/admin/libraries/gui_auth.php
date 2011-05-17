<?php
// Set language, needs to be set here for full localization of the gui
set_language();
@session_start();
if (isset($_REQUEST['logout'])) {
	// logging out..
	// remove the user
	unset($_SESSION['AMP_user']);
	// flag to prompt for pw again
	$_SESSION['logout'] = true; 

	show_view($amp_conf['VIEW_LOGGEDOUT'], array('amp_conf'=>&$amp_conf));
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
			show_view($amp_conf['VIEW_UNAUTHORIZED'], array('amp_conf'=>&$amp_conf));
			exit;
		}
		define('FREEPBX_IS_AUTH', 'TRUE');
	case 'none':
		if (!isset($_SESSION['AMP_user'])) {
			$_SESSION['AMP_user'] = new ampuser($amp_conf['AMPDBUSER']);
			$_SESSION['AMP_user']->setAdmin();
		}
    if (!defined('FREEPBX_IS_AUTH')) {
		  define('FREEPBX_IS_AUTH', 'TRUE');
    }
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
					if (($_SERVER['PHP_AUTH_USER'] == $amp_conf['AMPDBUSER']) && ($_SERVER['PHP_AUTH_PW'] == $amp_conf['AMPDBPASS']) && $amp_conf['AMP_ACCESS_DB_CREDS']) {
	
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
			show_view($amp_conf['VIEW_UNAUTHORIZED'], array('amp_conf'=>&$amp_conf));
			exit;
		}
		if (!defined('FREEPBX_IS_AUTH')) {
			define('FREEPBX_IS_AUTH', 'TRUE');
		}
	break;
}

?>
