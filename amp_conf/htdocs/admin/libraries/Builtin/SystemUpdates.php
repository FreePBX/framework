<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\Builtin;

class SystemUpdates {

	/**
	 * This checks to make sure we have the Sysadmin module, and that the
	 * sysadmin module is activated. If neither of these things are true,
	 * this machine can't do system updates.
	 *
	 * @return bool
	 */
	public function canDoSystemUpdates() {
		if (!function_exists("sysadmin_get_license")) {
			return false;
		}
		$lic = sysadmin_get_license();
		return (isset($lic['hostid']));
	}
}

