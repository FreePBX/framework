<?php
// vim: set ai ts=4 sw=4 ft=php:

/**
 * This is the FreePBX Big Module Object.
 *
 * Copyright (C) 2013 Schmooze Com, INC
 * Copyright (C) 2013 Rob Thomas <rob.thomas@schmoozecom.com>
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
 * @package   FreePBX BMO
 * @author    Rob Thomas <rob.thomas@schmoozecom.com>
 * @license   AGPL v3
 */

/*
 * For ease of use, this is a PDO Object. You can call it with standard
 * PDO paramaters, and it will connect as normal.
 *
 * However, if you just want to use it as a random Database thing, then
 * it'll figure out what you want to do and just do it, without you needing
 * to hold its hand.
 */
class Database extends PDO {

	public function __construct() {
		$args = func_num_args();

		if (is_object($args[0]) && get_class($args[0]) == "FreePBX") {
			$this->FreePBX = $args[0];
			array_shift($args);
		}

		$amp_conf = FreePBX::$conf;

		if (isset($args[0])) {
			$dsn = $args[0];
		} else {
			$dsn = "mysql:host=".$amp_conf['AMBDBHOST'].";dbname=".$amp_conf['AMPDBNAME'];
		}

		if (isset($args[1])) {
			$username = $args[1];
		} else {
			$username = $amp_conf['AMPDBUSER'];
		}

		if (isset($args[2])) {
			$password = $args[3];
		} else {
			$password = $amp_conf['AMPDBPASS'];
		}

		if (isset($args[3])) {
			parent::__construct($dsn, $username, $password, $args[3]);
		} else {
			parent::__construct($dsn, $username, $password);
		}
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

	public function sql($sql = null, $type = "query", $fetchmode = PDO::FETCH_BOTH) {
		if (!$sql)
			throw new Exception("No SQL Given to Database->sql()");

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
			throw new Exception("Unknown SQL query type of $type");
		}

		return $res;
	}

    private function sql_getRow($sql,$fetchmode) {
        $res = $this->query($sql);
        return $res->fetch($fetchmode);
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
		throw new Exception("getMessage was called on the DB Object");
	}

	/**
	 * COMPAT: isError - checks if the last query was successfull.
	 *
	 * This will throw an exception, as it shouldn't be used and is a holdover from the PEAR $db object.
	 */
	public function isError($result) {
		// Should check that the $result is an object, and it's a PDOStatement object, I think.
		throw new Exception("isError was called on the DB Object");
	}

	/**
	 * COMPAT: escapeSimple - Wraps the suppied string in quotes.
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
	 * Returns the first result of the first row of the query. Handy shortcot when you're doing
	 * a query that only needs one item returned.
	 */
	public function getOne($sql = null) {
		if ($sql === null)
			throw new Exception("No SQL given to getOne");

		return $this->sql_getOne($sql);
	}
}
