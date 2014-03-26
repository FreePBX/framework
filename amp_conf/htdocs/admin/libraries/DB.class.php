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
	public function __construct() {
		$this->db = FreePBX::create()->Database;
	}

	public function getCol($sql,$col=0,$params=array()) {
		if(!empty($params) && is_array($params)) {
			$res = $this->db->prepare($sql);
			$res->execute($params);
			return $res->fetchColumn($col);
		}
		$res = $this->db->query($sql);
		return $res->fetchColumn($col);
	}

	public function getAll($sql,$params=array(),$fetchmode=DB_FETCHMODE_DEFAULT) {
        $fetch = $this->setFetchMode($fetchmode);
		if(!empty($params) && is_array($params)) {
			$res = $this->db->prepare($sql);
			$res->execute($params);
			return $res->fetchAll($fetch);
		}
		$x = $this->db->sql($sql,'getAll',$fetch);
        return $x;
    }

	public function getRow($sql,$params=array(),$fetchmode=DB_FETCHMODE_DEFAULT) {
		$fetch = $this->setFetchMode($fetchmode);
		if(!empty($params) && is_array($params)) {
			$res = $this->db->prepare($sql);
			$res->execute($params);
			return $res->fetch($fetch);
		}
		$x = $this->db->sql($sql,'getRow',$fetch);
		return $x;
	}

	public function getOne($sql,$params=array()) {
		if(!empty($params) && is_array($params)) {
			$res = $this->db->prepare($sql);
			$res->execute($params);
			$line = $res->fetch(PDO::FETCH_NUM);
			if (isset($line[0])) {
				return $line[0];
			}
			return false;
		}
		$x = $this->db->sql($sql,'getOne');
		return $x;
	}

	public function getAssoc($sql,$force_array = false,$params = array(),$fetchmode = DB_FETCHMODE_ASSOC,$group = false) {
		$fetch = $this->setFetchMode($fetchmode);
		if(!empty($params) && is_array($params)) {
			$res = $this->db->prepare($sql);
			$res->execute($params);
			$result = $res->fetchAll($fetch);
		}
		$result = $this->db->sql($sql,'getAll',$fetch);
		if(empty($result)) {
			return false;
		}
		$final = array();
		foreach($result as $data) {
			$final[$data['keyword']] = $data['data'];
		}
		return $final;
	}

    public function insert_id() {
        return $this->db->lastInsertId();
    }

	public function escapeSimple($str = null) {
		// Using PDO::quote
		return $this->quote($str);
	}

	public function quoteSmart($in) {
		return $this->db->quote($in);
	}

	public function quote($in) {
		return $in;
	}

	public function IsError($value) {
		return false;
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
