<?php /* $Id: graph_pie.php 6816 2008-09-19 18:33:18Z p_lindheimer $ */
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
	include_once('/etc/asterisk/freepbx.conf');
}
defined('FREEPBX_IS_AUTH') OR die('No direct script access allowed');
include_once(dirname(__FILE__) . "/../lib/defines.php");
include_once(dirname(__FILE__) . "/../lib/Class.Table.php");
include_once(dirname(__FILE__) . "/../jpgraph_lib/jpgraph.php");
include_once(dirname(__FILE__) . "/../jpgraph_lib/jpgraph_pie.php");
include_once(dirname(__FILE__) . "/../jpgraph_lib/jpgraph_pie3d.php");


/*
NOTE GENERER LES SOUSTRACTIONS SUR LES DATES NOUS-MEME
RAPIDE
cdrasterisk=> SELECT sum(duration) FROM cdr WHERE calldate < '2005-02-01' AND calldate >= '2005-01-01';
   sum
----------
 69076793
(1 row)
 
 TRES LENT
cdrasterisk=> SELECT sum(duration) FROM cdr WHERE calldate < date '2005-02-01'  - interval '0 months' AND calldate >=  date '2005-02-01'  - interval '1 months' ;
   sum
----------
 69076793
(1 row)
*/

getpost_ifset(array('before', 'after', 'months_compare', 'min_call', 'fromstatsday_sday', 'days_compare', 'fromstatsmonth_sday', 'dsttype', 'srctype', 'clidtype', 'channel', 'resulttype', 'dst', 'src', 'clid', 'userfieldtype', 'userfield', 'accountcodetype', 'accountcode'));

$FG_DEBUG = 0;
$months = Array ( 0 => 'Jan', 1 => 'Feb', 2 => 'Mar', 3 => 'Apr', 4 => 'May', 5 => 'Jun', 6 => 'Jul', 7 => 'Aug', 8 => 'Sep', 9 => 'Oct', 10 => 'Nov', 11 => 'Dec' );

if (!isset($months_compare)) $months_compare = 3;
if (!isset($fromstatsmonth_sday)) $fromstatsmonth_sday = date("Y-m");	



//print_r (array_reverse ($mylegend));

// http://localhost/Asterisk/asterisk-stat-v1_4/graph_stat.php?min_call=0&fromstatsday_sday=11&days_compare=2&fromstatsmonth_sday=2005-02&dsttype=1&srctype=1&clidtype=1&channel=&resulttype=&dst=1649&src=&clid=&userfieldtype=1&userfield=&accountcodetype=1&accountcode=

// The variable FG_TABLE_NAME define the table name to use
$FG_TABLE_NAME=DB_TABLENAME;

//$link = DbConnect();
$DBHandle  = DbConnect();

// The variable Var_col would define the col that we want show in your table
// First Name of the column in the html page, second name of the field
$FG_TABLE_COL = array();

/*******
Calldate Clid Src Dst Dcontext Channel Dstchannel Lastapp Lastdata Duration Billsec Disposition Amaflags Accountcode Uniqueid Serverid
*******/


// The variable LIMITE_DISPLAY define the limit of record to display by page
$FG_LIMITE_DISPLAY=100;

// Number of column in the html table
$FG_NB_TABLE_COL=count($FG_TABLE_COL);



$FG_COL_QUERY = ' sum(duration) ';
if ($FG_DEBUG == 3) echo "<br>Table : $FG_TABLE_NAME  	- 	Col_query : $FG_COL_QUERY";
$instance_table_graph = new Table($FG_TABLE_NAME, $FG_COL_QUERY);


if ((isset($order) && is_null ($order)) || isset($sens) && is_null($sens) ){
	$order = $FG_TABLE_DEFAULT_ORDER;
	$sens  = $FG_TABLE_DEFAULT_SENS;
}


	
  function do_field($sql,$fld){
  		$fldtype = $fld.'type';
		global $$fld;
		global $$fldtype;
        if (isset($$fld) && ($$fld!='')){
                if (strpos($sql,'WHERE') > 0){
                        $sql = "$sql AND ";
                }else{
                        $sql = "$sql WHERE ";
                }
				$sql = "$sql $fld";
				if (isset ($$fldtype)){                
                        switch ($$fldtype) {
							case 1:	$sql = "$sql='".$$fld."'";  break;
							case 2: $sql = "$sql LIKE '".$$fld."%'";  break;
							case 3: $sql = "$sql LIKE '%".$$fld."%'";  break;
							case 4: $sql = "$sql LIKE '%".$$fld."'";
						}
                }else{ $sql = "$sql LIKE '%".$$fld."%'"; }
		}
        return $sql;
  }  
  $SQLcmd = '';

  if (isset($_GET['before'])) {
    if (strpos($SQLcmd, 'WHERE') > 0) { 	$SQLcmd = "$SQLcmd AND ";
    }else{     								$SQLcmd = "$SQLcmd WHERE "; }
    $SQLcmd = "$SQLcmd calldate<'".addslashes($_GET['before'])."'";
  }
  if (isset($_GET['after'])) {    if (strpos($SQLcmd, 'WHERE') > 0) {      $SQLcmd = "$SQLcmd AND ";
  } else {      $SQLcmd = "$SQLcmd WHERE ";    }
    $SQLcmd = "$SQLcmd calldate>'".addslashes($_GET['after'])."'";
  }
  $SQLcmd = do_field($SQLcmd, 'clid');
  $SQLcmd = do_field($SQLcmd, 'src');
  $SQLcmd = do_field($SQLcmd, 'dst');
  $SQLcmd = do_field($SQLcmd, 'channel');
  
  $SQLcmd = do_field($SQLcmd, 'userfield');
  $SQLcmd = do_field($SQLcmd, 'accountcode');

