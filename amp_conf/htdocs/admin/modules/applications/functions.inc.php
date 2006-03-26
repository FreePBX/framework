<?php 
// returns a associative arrays with keys 'destination' and 'description'
function applications_destinations() {
	return null;
}

/* 	Generates dialplan for conferences
	We call this with retrieve_conf
*/
function applications_get_config($engine) {
	global $ext;  
	switch($engine) {
		case "asterisk":
			if(is_array($applicationslist = applications_list())) {
				foreach($applicationslist as $item) {
					$var = $item['var'];
					$exten = $item['exten'];

          $ext->addGlobal($var, $exten);
        }
      }
		break;
	}
}

function applications_list(){
	//get all the variables for the applications
	$results = sql("SELECT * FROM applications","getAll",DB_FETCHMODE_ASSOC);
	return $results;
}

function applications_update($_REQUEST){
  //update the database
  $applications = applications_list();
  foreach($applications as $item){
    if (isset($_REQUEST[$item['var']])){
      $app =  $item['app'];
      $exten = $_REQUEST[$item['var']];
      $sql = "update applications set exten='$exten' where app='$app'";
    	sql($sql);
    }      
  }
}
?>
