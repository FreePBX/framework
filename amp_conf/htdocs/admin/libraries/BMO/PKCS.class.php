<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * PKCS Class for FreePBX's BMO.
 *
 * This is an interface to OpenSSL, for generating certificates
 * the majority of the work was ported from the Asterisk
 * Certificate generation script in contrib/scripts.
 * See: https://wiki.asterisk.org/wiki/display/AST/Secure+Calling+Tutorial
 * Special thanks to Joshua Colp, Matt Jordan and Malcolm Davenport
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
class PKCS {

	// Our path to openssl.
	private $openssl = "/usr/bin/openssl";

	private $defaults = array(
		"org" => "Asterisk",
		"ca_cn" => "Asterisk Private CA",
		"client_cn" => "asterisk",
		"server_cn" => ""
	);

	// This is how long we should wait for OpenSSL to run a command.
	// This may need to be tuned on things like the pi.
	public $timeout = 30;

	//TODO first element that comes in here is the freepbx object yikes
	public function __construct($debug=0) {
		$this->defaults['server_cn'] = exec("hostname -f");
		if(is_int($debug)) {
			$this->debug = $debug;
		} else {
			$this->debug = 0;
		}
	}

	/**
	 * Create a global configuration file for use
	 * when generating more base certificates
	 * @param {string} $cn The Common Name, usually a FQDN
	 * @param {string} $o  The organization name
	 */
	public function createConfig($cn,$o) {
		if(empty($cn) || empty($o)) {
			throw new Exception("Create Config Paramteters Left Blank!");
		}
		$location = $this->getKeysLocation();
		$ca = <<<EOF
[req]
distinguished_name = req_distinguished_name
prompt = no

[req_distinguished_name]
CN={$this->defaults['ca_cn']}
O={$o}

[ext]
basicConstraints=CA:TRUE

EOF;

		file_put_contents($location.'/ca.cfg',$ca);

	$tmp = <<<EOF
[req]
distinguished_name = req_distinguished_name
prompt = no

[req_distinguished_name]
CN={$cn}
O={$o}


EOF;

		file_put_contents($location.'/tmp.cfg',$tmp);
	}

	/**
	 * Create a Certificate Authority. If the CA already exists don't recreate it
	 * or we will end up invalidating all certificates we've already generated
	 * (at some point it would/will happen). Alternatively you can pass the force
	 * option and it will overwrite
	 * @param {string} $passphrase  The passphrase used to encrypt the key file
	 * @param {bool} $force=false Whether to force recreation if already exists
	 */
	public function createCA($passphrase,$force=false) {
		$location = $this->getKeysLocation();
		if(!file_exists($location . "/ca.key") || $force == true) {
			//Creating CA key ${CAKEY}
			$this->out("Creating CA key");
			$this->runOpenSSL("genrsa -des3 -out " . $location . "/ca.key -passout stdin 4096",$passphrase);
		} else {
			$this->out("CA key already exists, reusing");
		}

		if(!file_exists($location . "/ca.crt") || $force == true) {
			//Creating CA certificate ${CACERT}
			$this->out("Creating CA certificate");
			$this->runOpenSSL("req -new -config " . $location . "/ca.cfg -x509 -days 365 -key " . $location . "/ca.key -out " . $location . "/ca.crt -passin stdin", $passphrase);
		} else {
			$this->out("CA certificate already exists, reusing");
		}
		$this->checkPermissions($location);
	}

