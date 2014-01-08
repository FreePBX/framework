<?php
// vim: set ai ts=4 sw=4 ft=php:

/*
 * This is the FreePBX BMO Database Helper
 *
 * Copyright 2013 Rob Thomas <rob.thomas@schmoozecom.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * DB_Helper provides $this->getConfig and $this->setConfig
 *
 * This is for use with FreePBX's BMO
 */

class DB_Helper {

	private static $db;
	private static $dbname = "kvstore";
	private static $getPrep;

	private static $checked = false;

	private static $dbGet;
	private static $dbDel;
	private static $dbAdd;

	/** Don't new DB_Helper */
	public function __construct() {
		throw new Exception("You should never 'new' this. Just use it as an 'extends'");
	}

	/** This is our pseudo-__construct, called whenever our public functions are called. */
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

	/**
	 * Requests a var previously stored
	 *
	 * getConfig requests the variable stored with the key $var, and returns it.
	 * Note that it will return an array or a StdObject if it was handed an array
	 * or object, respectively.
	 *
	 * The optional second paramater allows you to specify a sub-grouping - if
	 * you setConfig('foo', 'bar'), then getConfig('foo') == 'bar'. However,
	 * if you getConfig('foo', 1), that will return (bool) false.
	 *
	 * @param string $var Key to request (not null)
	 * @param string $id Optional sub-group ID. 
	 * @return bool|string|array|StdObject Returns what was handed to setConfig, or bool false if it doesn't exist
	 */
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

	/**
	 * Store a variable, array or object.
	 *
	 * setConfig stores $val against $key, in a format that will return
	 * it almost identically when returned by getConfig.
	 *
	 * The optional third paramater allows you to specify a sub-grouping - if
	 * you setConfig('foo', 'bar'), then getConfig('foo') == 'bar'. However,
	 * getConfig('foo', 1) === (bool) false.
	 *
	 * @param string $key Key to set $var to (not null)
	 * @param string $var Value to set $key to. Can be (bool) false, which will delete the key.
	 * @param string $id Optional sub-group ID. 
	 * @return true
	 */
	public function setConfig($key = null, $val = false, $id = "noid") {

		if ($key === null)
			throw new Exception("Can't setConfig null");

		// Our pretend __construct();
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
