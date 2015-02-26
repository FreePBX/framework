<?php
/**
 * What does this class do?
 * This class is a PDO wrapper for PearDB.
 * We moved from PearDB to PDO in 13 but much of the code
 * still references PearDB functionality so we have to have
 * a wrapper class
 * Copyright Schmooze Com, Inc 2014
 */

/**
 * Indicates the current default fetch mode should be used
 * @see DB_common::$fetchmode
 */
define('DB_FETCHMODE_DEFAULT', 0);

/**
 * Column data indexed by numbers, ordered from 0 and up
 */
define('DB_FETCHMODE_ORDERED', 1);

/**
 * Column data indexed by column names
 */
define('DB_FETCHMODE_ASSOC', 2);

/**
 * Column data as object properties
 */
define('DB_FETCHMODE_OBJECT', 3);

/**
 * For multi-dimensional results, make the column name the first level
 * of the array and put the row number in the second level of the array
 *
 * This is flipped from the normal behavior, which puts the row numbers
 * in the first level of the array and the column names in the second level.
 */
define('DB_FETCHMODE_FLIPPED', 4);

/**
 * Table already exists error
 */
define('DB_ERROR_ALREADY_EXISTS', -5);

/**
 * Can not create table error
 */
define('DB_ERROR_CANNOT_CREATE', -15);

