<?php

class ampuser {
	public $username;
	public $id;
	private $password;
	private $extension_high;
	private $extension_low;
	private $sections;
	private $mode = "database";

	function __construct($username, $mode="database") {
		$this->username = $username;
		$this->mode = $mode;
		if ($user = $this->getAmpUser($username)) {
			$this->password = $user["password_sha1"];
			$this->extension_high = $user["extension_high"];
			$this->extension_low = $user["extension_low"];
			$this->sections = $user["sections"];
			$this->id = isset($user['id']) ? $user['id'] : null;
		} else {
			// user doesn't exist
			$this->password = false;
			$this->extension_high = "";
			$this->extension_low = "";
			$this->sections = array();
		}
	}

	/** Give this user full admin access
	*/
	function setAdmin() {
		$this->extension_high = "";
		$this->extension_low = "";
		$this->deptname = "";
		$this->sections = array("*");
	}

	function checkPassword($password) {
		// strict checking so false will never match
		switch($this->mode) {
			case "usermanager":
				try {
					return FreePBX::Userman()->getGlobalSettingByID($this->id,'pbx_login') && FreePBX::Userman()->checkCredentials($this->username,$password);
				} catch(Exception $e) {}
				//fail-through
			case "database":
			default:
				return ($this->password === $password);
		}
	}

	function checkSection($section) {
		// if they have * then it means all sections
		return in_array("*", $this->sections) || in_array($section, $this->sections);
	}

	function getAmpUser($username) {
		switch($this->mode) {
			case "usermanager":
				try {
					$um = FreePBX::Userman()->getUserByUsername($username);
					$user = array();
					$user['id'] = $um['id'];
					$user["username"] = $um['username'];
					$user["password_sha1"] = $um['password'];
					$user["extension_low"] = "";
					$user["extension_high"] = "";
					$sections = FreePBX::Userman()->getGlobalSettingByID($um['id'],'pbx_modules');
					$user["sections"] = !empty($sections) && is_array($sections) ? $sections : array();
					return $user;
				} catch(Exception $e) {}
				//fail-through
			case "database":
			default:
				$sql = "SELECT username, password_sha1, extension_low, extension_high, deptname, sections FROM ampusers WHERE username = ?";
				$sth = FreePBX::Database()->prepare($sql);
				$sth->execute(array($username));
				$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
				if (count($results) > 0) {
					$user = array();
					$user["username"] = $results[0]['username'];
					$user["password_sha1"] = $results[0]['password_sha1'];
					$user["extension_low"] = $results[0]['extension_low'];
					$user["extension_high"] = $results[0]['extension_high'];
					$user["sections"] = explode(";",$results[0]['sections']);
					return $user;
				} else {
					return false;
				}
			break;
		}

	}
}