$date_clause='';

$min_call= intval($min_call);
if (($min_call!=0) && ($min_call!=1)) $min_call=0;

if (!isset($fromstatsday_sday)){	
	$fromstatsday_sday = date("d");
	$fromstatsmonth_sday = date("Y-m");	
}

if (!isset($days_compare) ){		
	$days_compare=2;
}

 

list($myyear, $mymonth)= split ("-", $fromstatsmonth_sday);

$mymonth = $mymonth +1;
if ($mymonth==13) {
		$mymonth=1;		
		$myyear = $myyear + 1;
}


for ($i=0; $i<=$months_compare; $i++){
	// creer un table legende	
	$current_mymonth = $mymonth -$i;
	if ($current_mymonth<=1) {
		$current_mymonth=$current_mymonth+12;		
		$minus_oneyar = 1;
	} else {
		$minus_oneyar = 0;
	}
	$current_myyear = $myyear - $minus_oneyar;
	
	$current_mymonth2 = $mymonth -$i -1;
	if ($current_mymonth2<=0) {
		$current_mymonth2=$current_mymonth2+12;		
		$minus_oneyar = 1;
	}
	$current_myyear2 = $myyear - $minus_oneyar;

	//echo "<br>$current_myyear-".sprintf("%02d",intval($current_mymonth));
	
	
	
	
	//echo '<br>'.$date_clause;
	
	if (DB_TYPE == "postgres"){	
		$date_clause= " AND calldate >= '$current_myyear2-".sprintf("%02d",intval($current_mymonth2))."-01' AND calldate < '$current_myyear-".sprintf("%02d",intval($current_mymonth))."-01'";				
	}else{
		$date_clause= " AND calldate >= '$current_myyear2-".sprintf("%02d",intval($current_mymonth2))."-01' AND calldate < '$current_myyear-".sprintf("%02d",intval($current_mymonth))."-01'";		
	}
		
	  
	if (strpos($SQLcmd, 'WHERE') > 0) { 
		$FG_TABLE_CLAUSE = substr($SQLcmd,6).$date_clause; 
	}elseif (strpos($date_clause, 'AND') > 0){
		$FG_TABLE_CLAUSE = substr($date_clause,5); 
	}
	
	if ($FG_DEBUG == 3) echo $FG_TABLE_CLAUSE;
	
	/* --AMP BEGIN-- */
	//enforce restrictions for this AMP User
//	session_start();
	$AMP_CLAUSE = $_SESSION['AMP_SQL'];
	if (!isset($AMP_CLAUSE)) {
		$AMP_CLAUSE = " AND src = 'NeverReturnAnything'";
	}
	$FG_TABLE_CLAUSE .= $AMP_CLAUSE;
	/* --AMP END-- */	
	
	$list_total = $instance_table_graph -> Get_list ($FG_TABLE_CLAUSE, null, null, null, null, null, null);
	$data[] = $list_total[0][0];	
	$mylegend[] = $months[$current_mymonth2-1]." $current_myyear : ".intval($list_total[0][0]/60)." min";

}
//print_r($data);

/**************************************/




//$data = array(40,60,21,33, 10, NULL);

$graph = new PieGraph(475,200,"auto");
$graph->SetShadow();

$graph->title->Set("Traffic Last $months_compare Months");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$p1 = new PiePlot3D($data);
$p1->ExplodeSlice(0);
$p1->SetCenter(0.35);
//print_r($gDateLocale->GetShortMonth());
//Array ( [0] => Jan [1] => Feb [2] => Mar [3] => Apr [4] => May [5] => Jun [6] => Jul [7] => Aug [8] => Sep [9] => Oct [10] => Nov [11] => Dec )
//$p1->SetLegends($gDateLocale->GetShortMonth());
$p1->SetLegends($mylegend);


// Format the legend box
$graph->legend->SetColor('navy');
$graph->legend->SetFillColor('gray@0.8');
$graph->legend->SetLineWeight(1);
//$graph->legend->SetFont(FF_ARIAL,FS_BOLD,8);
$graph->legend->SetShadow('gray@0.4',3);
//$graph->legend->SetAbsPos(10,80,'right','bottom');


$graph->Add($p1);
$graph->Stroke();




?>