class DB {
	private $db = null;
	private static $error = null;
	private $res = null;
	private $defaultFetch = DB_FETCHMODE_ORDERED;
	public function __construct($dbh=null) {
		$this->db = !empty($dbh) ? $dbh : FreePBX::create()->Database;
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function sql($sql = null, $type = "query", $fetchmode=DB_FETCHMODE_DEFAULT) {
		$fetch = $this->correctFetchMode($fetchmode);
		if(!method_exists($this->db,"sql")) {
			return $this->db->$type($sql);
		}
		return $this->db->sql($sql,$type,$fetch);
	}

	/**
	 * http://pear.php.net/manual/en/package.database.db.db-common.getcol.php
	 * @param string  $sql    [description]
	 * @param integer $col    [description]
	 * @param array   $params [description]
	 */
	public function getCol($sql,$col=0,$params=array()) {
		self::$error = null;
		$array = array();
		try {
			if(!empty($params) && is_array($params)) {
				$this->res = $this->db->prepare($sql);
				$this->res->execute($params);
				while($row = $this->res->fetchColumn($col)) {
					$array[] = $row;
				}
				return $array;
			}
			$this->res = $this->db->query($sql);
			if($this->res === false) {
				return false;
			}
			while($row = $this->res->fetchColumn($col)) {
				$array[] = $row;
			}
			return $array;
		} catch (Exception $e) {
			return new DB_Error($e);
		}
	}

	/**
	 * http://pear.php.net/manual/en/package.database.db.db-common.getall.php
	 * @param string  $sql       [description]
	 * @param array  $params    [description]
	 * @param constant $fetchmode [description]
	 */
	public function getAll($sql,$params=array(),$fetchmode=DB_FETCHMODE_DEFAULT) {
		//this is a sad workaround for people who couldn't follow documentation for functions
		$fetchmode = $this->isFetchMode($params) ? $params : $fetchmode;
		self::$error = null;
		try {
			$fetch = $this->correctFetchMode($fetchmode);
			if(!empty($params) && is_array($params)) {
				$this->res = $this->db->prepare($sql);
				$this->res->execute($params);
				return $this->res->fetchAll($fetch);
			}
			$this->res = $this->db->query($sql);
			if($this->res === false) {
				return false;
			}
			return $this->res->fetchAll($fetch);
		} catch (Exception $e) {
			return new DB_Error($e);
		}
	}

	/**
	 * http://pear.php.net/manual/en/package.database.db.db-common.getrow.php
	 * @param string  $sql       [description]
	 * @param array  $params    [description]
	 * @param constant $fetchmode [description]
	 */
	public function getRow($sql,$params=array(),$fetchmode=DB_FETCHMODE_DEFAULT) {
		//this is a sad workaround for people who couldn't follow documentation for functions
		$fetchmode = $this->isFetchMode($params) ? $params : $fetchmode;
		self::$error = null;
		try {
			$fetch = $this->correctFetchMode($fetchmode);
			if(!empty($params) && is_array($params)) {
				$this->res = $this->db->prepare($sql);
				$this->res->execute($params);
				return $this->res->fetch($fetch);
			}
			$this->res = $this->db->query($sql);
			if($this->res === false) {
				return false;
			}
			return $this->res->fetch($fetch);
		} catch (Exception $e) {
			return new DB_Error($e);
		}
	}

	/**
	 * http://pear.php.net/manual/en/package.database.db.db-common.getrow.php
	 * @param string  $sql    [description]
	 * @param array  $params [description]
	 */
	public function getOne($sql,$params=array()) {
		self::$error = null;
		try {
			if(!empty($params) && is_array($params)) {
				$this->res = $this->db->prepare($sql);
				$this->res->execute($params);
				$line = $this->res->fetch(PDO::FETCH_NUM);
				if (isset($line[0])) {
					return $line[0];
				}
				return false;
			}
			$this->res = $this->db->query($sql);
			if($this->res === false) {
				return false;
			}
			$line = $this->res->fetchColumn();
			return !empty($line) ? $line : false;
		} catch (Exception $e) {
			return new DB_Error($e);
		}
	}

	/**
	 * http://pear.php.net/manual/en/package.database.db.db-common.getassoc.php
	 * @param string  $sql         [description]
	 * @param bool  $force_array [description]
	 * @param array  $params      [description]
	 * @param constant  $fetchmode   [description]
	 * @param bool  $group       [description]
	 */
	public function getAssoc($sql, $force_array = false, $params = array(),
															$fetchmode = DB_FETCHMODE_DEFAULT, $group = false) {
		//this is a sad workaround for people who couldn't follow documentation for functions
		$fetchmode = $this->isFetchMode($params) ? $params : $fetchmode;
		self::$error = null;
		try {
			$fetch = $this->correctFetchMode($fetchmode);
			if(!empty($params) && is_array($params)) {
				$this->res = $this->db->prepare($sql);
				$this->res->execute($params);
				$result = $this->res->fetchAll($fetch);
			}
			$this->res = $this->db->query($sql);
			if($this->res === false) {
				return false;
			}
			$result = $this->res->fetchAll($fetch);
			if($result === false) {
				return false;
			}
			$final = array();
			switch($fetch) {
				case \PDO::FETCH_NUM:
					foreach($result as $data) {
						if(count($data) > 2) {
							$k = array_shift($data);
							$v = array_values($data);
							$final[$k] = $v;
						} elseif(count($data) == 2) {
							$k = array_shift($data);
							$v = array_values($data);
							$final[$k] = $v;
						} else {
							return false;
						}
					}
				break;
				default:
					throw new \Exception("Unsupported getAssoc Conversion mode");
				break;
			}
			return $final;
		} catch (Exception $e) {
			return new DB_Error($e);
		}
	}

	/**
	 * http://pear.php.net/manual/en/package.database.db.db-common.affectedrows.php
	 */
	public function affectedRows() {
		return isset($this->res) ? $this->res->rowCount() : false;
	}

	/**
	 * Get Last Insert ID
	 * @return [type] [description]
	 */
	public function insert_id() {
		return $this->db->lastInsertId();
	}

	public function escapeSimple($str = null) {
		// Using PDO::quote
		// But remove first ' and last ' as PearDB didnt add those
		return substr($this->quote($str), 1, -1);
	}

	public function quoteSmart($in) {
		return $this->db->quote($in);
	}

	public function quote($in) {
		return $this->db->quote($in);
	}

	/**
	 * [IsError description]
	 * @param [type] $e [description]
	 */
	public static function IsError($e) {
		if(is_object($e) && get_class($e) == "DB_Error") {
			return $e;
		}
		return false;
	}

	public function prepare($query) {
		return $this->db->prepare($query);
	}

	public function execute($stmt, $data = array()) {
		try {
			if(!isset($data[0])) {
				return $stmt->execute(array_values($data));
			} else {
				return $stmt->execute($data);
			}
		} catch(Exception $e) {
			return new DB_Error($e);
		}
	}

	/**
	 * http://pear.php.net/manual/en/package.database.db.db-common.executemultiple.php
	 * @param object $stmt [description]
	 * @param array  $data [description]
	 */
	public function executeMultiple($stmt, $data = array()) {
		try {
			foreach($data as &$row) {
				if(!isset($row[0])) {
					$stmt->execute(array_values($row));
				} else {
					$stmt->execute($row);
				}
			}
		} catch(Exception $e) {
			return new DB_Error($e);
		}
		return true;
	}

	/**
	 * http://pear.php.net/manual/en/package.database.db.db-common.query.php
	 * @param  string  $sql    [description]
	 * @param  array  $params [description]
	 * @return [type]         [description]
	 */
	public function query($sql,$params=array()) {
		self::$error = null;
		if(empty($params)) {
			try {
				$sth = $this->db->query($sql);
			} catch(\Exception $e) {
				return new DB_Error($e);
			}
		} else {
			try {
				$sth = $this->db->prepare($sql);
				$sth->execute($params);
			} catch(\Exception $e) {
				return new DB_Error($e);
			}
		}
		return new DB_result($sth);
	}

	/**
	 * http://pear.php.net/manual/en/package.database.db.db-common.setfetchmode.php
	 * @param int $fetchmode The fetchmode
	 */
	public function setFetchMode($fetchmode) {
		switch($fetchmode) {
			case DB_FETCHMODE_DEFAULT:
				throw new \Exception("You can't set the default to the default");
			break;
			case DB_FETCHMODE_OBJECT:
			case DB_FETCHMODE_ASSOC:
			case DB_FETCHMODE_ORDERED:
				$this->defaultFetch = $fetchmode;
			break;
			default:
				throw new Exception("Unknown SQL fetchmode of $fetchmode");
			break;
		}
	}

	private function isFetchMode($mixed) {
		return (is_int($mixed) && ($mixed == DB_FETCHMODE_DEFAULT || $mixed == DB_FETCHMODE_OBJECT || $mixed == DB_FETCHMODE_ASSOC || $mixed == DB_FETCHMODE_ORDERED));
	}

	/**
	 * Adjust the Fetch mode for PDO from PearDB
	 * @param [type] $PearDBFetchMode [description]
	 */
	private function correctFetchMode($PearDBFetchMode=DB_FETCHMODE_DEFAULT) {
		switch($PearDBFetchMode) {
			case DB_FETCHMODE_OBJECT:
				$fetch = PDO::FETCH_OBJ;
			break;
			case DB_FETCHMODE_ASSOC:
				$fetch = PDO::FETCH_ASSOC;
			break;
			case DB_FETCHMODE_DEFAULT:
				$fetch = $this->correctFetchMode($this->defaultFetch);
			break;
			case DB_FETCHMODE_ORDERED:
				$fetch = PDO::FETCH_NUM;
			break;
			default:
				throw new Exception("Unknown SQL fetchmode of $fetchmode");
			break;
		}
		return $fetch;
	}
}

class DB_result {
	private $sth = null;

