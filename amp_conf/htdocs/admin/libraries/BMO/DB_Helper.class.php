<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * DB_Helper provides $this->getConfig and $this->setConfig
 *
 * This is for use with FreePBX's BMO
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class DB_Helper {

	private static $db;
	private static $dbname = "kvstore";
	private static $getPrep;

	private static $checked = false;

	private static $dbGet;
	private static $dbGetAll;
	private static $dbDel;
	private static $dbAdd;
	private static $dbDelId;
	private static $dbDelMod;

	/* These are only added when required */
	private static $dbGetFirst = false;
	private static $dbGetLast = false;

	/* Allow overriding of class detection */
	private $classOverride = false;

	/** Don't new DB_Helper */
	public function __construct() {
		throw new \Exception("You should never 'new' DB_Helper. Just use it as an 'extends'");
	}

	/** This is our pseudo-__construct, called whenever our public functions are called. */
	private static function checkDatabase() {
		// Have we already run?
		if (self::$checked != false) {
			return;
		}

		if (!isset(self::$db)) {
			self::$db = \FreePBX::create()->Database;
		}

		// Definitions
		$create = "CREATE TABLE IF NOT EXISTS ".self::$dbname." ( `module` CHAR(64) NOT NULL, `key` CHAR(255) NOT NULL, `val` LONGBLOB, `type` CHAR(16) DEFAULT NULL, `id` CHAR(255) DEFAULT NULL)";
		// These are limited to 50 chars as prefixes are limited to 255 chars in total (or 1000 in later versions
		// of mysql), and UTF can cause that to overflow. 50 is plenty.
		$index['index1'] = "ALTER TABLE ".self::$dbname." ADD INDEX index1 (`key`(50))";
		$index['index3'] = "ALTER TABLE ".self::$dbname." ADD UNIQUE INDEX index3 (`module`, `key`(50), `id`(50))";
		$index['index5'] = "ALTER TABLE ".self::$dbname." ADD INDEX index5 (`module`, `id`(50))";

		// Check to make sure our Key/Value table exists.
		try {
			$res = self::$db->query("SELECT * FROM `".self::$dbname."` LIMIT 1");
		} catch (\Exception $e) {
			if ($e->getCode() == "42S02") { // Table does not exist
				self::$db->query($create);
			} else {
				self::checkException($e);
			}
		}

		// Check for indexes.
		// TODO: This only works on MySQL
		$res = self::$db->query("SHOW INDEX FROM `".self::$dbname."`");
		$out = $res->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP, 2);
		foreach ($out as $i => $null) {
			// Do we not know about this index? (Are we upgrading?)
			if (!isset($index[$i])) {
				self::$db->query("ALTER TABLE ".self::$dbname." DROP INDEX $i");
			}
		}

		// Now lets make sure all our indexes exist.
		foreach ($index as $i => $sql) {
			if (!isset($out[$i])) {
				self::$db->query($sql);
			}
		}

		// Add our stored procedures
		self::$dbGet = self::$db->prepare("SELECT `val`, `type` FROM `".self::$dbname."` WHERE `module` = :mod AND `key` = :key AND `id` = :id");
		self::$dbGetAll = self::$db->prepare("SELECT `key` FROM `".self::$dbname."` WHERE `module` = :mod AND `id` = :id ORDER BY `key`");
		self::$dbDel = self::$db->prepare("DELETE FROM `".self::$dbname."` WHERE `module` = :mod AND `key` = :key  AND `id` = :id");
		self::$dbAdd = self::$db->prepare("INSERT INTO `".self::$dbname."` ( `module`, `key`, `val`, `type`, `id` ) VALUES ( :mod, :key, :val, :type, :id )");
		self::$dbDelId = self::$db->prepare("DELETE FROM `".self::$dbname."` WHERE `module` = :mod AND `id` = :id");
		self::$dbDelMod = self::$db->prepare("DELETE FROM `".self::$dbname."` WHERE `module` = :mod");

		// Now this has run, everything IS JUST FINE.
		self::$checked = true;
	}

	/**
	 * Return the name of the table we're using.
	 *
	 * Just returns self::$dbname.
	 */

	public function getTableName() {
		return self::$dbname;
	}

	/**
	 * Requests a var previously stored
	 *
	 * getConfig requests the variable stored with the key $var, and returns it.
	 * Note that it will return an array or a StdObject if it was handed an array
	 * or object, respectively.
	 *
	 * The optional second parameter allows you to specify a sub-grouping - if
	 * you setConfig('foo', 'bar'), then getConfig('foo') == 'bar'. However,
	 * if you getConfig('foo', 1), that will return (bool) false.
	 *
	 * @param string $var Key to request (not null)
	 * @param string $id Optional sub-group ID.
	 * @return bool|string|array|StdObject Returns what was handed to setConfig, or bool false if it doesn't exist
	 */
	public function getConfig($var = null, $id = "noid") {
		if ($var === null) {
			throw new \Exception("Can't getConfig for null");
		}

		// Call our pretend __construct
		self::checkDatabase();

		// Who's asking?
		if ($this->classOverride) {
			$mod = $this->classOverride;
			$this->classOverride = false;
		} else {
			$mod = get_class($this);
		}

		$query[':mod'] = $mod;
		$query[':id'] = $id;
		$query[':key'] = $var;

		try {
			self::$dbGet->execute($query);
		} catch (\Exception $e) {
			self::checkException($e);
		}
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
	 * The optional third parameter allows you to specify a sub-grouping - if
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
			throw new \Exception("Can't setConfig null");

		// Our pretend __construct();
		self::checkDatabase();

		// Start building the query
		$query[':key'] = $key;
		$query[':id'] = $id;

		// Which module is calling this?
		if ($this->classOverride) {
			$mod = $this->classOverride;
			$this->classOverride = false;
		} else {
			$mod = get_class($this);
		}

		$query[':mod'] = $mod;

		// Delete any that previously match
		try {
			$res = self::$dbDel->execute($query);
		} catch (\Exception $e) {
			self::checkException($e);
		}

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

	/**
	 * Alias function to delete
	 * @param {string} $key = null The key name
	 */
	public function delConfig($key = null) {
		$this->setConfig($key);
	}

	/**
	 * Store multiple variables, arrays or objects.
	 *
	 * setMultiConfig is the same as setConfig, except it uses an associative array,
	 * and uses a transaction to speed up the commit.
	 *
	 * @param array $keyval
	 * @param string $id Optional sub-group ID.
	 * @return true
	 */
	public function setMultiConfig($keyval = false, $id = "noid") {
		if (!is_array($keyval)) {
			throw new \Exception('setMultiConfig was not given an array');
		}

		self::$db->beginTransaction();
		foreach ($keyval as $key => $val) {
			$this->setConfig($key, $val, $id);
		}
		self::$db->commit();
	}

	/**
	 * Returns an associative array of all key=>value pairs referenced by $id
	 *
	 * If no $id was provided, return all pairs that weren't set with an $id.
	 * Returns an ordered list from however MySQL orders it (order by `key`)
	 *
	 * @param string $id Optional sub-group ID.
	 * @return array
	 */
	public function getAll($id = "noid") {

		// Our pretend __construct();
		self::checkDatabase();

		// Basic fetchAll.
		if ($this->classOverride) {
			$mod = $this->classOverride;
			$this->classOverride = false;
		} else {
			$mod = get_class($this);
		}

		$query[':mod'] = $mod;
		$query[':id'] = $id;

		$out = $this->getAllKeys($id);

		foreach ($out as $k) {
			$retarr[$k] = $this->getConfig($k, $id);
		}

		if (isset($retarr)) {
			return $retarr;
		} else {
			return array();
		}
	}

	/**
	 * Delete All Keys from module
	 */
	public function deleteAll() {
		// Our pretend __construct();
		self::checkDatabase();

		if ($this->classOverride) {
			$mod = $this->classOverride;
			$this->classOverride = false;
		} else {
			$mod = get_class($this);
		}

		$query[':mod'] = $mod;

		try {
			$ret = self::$dbDelMod->execute($query);
		} catch (\Exception $e) {
			self::checkException($e);
		}
		return $ret;
	}

	/**
	 * Returns an array of all keys referenced by $id
	 *
	 * If no $id was provided, return all pairs that weren't set with an $id.
	 * Returns an ordered list from however MySQL orders it (order by `key`)
	 *
	 * @param string $id Optional sub-group ID.
	 * @return array
	 */
	public function getAllKeys($id = "noid") {

		// Our pretend __construct();
		self::checkDatabase();

		// Basic fetchAll.
		if ($this->classOverride) {
			$mod = $this->classOverride;
			$this->classOverride = false;
		} else {
			$mod = get_class($this);
		}
		$query[':mod'] = $mod;
		$query[':id'] = $id;

		try {
			self::$dbGetAll->execute($query);
		} catch (\Exception $e) {
			self::checkException($e);
		}
		$ret = self::$dbGetAll->fetchAll(\PDO::FETCH_COLUMN, 0);

		return $ret;
	}

	/**
	 * Returns a standard array of all IDs, excluding 'noid'.
	 * Due to font ambiguity (with LL in lower case and I in upper case looking identical in some situations) this uses 'ids' in lower case.
	 *
	 * @return array
	 */
	public function getAllids() {

		// Our pretend __construct();
		self::checkDatabase();

		if ($this->classOverride) {
			$mod = $this->classOverride;
			$this->classOverride = false;
		} else {
			$mod = get_class($this);
		}
		$ret = self::$db->query("SELECT DISTINCT(`id`) FROM `".self::$dbname."` WHERE `module` = '$mod' AND `id` <> 'noid' ")->fetchAll(\PDO::FETCH_COLUMN, 0);
		return $ret;
	}

	/**
	 * Delete all entries that match the ID specified
	 *
	 * This normally is used to remove an item.
	 *
	 * @param string $id Optional sub-group ID.
	 * @return void
	 */
	public function delById($id = null) {

		self::checkDatabase();

		if ($id === null) {
			throw new \Exception("Coder error. You can't delete a blank ID");
		}

		if ($this->classOverride) {
			$mod = $this->classOverride;
			$this->classOverride = false;
		} else {
			$mod = get_class($this);
		}

		$query[':mod']= $mod;
		$query[':id'] = $id;
		try {
			self::$dbDelId->execute($query);
		} catch (\Exception $e) {
			self::checkException($e);
		}
	}

	/**
	 * Return the FIRST ordered entry with this id
	 *
	 * Useful with timestamps?
	 *
	 * @param string $id Required grouping ID.
	 * @return array
	 */
	public function getFirst($id = null) {

		if ($id === null) {
			throw new \Exception("Coder error. getFirst requires an ID");
		}

		self::checkDatabase();

		if (self::$dbGetFirst === false) {
			self::$dbGetFirst = self::$db->prepare("SELECT `key` FROM `".self::$dbname."` WHERE `module` = :mod AND `id` = :id ORDER BY `key` LIMIT 1");
		}

		if ($this->classOverride) {
			$mod = $this->classOverride;
			$this->classOverride = false;
		} else {
			$mod = get_class($this);
		}
		$query[':mod']= $mod;
		$query[':id'] = $id;
		try {
			self::$dbGetFirst->execute($query);
		} catch (\Exception $e) {
			self::checkException($e);
		}
		$ret = self::$dbGetFirst->fetchAll(\PDO::FETCH_COLUMN, 0);
		return $ret[0];
	}

	/**
	 * Return the LAST ordered entry with this id
	 *
	 * @param string $id Required grouping ID.
	 * @return array
	 */
	public function getLast($id = null) {

		if ($id === null) {
			throw new \Exception("Coder error. getFirst requires an ID");
		}

		self::checkDatabase();

		if (self::$dbGetLast === false) {
			self::$dbGetLast = self::$db->prepare("SELECT `key` FROM `".self::$dbname."` WHERE `module` = :mod AND `id` = :id ORDER BY `key` DESC LIMIT 1");
		}

		if ($this->classOverride) {
			$mod = $this->classOverride;
			$this->classOverride = false;
		} else {
			$mod = get_class($this);
		}
		$query[':mod']= $mod;
		$query[':id'] = $id;
		try {
			self::$dbGetLast->execute($query);
		} catch (\Exception $e) {
			self::checkException($e);
		}
		$ret = self::$dbGetLast->fetchAll(\PDO::FETCH_COLUMN, 0);
		return $ret[0];
	}

	/**
	 * Check for exceptions when PDO does things, and try to auto-heal them
	 *
	 */
	public static function checkException($e) {
		if (!is_a($e, "PDOException")) {
			print "checkException wasn't given an Exception. Madness\n";
			exit;
		}

		// I can't seem to see how this is goign to go with i18n. Argh.
		$msg = $e->getMessage();
		if (preg_match('/(134|145)/', $msg)) {
			// Table corrupt. Repair it.
			print _("Database Error detected. Automatic repair started. Please retry");
			$res = self::$db->exec("REPAIR TABLE ".self::$dbname);
			exit(-2);
		} else {
			// Pass it up to the next handler
			throw $e;
		}
	}
}
