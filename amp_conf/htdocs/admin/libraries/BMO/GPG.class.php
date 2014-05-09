<?php
// vim: :set filetype=php tabstop=4 shiftwidth=4 autoindent smartindent:
/**
 * This is part of the FreePBX Big Module Object.
 *
 * Copyright (C) 2013 Schmooze Com, INC
 * Copyright (C) 2013 Rob Thomas <rob.thomas@schmoozecom.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   FreePBX BMO
 * @author    Rob Thomas <rob.thomas@schmoozecom.com>
 * @license   AGPL v3
 */

/**
 * GPG Class for FreePBX's BMO.
 *
 * This is an interface to GPG, for validating FreePBX Modules.
 * It uses the GPG Web-of-trust to ensure modules are valid
 * and haven't been tampered with.
 */
class GPG {

	// Statuses!
	const STATE_GOOD = 1;
	const STATE_TAMPERED = 2;
	const STATE_INVALID = 4;
	const STATE_UNSIGNED = 8;
	const STATE_UNSUPPORTED = 16;
	const STATE_EXPIRED = 32;
	const STATE_REVOKED = 64;

	// This is the FreePBX Master Key.
	private $freepbxkey = '2016349F5BC6F49340FCCAF99F9169F4B33B4659';

	// Our path to GPG.
	private $gpg = "/usr/bin/gpg";
	// Default options.
	private $gpgopts = "--keyserver-options auto-key-retrieve=true";

	// This is how long we should wait for GPG to run a command.
	// This may need to be tuned on things like the pi.
	public $timeout = 2;


	/**
	 * Validate a file using WoT
	 * @param string $file Filename (explicit or relative path)
	 * @return bool returns true or false
	 */
	public function verifyFile($filename, $retry = true) {
		if (!file_exists($filename)) {
			throw new Exception("Unable to open file $filename");
		}

		$out = $this->runGPG("--verify $filename");
		if (strpos($out['status'][1], "[GNUPG:] NO_PUBKEY") === 0) {
			// This should never happen, as we try to auto-download
			// the keys. However, if the keyserver timed out, or,
			// was out of date, we'll try it manually.
			//
			// strlen("[GNUPG:] NO_PUBKEY ") == 19.
			//
			if ($retry && $this->getKey(substr($out['status'][1], 19))) {
				return $this->verifyFile($filename, false);
			} else {
				return false;
			}
		}

		// Now, how does it check out?
		$status = $this->checkStatus($out);
		if ($status['trust']) {
			// It's trusted!  For the interim, we want to make sure that it's signed
			// by the FreePBX Key, or, by a key that's been signed by the FreePBX Key.
			// This is going above-and-beyond the web of trust thing, and we may end up
			// removing it.
			$validline = explode(" ", array_pop($out['status']));
			$thissig = $validline[2];
			$longkey = substr($this->freepbxkey, -16);
			$allsigs = $this->runGPG("--keyid-format long --with-colons --check-sigs $thissig");
			$isvalid = false;
			foreach ($allsigs as $line) {
				$tmparr = explode(":", $line);
				if ($tmparr[4] == $longkey) {
					$isvalid = true;
				}
			}

			return $isvalid;
		} // else
		return false;
	}

	/**
	 * Check the module.sig file against the contents of the 
	 * directory
	 *
	 * @return int GPG::STATUS_whatever.
	 */

	public function verifyModule($path) {
		// TBD
	}

	/**
	 * getKey function to download and install a specified key
	 *
	 * If no key is provided, install the FreePBX key.
	 * Throws an exception if unable to find the key requested
	 */
	public function getKey($key = null) {
		// If we weren't given one, then load the FreePBX Key
		$key = $this->freepbxkey;

		// Lets make sure we don't already have that key.
		$out = $this->runGPG("--list-keys $key");

		if ($out['exitcode'] == 0) {
			// We already have this key
			return true;
		}

		// List of well-known keyservers.
		$keyservers = array("pool.sks-keyservers.net", "pgp.mit.edu", "keyserver.ubuntu.com",
			"keyserver.pgp.com", "pool.sks-keyservers.net"); // Yes. sks is there twice.

		if (strlen($key) > 16) {
			$key = substr($key, -16);
		}

		if (!ctype_xdigit($key)) {
			throw new Exception("Key provided - $key - is not hex");
		}

		foreach ($keyservers as $ks) {
			try {
				$retarr = $this->runGPG("--keyserver $ks --recv-keys $key");
			} catch (RuntimeException $e) {
				// Took too long. We'll just try the next one.
				continue;
			}

			if ($retarr['status'][0] == "[GNUPG:] NODATA 1") {
				// not found on this keyserver. Try the next!
				continue;
			}
			// We found it. And loaded it. Yay!
			return true;
		}

		// We weren't able to find it.
		throw Exception("Unable to find key");
	}

