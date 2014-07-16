<?php
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
	include_once('/etc/asterisk/freepbx.conf');
}
include('PKCS.class.php');

$pkcs = new PKCS(5);

$o = $pkcs->getAllKeys();

$servername = ''; //need to put server name in here
$pkcs->createConfig($servername,'My Super Company');
$pkcs->createCA('dsfdslkfjsdlkf');
$pkcs->createCert('asterisk','dsfdslkfjsdlkf');
