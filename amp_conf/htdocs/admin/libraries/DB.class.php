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

	public static function connect($dsn=null) {
		$pdsn = DB::parseDSN($dsn);
		if(!empty($pdsn)) {
			$db_port = empty($pdsn['port']) ? '' :  ';port=' . $pdsn['port'];
			return new DB(new \FreePBX\Database($pdsn['dbsyntax'].':host='.$pdsn['hostspec'].$db_port.';dbname='.$pdsn['database'],$pdsn['username'],$pdsn['password']));
		}
		throw new \Exception("Could not understand DSN for connect");
	}

	/**
	 * Parse a data source name
	 *
	 * Additional keys can be added by appending a URI query string to the
	 * end of the DSN.
	 *
	 * The format of the supplied DSN is in its fullest form:
	 * <code>
	 *  phptype(dbsyntax)://username:password@protocol+hostspec/database?option=8&another=true
	 * </code>
	 *
	 * Most variations are allowed:
	 * <code>
	 *  phptype://username:password@protocol+hostspec:110//usr/db_file.db?mode=0644
	 *  phptype://username:password@hostspec/database_name
	 *  phptype://username:password@hostspec
	 *  phptype://username@hostspec
	 *  phptype://hostspec/database
	 *  phptype://hostspec
	 *  phptype(dbsyntax)
	 *  phptype
	 * </code>
	 *
	 * @param string $dsn Data Source Name to be parsed
	 *
	 * @return array an associative array with the following keys:
	 *  + phptype:  Database backend used in PHP (mysql, odbc etc.)
	 *  + dbsyntax: Database used with regards to SQL syntax etc.
	 *  + protocol: Communication protocol to use (tcp, unix etc.)
	 *  + hostspec: Host specification (hostname[:port])
	 *  + database: Database to use on the DBMS server
	 *  + username: User name for login
	 *  + password: Password for login
	 */
	public static function parseDSN($dsn) {
		$parsed = array(
			'phptype'  => false,
			'dbsyntax' => false,
			'username' => false,
			'password' => false,
			'protocol' => false,
			'hostspec' => false,
			'port'     => false,
			'socket'   => false,
			'database' => false,
		);

		if (is_array($dsn)) {
			$dsn = array_merge($parsed, $dsn);
			if (!$dsn['dbsyntax']) {
				$dsn['dbsyntax'] = $dsn['phptype'];
			}
			return $dsn;
		}

		// Find phptype and dbsyntax
		if (($pos = strpos($dsn, '://')) !== false) {
			$str = substr($dsn, 0, $pos);
			$dsn = substr($dsn, $pos + 3);
		} else {
			$str = $dsn;
			$dsn = null;
		}

		// Get phptype and dbsyntax
		// $str => phptype(dbsyntax)
		if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
			$parsed['phptype']  = $arr[1];
			$parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
		} else {
			$parsed['phptype']  = $str;
			$parsed['dbsyntax'] = $str;
		}

		if (!count($dsn)) {
			return $parsed;
		}

		// Get (if found): username and password
		// $dsn => username:password@protocol+hostspec/database
		if (($at = strrpos($dsn,'@')) !== false) {
			$str = substr($dsn, 0, $at);
			$dsn = substr($dsn, $at + 1);
			if (($pos = strpos($str, ':')) !== false) {
				$parsed['username'] = rawurldecode(substr($str, 0, $pos));
				$parsed['password'] = rawurldecode(substr($str, $pos + 1));
			} else {
				$parsed['username'] = rawurldecode($str);
			}
		}

		// Find protocol and hostspec

		if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
			// $dsn => proto(proto_opts)/database
			$proto       = $match[1];
			$proto_opts  = $match[2] ? $match[2] : false;
			$dsn         = $match[3];

		} else {
			// $dsn => protocol+hostspec/database (old format)
			if (strpos($dsn, '+') !== false) {
				list($proto, $dsn) = explode('+', $dsn, 2);
			}
			if (strpos($dsn, '/') !== false) {
				list($proto_opts, $dsn) = explode('/', $dsn, 2);
			} else {
				$proto_opts = $dsn;
				$dsn = null;
			}
		}

		// process the different protocol options
		$parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
		$proto_opts = rawurldecode($proto_opts);
		if (strpos($proto_opts, ':') !== false) {
			list($proto_opts, $parsed['port']) = explode(':', $proto_opts);
		}
		if ($parsed['protocol'] == 'tcp') {
			$parsed['hostspec'] = $proto_opts;
		} elseif ($parsed['protocol'] == 'unix') {
			$parsed['socket'] = $proto_opts;
		}

		// Get dabase if any
		// $dsn => database
		if ($dsn) {
			if (($pos = strpos($dsn, '?')) === false) {
				// /database
				$parsed['database'] = rawurldecode($dsn);
			} else {
				// /database?param1=value1&param2=value2
				$parsed['database'] = rawurldecode(substr($dsn, 0, $pos));
				$dsn = substr($dsn, $pos + 1);
				if (strpos($dsn, '&') !== false) {
					$opts = explode('&', $dsn);
				} else { // database?param1=value1
					$opts = array($dsn);
				}
				foreach ($opts as $opt) {
					list($key, $value) = explode('=', $opt);
					if (!isset($parsed[$key])) {
						// don't allow params overwrite
						$parsed[$key] = rawurldecode($value);
					}
				}
			}
		}
		return $parsed;
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
				if(!$force_array) {
					foreach($result as $data) {
						if(count($data) >= 2) {
							$k = array_shift($data);
							$v = array_values($data);
							if(!$group) {
								$final[$k] = $v[0];
							} else {
								$final = $v;
							}
						} else {
							return false;
						}
					}
				} elseif($force_array && count($result[0]) == 2) {
					foreach($result as $data) {
						$k = array_shift($data);
						$v = array_values($data);
						if(!$group) {
							$final[$k] = $v;
						} else {
							foreach($v as $v1) {
								$final[] = $v1;
							}
						}
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
				if(!is_array($params)) {
					$params = array($params);
				}
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
		/*TODO Implement for Rob
		switch($mixed) {
			case DB_FETCHMODE_DEFAULT:
			case DB_FETCHMODE_OBJECT:
			case DB_FETCHMODE_ASSOC:
			case DB_FETCHMODE_ORDERED:
				return true;
			default:
				throw new Exception("Invalid Fetch Mode!");
			break;
		}
		*/
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
