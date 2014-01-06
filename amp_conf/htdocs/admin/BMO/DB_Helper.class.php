<?php

class DB_Helper {

	private static $db;
	private static $dbname = "kvstore";
	private static $getPrep;

	private static $checked = false;

	private static function checkDatabase() {
		// Definitions
		$create = "CREATE TABLE IF NOT EXISTS ".self::$dbname." ( `module` CHAR(64) NOT NULL, `key` CHAR(255) NOT NULL, `val` LONGBLOB )";
		$index['index1'] = "ALTER TABLE ".self::$dbname." ADD UNIQUE INDEX index1 (`module`, `key`)";
		$index['index2'] = "ALTER TABLE ".self::$dbname." ADD INDEX index2 (`key`)";

		// Have we already run?
		if (self::$checked)
			return;

		// Check to make sure our Key/Value table exists.
		try {
			$res = self::$db->query("SELECT * FROM `".self::$dbname."` LIMIT 1");
		} catch (Exception $e) {
			if ($e->getCode() == "42S02") { // Table does not exist
				self::$db->query($create);
			} else {
				print "I have ".$e->getCode()." as an error\n";
			}
		}

		// Check for indexes.
		// TODO: This only works on MySQL
		$res = self::$db->query("SHOW INDEX FROM `".self::$dbname."`");
		$out = $res->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP, 2);
		foreach ($index as $i => $sql) {
			if (!isset($out[$i]))
				self::$db->query($sql);
		}

		// Now this has run, everything IS JUST FINE.
		self::$checked = true;
	}

	public function getConfig($var = null) {
		if ($var === null)
			throw new Exception("Can't getConfig for null");

		if (!isset(self::$db))
			self::$db = FreePBX::create()->Database;

		// Right! Lets see if we can grab the requested values.
		self::checkDatabase();

		// Start by caring about which module is asking
		$mod = get_class($this);

		if (!isset($this->dbGet))
			$this->dbGet = self::$db->prepare("SELECT `val` FROM `".self::$dbname."` WHERE `module` = :mod AND `key` = :key");

		$this->dbGet->execute(array(':mod' => $mod, ':key' => $var));
		$res = $this->dbGet->fetchAll(PDO::FETCH_COLUMN, 0);
		if (isset($res[0]))
			return $res[0];

		// We don't have a result. Maybe there's a default?
		if (property_exists($mod, "dbDefaults")) {
			$def = $mod::$dbDefaults;
			if (isset($def[$var]))
				return $def[$var];
		}

		return false;
	}
}

