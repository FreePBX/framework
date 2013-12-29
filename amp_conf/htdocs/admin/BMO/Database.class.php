<?php
// vim: set ai ts=4 sw=4 ft=php:

/*
 * This is the FreePBX Database Object
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

		// We don't want bootstrap to do ANYTHING apart from
		// load the $amp_conf variables.
		$bootstrap_settings['returnimmediately'] = true;
		include '/etc/freepbx.conf';

		if (isset($args[0])) {
			$dsn = $args[0];
		} else {
			$dsn = "mysql:host=localhost;dbname=".$amp_conf['AMPDBNAME'];
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
	 * Queries Database using PDO
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
		default:
			throw new Exception("Unknown SQL query type of $type");
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
}

