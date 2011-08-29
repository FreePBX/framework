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

switch ($amp_conf['AMPDBENGINE']) {
	case "pgsql":
	case "mysql":
		/* datasource in in this style:
		dbengine://username:password@host/database */
		
		$datasource = 'mysql://'
					. $amp_conf['AMPDBUSER']
					. ':'
					. $amp_conf['AMPDBPASS']
					. '@'
					. $amp_conf['AMPDBHOST']
					. '/'
					. $amp_conf['AMPDBNAME'];
		$db = DB::connect($datasource); // attempt connection
		break;	
	
	case "sqlite":
		die_freepbx("SQLite2 support is deprecated. Please use sqlite3 only.");
		break;

	case "sqlite3":
	
		/* on centos this extension is not loaded by default */
		if (! extension_loaded('sqlite3') && ! extension_loaded('SQLITE3'))
			die_freepbx('sqlite3.so extension must be loaded to run with sqlite3');

		if (! @require_once('DB/sqlite3.php') )
		{
			die_freepbx("Your PHP installation has no PEAR/SQLite3 support. Please install php-sqlite3 and php-pear.");
		}
		
		$datasource = "sqlite3:///" . $amp_conf['AMPDBFILE'] . "?mode=0666";
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
