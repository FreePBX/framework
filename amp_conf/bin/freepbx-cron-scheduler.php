#!/usr/bin/php -q
<?php

define("AMP_CONF", "/etc/amportal.conf");
$amportalconf = AMP_CONF;

// Emulate gettext extension functions if gettext is not available
if (!function_exists('_')) {
	function _($str) {
		return $str;
	}
}

function out($text) {
	//echo $text."\n";
}

function outn($text) {
	//echo $text;
}

function error($text) {
	echo "[ERROR] ".$text."\n";
}

function fatal($text, $extended_text="", $type="FATAL") {
	global $db;

	echo "[$type] ".$text." ".$extended_text."\n";

	if(!DB::isError($db)) {
		$nt = notifications::create($db);
		$nt->add_critical('cron_manager', $type, $text, $extended_text);
	}

	exit(1);
}

function debug($text) {
	global $debug;
	
	if ($debug) echo "[DEBUG-preDB] ".$text."\n";
}

// bootstrap retrieve_conf by getting the AMPWEBROOT since that is currently where the necessary
// functions.inc.php resides, and then use that parser to properly parse the file and get all
// the defaults as needed.
//
function parse_amportal_conf_bootstrap($filename) {
	$file = file($filename);
	foreach ($file as $line) {
		if (preg_match("/^\s*([\w]+)\s*=\s*\"?([\w\/\:\.\*\%-]*)\"?\s*([;#].*)?/",$line,$matches)) {
			$conf[ $matches[1] ] = $matches[2];
		}
	}
	if ( !isset($conf["AMPWEBROOT"]) || ($conf["AMPWEBROOT"] == "")) {
		$conf["AMPWEBROOT"] = "/var/www/html";
	} else {
		$conf["AMPWEBROOT"] = rtrim($conf["AMPWEBROOT"],'/');
	}

	return $conf;
}

/********************************************************************************************************************/

// **** Make sure we have STDIN etc

// from  ben-php dot net at efros dot com   at  php.net/install.unix.commandline
if (version_compare(phpversion(),'4.3.0','<') || !defined("STDIN")) {
	define('STDIN',fopen("php://stdin","r"));
	define('STDOUT',fopen("php://stdout","r"));
	define('STDERR',fopen("php://stderr","r"));
	register_shutdown_function( create_function( '' , 'fclose(STDIN); fclose(STDOUT); fclose(STDERR); return true;' ) );
}
   
// **** Make sure we have PEAR's DB.php, and include it

outn(_("Checking for PEAR DB.."));
if (! @ include('DB.php')) {
	out(_("FAILED"));
	fatal(_("PEAR Missing"),sprintf(_("PEAR must be installed (requires DB.php). Include path: %s "), ini_get("include_path")));
}
out(_("OK"));


// **** Check for amportal.conf

outn(sprintf(_("Checking for %s "), $amportalconf)._(".."));
if (!file_exists($amportalconf)) {
	fatal(_("amportal.conf access problem: "),sprintf(_("The %s file does not exist, or is inaccessible"), $amportalconf));
}
out(_("OK"));

// **** read amportal.conf

outn(sprintf(_("Bootstrapping %s .."), $amportalconf));
$amp_conf = parse_amportal_conf_bootstrap($amportalconf);
if (count($amp_conf) == 0) {
	fatal(_("amportal.conf parsing failure"),sprintf(_("no entries found in %s"), $amportalconf));
}
out(_("OK"));

outn(sprintf(_("Parsing %s .."), $amportalconf));
require_once($amp_conf['AMPWEBROOT']."/admin/functions.inc.php");
$amp_conf = parse_amportal_conf($amportalconf);
if (count($amp_conf) == 0) {
	fatal(_("amportal.conf parsing failure"),sprintf(_("no entries found in %s"), $amportalconf));
}
out(_("OK"));

$asterisk_conf_file = $amp_conf["ASTETCDIR"]."/asterisk.conf";
outn(sprintf(_("Parsing %s .."), $asterisk_conf_file));
$asterisk_conf = parse_asterisk_conf($asterisk_conf_file);
if (count($asterisk_conf) == 0) {
	fatal(_("asterisk.conf parsing failure"),sprintf(_("no entries found in %s"), $asterisk_conf_file));
}
out(_("OK"));