	/**
	 * Create a Certificate from the provided basename
	 * @param {string} $base       The basename
	 * @param {string} $passphrase The CA key passphrase
	 */
	public function createCert($base,$passphrase) {
		$location = $this->getKeysLocation();
		//Creating certificate ${base}.key
		$this->out("Creating certificate for " . $base);
		$this->runOpenSSL("genrsa -out " . $location . "/" . $base . ".key 1024");
		//Creating signing request ${base}.csr
		$this->out("Creating signing request for " . $base);
		$this->runOpenSSL("req -batch -new -config " . $location . "/ca.cfg -key " . $location . "/" . $base . ".key -out " . $location . "/" . $base . ".csr");
		//Creating certificate ${base}.crt
		$this->out("Creating certificate " . $base);
		$this->runOpenSSL("x509 -req -days 365 -in " . $location . "/" . $base . ".csr -CA " . $location . "/ca.crt -CAkey " . $location . "/ca.key -set_serial 01 -out " . $location . "/" . $base . ".crt -passin stdin", $passphrase);
		//Combining key and crt into ${base}.pem
		$this->out("Combining key and crt into " . $base . ".pem");
		$contents = file_get_contents($location . "/" . $base . ".key");
		$contents = $contents . file_get_contents($location . "/" . $base . ".crt");
		file_put_contents($location . "/" . $base . ".pem", $contents);
		$this->checkPermissions($location);
	}

	/**
	 * Actually run OpenSSL
	 * @param string Params to pass to OpenSSL
	 * @param string String to pass into OpenSSL (used to pass passphrases around)
	 * @return array returns assoc array consisting of (array)status, (string)stdout, (string)stderr and (int)exitcode
	 */
	public function runOpenSSL($params, $stdin = null) {

		$fds = array(
			array("pipe", "r"), // stdin
			array("pipe", "w"), // stdout
			array("pipe", "w"), // stderr
			array("pipe", "w"), // Status
		);

		$webuser = FreePBX::Freepbx_conf()->get('AMPASTERISKWEBUSER');
		$keyloc = $this->getKeysLocation();

		// We need to ensure that our environment variables are sane.
		// Luckily, we know just the right things to say...
		if (!isset($this->opensslenv)) {
			$this->opensslenv['PATH'] = "/bin:/usr/bin";
			$this->opensslenv['USER'] = $webuser;
			$this->opensslenv['HOME'] = $keyloc;
			$this->opensslenv['SHELL'] = "/bin/bash";
		}

		$cmd = $this->openssl. " $params";
		$proc = proc_open($cmd, $fds, $pipes, "/tmp", $this->opensslenv);

		if (!is_resource($proc)) { // Unable to start!
			throw new Exception("Unable to start OpenSSL");
		}

		// If we need to send stuff to stdin, then do it!
		if ($stdin) {
			fwrite($pipes[0], $stdin);
			fclose($pipes[0]);
		}

		// Wait $timeout seconds for it to finish.
		$tmp = null;
		$r = array($pipes[3]);
		if (!stream_select($r , $tmp, $tmp, $this->timeout)) {
			throw new RuntimeException("OpenSSL took too long to run the command \"$cmd\".");
		}

		$status = explode("\n", stream_get_contents($pipes[3]));
		array_pop($status);  // Remove trailing blank line
		$retarr['status'] = $status;
		$retarr['stdout'] = stream_get_contents($pipes[1]);
		$retarr['stderr'] = stream_get_contents($pipes[2]);
		$exitcode = proc_close($proc);
		$retarr['exitcode'] = $exitcode;

		return $retarr;
	}

	/**
	 * Return a list of all Certificates from the key folder
	 * @return array
	 */
	public function getAllCertificates() {
		$keyloc = $this->getKeysLocation();
		return $this->getFileList($keyloc);
	}

	/**
	* Return a list of all Certificates from the key folder
	* @return array
	*/
	public function getAllAuthorityFiles() {
		$keyloc = $this->getKeysLocation();
		$cas = array();
		$files = $this->getFileList($keyloc);
		foreach($files as $file) {
			if(preg_match('/ca\.crt/',$file) || preg_match('/ca\d\.crt/',$file)) {
				if(in_array('ca.key',$files)) {
					$cas[] = $file;
				}
			}
		}
		return $cas;
	}

