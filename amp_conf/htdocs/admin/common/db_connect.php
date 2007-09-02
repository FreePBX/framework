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

		require_once('DB/sqlite3.php');
		$datasource = "sqlite3:///" . $amp_conf["AMPDBFILE"] . "?mode=0666";
		$db = DB::connect($datasource);
		break;

	default:
		die_freepbx( "Unknown SQL engine: [$db_engine]");
}

// if connection failed show error
// don't worry about this for now, we get to it in the errors section
if(DB::isError($db)) {
	die_freepbx($db->getDebugInfo()); 
}

// Now send or delete warning wrt to default passwords:
//
$nt = notifications::create($db);

if ($amp_conf['AMPDBPASS'] == $amp_conf_defaults['AMPDBPASS'][1]) {
	$nt->add_warning('core', 'AMPDBPASS', _("Default SQL Password Used"), _("You are using the default SQL password that is widely known, you should set a secure password"));
} else {
	$nt->delete('core', 'AMPDBPASS');
}

