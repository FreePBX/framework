<?php
global $amp_conf;
global $db;

// TODO: exit here for now. This will be migration code for new routes once the work is finished.
exit;

//TODO: DEBUG remove and restart for testing
//
$result = $db->query("DROP TABLE IF EXISTS `outbound_routes`");
$result = $db->query("DROP TABLE IF EXISTS `outbound_route_patterns`");
$result = $db->query("DROP TABLE IF EXISTS `outbound_route_trunks`");
$result = $db->query("DROP TABLE IF EXISTS `outbound_route_sequence`");

$outbound_routes = "
CREATE TABLE outbound_routes (
	`route_id` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`name` VARCHAR( 40 ),
	`outcid` VARCHAR( 40 ),
	`outcid_override_exten` VARCHAR( 20 ),
	`password` VARCHAR( 30 ),
	`emergency_route` VARCHAR( 4 ),
	`intra_company_route` VARCHAR( 4 ),
	`mohclass` VARCHAR( 80 ),
	`time_group_id` INTEGER DEFAULT NULL
)
";

$outbound_route_patterns = "
CREATE TABLE outbound_route_patterns (
	`route_id` INTEGER NOT NULL,
	`match_pattern_prefix` VARCHAR( 60 ),
	`match_pattern_pass` VARCHAR( 60 ),
	`match_cid` VARCHAR( 60 ),
	`prepend_digits` VARCHAR( 100 ),
  PRIMARY KEY (`route_id`, `match_pattern_prefix`, `match_pattern_pass`,`match_cid`,`prepend_digits`)
)
";

$outbound_route_trunks = "
CREATE TABLE outbound_route_trunks (
	`route_id` INTEGER NOT NULL,
	`trunk_id` INTEGER NOT NULL,
	`seq` INTEGER NOT NULL,
  PRIMARY KEY  (`route_id`, `trunk_id`, `seq`) 
)
";

$outbound_route_sequence = "
CREATE TABLE outbound_route_sequence (
	`route_id` INTEGER NOT NULL,
	`seq` INTEGER NOT NULL,
  PRIMARY KEY  (`route_id`, `seq`) 
)
";

