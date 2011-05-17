<?php

define("NOTIFICATION_TYPE_CRITICAL", 100);
define("NOTIFICATION_TYPE_SECURITY", 200);
define("NOTIFICATION_TYPE_UPDATE",  300);
define("NOTIFICATION_TYPE_ERROR",    400);
define("NOTIFICATION_TYPE_WARNING" , 500);
define("NOTIFICATION_TYPE_NOTICE",   600);

class notifications{

	var $not_loaded = true;
	var $notification_table = array();
	var $_db;
		
	function &create(&$db) {
		static $obj;
		if (!isset($obj)) {
			$obj = new notifications($db);
		}
		return $obj;
	}

	function notifications(&$db) {
		$this->_db =& $db;
	}

  function exists($module, $id) {
    $count = sql("SELECT count(*) FROM notifications WHERE `module` = '$module' AND `id` = '$id'", 'getOne');
    return ($count);
  }

	function add_critical($module, $id, $display_text, $extended_text="", $link="", $reset=true, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_CRITICAL, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
    $this->_freepbx_log(FPBX_LOG_CRITICAL, $module, $id, $display_text);
	}
	function add_security($module, $id, $display_text, $extended_text="", $link="", $reset=true, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_SECURITY, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
    $this->_freepbx_log(FPBX_LOG_SECURITY, $module, $id, $display_text);
	}
	function add_update($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_UPDATE, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
    $this->_freepbx_log(FPBX_LOG_UPDATE, $module, $id, $display_text);
	}
	function add_error($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_ERROR, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
    $this->_freepbx_log(FPBX_LOG_ERROR, $module, $id, $display_text);
	}
	function add_warning($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_WARNING, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
    $this->_freepbx_log(FPBX_LOG_WARNING, $module, $id, $display_text);
	}
	function add_notice($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=true) {
		$this->_add_type(NOTIFICATION_TYPE_NOTICE, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
    $this->_freepbx_log(FPBX_LOG_NOTICE, $module, $id, $display_text);
	}


	function list_critical($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_CRITICAL, $show_reset);
	}
	function list_security($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_SECURITY, $show_reset);
	}
	function list_update($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_UPDATE, $show_reset);
	}
	function list_error($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_ERROR, $show_reset);
	}
	function list_warning($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_WARNING, $show_reset);
	}
	function list_notice($show_reset=false) {
		return $this->_list(NOTIFICATION_TYPE_NOTICE, $show_reset);
	}
	function list_all($show_reset=false) {
		return $this->_list("", $show_reset);
	}


	function reset($module, $id) {
		$module        = q($module);
		$id            = q($id);

		$sql = "UPDATE notifications SET reset = 1 WHERE module = $module AND id = $id";
		sql($sql);
	}

	function delete($module, $id) {
		$module        = q($module);
		$id            = q($id);

		$sql = "DELETE FROM notifications WHERE module = $module AND id = $id";
		sql($sql);
	}

	function safe_delete($module, $id) {
		$module        = q($module);
		$id            = q($id);

		$sql = "DELETE FROM notifications WHERE module = $module AND id = $id AND candelete = 1";
		sql($sql);
	}

	/* Internal functions
	 */

	function _add_type($level, $module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		if ($this->not_loaded) {
			$this->notification_table = $this->_list("",true);
			$this->not_loaded = false;
		}

		$existing_row = false;
		foreach ($this->notification_table as $row) {
			if ($row['module'] == $module && $row['id'] == $id ) {
				$existing_row = $row;
				break;
			}
		}
		// Found an existing row - check if anything changed or if we are suppose to reset it
		//
		$candelete = $candelete ? 1 : 0;
		if ($existing_row) {

			if (($reset && $existing_row['reset'] == 1) || $existing_row['level'] != $level || $existing_row['display_text'] != $display_text || $existing_row['extended_text'] != $extended_text || $existing_row['link'] != $link || $existing_row['candelete'] == $candelete) {

				// If $reset is set to the special case of PASSIVE then the updates will not change it's value in an update
				//
				$reset_value = ($reset == 'PASSIVE') ? $existing_row['reset'] : 0;

				$module        = q($module);
				$id            = q($id);
				$level         = q($level);
				$display_text  = q($display_text);
				$extended_text = q($extended_text);
				$link          = q($link);
				$now = time();
				$sql = "UPDATE notifications SET
					level = $level,
					display_text = $display_text,
					extended_text = $extended_text,
					link = $link,
					reset = $reset_value,
					candelete = $candelete,
					timestamp = $now
					WHERE module = $module AND id = $id
				";
				sql($sql);

				// TODO: I should really just add this to the internal cache, but really
				//       how often does this get called that if is a big deal.
				$this->not_loaded = true;
			}
		} else {
			// No existing row so insert this new one
			//
			$now           = time();
			$module        = q($module);
			$id            = q($id);
			$level         = q($level);
			$display_text  = q($display_text);
			$extended_text = q($extended_text);
			$link          = q($link);
			$sql = "INSERT INTO notifications 
				(module, id, level, display_text, extended_text, link, reset, candelete, timestamp)
				VALUES 
				($module, $id, $level, $display_text, $extended_text, $link, 0, $candelete, $now)
			";
			sql($sql);

			// TODO: I should really just add this to the internal cache, but really
			//       how often does this get called that if is a big deal.
			$this->not_loaded = true;
		}
	}

	function _list($level, $show_reset=false) {

		$level = q($level);
		$where = array();

		if (!$show_reset) {
			$where[] = "reset = 0";
		}

		switch ($level) {
			case NOTIFICATION_TYPE_CRITICAL:
			case NOTIFICATION_TYPE_SECURITY:
			case NOTIFICATION_TYPE_UPDATE:
			case NOTIFICATION_TYPE_ERROR:
			case NOTIFICATION_TYPE_WARNING:
			case NOTIFICATION_TYPE_NOTICE:
				$where[] = "level = $level ";
				break;
			default:
		}
		$sql = "SELECT * FROM notifications ";
		if (count($where)) {
			$sql .= " WHERE ".implode(" AND ", $where);
		}
		$sql .= " ORDER BY level, module";

		$list = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
		return $list;
	}

  function _freepbx_log($level, $module, $id, $display_text) {
    global $amp_conf;
    if ($amp_conf['LOG_NOTIFICATIONS']) {
      freepbx_log($level,"[NOTIFICATION]-[$module]-[$id] - $display_text");
    }
  }
	/* Returns the number of active notifications
	 */
	function get_num_active() {
		$sql = "SELECT COUNT(id) FROM notifications WHERE reset = 0";
		return sql($sql,'getOne');
	}
}

?>
