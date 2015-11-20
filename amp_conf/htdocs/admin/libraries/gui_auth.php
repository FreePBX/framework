<?php
//promt for a password if there there is no user set
if (!isset($_SESSION['AMP_user'])) {

	//|| (isset($_SESSION['AMP_user']->username) && $_SESSION['AMP_user']->username != $_SERVER['PHP_AUTH_USER'])) {
	//if we dont have a username/pass prompt for one
	if (!$username || !$password) {
		switch(strtolower($amp_conf['AUTHTYPE'])) {
			case 'usermanager':
			case 'database':
				$no_auth = true;
			break;
			case 'webserver':
				header('HTTP/1.0 401 Unauthorized');
			case 'none':
				break;
		}
	}

	//test credentials
	switch (strtolower($amp_conf['AUTHTYPE'])) {
		case 'webserver':
			// handler for apache doing authentication
			$_SESSION['AMP_user'] = new ampuser($_SERVER['PHP_AUTH_USER']);
			if (empty($_SESSION['AMP_user']->username)) {
				unset($_SESSION['AMP_user']);
				$no_auth = true;
			}
			break;
		case 'none':
			$_SESSION['AMP_user'] = new ampuser($amp_conf['AMPDBUSER']);
			$_SESSION['AMP_user']->setAdmin();
			break;
		case 'usermanager':
			if (!class_exists("\\FreePBX\\modules\\Userman")) {
				// Unsurprisingly, it didn't. Let's load it.
				// We need to manually load it, as the autoloader WON'T.
				$hint = FreePBX::Config()->get("AMPWEBROOT")."/admin/modules/userman/Userman.class.php";
				try {
					FreePBX::create()->injectClass("Userman", $hint);
					if(method_exists(FreePBX::Userman(),"getCombinedGlobalSettingByID")) {
						$_SESSION['AMP_user'] = new ampuser($username,"usermanager");
						if (!$_SESSION['AMP_user']->checkPassword($password)) {
							unset($_SESSION['AMP_user']);
							//Fall through to database only
							//$no_auth = true;
							//if(!empty($username)) {
								//freepbx_log_security('Authentication failure for '.(!empty($username) ? $username : 'unknown').' from '.$_SERVER['REMOTE_ADDR']);
							//}
						} else {
							if(FreePBX::Userman()->getCombinedGlobalSettingByID($_SESSION['AMP_user']->id,'pbx_admin')) {
								$_SESSION['AMP_user']->setAdmin();
							}
							//We are logged in. Stop processing
							break;
						}
					}
				} catch(Exception $e) {}
			}
			//no break here so that we can fall back to database if userman is broken
		case 'database':
		default:
			// not logged in, and have provided a user/pass
			$_SESSION['AMP_user'] = new ampuser($username);
			if (!$_SESSION['AMP_user']->checkPassword($password)) {
				// failed, one last chance -- fallback to amportal.conf db admin user
				if ($amp_conf['AMP_ACCESS_DB_CREDS'] && $username == $amp_conf['AMPDBUSER'] && $password == $amp_conf['AMPDBPASS']) {
					// password succesfully matched amportal.conf db admin user, set admin access
					$_SESSION['AMP_user']->setAdmin();
				} else {
					// password failed and admin user fall-back failed
					unset($_SESSION['AMP_user']);
					$no_auth = true;
					//for now because of how freepbx works
					if(!empty($username)) {
						freepbx_log_security('Authentication failure for '.(!empty($username) ? $username : 'unknown').' from '.$_SERVER['REMOTE_ADDR']);
					}
				}
			}
			break;
	}
}

if (isset($_SESSION['AMP_user'])) {
	define('FREEPBX_IS_AUTH', 'TRUE');
}
?>
