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

$db_user = $amp_conf["AMPDBUSER"];
$db_pass = $amp_conf["AMPDBPASS"];
$db_host = 'localhost';
$db_name = 'asterisk';
$db_engine = 'mysql';

$datasource = $db_engine.'://'.$db_user.':'.$db_pass.'@'.$db_host.'/'.$db_name;

/* datasource in in this style:

dbengine://username:password@host/database */

$db = DB::connect($datasource); // attempt connection

// if connection failed show error
// don't worry about this for now, we get to it in the errors section
if(DB::isError($db)) {
	die($db->getDebugInfo()); 
}
