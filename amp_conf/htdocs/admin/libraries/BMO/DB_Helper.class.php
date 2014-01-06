<?php

class DB_Helper {

	private static $db;
	private static $dbname = "kvstore";
	private static $getPrep;

	private static $checked = false;

	private static $dbGet;
	private static $dbDel;
	private static $dbAdd;

	public function __construct() {
		throw new Exception("You should never 'new' this. Just use it as an 'extends'");
	}

	// This is our pseudo-__construct, called whenever our public functions are called.
	private static function checkDatabase() {
		// Have we already run?
		if (self::$checked != false)
			return;

		if (!isset(self::$db))
			self::$db = FreePBX::create()->Database;

		// Definitions
		$create = "CREATE TABLE IF NOT EXISTS ".self::$dbname." ( `module` CHAR(64) NOT NULL, `key` CHAR(255) NOT NULL, `val` LONGBLOB, `type` CHAR(16) DEFAULT NULL, `id` CHAR(255) DEFAULT NULL)";
		$index['index1'] = "ALTER TABLE ".self::$dbname." ADD UNIQUE INDEX index1 (`module`, `key`)";
		$index['index2'] = "ALTER TABLE ".self::$dbname." ADD INDEX index2 (`key`)";
		$index['index3'] = "ALTER TABLE ".self::$dbname." ADD INDEX index3 (`id`)";

		// Check to make sure our Key/Value table exists.
		try {
			$res = self::$db->query("SELECT * FROM `".self::$dbname."` LIMIT 1");
		} catch (Exception $e) {
			if ($e->getCode() == "42S02") { // Table does not exist
				self::$db->query($create);
			} else {
				print "I have ".$e->getCode()." as an error<br>\nI don't know what that means.<br/>";
				exit;
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

		// Add our stored procedures
		self::$dbGet = self::$db->prepare("SELECT `val`, `type` FROM `".self::$dbname."` WHERE `module` = :mod AND `key` = :key AND `id` = :id");
		self::$dbDel = self::$db->prepare("DELETE FROM `".self::$dbname."` WHERE `module` = :mod AND `key` = :key  AND `id` = :id");
		self::$dbAdd = self::$db->prepare("INSERT INTO `".self::$dbname."` ( `module`, `key`, `val`, `type`, `id` ) VALUES ( :mod, :key, :val, :type, :id )");

		// Now this has run, everything IS JUST FINE.
		self::$checked = true;

	}

	public function getConfig($var = null, $id = "noid") {
		if ($var === null)
			throw new Exception("Can't getConfig for null");

		// Call our pretend __construct
		self::checkDatabase();

		// Who's asking?
		$mod = get_class($this);
		$query[':mod'] = $mod;
		$query[':id'] = $id;
		$query[':key'] = $var;

		self::$dbGet->execute($query);
		$res = self::$dbGet->fetchAll();
		if (isset($res[0])) {
			// Found!
			if ($res[0]['type'] == "json-obj") {
				return json_decode($res[0]['val']);
			} elseif ($res[0]['type'] == "json-arr") {
				return json_decode($res[0]['val'], true);
			} else {
				return $res[0]['val'];
			}
		}

		// We don't have a result. Maybe there's a default?
		if (property_exists($mod, "dbDefaults")) {
			$def = $mod::$dbDefaults;
			if (isset($def[$var]))
				return $def[$var];
		}

		return false;
	}

	public function setConfig($key = null, $val = false, $id = "noid") {
		// Note that setting a key to false DELETES it.

		if ($key === null)
			throw new Exception("Can't setConfig null");

		self::checkDatabase();

		// Start building the query
		$query[':key'] = $key;
		$query[':id'] = $id;

		// Which module is calling this?
		$query[':mod'] = get_class($this);

		// Delete any that previously match
		$res = self::$dbDel->execute($query);


		if ($val === false) // Just wanted to delete
			return true;

		if (is_array($val)) {
			$query[':val'] = json_encode($val);
			$query[':type'] = "json-arr";
		} elseif (is_object($val)) {
			$query[':val'] = json_encode($val);
			$query[':type'] = "json-obj";
		} else {
			$query[':val'] = $val;
			$query[':type'] = null;
		}

		self::$dbAdd->execute($query);
		return true;
	}
}
