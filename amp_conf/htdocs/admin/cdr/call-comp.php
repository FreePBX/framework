<?php /* $Id$ */
include ("./lib/defines.php");
include ("./lib/Class.Table.php");


getpost_ifset(array('current_page', 'fromstatsday_sday', 'fromstatsmonth_sday', 'days_compare', 'min_call'));


if (!isset ($current_page) || ($current_page == "")){	
		$current_page=0; 
	}

// this variable specifie the debug type (0 => nothing, 1 => sql result, 2 => boucle checking, 3 other value checking)
$FG_DEBUG = 0;

// The variable FG_TABLE_NAME define the table name to use
$FG_TABLE_NAME=DB_TABLENAME;



// THIS VARIABLE DEFINE THE COLOR OF THE HEAD TABLE
$FG_TABLE_HEAD_COLOR = "#D1D9E7";


$FG_TABLE_EXTERN_COLOR = "#7F99CC"; //#CC0033 (Rouge)
$FG_TABLE_INTERN_COLOR = "#EDF3FF"; //#FFEAFF (Rose)




// THIS VARIABLE DEFINE THE COLOR OF THE HEAD TABLE
$FG_TABLE_ALTERNATE_ROW_COLOR[] = "#FFFFFF";
$FG_TABLE_ALTERNATE_ROW_COLOR[] = "#F2F8FF";



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
$FG_COL_QUERY='calldate, channel, src, clid, lastapp, lastdata, dst, disposition, duration';
//$FG_COL_QUERY='calldate, channel, src, clid, lastapp, lastdata, dst, serverid, disposition, duration';
$FG_COL_QUERY_GRAPH='calldate, duration';

// The variable LIMITE_DISPLAY define the limit of record to display by page
$FG_LIMITE_DISPLAY=25;

// Number of column in the html table
$FG_NB_TABLE_COL=count($FG_TABLE_COL);

// The variable $FG_EDITION define if you want process to the edition of the database record
$FG_EDITION=true;

//This variable will store the total number of column
$FG_TOTAL_TABLE_COL = $FG_NB_TABLE_COL;
if ($FG_DELETION || $FG_EDITION) $FG_TOTAL_TABLE_COL++;

//This variable define the Title of the HTML table
$FG_HTML_TABLE_TITLE=" - Call Logs - ";

//This variable define the width of the HTML table
$FG_HTML_TABLE_WIDTH="90%";




if ($FG_DEBUG == 3) echo "<br>Table : $FG_TABLE_NAME  	- 	Col_query : $FG_COL_QUERY";
$instance_table = new Table($FG_TABLE_NAME, $FG_COL_QUERY);
$instance_table_graph = new Table($FG_TABLE_NAME, $FG_COL_QUERY_GRAPH);


if ( is_null ($order) || is_null($sens) ){
	$order = $FG_TABLE_DEFAULT_ORDER;
	$sens  = $FG_TABLE_DEFAULT_SENS;
}


if ($_POST['posted']==1){
	
  function do_field($sql,$fld){
        if ($fld && $_POST[$fld]){
                if (strpos($sql,'WHERE') > 0){
                        $sql = "$sql AND ";
                }else{
                        $sql = "$sql WHERE ";
                }
				$sql = "$sql $fld";
                if (array_key_exists($fld.'type', $_POST)){
                        $dType = $_POST[$fld.'type'];
                        switch ($dType) {
							case 1:	$sql = "$sql='$_POST[$fld]'";  break;
							case 2: $sql = "$sql LIKE '$_POST[$fld]%'";  break;
							case 3: $sql = "$sql LIKE '%$_POST[$fld]%'";  break;
							case 4: $sql = "$sql LIKE '%$_POST[$fld]'";
						}
                }else{ $sql = "$sql LIKE '%$_POST[$fld]%'"; }
		}
        return $sql;
  }  
  $SQLcmd = '';

  if ($_POST['before']) {
    if (strpos($SQLcmd, 'WHERE') > 0) { 	$SQLcmd = "$SQLcmd AND ";
    }else{     								$SQLcmd = "$SQLcmd WHERE "; }
    $SQLcmd = "$SQLcmd calldate<'".$_POST['before']."'";
  }
  if ($_POST['after']) {    if (strpos($SQLcmd, 'WHERE') > 0) {      $SQLcmd = "$SQLcmd AND ";
  } else {      $SQLcmd = "$SQLcmd WHERE ";    }
    $SQLcmd = "$SQLcmd calldate>'".$_POST['after']."'";
  }
  $SQLcmd = do_field($SQLcmd, 'clid');
  $SQLcmd = do_field($SQLcmd, 'src');
  $SQLcmd = do_field($SQLcmd, 'dst');
  $SQLcmd = do_field($SQLcmd, 'channel');
  
}