	/**
	 * trustFreePBX function
	 *
	 * Specifically marks the FreePBX Key as ultimately trusted
	 */
	public function trustFreePBX() {
		// Ensure the FreePBX Key is trusted.
		$out = $this->runGPG("--export-ownertrust");
		$stdout = explode("\n", $out['stdout']);
		array_pop($stdout); // Remove trailing blank line.
		if (strpos($stdout[0], "# List of assigned trustvalues") !== 0) {
			throw new Exception("gpg --export-ownertrust didn't return sane stuff");
		}

		$trusted = false;
		foreach ($stdout as $line) {
			if (!$line || $line[0] == "#") {
				continue;
			}

			// We now have a trust line that looks like "2016349F5BC6F49340FCCAF99F9169F4B33B4659:6:"
			$trust = explode(':', $line);
			if ($trust[0] == $this->freepbxkey) {
				$trusted = true;
			}
		}

		if (!$trusted) {
			// We need to trust the FreePBX Key
			$stdout[] = $this->freepbxkey.":6:";
			$stdout[] = "# Trailing comment";
			// Create our temporary file.
			$fd = fopen("php://temp", "r+");
			fwrite($fd, join("\n", $stdout));
			fseek($fd, 0);
			$out = $this->runGPG("--import-ownertrust", $fd);
			if ($out['exitcode'] != 0) {
				throw new Exception("Unable to trust the FreePBX Key! -- ".json_encode($out));
			}
			fclose($fd);
		}
		return true;

	}

	/**
	 * Strips signature from .gpg file
	 *
	 * This saves the file, minus the .gpg extension, to the same directory
	 * the .gpg file is in. It returns the filename of the output file if
	 * valid, throws an exception if unable to validate
	 */
	public function getFile($filename) {
		// Trust that we have the key?

		if (substr($filename, -4) == ".gpg") {
			$output = substr($filename, 0, -4);
		} else {
			throw new Exception("I can only do .gpg files at the moment");
		}

		$out = $this->runGPG("--batch --yes --out $output --decrypt $filename");
		if ($out['exitcode'] == 0) {
			return $output;
		}
		throw new Exception("Unable to strip signature - result was ".json_encode($out));
	}

	/**
	 * Actually run GPG
	 * @param string Params to pass to gpg
	 * @param fd File Descriptor to feed to stdin of gpg
	 * @return array returns assoc array consisting of (array)status, (string)stdout, (string)stderr and (int)exitcode
	 */
	public function runGPG($params, $stdin = null) {
		$fds = array(
			array("file", "/dev/null", "r"), // stdin
			array("pipe", "w"), // stdout
			array("pipe", "w"), // stderr
			array("pipe", "w"), // Status
		);

		// If we need to send stuff to stdin, then do it!
		if ($stdin) {
			$fds[0] = $stdin;
		}

		$proc = proc_open($this->gpg." ".$this->gpgopts." --status-fd 3 $params", $fds, $pipes);
		if (!is_resource($proc)) { // Unable to start!
			throw new Exception("Unable to start PGP");
		}

		// Wait $timeout seconds for it to finish.
		$tmp = null;
		$r = array($pipes[3]);
		if (!stream_select($r , $tmp, $tmp, $this->timeout)) {
			throw new RuntimeException("gpg took too long to run.");
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
	 * Return array of all of my private keys
	 */
	public function getMyKeys() {
		$out = $this->runGPG("-K --with-colons");
		$keys = explode("\n", $out['stdout']);
		array_pop($keys);

		$mykeys = array();
		foreach ($keys as $k) {
			$line = explode(":", $k);
			if ($line[0] == "sec") { // This is a key!
				$mykeys[] = $line[4];
			}
		}
		return $mykeys;
	}

	/**
	 * Get list of files in a directory
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
	 * Generate list of hashes to validate
	 */
	public function getHashes($dir) {
		if (!is_dir($dir)) {
			throw new Exception("You gave me something that isn't a directory!");
		}

		$hasharr = array();

		$files = $this->getFileList($dir);
		foreach ($files as $file) {
			$hasharr[$file] = hash_file('sha256', "$dir/$file");
		}

		return $hasharr;
	}

	/**
	 * Check the module.sig file
	 *
	 * If it's valid, return the processed contents of the sig file.
	 * If it's not valid, return false.
	 */
	public function checkSig($sigfile) {
		if (!is_file($sigfile)) {
			throw new Exception("Not a file...");
		}

		$out = $this->runGPG("--output - $sigfile");
		$status = $this->checkStatus($out['status']);
		if (!$status['trust']) {
			return false;
		}
		$modules = parse_ini_string($out['stdout'], true);
		return $modules;
	}


	/**
	 * Check the return status of GPG to validate
	 * a signature
	 */
	private function checkStatus($status) {
		if (!is_array($status)) {
			throw new Exception("You didn't give me a status. Sad panda");
		}

		$retarr['valid'] = false;
		$retarr['trust'] = false;

		foreach ($status as $l) {
			if (strpos($l, "[GNUPG:] VALIDSIG") === 0) {
				$retarr['valid'] = true;
				$tmparr = explode(' ', $l);
				$retarr['signedby'] = $tmparr[2];
				$retarr['timestamp'] = $tmparr[4];
			}
			if (strpos($l, "[GNUPG:] BADSIG") === 0) {
				$retarr['trustdetails'] = "Bad Signature, Tampered!";
			}
			if (strpos($l, "[GNUPG:] ERRSIG") === 0) {
				$retarr['trustdetails'] = "Unknown Signature";
			}
			if (strpos($l, "[GNUPG:] REVKEYSIG") === 0) {
				$retarr['trustdetails'] = "Signed by Revoked Key";
			}
			if (strpos($l, "[GNUPG:] EXPKEYSIG") === 0) {
				$retarr['trustdetails'] = "Signed by Expired Key";
			}

			if (strpos($l, "[GNUPG:] TRUST_ULTIMATE") === 0 || strpos($l, "[GNUPG:] TRUST_FULLY") === 0) {
				$retarr['trust'] = true;
			}
		}

		return $retarr;
	}
}

