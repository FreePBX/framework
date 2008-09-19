<?php
include_once(dirname(__FILE__) . "/../cdr/lib/defines.php");
include_once(dirname(__FILE__) . "/../cdr/lib/Class.Table.php");
include_once(dirname(__FILE__) . "/../cdr/jpgraph_lib/jpgraph.php");
include_once(dirname(__FILE__) . "/../cdr/jpgraph_lib/jpgraph_line.php");

// this variable specifie the debug type (0 => nothing, 1 => sql result, 2 => boucle checking, 3 other value checking)
$FG_DEBUG = 0;


getpost_ifset(array('min_call', 'fromstatsday_sday', 'days_compare', 'fromstatsmonth_sday', 'dsttype', 'srctype', 'clidtype', 'channel', 'resulttype', 'dst', 'src', 'clid', 'userfieldtype', 'userfield', 'accountcodetype', 'accountcode'));


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

$FG_TABLE_COL[]=array ("Calldate", "calldate", "18%", "center", "SORT", "19");
$FG_TABLE_COL[]=array ("Channel", "channel", "13%", "center", "", "30");
$FG_TABLE_COL[]=array ("Source", "src", "10%", "center", "", "30");
$FG_TABLE_COL[]=array ("Clid", "clid", "12%", "center", "", "30");
$FG_TABLE_COL[]=array ("Lastapp", "lastapp", "8%", "center", "", "30");

$FG_TABLE_COL[]=array ("Lastdata", "lastdata", "12%", "center", "", "30");
$FG_TABLE_COL[]=array ("Dst", "dst", "9%", "center", "SORT", "30");
//$FG_TABLE_COL[]=array ("Serverid", "serverid", "10%", "center", "", "30");
$FG_TABLE_COL[]=array ("Disposition", "disposition", "9%", "center", "", "30");
$FG_TABLE_COL[]=array ("Duration", "duration", "6%", "center", "SORT", "30");


$FG_TABLE_DEFAULT_ORDER = "calldate";
$FG_TABLE_DEFAULT_SENS = "DESC";

// This Variable store the argument for the SQL query
$FG_COL_QUERY='calldate, duration';
$FG_COL_QUERY_GRAPH='calldate, duration';

// The variable LIMITE_DISPLAY define the limit of record to display by page
$FG_LIMITE_DISPLAY=100;

// Number of column in the html table
$FG_NB_TABLE_COL=count($FG_TABLE_COL);





if ($FG_DEBUG == 3) echo "<br>Table : $FG_TABLE_NAME  	- 	Col_query : $FG_COL_QUERY";
$instance_table = new Table($FG_TABLE_NAME, $FG_COL_QUERY);
$instance_table_graph = new Table($FG_TABLE_NAME, $FG_COL_QUERY_GRAPH);