$date_clause='';
// Period (Month-Day)


if (!isset($fromstatsday_sday)){	
	$fromstatsday_sday = date("d");
	$fromstatsmonth_sday = date("Y-m");	
}


if (!isset($days_compare)){		
	$days_compare=2;
}



//if (isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) $date_clause.=" AND calldate <= '$fromstatsmonth_sday-$fromstatsday_sday+23' AND calldate >= SUBDATE('$fromstatsmonth_sday-$fromstatsday_sday',INTERVAL $days_compare DAY)";

if (DB_TYPE == "postgres"){	
	if (isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) $date_clause.=" AND calldate < date'$fromstatsmonth_sday-$fromstatsday_sday'+ INTERVAL '1 DAY' AND calldate >= date'$fromstatsmonth_sday-$fromstatsday_sday' - INTERVAL '$days_compare DAY'";
}else{
	if (isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) $date_clause.=" AND calldate < ADDDATE('$fromstatsmonth_sday-$fromstatsday_sday',INTERVAL 1 DAY) AND calldate >= SUBDATE('$fromstatsmonth_sday-$fromstatsday_sday',INTERVAL $days_compare DAY)";  
}

if ($FG_DEBUG == 3) echo "<br>$date_clause<br>";
/*
Month
fromday today
frommonth tomonth (true)
fromstatsmonth tostatsmonth

fromstatsday_sday
fromstatsmonth_sday
tostatsday_sday
tostatsmonth_sday
*/


  
if (strpos($SQLcmd, 'WHERE') > 0) { 
	$FG_TABLE_CLAUSE = substr($SQLcmd,6).$date_clause; 
}elseif (strpos($date_clause, 'AND') > 0){
	$FG_TABLE_CLAUSE = substr($date_clause,5); 
}


//> function Get_list ($clause=null, $order=null, $sens=null, $field_order_letter=null, $letters = null, $limite=null, $current_record = NULL)
$list = $instance_table -> Get_list ($FG_TABLE_CLAUSE, $order, $sens, null, null, $FG_LIMITE_DISPLAY, $current_page*$FG_LIMITE_DISPLAY);

$list_total = $instance_table_graph -> Get_list ($FG_TABLE_CLAUSE, null, null, null, null, null, null);
if ($FG_DEBUG == 3) echo "<br>Clause : $FG_TABLE_CLAUSE";
//$nb_record = $instance_table -> Table_count ($FG_TABLE_CLAUSE);
$nb_record = count($list_total);
if ($FG_DEBUG >= 1) var_dump ($list);



if ($nb_record<=$FG_LIMITE_DISPLAY){ 
	$nb_record_max=1;
}else{ 
	$nb_record_max=(intval($nb_record/$FG_LIMITE_DISPLAY)+1);
}

if ($FG_DEBUG == 3) echo "<br>Nb_record : $nb_record";
if ($FG_DEBUG == 3) echo "<br>Nb_record_max : $nb_record_max";

?>

<script language="JavaScript" type="text/JavaScript">
<!--
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}

//-->
</script>



