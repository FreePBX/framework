<?php /* $Id$ */

require_once('DB.php'); // PEAR

function parse_amportal_conf($filename) {
        $file = file($filename);
        foreach ($file as $line) {
                if (preg_match("/^\s*([a-zA-Z0-9]+)\s*=\s*(.*)\s*([;#].*)?/",$line,$matches)) {
                        $conf[ $matches[1] ] = $matches[2];
                }
        }
        return $conf;
}

$amp_conf = parse_amportal_conf("/etc/amportal.conf");


define ("WEBROOT", "http://".$amp_conf["AMPWEBADDRESS"]."/admin/cdr/");
define ("FSROOT", $amp_conf["AMPWEBROOT"]."/admin/cdr/");



define ("LIBDIR", FSROOT."lib/");


define ("HOST", $amp_conf["AMPDBHOST"]);
define ("PORT", "5432");
define ("USER", $amp_conf["AMPDBUSER"]);
define ("PASS", $amp_conf["AMPDBPASS"]);
define ("DBNAME", "asteriskcdrdb");
define ("DB_TYPE", "mysql"); // mysql or postgres


define ("DB_TABLENAME", "cdr");
 
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


function DbConnect()
{
  if (DB_TYPE == "postgres")
    { 
      $datasource = 'pgsql://'.USER.':'.PASS.'@'.HOST.'/'.DBNAME;
    }
  else
    { 
      $datasource = DB_TYPE.'://'.USER.':'.PASS.'@'.HOST.'/'.DBNAME;
    }

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
			$$test_var = $_POST[$test_var]; 
		} elseif (isset($_GET[$test_var])) {
			global $$test_var; 
			$$test_var = $_GET[$test_var];
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




?>
