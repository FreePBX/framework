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

}
