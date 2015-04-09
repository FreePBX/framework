<?php
/** Legacy functions associated with ampuser class **/

/**
 * Returns true if extension is within allowed range
 * Depreciated
 * @param int $extension Extension number
 */
function checkRange($extension){
	$low = isset($_SESSION["AMP_user"]->_extension_low)?$_SESSION["AMP_user"]->_extension_low:'';
	$high = isset($_SESSION["AMP_user"]->_extension_high)?$_SESSION["AMP_user"]->_extension_high:'';

	if ((($extension >= $low) && ($extension <= $high)) || ($low == '' && $high == ''))
		return true;
	else
		return false;
}

/**
 * Legacy Check AMP Administrators Department
 * ALWAYS return true here now forever
 * @param string $dept department
 */
function checkDept($dept){
	return true;
}
