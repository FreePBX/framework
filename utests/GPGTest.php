<?php

class GPGTest extends PHPUnit_Framework_TestCase {

	protected static $f;

	public static function setUpBeforeClass() {
		include 'setuptests.php';
		self::$f = FreePBX::create();
	}

	public function testGPG() {
		$f = self::$f;
		$this->assertTrue($f->GPG->trustFreePBX(), "Unable to trust FreePBX");
	}

	public function testPermissions() {
		$f = self::$f;
		$dir = "/home/asterisk/.gnupg";
		$user = "asterisk";
		$getpwnam = posix_getpwnam($user);
		$uid = $getpwnam['uid'];
		$gid = $getpwnam['gid'];
		`rm -rf $dir`;
		$this->assertFalse(is_dir($dir), "Directory not removed");
		$this->assertTrue($f->GPG->trustFreePBX(), "Unable to trust FreePBX");
		// Now, check permissions
		$this->assertTrue(is_dir($dir), "Directory not created");
		$stat = stat($dir);
		$this->assertEquals($uid, $stat['uid'], "$dir has the wrong permissions");
		// Check all the files.
		$files = glob($dir."/*");
		foreach ($files as $f) {
			$fstat = stat($f);
			$this->assertEquals($uid, $fstat['uid'], "$f has the wrong uid");
			$this->assertEquals($gid, $fstat['gid'], "$f has the wrong gid");
		}
	}

	public function testVerifyFile() {
		$file = "owEBagKV/ZANAwACAVH1to0lFV3LAaw6YgR0ZXN0VDrnF1RoaXMgaXMgYSBmaWxlIHVzZWQgZm9yIHRlc3RpbmcgR1BHOjp2ZXJpZnlGaWxlCokCHAQAAQIABgUCVDrnFwAKCRBR9baNJRVdyw19D/9yaOxzsZF/sVQVZuhT1jsQLTTJPTQnguiQYOVHr8o5dQ+Os6dUlrNX+PxxAINIS9iY/XmnxjUbG3GXiWrMIc6DK2GEpMj/T46IXcKCyixuWSukCcOAT/dBAFbsBuGVTV78a6qeZZp0NGhvrDuEYCVaYxx1aGLIssL/SvMMB2pR0eynxGK+P4so6zK2D6YIM3s5YyS3Kj9A19vzVQpEoGi4hpmG1dWP3zMdPLwSe6rwdEE4QEkkwITHXIxa5o9+IhaFxTDUFuLC6iMgaOQCLVAYM2mPZzloWac8yQIxF12nA+9b4zryftzVsfakJXO4jVBglygIHiRjGTmQI8pB8cwWRNSe4+PReVgWFi6sNUrPEEzw5CRTtdLq/TajWXTH12tqCFXJwtA6Vl7jbQYu5cG47b6hd3dfxHivSN0v1nCqyQh/qDNJ2iSWQGRVD1h+rKSTdX5L01fz7f6tRsX0mJ0pnfDNbmeJmPIZWQY5f0cXzHLYUb6n3I2Z1V159GDVKVjmxhWZdjlhpXtn+KDs6SwC0oqO3+ZH373ibMF+AjGvVowqw9rgI0fwdPRDommpFYXv5XKIUGg0V/Vx88IA+XP1EEYJi7O9g2uRjyN7SaigCbXpkjRpHjnyTty9H0whqWDltaj9N2hVGRUM9zkIUtuY5RCU0Y9xhZ6CwV5zUt9/fAo=";
		@unlink("/tmp/verifyfile.gpg");
		if (!file_put_contents("/tmp/verifyfile.php", base64_decode($file))) {
			$this->fail("Unable to save /tmp/verifyfile.php");
		}
		$f = self::$f;
		$this->assertTrue($f->GPG()->verifyFile("/tmp/verifyfile.php"));

		// Now, mangle it slightly.
		$file[46] = "S"; // Change 'This' to 'Uhis'
		@unlink("/tmp/verifyfile.gpg");
		if (!file_put_contents("/tmp/verifyfile.php", base64_decode($file))) {
			$this->fail("Unable to save /tmp/verifyfile.php");
		}
		$this->assertFalse($f->GPG()->verifyFile("/tmp/verifyfile.php"), "Invalid file validated");
	}

	public function testUntrustedFile() {
		$file = "owEBdwGI/pANAwACAeli+sssISJQActHYgBUOu2iVGhpcyBpcyBhIHZhbGlkLCBidXQgdW50cnVzdGVkIGZpbGUgZm9yIGNoZWNraW5nIEdQRzo6dmVyaWZ5RmlsZQqJARwEAAECAAYFAlQ67cAACgkQ6WL6yywhIlDaZAf+MPROYVwNHZDt+lyQNUJ1DwQvxCQql6yIeNPzCpLOHSRuqO1P3UBySAl03WUqm7WE8Acqg7ZUBE/jgOQ9Sdjv0odF9rtKidXY5c8C9A2gV+tJsYpBPTfs2o3OfOTTSMpRZj55JdhL8c2ziamSHdWK5pGexrDG8CtiLMShUGboK95IwRhrVBgW5/szl2vg/ZY4HX1hVqLS6OizQg+PTFqyzXgNFNAmxlWQ0mLCw4ZzQIPMwYJ/HOvJdX7beQzvyzoaSDnzQnkCQQPI1RILKjQ8q84YXUUh/lcgUTpgYL0Bg0j83+qOzrLRF0gX4H1utlu31eECGbQUG1d2FviDO8clmQ==";
		@unlink("/tmp/verifyfile.gpg");
		if (!file_put_contents("/tmp/verifyfile.php", base64_decode($file))) {
			$this->fail("Unable to save /tmp/verifyfile.php");
		}
		$f = self::$f;
		$this->assertFalse($f->GPG()->verifyFile("/tmp/verifyfile.php"), "Untrusted file is trusted");

		// Now, make it trusted by this machine
		$trust= array("AC402EA5C2717B8FF6E7D9A6551E31DCF65F39A6:6:", "2016349F5BC6F49340FCCAF99F9169F4B33B4659:6:");
		$fd = fopen("php://temp", "r+");
		$tr = join("\n", $trust)."\n" ;
		fwrite($fd, $tr);
		fseek($fd, 0);
		$out = $f->GPG()->runGPG("--import-ownertrust", $fd);
		if ($out['exitcode'] != 0) {
			print_r($out);
			$this->fail("Unable to locally trust the other key!");
		}
		$this->assertFalse($f->GPG()->verifyFile("/tmp/verifyfile.php"), "Locally trusted file is trusted, it shouldn't be.");

	}
}