<!-- ** ** ** ** ** Part for the research ** ** ** ** ** -->
	<center>
	<FORM METHOD=POST ACTION="<?php =$PHP_SELF?>?s=1&t=1&order=<?php =$order?>&sens=<?php =$sens?>&current_page=<?php =$current_page?>">
	<INPUT TYPE="hidden" NAME="posted" value=1>
	<INPUT TYPE="hidden" NAME="display" value="1">
		<table class="bar-status" width="75%" border="0" cellspacing="1" cellpadding="2" align="center">
			<tbody>
			
			<tr>
        		<td align="left" bgcolor="#000033">					
					<font face="verdana" size="1" color="#ffffff"><b>Select the day</b></font>
				</td>
      			<td align="left" bgcolor="#acbdee">
					<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#acbdee"><tr><td>
	  				<b>From : </b><select name="fromstatsday_sday">
					<?php  
						for ($i=1;$i<=31;$i++){
							if ($fromstatsday_sday==sprintf("%02d",$i)){$selected="selected";}else{$selected="";}
							echo '<option value="'.sprintf("%02d",$i)."\"$selected>".sprintf("%02d",$i).'</option>';
						}
					?>					
					</select>
				 	<select name="fromstatsmonth_sday">
					<?php 	$year_actual = date("Y");  	
						for ($i=$year_actual;$i >= $year_actual-1;$i--)
						{		   
							   $monthname = array( "January", "February","March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
							   if ($year_actual==$i){
									$monthnumber = date("n")-1; // Month number without lead 0.
							   }else{
									$monthnumber=11;
							   }		   
							   for ($j=$monthnumber;$j>=0;$j--){	
										$month_formated = sprintf("%02d",$j+1);
							   			if ($fromstatsmonth_sday=="$i-$month_formated"){$selected="selected";}else{$selected="";}
										echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";				
							   }
						}								
					?>										
					</select>
					</td><td>&nbsp;&nbsp;
					<b>Laps of days to compare :</b> 
				 	<select name="days_compare">
					<option value="4" <?php if ($days_compare=="4"){ echo "selected";}?>>- 4 days</option>
					<option value="3" <?php if ($days_compare=="3"){ echo "selected";}?>>- 3 days</option>
					<option value="2" <?php if (($days_compare=="2")|| !isset($days_compare)){ echo "selected";}?>>- 2 days</option>
					<option value="1" <?php if ($days_compare=="1"){ echo "selected";}?>>- 1 day</option>
					</select>
					</td></tr></table>
	  			</td>
    		</tr>	
			

			<tr>
        		<td class="bar-search" align="left" bgcolor="#555577"> </td>

				<td class="bar-search" align="center" bgcolor="#cddeff">
					<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#cddeff"><tr><td>				
						<b>Graph :</b>
							<select name="min_call">					
							<option value=1 <?php  if ($min_call==1){ echo "selected";}?>>Minutes by hours</option>
							<option value=0 <?php  if (($min_call==0) || !isset($min_call)){ echo "selected";}?>>Number of calls by hours</option>
							</select>
						</td>
						<td align="right">							
							<input type="image"  name="image16" align="top" border="0" src="images/button-search.gif" />
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					</td></tr></table>
	  			</td>
    		</tr>
		</tbody></table>
	</FORM>
</center>





<!-- ** ** ** ** ** Part to display the GRAPHIC ** ** ** ** ** -->
<br><br>

<?php 
if (is_array($list) && count($list)>0){

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

$mmax=0;
$totalcall==0;
$totalminutes=0;
foreach ($table_graph as $tkey => $data){	
	if ($mmax < $data[1]) $mmax=$data[1];
	$totalcall+=$data[0];
	$totalminutes+=$data[1];
}

?>


<!-- TITLE GLOBAL -->
<center>
 <table border="0" cellspacing="0" cellpadding="0" width="80%"><tbody><tr><td align="left" height="30">
		<table cellspacing="0" cellpadding="1" bgcolor="#000000" width="50%"><tbody><tr><td>
			<table cellspacing="0" cellpadding="0" width="100%"><tbody>
				<tr><td bgcolor="#600101" align="left"><font face="verdana" size="1" color="white"><b>TOTAL</b></font></td></tr>
			</tbody></table>
		</td></tr></tbody></table>
 </td></tr></tbody></table>
		  
<!-- FIN TITLE GLOBAL MINUTES //-->
				
<table border="0" cellspacing="0" cellpadding="0" width="80%">
<tbody><tr><td bgcolor="#000000">			
	<table border="0" cellspacing="1" cellpadding="2" width="100%"><tbody>
	<tr>	
		<td align="center" bgcolor="#600101"></td>
    	<td bgcolor="#b72222" align="center" colspan="4"><font face="verdana" size="1" color="#ffffff"><b>ASTERISK MINUTES</b></font></td>
    </tr>
	<tr bgcolor="#600101">
		<td align="right" bgcolor="#b72222"><font face="verdana" size="1" color="#ffffff"><b>DATE</b></font></td>
        <td align="center"><font face="verdana" size="1" color="#ffffff"><b>DURATION</b></font></td>
		<td align="center"><font face="verdana" size="1" color="#ffffff"><b>GRAPHIC</b></font></td>
		<td align="center"><font face="verdana" size="1" color="#ffffff"><b>CALLS</b></font></td>
		<td align="center"><font face="verdana" size="1" color="#ffffff"><b>TMC</b></font></td>
                			
		<!-- LOOP -->
	<?php  		
		$i=0;
		// #ffffff #cccccc
		foreach ($table_graph as $tkey => $data){	
		$i=($i+1)%2;		
		$tmc = $data[1]/$data[0];
		
		$tmc_60 = sprintf("%02d",intval($tmc/60)).":".sprintf("%02d",intval($tmc%60));		
		
		$minutes_60 = sprintf("%02d",intval($data[1]/60)).":".sprintf("%02d",intval($data[1]%60));
		$widthbar= intval(($data[1]/$mmax)*200); 
		
		//bgcolor="#336699" 
	?>
		</tr><tr>
		<td align="right" class="sidenav" nowrap="nowrap"><font face="verdana" size="1" color="#ffffff"><?php =$tkey?></font></td>
		<td bgcolor="<?php =$FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right" nowrap="nowrap"><font face="verdana" color="#000000" size="1"><?php =$minutes_60?> </font></td>
        <td bgcolor="<?php =$FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="left" nowrap="nowrap" width="<?php =$widthbar+60?>">
        <table cellspacing="0" cellpadding="0"><tbody><tr>
        <td bgcolor="#e22424"><img src="images/spacer.gif" width="<?php =$widthbar?>" height="6"></td>
        </tr></tbody></table></td>
        <td bgcolor="<?php =$FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right" nowrap="nowrap"><font face="verdana" color="#000000" size="1"><?php =$data[0]?></font></td>
        <td bgcolor="<?php =$FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right" nowrap="nowrap"><font face="verdana" color="#000000" size="1"><?php =$tmc_60?> </font></td>
     <?php 	 }	 
	 	$total_tmc_60 = sprintf("%02d",intval(($totalminutes/$totalcall)/60)).":".sprintf("%02d",intval(($totalminutes/$totalcall)%60));				
		$total_minutes_60 = sprintf("%02d",intval($totalminutes/60)).":".sprintf("%02d",intval($totalminutes%60));
	 
	 ?>                   	
	</tr>
	<!-- FIN DETAIL -->		
	
				
				<!-- FIN BOUCLE -->

	<!-- TOTAL -->
	<tr bgcolor="#600101">
		<td align="right" nowrap="nowrap"><font face="verdana" size="1" color="#ffffff"><b>TOTAL</b></font></td>
		<td align="center" nowrap="nowrap" colspan="2"><font face="verdana" size="1" color="#ffffff"><b><?php =$total_minutes_60?> </b></font></td>
		<td align="center" nowrap="nowrap"><font face="verdana" size="1" color="#ffffff"><b><?php =$totalcall?></b></font></td>
		<td align="center" nowrap="nowrap"><font face="verdana" size="1" color="#ffffff"><b><?php =$total_tmc_60?></b></font></td>                        
	</tr>
	<!-- FIN TOTAL -->

	  </tbody></table>
	  <!-- Fin Tableau Global //-->

</td></tr></tbody></table>
<?php  }else{ ?>
	<center><h3>No calls in your selection.</h3></center>
<?php  } ?>
	<br>
 	<IMG SRC="graph_stat.php?min_call=<?php =$min_call?>&fromstatsday_sday=<?php =$fromstatsday_sday?>&days_compare=<?php =$days_compare?>&fromstatsmonth_sday=<?php =$fromstatsmonth_sday?>" ALT="Stat Graph">

</center>
