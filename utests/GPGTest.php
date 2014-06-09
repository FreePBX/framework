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

}
