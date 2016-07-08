<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * For ease of use, this is a PDO Object. You can call it with standard
 * PDO paramaters, and it will connect as normal.
 *
 * However, if you just want to use it as a random Database thing, then
 * it'll figure out what you want to do and just do it, without you needing
 * to hold its hand.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Database extends \PDO {
	private $dConfig = null; //doctrine config
	private $dsn = null; //pdo dsn
	private $dConn = null; //docterine connection
	private $dVersion = null; //driver version
	/**
	 * Connecting to the Database object
	 * If you pass nothing to this it will assume the default database
	 *
	 * Otherwise you can send it parameters that match PDO parameter settings:
	 * PDO::__construct ( string $dsn [, string $username [, string $password [, array $options ]]] )
	 *
	 * You will then be returned a PDO Database object that you can work with
	 * to manipulate databases outside of FreePBX, a good example of this is with
	 * CDRs where the module has to connect to the external CDR Database
	 */
	public function __construct() {
		$args = func_get_args();

		if (is_object($args[0]) && get_class($args[0]) == "FreePBX") {
			$this->FreePBX = $args[0];
			array_shift($args);
		}

		if (class_exists("FreePBX")) {
			$amp_conf = \FreePBX::$conf;
		} else if (is_array($args[0]) && !empty($args[0])) {
			$amp_conf = $args[0];
			array_shift($args);
		} else {
			throw new \Exception("FreePBX class does not exist, and no amp_conf found.");
		}

		if (isset($args[1]) && !empty($args[0]) && is_string($args[0])) {
			$username = $args[1];
		} else {
			$username = $amp_conf['AMPDBUSER'];
		}

		if (isset($args[2]) && !empty($args[0]) && is_string($args[0])) {
			$password = $args[2];
		} else {
			$password = $amp_conf['AMPDBPASS'];
		}

		//Isset, not empty and is a string that's the only valid DSN we will accept here
		if (isset($args[0]) && !empty($args[0]) && is_string($args[0])) {
			$this->dsn = $args[0];
		} else {
			if(empty($amp_conf['AMPDBSOCK'])) {
				$host = !empty($amp_conf['AMPDBHOST']) ? $amp_conf['AMPDBHOST'] : 'localhost';
				$this->dsn = $amp_conf['AMPDBENGINE'].":host=".$host.";dbname=".$amp_conf['AMPDBNAME'].";charset=utf8";
				$this->dConfig = array(
					'dbname' => $amp_conf['AMPDBNAME'],
					'user' => $username,
					'password' => $password,
					'host' => $host,
					'driver' => $this->doctrineEngineAlias($amp_conf['AMPDBENGINE']),
					'charset' => 'utf8'
				);
			} else {
				$this->dsn = $amp_conf['AMPDBENGINE'].":unix_socket=".$amp_conf['AMPDBSOCK'].";dbname=".$amp_conf['AMPDBNAME'].";charset=utf8";
				$this->dConfig = array(
					'dbname' => $amp_conf['AMPDBNAME'],
					'user' => $username,
					'password' => $password,
					'unix_socket' => $amp_conf['AMPDBSOCK'],
					'driver' => $this->doctrineEngineAlias($amp_conf['AMPDBENGINE']),
					'charset' => 'utf8'
				);
			}
		}

		$options = isset($args[3]) ? $args[3] : array();
		if (version_compare(PHP_VERSION, '5.3.6', '<')) {
			$options[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
		}

		try {
			if (!empty($options)) {
				parent::__construct($this->dsn, $username, $password, $options);
			} else {
				parent::__construct($this->dsn, $username, $password);
			}
		} catch(\Exception $e) {
			die_freepbx($e->getMessage(), $e);
		}
		$this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$this->dVersion = $this->getAttribute(\PDO::ATTR_SERVER_VERSION);
	}

	public function migrate($table) {
		if(!class_exists("FreePBX\Database\Migration",false)) {
			include __DIR__."/Database/Migration.class.php";
		}
		if(empty($this->dConn)) {
			$config = new \Doctrine\DBAL\Configuration();
			$this->dConn = \Doctrine\DBAL\DriverManager::getConnection($this->dConfig, $config);

			//http://wildlyinaccurate.com/doctrine-2-resolving-unknown-database-type-enum-requested/
			$platform = $this->dConn->getDatabasePlatform();
			$platform->registerDoctrineTypeMapping('enum', 'string');
		}

		return new Database\Migration($this->dConn, $table, $this->dConfig['driver'], $this->dVersion);
	}

	private function doctrineEngineAlias($engine) {
		/*
		db2: alias for ibm_db2
		mssql: alias for pdo_sqlsrv
		mysql/mysql2: alias for pdo_mysql
		pgsql/postgres/postgresql: alias for pdo_pgsql
		sqlite/sqlite3: alias for pdo_sqlite
		 */
		$final = $engine;
		switch($engine) {
			case "db2":
				$final = 'ibm_db2';
			break;
			case "mssql":
				$final = 'pdo_sqlsrv';
			break;
			case "mysql":
			case "mysql2":
				$final = 'pdo_mysql';
			break;
			case "pgsql":
			case "postgres":
			case "postgresql":
				$final = 'pdo_pgsql';
			break;
			case "sqlite":
			case "sqlite3":
				$final = 'pdo_sqlite';
			break;
		}
		return $final;
	}

	/**
	 * COMPAT: Queries Database using PDO
	 *
	 * This is a FreePBX Compatibility hook for the global 'sql' function that
	 * previously used PEAR::DB
	 *
	 * @param $sql string SQL String to run
	 * @param $type string Type of query
	 * @param $fetchmode int One of the PDO::FETCH_ methos (see http://www.php.net/manual/en/pdo.constants.php for info)
	 */
	public function sql($sql = null, $type = "query", $fetchmode = \PDO::FETCH_BOTH) {
		if (!$sql)
			throw new \Exception("No SQL Given to Database->sql()");

		switch ($type) {
		case "query":
			// Note that the basic PDO::query doesn't fetch. So no need for $fetchmode
			$res = $this->sql_query($sql);
			break;
		case "getAll":
			// Return the complete result set
			$res = $this->sql_getAll($sql, $fetchmode);
			break;
		case "getOne":
			// Return the first item of the first row
			$res = $this->sql_getOne($sql);
			break;
		case "getRow":
			// Return the first the first row
			$res = $this->sql_getRow($sql, $fetchmode);
			break;
		default:
			throw new \Exception("Unknown SQL query type of $type");
		}

		return $res;
	}

	/**
	 * Returns a PDOStatement object
	 *
	 * This is for compatibility with older code. I expect this will never be used,
	 * as PDO has much smarter ways of doing things.
	 *
	 * @param $sql string SQL String
	 * @return object PDOStatement object
	 */
	private function sql_query($sql) {
		return $this->query($sql);
	}

	/**
	 * Performs a SQL Query, and returns all results
	 *
	 * This should always return the exact same result as PEAR's $db->getAll query.
	 *
	 * @param $sql string SQL String
	 * @param $fetchmode int PDO::FETCH_* Method
	 * @return array|object Result of the SQL Query
	 */
	private function sql_getAll($sql, $fetchmode) {
		$res = $this->query($sql);
		return $res->fetchAll($fetchmode);
	}

	private function sql_getRow($sql, $fetchmode) {
		$res = $this->query($sql);
		return $res->fetch($fetchmode);
	}

	/**
	 * Perform a SQL Query, and return the first item of the first row.
	 *
	 * @param $sql string SQL String
	 * @return string
	 */

	private function sql_getOne($sql) {
		$res = $this->query($sql);
		$line = $res->fetchColumn();
		return !empty($line) ? $line : false;
	}

	/**
	 * COMPAT: getMessage - returns an error message
	 *
	 * This will throw an exception, as it shouldn't be used and is a holdover from the PEAR $db object.
	 */
	public function getMessage() {
		// There is a PDO call for this.. I think.
		throw new \Exception("getMessage was called on the DB Object");
	}

	/**
	 * COMPAT: isError - checks if the last query was successfull.
	 *
	 * This will throw an exception, as it shouldn't be used and is a holdover from the PEAR $db object.
	 */
	public function isError($result) {
		// Should check that the $result is an object, and it's a PDOStatement object, I think.
		throw new \Exception("isError was called on the DB Object");
	}

	/**
	 * COMPAT: escapeSimple - Wraps the supplied string in quotes.
	 *
	 * This wraps the requested string in quotes, and returns it. It's a bad idea. You should be using
	 * prepared queries for this. At some point this will be deprecated and removed.
	 */
	public function escapeSimple($str = null) {
		// Using PDO::quote
		return $this->quote($str);
	}

	/**
	 * HELPER: getOne - Returns first result
	 *
	 * Returns the first result of the first row of the query. Handy shortcut when you're doing
	 * a query that only needs one item returned.
	 */
	public function getOne($sql = null) {
		if ($sql === null)
			throw new \Exception("No SQL given to getOne");

		return $this->sql_getOne($sql);
	}
}