if ( is_null ($order) || is_null($sens) ){
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

  if ($_GET['before']) {
    if (strpos($SQLcmd, 'WHERE') > 0) { 	$SQLcmd = "$SQLcmd AND ";
    }else{     								$SQLcmd = "$SQLcmd WHERE "; }
    $SQLcmd = "$SQLcmd calldate<'".addslashes($_GET['before'])."'";
  }
  if ($_GET['after']) {    if (strpos($SQLcmd, 'WHERE') > 0) {      $SQLcmd = "$SQLcmd AND ";
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

 

if (DB_TYPE == "postgres"){	
	if (isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) 
	$date_clause.=" AND calldate < date'$fromstatsmonth_sday-$fromstatsday_sday'+ INTERVAL '1 DAY' AND calldate >= date'$fromstatsmonth_sday-$fromstatsday_sday' - INTERVAL '$days_compare DAY'";
}else{
	if (isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) $date_clause.=" AND calldate < ADDDATE('$fromstatsmonth_sday-$fromstatsday_sday',INTERVAL 1 DAY) AND calldate >= SUBDATE('$fromstatsmonth_sday-$fromstatsday_sday',INTERVAL $days_compare DAY)";  
}
	
  
if (strpos($SQLcmd, 'WHERE') > 0) { 
	$FG_TABLE_CLAUSE = substr($SQLcmd,6).$date_clause; 
}elseif (strpos($date_clause, 'AND') > 0){
	$FG_TABLE_CLAUSE = substr($date_clause,5); 
}

if ($FG_DEBUG == 3) echo $FG_TABLE_CLAUSE;

/* --AMP BEGIN-- */
//enforce restrictions for this AMP User
session_start();
$AMP_CLAUSE = $_SESSION['AMP_SQL'];
if (!isset($AMP_CLAUSE)) {
        $AMP_CLAUSE = " AND src = 'NeverReturnAnything'";
}
$FG_TABLE_CLAUSE .= $AMP_CLAUSE;
/* --AMP END-- */


//$list = $instance_table -> Get_list ($FG_TABLE_CLAUSE, $order, $sens, null, null, null, null);


$list_total = $instance_table_graph -> Get_list ($FG_TABLE_CLAUSE, 'calldate', 'ASC', null, null, null, null);


/**************************************/


$table_graph=array();
$table_graph_hours=array();
$numm=0;
foreach ($list_total as $recordset){
		$numm++;
		$mydate= substr($recordset[0],0,10);
		$mydate_hours= substr($recordset[0],0,13);
		//echo "$mydate<br>";
		if (is_array($table_graph_hours[$mydate_hours])){
			$table_graph_hours[$mydate_hours][0]++;
			$table_graph_hours[$mydate_hours][1]=$table_graph_hours[$mydate_hours][1]+$recordset[1];
		}else{
			$table_graph_hours[$mydate_hours][0]=1;
			$table_graph_hours[$mydate_hours][1]=$recordset[1];
		}
		
		
		if (is_array($table_graph[$mydate])){
			$table_graph[$mydate][0]++;
			$table_graph[$mydate][1]=$table_graph[$mydate][1]+$recordset[1];
		}else{
			$table_graph[$mydate][0]=1;
			$table_graph[$mydate][1]=$recordset[1];
		}
		
}

//print_r($table_graph_hours);
//exit();

$mmax=0;
$totalcall==0;
$totalminutes=0;
foreach ($table_graph as $tkey => $data){	
	if ($mmax < $data[1]) $mmax=$data[1];
	$totalcall+=$data[0];
	$totalminutes+=$data[1];
}




/************************************************/


$datax1 = array_keys($table_graph_hours);
$datay1 = array_values ($table_graph_hours);

//$days_compare // 3
$nbday=0;  // in tableau_value and tableau_hours to select the day in which you store the data
//$min_call=0; // min_call variable : 0 > get the number of call 1 > number minutes


$table_subtitle[]="Statistic : Number of call by Hours";
$table_subtitle[]="Statistic : Minutes by Hours";


$table_colors[]="green@0.3";
$table_colors[]="blue@0.3";
$table_colors[]="red@0.3";
$table_colors[]="yellow@0.3";
$table_colors[]="purple@0.3";






/*$table_graph_hours = array();
$table_graph_hours["2004-01-08 15"] = array (100, 15);
$table_graph_hours["2004-01-08 16"] = array (100, 15);
$table_graph_hours["2004-01-08 17"] = array (100, 15);
*/
$jour = substr($datax1[0],8,2); //le jour courant 
$legend[0] = substr($datax1[0],0,10); //l

//print_r ($table_graph_hours);
// Create the graph to compare the day
// extract all minutes/nb call for each hours 
foreach ($table_graph_hours as $key => $value) {
	
	$jour_suivant = substr($key,8,2);
	
	if($jour_suivant != $jour) {
		  $nbday++; 
		  $legend[$nbday] = substr($key,0,10);
		  $jour = $jour_suivant;
	}
  
	
	$heure = intval(substr($key,11,2));

	if ($min_call == 0) $div = 1; else $div = 60;

	$tableau_value[$nbday][$heure] = $value[$min_call]/$div;
}



// je remplie les cases vide par des 0
for ($i=0; $i<=$nbday; $i++)
      for ($j=0; $j<24; $j++)
              if (!isset($tableau_value[$i][$j])) $tableau_value[$i][$j]=0;

//Je remplace les 0 par null pour pour les heures 
$i = 23;
while ($tableau_value[$nbday][$i] == 0) {
      $tableau_value[$nbday][$i] = null;
      $i--;
}


  


//print_r($tableau_value);
//print_r($tableau_hours);

/*echo "<br>nb tableau_value:".count($tableau_value);
echo "<br>nb tableau_hours:".count($tableau_hours);
print_r($tableau_value[0]);
echo "<br><br>";
print_r($tableau_hours[0]);
echo "<br><br>";
*/


foreach ($datay1 as $tkey => $data){
	$dataz1[]=$data[1];
	$dataz2[]=$data[0];
	
	
}
/*$datay1 = array(2,6,7,12,13,18);
echo "<br>nb x1:".count($datax1);
echo "<br>nb z1:".count($dataz1);
print_r($datax1);
echo "<br><br>";
print_r($dataz1);
echo "<br><br>";
print_r($datay1);*/

//print_r($dataz1);
//$dataz1 = array(2,6,7,12,13,2,6,7,12,13,2,6,7,12,13,2,6,7,12,13,2,6,7,12,13);
//print_r($dataz1);
//$datax1 = array(5,12,12,19,25,20);

// Setup the graph
$graph = new Graph(750,450);
$graph->SetMargin(40,40,45,90); //droit,gauche,haut,bas
$graph->SetMarginColor('white');
$graph->SetScale("linlin");

// Hide the frame around the graph
$graph->SetFrame(false);

// Setup title
$graph->title->Set("Graphic");
//$graph->title->SetFont(FF_VERDANA,FS_BOLD,14);

// Note: requires jpgraph 1.12p or higher
$graph->SetBackgroundGradient('#FFFFFF','#CDDEFF:0.8',GRAD_HOR,BGRAD_PLOT);
$graph->tabtitle->Set($table_subtitle[$min_call]);
$graph->tabtitle->SetWidth(TABTITLE_WIDTHFULL);

// Enable X and Y Grid
$graph->xgrid->Show();
$graph->xgrid->SetColor('gray@0.5');
$graph->ygrid->SetColor('gray@0.5');

$graph->yaxis->HideZeroLabel();
$graph->xaxis->HideZeroLabel();
$graph->ygrid->SetFill(true,'#EFEFEF@0.5','#CDDEFF@0.5');


//$graph->xaxis->SetTickLabels($tableau_hours[0]);

// initialisaton fixe de AXE X
$tableau_hours[0] = array("","01","02","03","04","05","06","07","08","09","10","11","12","13","14","15","16","17","18","19","20","21","22","23");
$graph->xaxis->SetTickLabels($tableau_hours[0]);  

// Setup X-scale
//$graph->xaxis->SetTickLabels($tableau_hours[0]);
$graph->xaxis->SetLabelAngle(90);

// Format the legend box
$graph->legend->SetColor('navy');
$graph->legend->SetFillColor('gray@0.8');
$graph->legend->SetLineWeight(1);
//$graph->legend->SetFont(FF_ARIAL,FS_BOLD,8);
$graph->legend->SetShadow('gray@0.4',3);
$graph->legend->SetAbsPos(15,130,'right','bottom');

// Create the line plots

/*$p1 = new LinePlot($datax1);
$p1->SetColor("red");
$p1->SetFillColor("yellow@0.5");
$p1->SetWeight(2);
$p1->mark->SetType(MARK_IMG_DIAMOND,5,0.6);
$p1->SetLegend('2006');
$graph->Add($p1);
*/
for ($indgraph=0;$indgraph<=$nbday;$indgraph++){
	
	$p2[$indgraph] = new LinePlot($tableau_value[$indgraph]);
	$p2[$indgraph]->SetColor($table_colors[$indgraph]);
	$p2[$indgraph]->SetWeight(2);
	$p2[$indgraph]->SetLegend($legend[$indgraph]);
	//$p2->mark->SetType(MARK_IMG_MBALL,'red');
	$graph->Add($p2[$indgraph]);
	
}

// Add a vertical line at the end scale position '7'
//$l1 = new PlotLine(VERTICAL,7);
//$graph->Add($l1);

// Output the graph
$graph->Stroke();



?>
