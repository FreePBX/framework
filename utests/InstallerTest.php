<?php

class InstallerTest extends PHPUnit_Framework_TestCase {

	protected static $i;

	public static function setUpBeforeClass() {
		include 'setuptests.php';
		self::$i = FreePBX::Installer();
	}

	public function testFramework() {
		$wr = "/var/www/html";
		$i = self::$i;
		$this->assertEquals($i->getDestination("framework", "upgrades/1.2.3/file"), "$wr/admin/modules/framework/upgrades/1.2.3/file", "Not-special file failed");
		$checks = array (
			"amp_conf/htdocs/admin/ajax.php" => "$wr/admin/ajax.php",
			"amp_conf/astetc/cdr_mysql.conf" => "/etc/asterisk/cdr_mysql.conf",
			"amp_conf/bin/amportal" => "/var/lib/asterisk/bin/amportal",
			"amp_conf/sbin/amportal" => "/usr/local/sbin/amportal",
			"amp_conf/sounds/dir-intro-fnln.gsm" => "/var/lib/asterisk/sounds/dir-intro-fnln.gsm",
			"amp_conf/agi-bin/phpagi-asmanager.php" => "/var/lib/asterisk/agi-bin/phpagi-asmanager.php",
		);

		foreach ($checks as $src => $dst) {
			$this->assertEquals($dst, $i->getDestination("framework", $src), "$src not pointing to $dst");
		}
	}

	public function testOtherModule() {
		$wr = "/var/www/html";
		$i = self::$i;
		$this->assertEquals($i->getDestination("cdr", "functions.inc.php"), "$wr/admin/modules/cdr/functions.inc.php", "default dest failed");
	}

}
