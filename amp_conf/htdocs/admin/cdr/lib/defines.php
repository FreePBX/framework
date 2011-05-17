<?php /* $Id$ */
if (!isset($bootstrap_settings['amportal_conf_initialized']) || !$bootstrap_settings['amportal_conf_initialized']) {
  $restrict_mods = true;
  $bootstrap_settings['skip_astman'] = true;
  if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
	  include_once('/etc/asterisk/freepbx.conf');
  }
}
defined('FREEPBX_IS_AUTH') OR die('No direct script access allowed');
require_once('DB.php'); // PEAR

if (isset($amp_conf["AMPWEBADDRESS"])) {
	define ("WEBROOT", "http://".$amp_conf["AMPWEBADDRESS"]."/admin/cdr/");
}
define ("FSROOT", $amp_conf["AMPWEBROOT"]."/admin/cdr/");

define ("LIBDIR", FSROOT."lib/");

define ("HOST", (( (!isset($amp_conf["CDRDBHOST"]) || !$amp_conf["CDRDBHOST"]) ? $amp_conf["AMPDBHOST"] : $amp_conf["CDRDBHOST"] )) );
define ("PORT", (( (!isset($amp_conf["CDRDBPORT"]) || !$amp_conf["CDRDBPORT"]) ? "5432" : $amp_conf["CDRDBPORT"] )) );
define ("USER", (( (!isset($amp_conf["CDRDBUSER"]) || !$amp_conf["CDRDBUSER"]) ? $amp_conf["AMPDBUSER"] : $amp_conf["CDRDBUSER"] )) );
define ("PASS", (( (!isset($amp_conf["CDRDBPASS"]) || !$amp_conf["CDRDBPASS"]) ? $amp_conf["AMPDBPASS"] : $amp_conf["CDRDBPASS"] )) );
define ("DBNAME", (( (!isset($amp_conf["CDRDBNAME"]) || !$amp_conf["CDRDBNAME"]) ? "asteriskcdrdb" : $amp_conf["CDRDBNAME"] )) );
define ("DB_TYPE", (( (!isset($amp_conf["CDRDBTYPE"]) ||!$amp_conf["CDRDBTYPE"]) ? $amp_conf["AMPDBENGINE"] : $amp_conf["CDRDBTYPE"] )) ); // mysql or postgres
define ("DB_TABLENAME", (( (!isset($amp_conf["CDRDBTABLENAME"]) || !$amp_conf["CDRDBTABLENAME"]) ? "cdr" : $amp_conf["CDRDBTABLENAME"] )) );

// Regarding to the dst you can setup an application name
// Make more sense to have a text that just a number
// especially if you have a lot of extension in your dialplan
$appli_list['*78']=array("dnd-enable");
$appli_list['*79']=array("dnd-disable");
$appli_list['*98']=array("Voicemail");
$appli_list['*72']=array("Call_Forward-enable");
$appli_list['*73']=array("Call_Forward-disable");
$appli_list['*69']=array("Call_Trace");
$appli_list['s']=array("Catch-All");


function DbConnect() {
	$options = array();
	if (DB_TYPE == "postgres") { 
		$datasource = 'pgsql://'.USER.':'.PASS.'@'.HOST.'/'.DBNAME;
	} else if (DB_TYPE == "sqlite3") {
                /* on centos this extension is not loaded by default */
                if (! extension_loaded('sqlite3')  && ! extension_loaded('SQLITE3'))
                        die_freepbx('sqlite3.so extension must be loaded to run with sqlite3');

                if (! @require_once('DB/sqlite3.php') )
                {
                        die_freepbx("Your PHP installation has no PEAR/SQLite3 support. Please install php-sqlite3 and php-pear.");
                }

                $datasource = "sqlite3:///asteriskcdr.db?mode=0666";
                $options = array(
                        'debug'       => 4,
                        'portability' => DB_PORTABILITY_NUMROWS
                );
    }else{ 
      $datasource = DB_TYPE.'://'.USER.':'.PASS.'@'.HOST.'/'.DBNAME;

    }
  if(!empty($options))
    $db = DB::connect($datasource,$options); // attempt connection with options (sqlite3)
   else
    $db = DB::connect($datasource); // attempt connection

  if(DB::isError($db))
    {
      die($db->getDebugInfo()); 
    }
 
  return $db;
}


function getpost_ifset($test_vars)
{
	if (!is_array($test_vars)) {
		$test_vars = array($test_vars);
	}
	foreach($test_vars as $test_var) { 
		if (isset($_POST[$test_var])) { 
			global $$test_var;
			$$test_var = htmlspecialchars($_POST[$test_var]); 
		} elseif (isset($_GET[$test_var])) {
			global $$test_var; 
			$$test_var = htmlspecialchars($_GET[$test_var]);
		}
	}
}



function display_minute($sessiontime){
		global $resulttype;
		if ((!isset($resulttype)) || ($resulttype=="min")){  
				$minutes = sprintf("%02d",intval($sessiontime/60)).":".sprintf("%02d",intval($sessiontime%60));
		}else{
				$minutes = $sessiontime;
		}
		echo $minutes;
}

function display_2dec($var){		
		echo number_format($var,2);
}

function display_2bill($var){	
		$var=$var/100;
		echo '$ '.number_format($var,2);
}

function remove_prefix($phonenumber){
		
		if (substr($phonenumber,0,3) == "011"){
					echo substr($phonenumber,3);
					return 1;
		}
		echo $phonenumber;
}


function display_acronym($field){		
		echo '<acronym title="'.$field.'">'.substr($field,0,10).'...</acronym>';		
}

function filter_html($field) {
  echo  htmlspecialchars($field);
}

?>