	/**
	 * Get the Asterisk Key Folder Location
	 * @return string The location of the key folder
	 */
	public function getKeysLocation() {
		$webuser = FreePBX::Freepbx_conf()->get('AMPASTERISKWEBUSER');

		if (!$webuser) {
			throw new Exception("I don't know who I should be running OpenSSL as.");
		}

		// We need to ensure that we can actually read the Key files.
		$keyloc = FreePBX::Freepbx_conf()->get('ASTETCDIR');
		if (!file_exists($keyloc . "/keys")) {
			if(!mkdir($keyloc . "/keys")) {
				throw new Exception("Could Not Create the Asterisk Keys Folder in " . $keyloc);
			}
		}

		if (is_writable($keyloc. "/keys")) {
			return $keyloc . "/keys";
		} else {
			throw new Exception("Don't have permission/can't write to " . $keyloc . "/keys");
		}
	}

	private function out($message,$level=1) {
		if($level < $this->debug) {
			echo $message . "\n";
		}
	}

	/**
	 * Get list of files in a directory
	 * @param string $dir The directory to get the file list of/from
	 */
	private function getFileList($dir) {
		// When we require PHP5.4, use RecursiveDirectoryIterator.
		// Until then..

		$retarr = array();
		$this->recurseDirectory($dir, $retarr, strlen($dir)+1);
		return $retarr;
	}

	/**
	 * Recursive routine for getFileList
	 * @param string $dir The directory to recurse into
	 * @param array $retarry The returned array
	 * @param string $strip What to strip off of the directory
	 */
	private function recurseDirectory($dir, &$retarr, $strip) {

		$dirarr = scandir($dir);
		foreach ($dirarr as $d) {
			// Always exclude hidden files.
			if ($d[0] == ".") {
				continue;
			}
			$fullpath = "$dir/$d";

			if (is_dir($fullpath)) {
				$this->recurseDirectory($fullpath, $retarr, $strip);
			} else {
				$retarr[] = substr($fullpath, $strip);
			}
		}
	}

	/**
	 * Check Permissions on said directory and fix if need be
	 * @param {string} $dir = false The Directory to check and fix
	 */
	private function checkPermissions($dir = false) {
		if (!$dir) {
			// No directory specified. Let's use the default.
			$dir = $this->getKeysLocation();
		}

		// If it ends in a slash, remove it, for sanity
		$dir = rtrim($dir, "/");

		if (!is_dir($dir)) {
			// That's worrying. Can I make it?
			$ret = @mkdir($dir);
			if (!$ret) {
				throw new Exception("Directory $dir doesn't exist, and I can't make it.");
			}
		}

		// Now, who should be running OpenSSL normally?
		$freepbxuser = FreePBX::Freepbx_conf()->get('AMPASTERISKWEBUSER');
		$pwent = posix_getpwnam($freepbxuser);
		$uid = $pwent['uid'];
		$gid = $pwent['gid'];

		// What are the permissions of the keys directory?
		$stat = stat($dir);
		if ($uid != $stat['uid'] || $gid != $stat['gid']) {
			// Permissions are wrong on the keys directory. Hopefully, I'm root, so I can fix them.
			if (!posix_geteuid() === 0) {
				throw new Exception("Permissions error on $dir - please re-run as root to automatically repair");
			}
			// We're root. Yay.
			chown($dir, $uid);
			chgrp($dir, $gid);
		}

		// Check the permissions of the files inside the .gpg directory
		$allfiles = glob($dir."/*");
		foreach ($allfiles as $file) {
			if ($uid != $stat['uid'] || $gid != $stat['gid']) {
				// Permissions are wrong on the keys directory. Hopefully, I'm root, so I can fix them.
				if (!posix_geteuid() === 0) {
					throw new Exception("Permissions error on $dir - please re-run as root to automatically repair");
				}
				// We're root. Yay.
				chown($file, $uid);
				chgrp($file, $gid);
			}
		}
	}
}
