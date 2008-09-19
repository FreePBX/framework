<?php
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

require_once('DB.php'); //PEAR must be installed

$db_engine = $amp_conf["AMPDBENGINE"];

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
		die_freepbx("SQLite2 support is deprecated. Please use sqlite3 only.");
		break;

	case "sqlite3":
		if (!isset($amp_conf["AMPDBFILE"]))
			die_freepbx("You must setup properly AMPDBFILE in /etc/amportal.conf");
			
		if (isset($amp_conf["AMPDBFILE"]) == "")
			die_freepbx("AMPDBFILE in /etc/amportal.conf cannot be blank");

		/* on centos this extension is not loaded by default */
		if (! extension_loaded('sqlite3') && ! extension_loaded('SQLITE3'))
			dl('sqlite3.so');

		if (! @require_once('DB/sqlite3.php') )
		{
			die_freepbx("Your PHP installation has no PEAR/SQLite3 support. Please install php-sqlite3 and php-pear.");
		}

		$datasource = "sqlite3:///" . $amp_conf["AMPDBFILE"] . "?mode=0666";
                $options = array(
       	           	'debug'       => 4,
			'portability' => DB_PORTABILITY_NUMROWS
		);
		$db = DB::connect($datasource, $options);
		break;

	default:
		die_freepbx( "Unknown SQL engine: [$db_engine]");
}

// if connection failed show error
// don't worry about this for now, we get to it in the errors section
if(DB::isError($db)) {
	die_freepbx($db->getMessage()); 
}

// Now send or delete warning wrt to default passwords:
//
if (!isset($quietmode) || !$quietmode) {
	$nt = notifications::create($db);

	if ($amp_conf['AMPDBPASS'] == $amp_conf_defaults['AMPDBPASS'][1]) {
		$nt->add_warning('core', 'AMPDBPASS', _("Default SQL Password Used"), _("You are using the default SQL password that is widely known, you should set a secure password"));
	} else {
		$nt->delete('core', 'AMPDBPASS');
	}

	// Check and increase php memory_limit if needed and if allowed on the system
	//
	$current_memory_limit = rtrim(ini_get('memory_limit'),'M');
	$proper_memory_limit = '100';
	if ($current_memory_limit < $proper_memory_limit) {
		if (ini_set('memory_limit',$proper_memory_limit.'M') !== false) {
			$nt->add_notice('core', 'MEMLIMIT', _("Memory Limit Changed"), sprintf(_("Your memory_limit, %sM, is set too low and has been increased to %sM. You may want to change this in you php.ini config file"),$current_memory_limit,$proper_memory_limit));
		} else {
			$nt->add_warning('core', 'MEMERR', _("Low Memory Limit"), sprintf(_("Your memory_limit, %sM, is set too low and may cause problems. FreePBX is not able to change this on your system. You should increase this to %sM in you php.ini config file"),$current_memory_limit,$proper_memory_limit));
		}
	} else {
		$nt->delete('core', 'MEMLIMIT');
	}

	// send error if magic_quotes_gpc is enabled on this system as much of the code base assumes not
	//
	if(get_magic_quotes_gpc()) {
		$nt->add_error('core', 'MQGPC', _("Magic Quotes GPC"), _("You have magic_quotes_gpc enabled in your php.ini, http or .htaccess file which will cause errors in some modules. FreePBX expects this to be off and runs under that assumption"));
	} else {
		$nt->delete('core', 'MQGPC');
	}
}
