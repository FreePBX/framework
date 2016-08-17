<?php

class modulelist{
	public $_loaded = false;
	public $module_array = array();
	private $_db;

	private static $obj = false;

	public static function create($db) {
		if (!self::$obj) {
			self::$obj = new modulelist($db);
		}
		return self::$obj;
	}

	public function __construct(&$db) {
		$this->_db =& $db;
		$module_serialized = sql("SELECT `data` FROM `module_xml` WHERE `id` = 'mod_serialized'","getOne");
		if (isset($module_serialized) && $module_serialized) {
			$this->module_array = (unserialize($module_serialized));
			$this->_loaded = true;
		}
	}
	public function is_loaded() {
		return $this->_loaded;
	}
	public function initialize(&$module_list) {
		$this->module_array = $module_list;
		// strip out extraneous fields (help especially when printing out debugs
		//
		foreach ($this->module_array as $mod_key => $mod) {
			if (isset($mod['changelog'])) {
				//unset($this->module_array[$mod_key]['changelog']);
			}
			if (isset($mod['attention'])) {
				unset($this->module_array[$mod_key]['attention']);
			}
			if (!isset($mod['license'])) {
				$this->module_array[$mod_key]['license'] = '';
			}
			if (isset($mod['location'])) {
				unset($this->module_array[$mod_key]['location']);
			}
			if (isset($mod['md5sum'])) {
				unset($this->module_array[$mod_key]['md5sum']);
			}
			if (isset($mod['sha1sum'])) {
				unset($this->module_array[$mod_key]['sha1sum']);
			}
			if (!isset($mod['track'])) {
				$this->module_array[$mod_key]['track'] = 'stable';
			}
		}
		$module_serialized = $this->_db->escapeSimple(serialize($this->module_array));
		sql("REPLACE INTO `module_xml` (`id`, `time`, `data`) VALUES ('mod_serialized', '".time()."','".$module_serialized."')");
		$this->_loaded = true;
	}
	public function invalidate() {
		unset($this->module_array);
		sql("DELETE FROM `module_xml` WHERE `id` = 'mod_serialized'");
		$this->_loaded = false;
	}
}
