<?php
global $amp_conf;
include_once  $amp_conf['AMPWEBROOT'].'/admin/libraries/freepbx_conf.class.php';
$c = freepbx_conf::create();

$c->remove_conf_settings('PROXY_ENABLED');
$c->remove_conf_settings('PROXY_ADDRESS');
$c->remove_conf_settings('PROXY_USERNAME');
$c->remove_conf_settings('PROXY_PASSWORD');
$c->commit_conf_settings();

// Ensure that our Proxy Settings exist
$set = array('category' => 'Proxy Settings', 'emptyok' => 0, 'readonly' => 0, 'defaultval' => '');

if (!$c->conf_setting_exists('PROXY_ENABLED')) {
	$set['value'] = false;
	$set['defaultval'] = false;
	$set['name'] = "Use HTTP(S) Proxy";
	$set['description'] = "Enable this to send outbound HTTP and HTTPS requests via a proxy. This does not affect Voice or Video traffic.";
	$set['sortorder'] = 1;
	$set['type'] = CONF_TYPE_BOOL;
	$c->define_conf_setting('PROXY_ENABLED',$set);
}

/* Not implementing SOCKS proxy at the moment
if (!$c->conf_setting_exists('PROXY_TYPE')) {
	$set['value'] = "http";
	$set['options'] = array("http", "socks5");
	$set['name'] = "Proxy Type";
	$set['description'] = "Select the type of outbound proxy. This will normally be HTTP";
	$set['sortorder'] = 2;
	$set['type'] = CONF_TYPE_SELECT;
	$c->define_conf_setting('PROXY_TYPE',$set);
	unset($set['options']);
}

 */

$set['value'] = "";
$set['defaultval'] = "";
$set['emptyok'] = 1;

if (!$c->conf_setting_exists('PROXY_ADDRESS')) {
	$set['value'] = "";
	$set['name'] = "Proxy Address";
	$set['description'] = "Enter the address of the outbound proxy. This will be similar to http://10.1.1.1:3128";
	$set['sortorder'] = 3;
	$set['type'] = CONF_TYPE_TEXT;
	$c->define_conf_setting('PROXY_ADDRESS',$set);
}

if (!$c->conf_setting_exists('PROXY_USERNAME')) {
	$set['value'] = "";
	$set['name'] = "Proxy Username";
	$set['description'] = "If you need to authenticate to the proxy server, you must enter both a username and password. Leaving either (or both) blank disables Proxy Authentication";
	$set['sortorder'] = 4;
	$set['type'] = CONF_TYPE_TEXT;
	$c->define_conf_setting('PROXY_USERNAME',$set);
}

if (!$c->conf_setting_exists('PROXY_PASSWORD')) {
	$set['value'] = "";
	$set['name'] = "Proxy Password";
	$set['description'] = "If you need to authenticate to the proxy server, you must enter both a username and password. Leaving either (or both) blank disables Proxy Authentication";
	$set['sortorder'] = 5;
	$set['type'] = CONF_TYPE_TEXT;
	$c->define_conf_setting('PROXY_PASSWORD',$set);
}

$c->commit_conf_settings();


$c->commit_conf_settings();
