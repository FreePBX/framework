<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * Notification Class
 *
 * Adds or Remove Notifications to and from the FreePBX Dashboard/Email
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */

namespace FreePBX;
define("NOTIFICATION_TYPE_CRITICAL", 100);
define("NOTIFICATION_TYPE_SECURITY", 200);
define("NOTIFICATION_TYPE_SIGNATURE_UNSIGNED", 250);
define("NOTIFICATION_TYPE_UPDATE",   300);
define("NOTIFICATION_TYPE_ERROR",    400);
define("NOTIFICATION_TYPE_WARNING" , 500);
define("NOTIFICATION_TYPE_NOTICE",   600);

class Notifications {

	// Legacy pre-BMO Hooks
	public static function create() {
		return \FreePBX::create()->Notifications;
	}

	private $not_loaded = true;
	private $notification_table = array();

	/**
	 * Check to see if Notification Already exists
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 * @return int Returns the number of notifications per module & id
	 */
	function exists($module, $id) {
		$count = sql("SELECT count(*) FROM notifications WHERE `module` = '$module' AND `id` = '$id'", 'getOne');
		return ($count);
	}

	/**
	 * Add a Critical Notification Message
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 * @param string $display_text The text that will be displayed as the subject/header of the message
	 * @param string $extended_text The extended text of the notification when it is expanded
	 * @param string $link The link that is set to the notification
	 * @param bool $reset Reset notification on module update
	 * @param bool $candelete If the notification can be deleted by the user on the notifications display page
	 * @return int Returns the number of notifications per module & id
	 */
	function add_critical($module, $id, $display_text, $extended_text="", $link="", $reset=true, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_CRITICAL, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
		$this->_freepbx_log(FPBX_LOG_CRITICAL, $module, $id, $display_text, $extended_text);
	}
	/**
	 * Add a Security Notification Message
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 * @param string $display_text The text that will be displayed as the subject/header of the message
	 * @param string $extended_text The extended text of the notification when it is expanded
	 * @param string $link The link that is set to the notification
	 * @param bool $reset Reset notification on module update
	 * @param bool $candelete If the notification can be deleted by the user on the notifications display page
	 * @return int Returns the number of notifications per module & id
	 */
	function add_security($module, $id, $display_text, $extended_text="", $link="", $reset=true, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_SECURITY, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
		$this->_freepbx_log(FPBX_LOG_SECURITY, $module, $id, $display_text, $extended_text);
	}
	/**
	* Add a Unsigned Modules Notification Message
	*
	* @param string $module Raw name of the module requesting
	* @param string $id ID of the notification
	* @param string $display_text The text that will be displayed as the subject/header of the message
	* @param string $extended_text The extended text of the notification when it is expanded
	* @param string $link The link that is set to the notification
	* @param bool $reset Reset notification on module update
	* @param bool $candelete If the notification can be deleted by the user on the notifications display page
	* @return int Returns the number of notifications per module & id
	*/
	function add_signature_unsigned($module, $id, $display_text, $extended_text="", $link="", $reset=true, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_SIGNATURE_UNSIGNED, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
		$this->_freepbx_log(FPBX_LOG_SIGNATURE_UNSIGNED, $module, $id, $display_text, $extended_text);
	}
	/**
	 * Add an Update Notification Message
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 * @param string $display_text The text that will be displayed as the subject/header of the message
	 * @param string $extended_text The extended text of the notification when it is expanded
	 * @param string $link The link that is set to the notification
	 * @param bool $reset Reset notification on module update
	 * @param bool $candelete If the notification can be deleted by the user on the notifications display page
	 * @return int Returns the number of notifications per module & id
	 */
	function add_update($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_UPDATE, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
		$this->_freepbx_log(FPBX_LOG_UPDATE, $module, $id, $display_text, $extended_text);
	}
	/**
	 * Add an Error Notification Message
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 * @param string $display_text The text that will be displayed as the subject/header of the message
	 * @param string $extended_text The extended text of the notification when it is expanded
	 * @param string $link The link that is set to the notification
	 * @param bool $reset Reset notification on module update
	 * @param bool $candelete If the notification can be deleted by the user on the notifications display page
	 * @return int Returns the number of notifications per module & id
	 */
	function add_error($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_ERROR, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
		$this->_freepbx_log(FPBX_LOG_ERROR, $module, $id, $display_text, $extended_text);
	}
	/**
	 * Add a Warning Notification Message
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 * @param string $display_text The text that will be displayed as the subject/header of the message
	 * @param string $extended_text The extended text of the notification when it is expanded
	 * @param string $link The link that is set to the notification
	 * @param bool $reset Reset notification on module update
	 * @param bool $candelete If the notification can be deleted by the user on the notifications display page
	 * @return int Returns the number of notifications per module & id
	 */
	function add_warning($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		$this->_add_type(NOTIFICATION_TYPE_WARNING, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
		$this->_freepbx_log(FPBX_LOG_WARNING, $module, $id, $display_text, $extended_text);
	}
	/**
	 * Add a Notice Notification Message
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 * @param string $display_text The text that will be displayed as the subject/header of the message
	 * @param string $extended_text The extended text of the notification when it is expanded
	 * @param string $link The link that is set to the notification
	 * @param bool $reset Reset notification on module update
	 * @param bool $candelete If the notification can be deleted by the user on the notifications display page
	 * @return int Returns the number of notifications per module & id
	 */
	function add_notice($module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=true) {
		$this->_add_type(NOTIFICATION_TYPE_NOTICE, $module, $id, $display_text, $extended_text, $link, $reset, $candelete);
		$this->_freepbx_log(FPBX_LOG_NOTICE, $module, $id, $display_text, $extended_text);
	}

	/**
	 * List all Critical Messages
	 *
	 * @param bool $show_reset Show resettable messages
	 * @param bool $allow_filtering Allow us to filter results
	 * @return array Returns the list of Messages
	 */
	function list_critical($show_reset=false, $allow_filtering=true) {
		return $this->_list(NOTIFICATION_TYPE_CRITICAL, $show_reset, $allow_filtering);
	}
	/**
	* List all Unsigned Module Notification Messages
	*
	* @param bool $show_reset Show resettable messages
	* @param bool $allow_filtering Allow us to filter results
	* @return array Returns the list of Messages
	*/
	function list_signature_unsigned($show_reset=false, $allow_filtering=true) {
		return $this->_list(NOTIFICATION_TYPE_SIGNATURE_UNSIGNED, $show_reset, $allow_filtering);
	}
	/**
	 * List all Security Messages
	 *
	 * @param bool $show_reset Show resettable messages
	* @param bool $allow_filtering Allow us to filter results
	 * @return array Returns the list of Messages
	 */
	function list_security($show_reset=false, $allow_filtering=true) {
		return $this->_list(NOTIFICATION_TYPE_SECURITY, $show_reset, $allow_filtering);
	}
	/**
	 * List all Update Messages
	 *
	 * @param bool $show_reset Show resettable messages
	 * @param bool $allow_filtering Allow us to filter results
	 * @return array Returns the list of Messages
	 */
	function list_update($show_reset=false, $allow_filtering=true) {
		return $this->_list(NOTIFICATION_TYPE_UPDATE, $show_reset, $allow_filtering);
	}
	/**
	 * List all Error Messages
	 *
	 * @param bool $show_reset Show resettable messages
	 * @param bool $allow_filtering Allow us to filter results
	 * @return array Returns the list of Messages
	 */
	function list_error($show_reset=false, $allow_filtering=true) {
		return $this->_list(NOTIFICATION_TYPE_ERROR, $show_reset, $allow_filtering);
	}
	/**
	 * List all Warning Messages
	 *
	 * @param bool $show_reset Show resettable messages
	 * @param bool $allow_filtering Allow us to filter results
	 * @return array Returns the list of Messages
	 */
	function list_warning($show_reset=false, $allow_filtering=true) {
		return $this->_list(NOTIFICATION_TYPE_WARNING, $show_reset, $allow_filtering);
	}
	/**
	 * List all Notice Messages
	 *
	 * @param bool $show_reset Show resettable messages
	 * @param bool $allow_filtering Allow us to filter results
	 * @return array Returns the list of Messages
	 */
	function list_notice($show_reset=false, $allow_filtering=true) {
		return $this->_list(NOTIFICATION_TYPE_NOTICE, $show_reset, $allow_filtering);
	}
	/**
	 * List all Messages
	 *
	 * @param bool $show_reset Show resettable messages
	 * @param bool $allow_filtering Allow us to filter results
	 * @return array Returns the list of Messages
	 */
	function list_all($show_reset=false, $allow_filtering=true) {
		return $this->_list("", $show_reset, $allow_filtering);
	}


	/**
	 * Reset the status (hidden/shown) notifications of module & id
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 */
	function reset($module, $id) {
		$module        = q($module);
		$id            = q($id);

		$sql = "UPDATE notifications SET reset = 1 WHERE module = $module AND id = $id";
		sql($sql);
	}
	/**
	 * Forcefully Delete notifications of all specified level
	 *
	 * @param NOTIFICAION LEVEL or blank for ALL levels
	 */
	function delete_level($level="") {

		$sql = "DELETE FROM notifications";
		if ($level == '') {
			$level        = q($level);
			$sql .= ' ' . "WHERE level = $level";
		}
		sql($sql);
	}

	/**
	 * Forcefully Delete notifications of module & id
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 */
	function delete($module, $id) {
		$module        = q($module);
		$id            = q($id);

		$sql = "DELETE FROM notifications WHERE module = $module AND id = $id";
		sql($sql);
	}

	/**
	 * Delete notifications of module & id if it is allowed by `candelete`
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 */
	function safe_delete($module, $id) {
		$module        = q($module);
		$id            = q($id);

		$sql = "DELETE FROM notifications WHERE module = $module AND id = $id AND candelete = 1";
		sql($sql);
	}

	/**
	 * Ignore all future notifications for this type and delete
	 * if there are currently any
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 */
	function ignore_forever($module, $id) {

		$freepbx_conf =& \freepbx_conf::create();
		$setting = "NOTIFICATION_IGNORE_{$module}_{$id}";

		if (!$freepbx_conf->conf_setting_exists($setting)) {
			$set['value'] = true;
			$set['defaultval'] =& $set['value'];
			$set['options'] = '';
			$set['readonly'] = 1;
			$set['hidden'] = 1;
			$set['level'] = 10;
			$set['module'] = '';
			$set['category'] = 'Internal Use';
			$set['emptyok'] = 0;
			$set['name'] = "Ignore Notifications $module-$id";
			$set['description'] = "Always ignore notifications for $module-$id";
			$set['type'] = CONF_TYPE_BOOL;
			$freepbx_conf->define_conf_setting($setting,$set,true);
		} else {
			$freepbx_conf->set_conf_values(array($setting => true), true, true);
		}
		$this->delete($module, $id);
		return true;
	}

	/**
	 * Start paying attention to this notification type again
	 *
	 * Undoes the effect of method ignore_forever
	 *
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 */
	function undo_ignore_forever($module, $id) {

		$freepbx_conf =& \freepbx_conf::create();
		$setting = "NOTIFICATION_IGNORE_{$module}_{$id}";

		$freepbx_conf->remove_conf_setting($setting);
	}


	/* Internal functions
	 */

	/**
	 * Add a Notification Message
	 *
	 * @param const $level Notification Level
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 * @param string $display_text The text that will be displayed as the subject/header of the message
	 * @param string $extended_text The extended text of the notification when it is expanded
	 * @param string $link The link that is set to the notification
	 * @param bool $reset Reset notification on module update
	 * @param bool $candelete If the notification can be deleted by the user on the notifications display page
	 * @return int Returns the number of notifications per module & id
	 * @ignore
	 */
	function _add_type($level, $module, $id, $display_text, $extended_text="", $link="", $reset=false, $candelete=false) {
		global $amp_conf;
		if (!empty($amp_conf["NOTIFICATION_IGNORE_{$module}_{$id}"])) {
			return null;
		}
		if(!class_exists('modgettext',false)) {
			include dirname(__DIR__)."/modgettext.class.php";
		}
		\modgettext::push_textdomain(strtolower($module));

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
		\modgettext::pop_textdomain();
	}

	/**
	 * List Messages by Level
	 *
	 * @param const $level Notification Level to show (can be blank for all)
	 * @param bool $show_reset Show resettable messages
	 * @param bool $allow_filtering Filtering should only happen on areas were we display info
	 * @return array Returns the list of Messages
	 * @ignore
	 */
	function _list($level, $show_reset=false, $allow_filtering=false) {

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
			case NOTIFICATION_TYPE_SIGNATURE_UNSIGNED:
				$level = q($level);
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

		if ($allow_filtering) {
			//Use process hooks to allow us to filter out the data
			$filterHooks = \FreePBX::Hooks()->processHooks($list);
			$filter = $this->filterProcessHooks($filterHooks);

			if (!empty($filterHooks)) {
				return $filter;
			}
		}

		return $list;
	}

	/**
	 * FreePBX Logging
	 *
	 * @param const $level Notification Level to show (can be blank for all)
	 * @param string $module Raw name of the module requesting
	 * @param string $id ID of the notification
	 * @param string $display_text The text that will be displayed as the subject/header of the message
	 * @ignore
	 */
	function _freepbx_log($level, $module, $id, $display_text, $extended_text=null) {
		global $amp_conf;
		if ($amp_conf['LOG_NOTIFICATIONS']) {
			if ($extended_text) {
				$display_text .= " ($extended_text)";
			}
			freepbx_log($level,"[NOTIFICATION]-[$module]-[$id] - $display_text");
		}
	}
	/**
	 * Returns the number of active notifications
	 *
	 * @return int Number of active Notifications
	 */
	function get_num_active() {
		$sql = "SELECT COUNT(id) FROM notifications WHERE reset = 0";
		return sql($sql,'getOne');
	}

	/**
	 * Because process hooks returns back an array which is keyed by the module
	 * name, we need to ensure we aren't duplicating alerts here by looping
	 * through all the modules and putting together and authoritiative list
	 *
	 * @param  array $list A list of notifications that is returned by process hooks
	 * @return array
	 */
	private function filterProcessHooks($list) {
		//I don't want to refilter here but there is no option to not care about the
		//module name in process hooks at the moment
		$filtered = array();
		foreach ($list as $mod => $notifications) {
			if (isset($notifications['_filtered'])) {
				foreach ($notifications['_filtered'] as $hash => $notice) {
					if (!isset($filtered[$hash])) {
						$filtered[$hash] = $notice;
					}
				}
			}
		}

		return $filtered;
	}

	/**
	 * Filter our notifications based on process hooks
	 * @param  array $list An array of notifications
	 * @param  array $filter=array() A white list of notifications to allow
	 *         Example: array('sipsettings' => array('BINDPORT'))
	 * @return array
	 */
	public function filterByWhitelist($list, $filter = array()) {
		if (empty($filter)) {
			return $list;
		}

		$filteredNotifications = (isset($list['_filtered'])) ? $list['_filtered'] : array();

		//Only allow modules and id's we care about
		foreach ($list as $notification) {
			$hash = sha1($notification['module'].'-'.$notification['id'].'-'.$notification['timestamp']);

			if (isset($filter[$notification['module']]) && in_array($notification['id'], $filter[$notification['module']]) && !isset($filteredNotifications[$hash])) {
				$filteredNotifications[$hash] = $notification;
			//Always show tampered notices for security purposes
			} else if ($notification['module'] == 'freepbx' && $notification['id'] == 'FW_TAMPERED' && !isset($filteredNotifications[$hash])) {
				$filteredNotifications[$hash] = $notification;
			}
		}

		if (!empty($filteredNotifications)) {
			$list['_filtered'] = $filteredNotifications;
		}

		return $list;
	}
}
