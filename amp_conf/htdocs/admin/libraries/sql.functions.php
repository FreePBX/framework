<?php
/* queries database using PEAR.
*  $type can be query, getAll, getRow, getCol, getOne, etc
*  $fetchmode can be DB_FETCHMODE_ORDERED, DB_FETCHMODE_ASSOC, DB_FETCHMODE_OBJECT
*  returns array, unless using getOne
*/
function sql($sql,$type="query",$fetchmode=null) {
	global $db;
	$results = $db->$type($sql,$fetchmode);
	if(DB::IsError($results)) {
		die_freepbx($results->getDebugInfo() . "SQL - <br /> $sql" );
	}
	return $results;
}

/**  Format input so it can be safely used as a literal in a query. 
 * Literals are values such as strings or numbers which get utilized in places
 * like WHERE, SET and VALUES clauses of SQL statements.
 * The format returned depends on the PHP data type of input and the database 
 * type being used. This simply calls PEAR's DB::smartQuote() function
 * @param  mixed  The value to go into the database
 * @return string  A value that can be safely inserted into an SQL query
 */
function q(&$value) {
	global $db;
	return $db->quoteSmart($value);
}

// sql text formatting -- couldn't see that one was available already
function sql_formattext($txt) {
	global $db;
	if (isset($txt)) {
		$fmt = $db->escapeSimple($txt);
		$fmt = "'" . $fmt . "'";
	} else {
		$fmt = 'null';
	}

	return $fmt;
}


function execSQL( $file ) {
	global $db;
	$data = null;
	
	// run sql script
	$fd = fopen( $file ,"r" );
	
	while (!feof($fd)) { 
		$data .= fread($fd, 1024); 
	}
	fclose($fd);
	
	preg_match_all("/((SELECT|INSERT|UPDATE|DELETE|CREATE|DROP).*);\s*\n/Us", $data, $matches);
	foreach ($matches[1] as $sql) {
		$result = $db->query($sql);
		if(DB::IsError($result)) { return false; }
	}
  return true;
}
?>