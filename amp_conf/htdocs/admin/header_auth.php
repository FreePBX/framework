<?php
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT'); 
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); 
header('Cache-Control: post-check=0, pre-check=0',false); 
header('Pragma: no-cache'); 
session_cache_limiter('public, no-store'); 

// connect to database
require_once('common/db_connect.php'); //PEAR must be installed

function check_login() {
	global	$amp_conf;

	if ($amp_conf['AUTHTYPE'] == 'database') {
		$baselink = (isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

		// start a session and don't let it stop automatically
		session_set_cookie_params(0);
		session_start();
		setcookie('PHPSESSID', session_id());

		// check if the current loading of the page is the first loading after a logout
		if (isset($_SESSION['logout'])) {
			unset($_SESSION['logout']);
			//
			// initialize a relogin on Firefox
			// (request login with username 'relogin'):
			//
			// CAUTION: After that, relative hyperlinks like
			//  <a href="{$_SERVER['PHP_SELF']}">Link</a>
			// may be translated into an absolute hyperlink like
			//  http://relogin:relogin@...
			// which will lead to an error-message in Firefox.
			//
			// So you always have to use absolute hyperlinks like $baselink.
			//
			if (! preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) {
				$link = preg_replace('/^(https{0,1}\/\/)(.*)$/', '$1relogin:relogin@$2', $baselink);
				header("Location: $link");
				exit;
			}
		}

		// check if a new realm needs to be generated because
		// it's the first loading of the page (or the first loading
		// after a logout):
		//
		// Remark: The realm is generated with a random ID number
		// because Internet Explorer will forget the username if the
		// realm changes. Unfortunately Firefox doesn't do so.
		if (! isset($_SESSION['realm'])) {
			srand();
			$_SESSION['realm'] = 'freePBX (SEQ'.mt_rand( 1, 1000000000 ).')'; 
			$_SESSION['login'] = true;
			header("WWW-Authenticate: Basic realm=\"{$_SESSION['realm']}\""); 
			header('HTTP/1.0 401 Unauthorized'); 
			return false;
		}

		// check if a user has already logged in before
		if (isset($_SESSION['AMP_user'])) {
			unset($_SESSION['login']);
			return true;
		}

		// check if a user just entered a username and password
		//
		// is_authorized() has to return 'true' if and only if
		// the username and the passwort given are correct.
		if (isset($_SESSION['login'])) {
			if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
				$_SESSION['AMP_user'] = new ampuser($_SERVER['PHP_AUTH_USER']);

				if (!$_SESSION['AMP_user']->checkPassword($_SERVER['PHP_AUTH_PW'])) {
					// one last chance -- check admin user
					if ( !(count(getAmpAdminUsers()) > 0) && ($_SERVER['PHP_AUTH_USER'] == $amp_conf['AMPDBUSER']) 
						&& ($_SERVER['PHP_AUTH_PW'] == $amp_conf['AMPDBPASS'])) {

						// set admin access
						$_SESSION['AMP_user']->setAdmin();
						unset($_SESSION['login']);
						return true;
					}
				} else {
					unset($_SESSION['login']);
					return true;
				}
			}
		}

		// let the browser ask for a username and a password
		$_SESSION['login'] = true;
		header("WWW-Authenticate: Basic realm=\"{$_SESSION['realm']}\"");
		header('HTTP/1.0 401 Unauthorized');
		
		return false;
	} else {
		if (!isset($_SESSION['AMP_user'])) {
			$_SESSION['AMP_user'] = new ampuser($amp_conf['AMPDBUSER']);
		}
		$_SESSION['AMP_user']->setAdmin();

		return true;
	}
}

$result = check_login();
if ( !(isset($result) ? $result : false) ) {
	unset($_SESSION['AMP_user']);
}

include 'header.php';

if ( !(isset($result) ? $result : false) ) {
	echo "\t<br><br><br><br><center><h2>";
	echo _("You must log in first before you can access this page.");
	echo "</h2></center><br><br><br><br>\n"; 
	include 'footer.php';
	exit;
}
?>
