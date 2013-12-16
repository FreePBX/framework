<?php
// vim: set ai ts=4 sw=4 ft=php:

/*
 * Copyright (C) 2011 Philippe Lindheimer 
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
 */

/* This is a re-implementation of the freepbx_conf class
 *
 * Configuration types, constant should be used here
 * and in all calling functions.
 */

define("CONF_TYPE_BOOL",   'bool');
define("CONF_TYPE_TEXT",   'text');
define("CONF_TYPE_DIR",    'dir');
define("CONF_TYPE_INT",    'int');
define("CONF_TYPE_SELECT", 'select');
define("CONF_TYPE_FSELECT",'fselect');

/* Used by translation engine, apparently. */
if (false) {
	_('No Description Provided');
	_('Undefined Category');
}

class Config {

	public function __construct($freepbx = null) {
		if ($freepbx === null)
			throw new Exception("Config needs to be given a FreePBX Object when created");

		$this->FreePBX = $freepbx;

		$db = $this->FreePBX->Database;

		$stmt = $db->query("SELECT * FROM freepbx_settings ORDER BY category, sortorder, name");
		if ($stmt == false)
			throw new Exception("Unable to load freepbx_settings");

		$db_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach($db_raw as $setting) {
			$this->last_update_status[$setting['keyword']]['validated'] = false;
			$this->last_update_status[$setting['keyword']]['saved'] = false;
			$this->last_update_status[$setting['keyword']]['orig_value'] = $setting['value'];
			$this->last_update_status[$setting['keyword']]['saved_value'] = $setting['value'];
			$this->last_update_status[$setting['keyword']]['msg'] = '';
			$this->_last_update_status =& $this->last_update_status[$setting['keyword']];

			$this->db_conf_store[$setting['keyword']] = $setting;
			$this->db_conf_store[$setting['keyword']]['modified'] = false;
			// setup the conf array also
			// note the reference assignment, if it's actually the authoritative source
			$this->conf[$setting['keyword']] =& $this->db_conf_store[$setting['keyword']]['value'];

			// The assumption is that the database settings were validated on input. We are not going to throw errors when
			// reading them back but the last_update_status array is available for debugging purposes to review.
			//
			if (!$setting['emptyok'] && $setting['value'] == '') {
				$this->db_conf_store[$setting['keyword']]['value'] = $this->prepare_conf_value($setting['defaultval'], $setting['type'], $setting['emptyok'], $setting['options']);
			} else {
				$this->db_conf_store[$setting['keyword']]['value'] = $this->prepare_conf_value($setting['value'], $setting['type'], $setting['emptyok'], $setting['options']);
			}
		}
	}

	/** prepares a value to be inserted into the configuration settings using the
	 * type information and any provided validation rules. Integers that are out
	 * of range will be set to the lowest or highest values. Validation issues
	 * are recorded and can be examined with the get_last_update_status() method.
	 *
	 * @param mixed   integer, string or boolean to be prepared
	 * @param type    the type being validated
	 * @param bool    emptyok attribute of this setting
	 * @param mixed   options string or array used for validating the type
	 *
	 * @return string value to be inserted into the store
	 *                last_update_status is updated with any relevant issues
	 */
	private function prepare_conf_value($value, $type, $emptyok, $options = false) {
		switch ($type) {

		case CONF_TYPE_BOOL:
			$ret = $value ? 1 : 0;
			$this->_last_update_status['validated'] = true;
			break;

		case CONF_TYPE_SELECT:
			$val_arr = explode(',',$options);
			if (in_array($value,$val_arr)) {
				$ret = $value;
				$this->_last_update_status['validated'] = true;
			} else {
				$ret = null;
				$this->_last_update_status['validated'] = false;
				$this->_last_update_status['msg'] = _("Invalid value supplied to select");
				$this->_last_update_status['saved_value'] = $ret;
				$this->_last_update_status['saved'] = false;
				//
				// NOTE: returning from function early!
				return $ret;
			}
			break;

		case CONF_TYPE_FSELECT:
			if (!is_array($options)) {
				$options = unserialize($options);
			}
			if (array_key_exists($value, $options)) {
				$ret = $value;
				$this->_last_update_status['validated'] = true;
			} else {
				$ret = null;
				$this->_last_update_status['validated'] = false;
				$this->_last_update_status['msg'] = _("Invalid value supplied to select");
				$this->_last_update_status['saved_value'] = $ret;
				$this->_last_update_status['saved'] = false;
				//
				// NOTE: returning from function early!
				return $ret;
			}
			break;

		case CONF_TYPE_DIR:
			// we don't consider trailing '/' in a directory an error for validation purposes
			$value = rtrim($value,'/');
			// NOTE: fallthrough to CONF_TYPE_TEXT, NO break on purpose!
			//       |
			//       |
			//       V
		case CONF_TYPE_TEXT:
			if ($value == '' && !$emptyok) {
				$this->_last_update_status['validated'] = false;
				$this->_last_update_status['msg'] = _("Empty value not allowed for this field");
			} else if ($options != '' && $value != '') {
				if (preg_match($options,$value)) {
					$ret = $value;
					$this->_last_update_status['validated'] = true;
				} else {
					$ret = null;
					$this->_last_update_status['validated'] = false;
					$this->_last_update_status['msg'] = sprintf(_("Invalid value supplied violates the validation regex: %s"),$options);
					$this->_last_update_status['saved_value'] = $ret;
					$this->_last_update_status['saved'] = false;
					//
					// NOTE: returning from function early!
					return $ret;
				}
			} else {
				$ret = $value;
				$this->_last_update_status['validated'] = true;
			}
			break;

		case CONF_TYPE_INT:
			$ret = !is_numeric($value) && $value != '' ? '' : $value;
			$ret = $emptyok && (string) trim($ret) === '' ? '' : (int) $ret;

			if ($options != '' && (string) $ret !== '') {
				$range = is_array($options) ? $options : explode(',',$options);
				switch ($ret) {
				case $ret < $range[0]:
					$ret = $range[0];
					$this->_last_update_status['validated'] = false;
					$this->_last_update_status['msg'] = sprintf(_("Value [%s] out of range, changed to [%s]"),$value,$ret);
					break;
				case $ret > $range[1]:
					$ret = $range[1];
					$this->_last_update_status['validated'] = false;
					$this->_last_update_status['msg'] = sprintf(_("Value [%s] out of range, changed to [%s]"),$value,$ret);
					break;
				default:
					$this->_last_update_status['validated'] = (string) $ret === (string) $value;
					break;
				}
			} else {
				$this->_last_update_status['validated'] = (string) $ret === (string) $value;
			}
			break;

		default:
			die_freepbx(sprintf(_("unknown type: [%s]"),$type));
			break;
		}
		$this->_last_update_status['saved_value'] = $ret;
		$this->_last_update_status['saved'] = true;
		return $ret;
	}
}