	public function __construct($PDOStatement) {
		$this->sth = $PDOStatement;
	}

	public function fetchInto() {
		throw new Exception("fetchInto not implemented");
	}

	/**
	 * Fetches a row from a result set
	 * @param {int} $fetchmode = DB_DEFAULT_MODE The fetchmode to use
	 * @param {int} $rownum    = null            The row number to fetch
	 */
	public function fetchRow($fetchmode = DB_DEFAULT_MODE , $rownum = null) {
		$res = $this->sth->fetch($this->correctFetchMode($fetchmode));
		return isset($rownum) ? (isset($res[$rownum]) ? $res[$rownum] : false) : $res;
	}

	public function free() {
		throw new Exception("free not implemented");
	}

	public function nextResult() {
		throw new Exception("nextresult not implemented");
	}

	public function numCols() {
		throw new Exception("numcols not implemented");
	}

	/**
	 * Gets number of rows in a result set
	 * http://pear.php.net/manual/en/package.database.db.db-result.numrows.php
	 */
	public function numRows() {
		return $this->sth->rowCount();
	}

	/**
	 * Adjust the Fetch mode for PDO from PearDB
	 * @param integer $PearDBFetchMode The fetchmode to use
	 */
	private function correctFetchMode($PearDBFetchMode=DB_FETCHMODE_DEFAULT) {
		switch($PearDBFetchMode) {
			case DB_FETCHMODE_OBJECT:
				$fetch = PDO::FETCH_OBJ;
			break;
			case DB_FETCHMODE_ASSOC:
				$fetch = PDO::FETCH_ASSOC;
			break;
			case DB_FETCHMODE_ORDERED:
			case DB_FETCHMODE_DEFAULT:
				$fetch = PDO::FETCH_NUM;
			break;
			default:
				throw new Exception("Unknown SQL fetchmode of $fetchmode");
			break;
		}
		return $fetch;
	}
}

class DB_Error {
	private $e =null;
	public function __construct(Exception $exception = null) {
		$this->e = $exception;
	}
	public function getMessage() {
		return $this->e->getMessage();
	}

	public function getCode() {
		switch($this->e->getCode()) {
			case "42S01":
				return DB_ERROR_ALREADY_EXISTS;
			break;
			default:
				throw new Exception("Unknown Error Code");
			break;
		}
	}

	public function getUserInfo() {
		return $this->e->getMessage();
	}

	public function getDebugInfo() {
		return $this->e->getMessage();
	}
}
