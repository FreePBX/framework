<?php

/**
 * @file
 * main
 */
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
	include_once('/etc/asterisk/freepbx.conf');
}
include_once("includes/bootstrap.php");
ariPageHeader();
include_once("includes/common.php");

handler();

ariPageFooter();


?>



