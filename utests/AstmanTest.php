<?php

/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/
class AstmanTest extends PHPUnit_Framework_TestCase {

	protected static $c;

	public static function setUpBeforeClass() {
		self::$c = \FreePBX::create()->astman;
	}

	public function testPHPUnit() {
		$this->assertEquals("test", "test", "PHPUnit is broken.");
		$this->assertNotEquals("test", "nottest", "PHPUnit is broken.");
	}

	public function testAppExists() {
		$this->assertTrue(self::$c->app_exists('While'), "app_exists says 'while' does not exist it should");
		$this->assertFalse(self::$c->app_exists('FOO'), "app_exists says 'FOO' exist it shouldn't");
	}
}
