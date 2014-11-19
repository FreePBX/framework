<?php

class ModulesTest extends PHPUnit_Framework_TestCase {

	protected static $f;

	public static function setUpBeforeClass() {
		include 'setuptests.php';
		self::$f = FreePBX::create();
	}

	public function testPHPUnit() {
		$this->assertEquals("test", "test", "PHPUnit is broken.");
		$this->assertNotEquals("test", "nottest", "PHPUnit is broken.");
	}

	public function testModuleNames() {
		$this->assertEquals(self::$f->Modules->getClassName("sipsettings"), "Sipsettings");
		$this->assertEquals(self::$f->Modules->getClassName("astmodules"), "Core");
		$this->assertEquals(self::$f->Modules->getClassName("randomwrongmodule"), false);
	}
}