outn("Create new outbound_routes table.. ");
$result = $db->query($outbound_routes);
if (DB::IsError($result) && $result->getCode() == DB_ERROR_ALREADY_EXISTS ) {
  out("Table exists, skipping migration");
} elseif (DB::IsError($result)) {
  out("failed, FATAL Error");
	out($result->getMessage());
} else {
  out("ok");
  outn("create outbound_route_patterns.. ");
  $result = $db->query($outbound_route_patterns);
  if (DB::IsError($result) && $result->getCode() != DB_ERROR_ALREADY_EXISTS ) {
    out("failed, FATAL Error");
	  out($result->getDebugInfo());
  } else {
    out("ok");
    outn("create outbound_route_trunks.. ");
    $result = $db->query($outbound_route_trunks);
    if (DB::IsError($result) && $result->getCode() != DB_ERROR_ALREADY_EXISTS ) {
      out("failed, FATAL Error");
	    out($result->getDebugInfo());
    } else {
      out("ok");
      outn("create outbound_route_sequence.. ");
      $result = $db->query($outbound_route_sequence);
      if (DB::IsError($result) && $result->getCode() != DB_ERROR_ALREADY_EXISTS ) {
        out("failed, FATAL Error");
	      out($result->getDebugInfo());
      } else {
        out("ok");

        $routepriority = core_routing_getroutenames();

        $routes = array();
        $accum = array();
        $dialpattern = array();
        $turnkpriority = array();
        foreach ($routepriority as $route) {
		      $extdisplay = $route[0];
		      $accum[] = substr($extdisplay,4);
  
		      $routecid_array = core_routing_getroutecid($extdisplay);
          $accum[] = $routecid_array['routecid'];
          $accum[] = $routecid_array['routecid_mode'];

		      $accum[] = core_routing_getroutepassword($extdisplay);
		      $accum[] = core_routing_getrouteemergency($extdisplay);
		      $accum[] = core_routing_getrouteintracompany($extdisplay);
		      $accum[] = core_routing_getroutemohsilence($extdisplay);

		      $dialpattern[$extdisplay] = core_routing_getroutepatterns($extdisplay);
		      $trunkpriority[$extdisplay] = core_routing_getroutetrunks($extdisplay);

          $routes[$extdisplay] = $accum;
          unset($accum);
        }

        $compiled = $db->prepare('INSERT INTO `outbound_routes` (`name`, `outcid`, `outcid_override_exten`, `password`, `emergency_route`, `intra_company_route`, `mohclass`) values (?,?,?,?,?,?,?)');
	      $result = $db->executeMultiple($compiled,$routes);
	      if(DB::IsError($result)) {
		      out("FATAL: ".$result->getDebugInfo()."\n".'error inserting into outbound_routes table');	
        }
        $route_ids = $db->getCol('SELECT `route_id` FROM `outbound_routes` ORDER BY `route_id`');
	      if(DB::IsError($route_ids)) {
		      out("FATAL: ".$route_ids->getDebugInfo()."\n".'error getting route_ids to create outbound_route_sequence');	
        }
        // assumption here is that routepriorities always return in order, I think that is correct, which means we inserted in order
        // TODO: update existing pinsets here whether enabled or not
        $seq = 0;
        $outbound_route_sequence = array();
        foreach ($route_ids as $route_id) {
          outn("processing route_id $route_id..");
          $outbound_route_sequence[] = array($route_id,$seq);
          $seq++;

          $this_patterns = array_shift($dialpattern);

          $insert_patterns = array();
          foreach ($this_patterns as $pattern) {
            $parts = explode('|',$pattern,2);
            if (count($parts) == 1) {
              $prepend = '';
              $exten = $pattern;
            } else {
              $prepend = $parts[0];
              $exten = $parts[1];
            }
            $parts = explode('/',$exten,2);
            if (count($parts) == 1) {
              $insert_patterns[] = array($route_id, $prepend, $exten, '');
            } else {
              if ($parts[1][0] == "_") {
                $parts[1] = substr($parts[1],1);
              }
              $insert_patterns[] = array($route_id, $prepend, $parts[0], $parts[1]);
            }
          }
          $compiled = $db->prepare('INSERT INTO `outbound_route_patterns` (`route_id`, `match_pattern_prefix`, `match_pattern_pass`, `match_cid`) values (?,?,?,?)');
	        $result = $db->executeMultiple($compiled,$insert_patterns);
	        if(DB::IsError($result)) {
		        out("FATAL: ".$result->getDebugInfo()."\n".'error inserting into outbound_route_patterns table');	
          } else {
            outn('patterns..');
          }
          unset($insert_pattern);

          $this_trunks = array_shift($trunkpriority);
          $trunk_seq = 0;
          $insert_trunks = array();
          foreach ($this_trunks as $trunk) {
            $insert_trunks[] = array($route_id, substr($trunk,4),$trunk_seq);
            $trunk_seq++;
          }

          $compiled = $db->prepare('INSERT INTO `outbound_route_trunks` (`route_id`, `trunk_id`, `seq`) values (?,?,?)');
	        $result = $db->executeMultiple($compiled,$insert_trunks);
	        if(DB::IsError($result)) {
		        out("FATAL: ".$result->getDebugInfo()."\n".'error inserting into outbound_route_trunks table');	
          } else {
            outn('trunks..');
          }
          unset($insert_trunks);
          out("migrated");
        }

        $compiled = $db->prepare('INSERT INTO `outbound_route_sequence` (`route_id`, `seq`) values (?,?)');
	      $result = $db->executeMultiple($compiled,$outbound_route_sequence);
	      if(DB::IsError($result)) {
		      out("FATAL: ".$result->getDebugInfo()."\n".'error inserting into outbound_route_sequence table');	
        }
      }
    }
  }
}
