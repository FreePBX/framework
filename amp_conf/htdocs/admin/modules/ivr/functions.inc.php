<?php /* $id$ */

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function ivr_destinations() {
	//get the list of meetmes
	$results = ivr_list();
	
	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
				$extens[] = array('destination' => $result[0].',s,1', 'description' => $result['1']);
		}
	}
	
	return $extens;
}


//get info about auto-attendant
function ivr_get($menu_id) {
	global $db;
	//do another select for all parts in this aa_
//	$sql = "SELECT * FROM extensions WHERE context = '".$dept."aa_".$menu_num."' ORDER BY extension";
	$sql = "SELECT * FROM extensions WHERE context = '".$menu_id."' ORDER BY extension";
	$aalines = $db->getAll($sql);
	if(DB::IsError($aalines)) {
		die('aalines: '.$aalines->getMessage());
	}
	return $aalines;
}



//get unique voice menu numbers - returns 2 dimensional array
function ivr_list() {
	global $db;
	$dept = str_replace(' ','_',$_SESSION["AMP_user"]->_deptname);
	if (empty($dept)) $dept='%';  //if we are not restricted to dept (ie: admin), then display all AA menus
	$sql = "SELECT context,descr FROM extensions WHERE extension = 's' AND application LIKE 'DigitTimeout' AND context LIKE '".$dept."aa_%' ORDER BY context";
	$unique_aas = $db->getAll($sql);
	if(DB::IsError($unique_aas)) {
	   die('unique: '.$unique_aas->getMessage().'<hr>'.$sql);
	}
	return $unique_aas;
}

?>