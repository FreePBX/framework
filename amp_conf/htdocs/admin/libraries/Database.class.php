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
	}
}
