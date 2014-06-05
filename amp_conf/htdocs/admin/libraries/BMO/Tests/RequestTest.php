<?php

class RequestTest extends PHPUnit_Framework_TestCase {

	protected static $f;
	public static $reqDefaults = array( "test5" => "default" );

	public static function setUpBeforeClass() {
		include 'setuptests.php';
		self::$f = FreePBX::create();
		$_REQUEST['test1'] = 1;
		$_REQUEST['test2'] = "two";
		$_REQUEST['test3'] = "3";
		$_REQUEST['test4'] = "'<>\"";
	}

	public function testRequest() {
		$f = self::$f;
		$this->assertEquals($f->getReq('test1'), 1, "Not Equal to 1");
		$this->assertEquals($f->getReq('test2'), "two", "Not Equal to 'two'");
		$f->setReq('test1', 'one');
		$this->assertEquals($f->getReq('test1'), "one", "Update didn't stick");
		$this->assertEquals($f->getReqUnsafe('test4'), "'<>\"", "Couldn't get unfiltered variables");
		$this->assertEquals($f->getReq('test4'), "&#039;&lt;&gt;&quot;", "HTML Filtering not working!");
		$this->assertEquals($f->getReq('test5'), "", "Default was found without classOverride");
		$f->classOverride = "RequestTest";
		$this->assertEquals($f->getReq('test5'), "default", "Default not found with classOverride");
		$this->assertEquals($f->getReq('test5'), "", "classOverride not resetting");
		$f->setReq('test1', false);
		$this->assertEquals($f->getReq('test1'), false, "Unable to delete a request");
		$this->assertEquals($f->getReq('test6'), false, "Found a request that didn't exist");

	}

}
