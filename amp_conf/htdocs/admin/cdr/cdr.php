<?php /* $Id$ */


function cdrpage_getpost_ifset($test_vars)
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


//cdrpage_getpost_ifset(array('s', 't'));

$display=$_REQUEST['display'];

//$array = array ("CDR", "STATISTIC" => array("CALL LOGS", "CALLS COMPARE"));
//$s = $s ? $s : 0;
//$section="section$s$t";

//$racine=$PHP_SELF;
//$update = "19 December 2003";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>
	<head>
		<title>Asterisk CDR</title>
		<meta http-equiv="Content-Type" content="text/html">
		<link rel="stylesheet" type="text/css" media="print" href="/css/print.css">
		<link rel="stylesheet" type="text/css" media="screen" href="/admin/common/mainstyle.css">
		<style type="text/css" media="screen">
			div.content b{
    			color:#FFFFFF;
			}
			td{
    			font-size:10px;
			}
			b{
    			font-size:14px;
			}
		</style>
		<meta name="MSSmartTagsPreventParsing" content="TRUE">
	</head>
	
	<body leftmargin="0" topmargin="40" style="text-align:left">






		<!-- header BEGIN -->

		<!--<div id="fedora-nav"></div>-->

		<!-- header END -->


		<!-- leftside BEGIN -->
		<!--<div id="fedora-side-left">-->
		<!--<div id="fedora-side-nav-label">Site Navigation:</div>	<ul id="fedora-side-nav">-->
		<?php  /*
			$nkey=array_keys($array);
    		$i=0;
    		while($i<sizeof($nkey)){
			
				$op_strong = (($i==$s) && (!is_string($t))) ? '<strong>' : '';
				$cl_strong = (($i==$s) && (!is_string($t))) ? '</strong>' : '';
									
        		if(is_array($array[$nkey[$i]])){
					
					
					
					echo "\n\t<li>$op_strong<a href=\"$racine?s=$i\">".$nkey[$i]."</a>$cl_strong";
									
					$j=0;
					while($j<sizeof($array[$nkey[$i]] )){
						$op_strong = (($i==$s) && (isset($t)) && ($j==intval($t))) ? '<strong>' : '';
						$cl_strong = (($i==$s) && (isset($t))&& ($j==intval($t))) ? '</strong>' : '';						
						echo "<ul>";						
						echo "\n\t<li>$op_strong<a href=\"$racine?s=$i&t=$j\">".$array[$nkey[$i]][$j]."</a>$cl_strong";
						echo "</ul>";
						$j++;						
					}
						
        		}else{					
					echo "\n\t<li>$op_strong<a href=\"$racine?s=$i\">".$array[$nkey[$i]]."</a>$cl_strong";
				}
				echo "</li>\n";
        		
        		$i++;
    		}
			
		*/ ?>
<div class="nav">
	<li><a id="<?php  echo ($display=='' ? 'current':'') ?>" href="cdr.php?">Call Logs</a></li>
	<li><a id="<?php  echo ($display=='1' ? 'current':'') ?>" href="cdr.php?display=1">Call Compare</a></li>
	<li><a id="<?php  echo ($display=='2' ? 'current':'') ?>" href="cdr.php?display=2">Download Reports</a></li>
</div>

<div class="content">
		<!--	</ul> -->
		<!--</div>-->

		<!-- leftside END -->

		<!-- content BEGIN -->
		<!--<div id="fedora-middle-two">
			<div class="fedora-corner-tr">&nbsp;</div>
			<div class="fedora-corner-tl">&nbsp;</div>
			<div id="fedora-content">-->



<?php switch($display) {
	default: 
?>
	<br>
	<h2>Call Logs</h2>
	<?php require("call-log.php");?>

<?php 
	break;
	case '1';
?>
	<br>
	<h2>Call Compare</h2>
	<?php require("call-comp.php");?>

<?php 
	break;
	case '2';
	
	if ($_REQUEST['clear'] == "yes") {
		copy("/dev/null","/var/log/asterisk/cdr-csv/Master.csv");
	}
	
	//copy the current report to this dir
	copy("/var/log/asterisk/cdr-csv/Master.csv","Master.csv");
	

?>

	<br>
	<h2>Download Reports</h2>

	<li style="font-weight:bold;margin-bottom:10px;"><a href="Master.csv">Download current call detail reports</a><br>
	<li style="font-weight:bold;margin-bottom:10px;"><a href="cdr.php?display=2&clear=yes">Clear the call detail reports</a> (remove all entries & start over)

<?php 
	break;
?>
   
<?php }?>

</div>

	</body>
</html>
