<?php

function FixTables() {
        global $db;

      	$sql = "ALTER TABLE extensions modify context varchar(45) NOT NULL default 'default'";
      	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
      	}
      	$sql = "ALTER TABLE extensions modify application varchar(45) NOT NULL default ''";
      	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die($result->getMessage());
      	}
        return true;
}
function FixRoutes() {
        global $db;

        $sql = "DELETE from  extensions WHERE context='outbound-allroutes'";
        $result = $db->query($sql);
        if(DB::IsError($result)) {
        	die($result->getMessage().$sql);
        }
        $sql = "SELECT DISTINCT SUBSTRING(context,7) FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ";
        $routes = $db->getAll($sql);
        if (count($routes) > 0) {
                // there's a route therefore we need to rename them
		$key=1;
		foreach ($routes as $route) {
			$prefix=setroutepriorities($key);
      			$sql = "UPDATE extensions SET context = 'outrt-".$prefix."-".$route[0]."' WHERE context = 'outrt-".$route[0]."'";
      			$result = $db->query($sql);
			if(DB::IsError($result)) {
				die($result->getMessage());
      			}

			// Delete and readd the outbound-allroutes entries
	                $sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ";
	                $sql .= "('outbound-allroutes', ";
        	        $sql .= "'include', ";
	                $sql .= "'".$key."', ";
        	        $sql .= "'outrt-".$prefix."-".$route[0]."', ";
	                $sql .= "'', ";
	                $sql .= "'', ";
	                $sql .= "'2')";

	                $result = $db->query($sql);
	                if(DB::IsError($result)) {
	                        die($result->getMessage(). $sql);
	                }
			$key++;
	        }
        }
	else
		return false;

        return true;
}
function setroutepriorities($key)
{
        if ($key<10)
                $prefix = sprintf("00%d",$key);
        else if ((9<$key)&&($key<100))
                $prefix = sprintf("0%d",$key);
        else if ($key>100)
                $prefix = sprintf("%d",$key);
        return ($prefix);
}

outn("Upgrading Routes Names..");
$reason=FixTables();
$reason=FixRoutes();
out("OK");
?>