// **** Connect to database

outn(_("Connecting to database.."));

# the engine to be used for the SQL queries,
# if none supplied, backfall to mysql
$db_engine = "mysql";
if (isset($amp_conf["AMPDBENGINE"])){
	$db_engine = $amp_conf["AMPDBENGINE"];
}

// Define the notification class for logging to the dashboard
//
$nt = notifications::create($db);

switch ($db_engine)
{
	case "pgsql":
	case "mysql":
		/* datasource in in this style:
		dbengine://username:password@host/database */
	
		$db_user = $amp_conf["AMPDBUSER"];
		$db_pass = $amp_conf["AMPDBPASS"];
		$db_host = $amp_conf["AMPDBHOST"];
		$db_name = $amp_conf["AMPDBNAME"];
	
		$datasource = $db_engine.'://'.$db_user.':'.$db_pass.'@'.$db_host.'/'.$db_name;
		$db = DB::connect($datasource); // attempt connection
		break;
	
	case "sqlite":
		require_once('DB/sqlite.php');
	
		if (!isset($amp_conf["AMPDBFILE"]))
			fatal(_("AMPDBFILE not setup properly"),sprintf(_("You must setup properly AMPDBFILE in %s "), $amportalconf));
	
		if (isset($amp_conf["AMPDBFILE"]) == "")
			fatal(_("AMPDBFILE not setup properly"),sprintf(_("AMPDBFILE in %s cannot be blank"), $amportalconf));
	
		$DSN = array (
			"database" => $amp_conf["AMPDBFILE"],
			"mode" => 0666
		);
	
		$db = new DB_sqlite();
		$db->connect( $DSN );
		break;
	
	case "sqlite3":
		if (!isset($amp_conf["AMPDBFILE"]))
			fatal("You must setup properly AMPDBFILE in $amportalconf");
			
		if (isset($amp_conf["AMPDBFILE"]) == "")
			fatal("AMPDBFILE in $amportalconf cannot be blank");

		require_once('DB/sqlite3.php');
		$datasource = "sqlite3:///" . $amp_conf["AMPDBFILE"] . "?mode=0666";
		$db = DB::connect($datasource);
		break;

	default:
		fatal( "Unknown SQL engine: [$db_engine]");
}

if(DB::isError($db)) {
	out(_("FAILED"));
	debug($db->userinfo);
	fatal(_("database connection failure"),("failed trying to connect to the configured database"));
	
}
out(_("OK"));

// Check to see if email should be sent
//

$cm =& cronmanager::create($db);

$cm->run_jobs();

$email = $cm->get_email();
if ($email) {

	$text="";

	// clear email flag
	$nt->delete('freepbx', 'NOEMAIL');

	// set to false, if no updates are needed then it will not be
	// set to true and no email will go out even though the hash
	// may have changed.
	//
	$send_email = false;

	$security = $nt->list_security();
	if (count($security)) {
		$send_email = true;
		$text = "SECURITY NOTICE: ";
		foreach ($security as $item) {
			$text .= $item['display_text']."\n";
			$text .= $item['extended_text']."\n\n";
		}
	}
	$text .= "\n\n";

	$updates = $nt->list_update();
	if (count($updates)) {
		$send_email = true;
		$text = "UPDATE NOTICE: ";
		foreach ($updates as $item) {
			$text .= $item['display_text']."\n";
			$text .= $item['extended_text']."\n\n";
		}
	}

	if ($send_email && (! $cm->check_hash('update_email', $text))) {
		$cm->save_hash('update_email', $text);
		if (mail($email, _("FreePBX: New Online Updates Available"), $text)) {
			$nt->delete('freepbx', 'EMAILFAIL');
		} else {
			$nt->add_error('freepbx', 'EMAILFAIL', _('Failed to send online update email'), sprintf(_('An attempt to send email to: %s with online update status failed'),$email));
		}
	}
} else {
		$nt->add_notice('freepbx', 'NOEMAIL', _('No email address for online update checks'), _('You are automatically checking for online updates nightly but you have no email address setup to send the results. This can be set on the General Tab. They will continue to show up here.'), '', 'PASSIVE', false);
}
?>
