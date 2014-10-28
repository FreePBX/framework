<?php
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
class DB {
	private $db = null;
	private static $error = null;
	public function __construct($dbh=null) {
		$this->db = !empty($dbh) ? $dbh : FreePBX::create()->Database;
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function getCol($sql,$col=0,$params=array()) {
		$this->error = null;
		try {
			if(!empty($params) && is_array($params)) {
				$res = $this->db->prepare($sql);
				$res->execute($params);
				return $res->fetchColumn($col);
			}
			$res = $this->db->query($sql);
			if($res === false) {
				return false;
			}
			return $res->fetchColumn($col);
		} catch (Exception $e) {
			return $e;
		}
	}

	public function getAll($sql,$params=array(),$fetchmode=DB_FETCHMODE_DEFAULT) {
		$this->error = null;
		try {
			$fetch = $this->setFetchMode($fetchmode);
			if(!empty($params) && is_array($params)) {
				$res = $this->db->prepare($sql);
				$res->execute($params);
				return $res->fetchAll($fetch);
			}
			$res = $this->db->query($sql);
			if($res === false) {
				return false;
			}
			return $res->fetchAll($fetch);
		} catch (Exception $e) {
			return $e;
		}
	}

	public function getRow($sql,$params=array(),$fetchmode=DB_FETCHMODE_DEFAULT) {
		$this->error = null;
		try {
			$fetch = $this->setFetchMode($fetchmode);
			if(!empty($params) && is_array($params)) {
				$res = $this->db->prepare($sql);
				$res->execute($params);
				return $res->fetch($fetch);
			}
			$res = $this->db->query($sql);
			if($res === false) {
				return false;
			}
			return $res->fetch($fetch);
		} catch (Exception $e) {
			return $e;
		}
	}

	public function getOne($sql,$params=array()) {
		$this->error = null;
		try {
			if(!empty($params) && is_array($params)) {
				$res = $this->db->prepare($sql);
				$res->execute($params);
				$line = $res->fetch(PDO::FETCH_NUM);
				if (isset($line[0])) {
					return $line[0];
				}
				return false;
			}
			$res = $this->db->query($sql);
			if($res === false) {
				return false;
			}
			$line = $res->fetchColumn();
			return !empty($line) ? $line : false;
		} catch (Exception $e) {
			return $e;
		}
	}

	public function getAssoc($sql,$force_array = false,$params = array(),$fetchmode = DB_FETCHMODE_ASSOC,$group = false) {
		$this->error = null;
		try {
			$fetch = $this->setFetchMode($fetchmode);
			if(!empty($params) && is_array($params)) {
				$res = $this->db->prepare($sql);
				$res->execute($params);
				$result = $res->fetchAll($fetch);
			}
			$res = $this->db->query($sql);
			if($res === false) {
				return false;
			}
			$result = $res->fetchAll($fetch);
			if($result === false) {
				return false;
			}
			$final = array();
			foreach($result as $data) {
				$final[$data['keyword']] = $data['data'];
			}
			return $final;
		} catch (Exception $e) {
			return $e;
		}
	}

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

	public static function IsError($e) {
		if(is_object($e) && get_class($e) == "PDOException") {
			return $e;
		}
		return false;
	}

	public function prepare($query) {
		return $this->db->prepare($query);
	}

	public function execute($stmt, $data = array()) {
		try {
			return $stmt->execute($data);
		} catch(Exception $e) {
			return $e;
		}
	}

	public function executeMultiple($stmt, $data = array()) {
		try {
			foreach($data as &$row) {
				$stmt->execute($row);
			}
		} catch(Exception $e) {
			return $e;
		}
		return true;
	}

	public function query($sql,$params=array()) {
		$this->error = null;
		if(empty($params)) {
			try {
				$this->db->query($sql);
			} catch(\Exception $e) {
				return $e;
			}
		} else {
			try {
				$sth = $this->db->prepare($sql);
				$sth->execute($params);
			} catch(\Exception $e) {
				return $e;
			}
		}
	}

	private function setFetchMode($PearDBFetchMode=DB_FETCHMODE_DEFAULT) {
		switch($PearDBFetchMode) {
			case DB_FETCHMODE_ASSOC:
				$fetch = PDO::FETCH_ASSOC;
			break;
			case DB_FETCHMODE_DEFAULT:
				$fetch = PDO::FETCH_BOTH;
			break;
			default:
				throw new Exception("Unknown SQL fetchmode of $fetchmode");
			break;
		}
		return $fetch;
	}
}
