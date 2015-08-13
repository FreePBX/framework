<?php
class RequestTest extends PHPUnit_Framework_TestCase {

	protected static $f;
	private $config_file;
	private $config_file2;
	private $config_dir;

	/**
	* @beforeClass
	*/
	public static function setUpBeforeClass() {
		include "setuptests.php";
		self::$f = FreePBX::create();
		$this->config_file = tempnam();
		$this->config_file2 = tempnam();
		$this->config_dir = sys_get_temp_dir();
		$config = <<< 'EOF'
; Config file parser test
;-- Multi-line comment
in the header --;firstvalue=is not in a section!

[template-section](!)
foobar=barfoo

[first-section]
foo=bar
bar=[bracketed value]
one => two
hey =this is a big long\r\n
hey+=	multi-line value\r\n
hey +=that goes on and on

;-- block comment on one line! --;
[second_section](template-section)
setting=>value ;comment at the end
setting2=>value with a \; semicolon
setting3	= value;-- multiline comment starts here
and continues
and ends here--;setting4 =>value
;--a comment

[bad_section]
--;;another =>comment? i hope so
	setting5     => value
setting=value 2
#include foo.conf	

[first-section](+)
baz=bix

[voicemail]
9876 => 1234,Typical voicemail,,,attach=no|saycid=no|envelope=no|delete=no
5432=>1234,Typical voicemail,,,attach=no|saycid=no|envelope=no|delete=no

EOF;
		file_put_contents($this->config_dir . $this->config_file, $config);
		$config = str_replace("setting=value 2", "[invalid section]", $config);
		file_put_contents($this->config_dir . $this->config_file2, $config);
	}

	/**
	* @afterClass
	*/
	public static function setUpAfterClass() {
		unlink($this->config_dir . $this->config_file);
		unlink($this->config_dir . $this->config_file2);
	}

	/**
	* @test
	*/
	public function testLoadConfig() {
		$f = self::$f;
		$config = $f->LoadConfig->getConfig($this->config_file, $this->config_dir);
		$this->assertTrue(is_array($config["first-section"]));
		$this->assertTrue(strlen($config["first-section"]["hey"]) > 50);
		$this->assertEquals($config["second_section"]["setting2"], "value with a ; semicolon");
		$this->assertFalse(isset($config["second_section"]["another"]));
		$this->assertTrue(is_array($config["second_section"]["setting"]));
		$this->assertEquals($config["second_section"]["foobar"], "barfoo");
	}

	/**
	* @test
	* @expectedException Exception
	* @expectedExceptionMessageRegExp #Coding Error - don't understand '\[invalid section\]' in .*#
	*/
	public function testLoadConfigFailure() {
		$f = self::$f;
		$config = $f->LoadConfig->getConfig($this->config_file2, $this->config_dir);
	}
}
