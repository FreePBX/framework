<?php

/* If displaying popOver, we add a js script to prepare the page such as removing
 * rnav, adding fw_popover_process hidden field, etc. If processing the page
 * we need to get the latest drawselects with thosen goto target if we have it
 * and send it back. This could be more efficient by only getting the options for
 * the target and possibly related categories but the overhead is minimal to get
 * a single copy of the select box structure.
 */
$html = '';
switch($popover_mode) {
case 'display':
	$html .= "<script>popOverDisplay();</script>";
	break;
case 'process':
	$gotodest = fwmsg::get_dest();
	$drawselects_json = json_encode(drawselects($gotodest, 0, false, false, '', false, false));
	$html .= '<script>parent.closePopOver(' . $drawselects_json . ');</script>';
	break;
}
$html .= "\n";
echo $html;

