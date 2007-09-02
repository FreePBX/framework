<?php

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
	case 'database':
		if (!isset($_SESSION['AMP_user']) && isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && !isset($_REQUEST['logout'])) {
			if (isset($_SESSION['logout']) && $_SESSION['logout']) {
				// workaround for HTTP-auth - just tried to logout, don't allow a log in (with the same credentials)
				unset($_SESSION['logout']);
				// afterwards, this falls through to the !AMP_user check below, and sends 401 header, which causes the browser to re-prompt the user
			} else {
				// not logged in, and have provided a user/pass
				$_SESSION['AMP_user'] = new ampuser($_SERVER['PHP_AUTH_USER']);
				
				if (!$_SESSION['AMP_user']->checkPassword($_SERVER['PHP_AUTH_PW'])) {
					// failed, one last chance -- fallback to amportal.conf db admin user
					if ( (count(getAmpAdminUsers()) == 0) && ($_SERVER['PHP_AUTH_USER'] == $amp_conf['AMPDBUSER']) 
					  && ($_SERVER['PHP_AUTH_PW'] == $amp_conf['AMPDBPASS'])) {
	
						// password succesfully matched amportal.conf db admin user 
	
						// set admin access
						$_SESSION['AMP_user']->setAdmin();
					} else {
						// password failed and admin user fall-back failed
						unset($_SESSION['AMP_user']);
					}
				} // else, succesfully logged in
			} 
		}

		if (!isset($_SESSION['AMP_user'])) {
			// not logged in, send headers
			@header('WWW-Authenticate: Basic realm="FreePBX '._('Administration').'"');
			@header('HTTP/1.0 401 Unauthorized');
			showview("unauthorized");
			exit;
		}
	break;
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
			@header('WWW-Authenticate: Basic realm="FreePBX '._('Administration').'"');
			@header('HTTP/1.0 401 Unauthorized');
			showview("unauthorized");
			exit;
		}
	case 'none':
	default:
		if (!isset($_SESSION['AMP_user'])) {
			$_SESSION['AMP_user'] = new ampuser($amp_conf['AMPDBUSER']);
			$_SESSION['AMP_user']->setAdmin();
		}
	break;
}

?>
