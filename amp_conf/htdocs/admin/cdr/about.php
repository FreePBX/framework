<?php

function getpost_ifset($test_vars)
{
	if (!is_array($test_vars)) {
		$test_vars = array($test_vars);
	}
	foreach($test_vars as $test_var) { 
		if (isset($_POST[$test_var])) { 
			global $$test_var;
			$$test_var = addslashes($_POST[$test_var]); 
		} elseif (isset($_GET[$test_var])) {
			global $$test_var; 
			$$test_var = addslashes($_GET[$test_var]);
		}
	}
}


getpost_ifset(array('s', 't'));


$array = array ("INFO", "CONTACT");
$s = $s ? $s : 0;
$section="section$s$t";

$racine=$_SERVER['PHP_SELF'];
$update = "03 March 2005";

$paypal="OK"; //OK || NOK
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>		
		<title>Asterisk CDR</title>
		<meta http-equiv="Content-Type" content="text/html">
		<link rel="stylesheet" type="text/css" media="print" href="common/print.css">
		<SCRIPT LANGUAGE="JavaScript" SRC="common/encrypt.js"></SCRIPT>
		<style type="text/css" media="screen">
			@import url("common/layout.css");
			@import url("common/content.css");
			@import url("common/docbook.css");
		</style>
		<meta name="MSSmartTagsPreventParsing" content="TRUE">
	</head>
	<body>
	
	

	
	
		<!-- header BEGIN -->
		<div id="fedora-header">
			
			<div id="fedora-header-logo">
				 <table border="0" cellpadding="0" cellspacing="0"><tr><td><img src="images/asterisk.gif"  alt="CDR (Call Detail Records)"></td><td>
				 <H1><font color=#990000>&nbsp;&nbsp;&nbsp;CDR (Call Detail Records)</font></H1></td></tr></table>
			</div>

		</div>
		<div id="fedora-nav"></div>
		<!-- header END -->
		
		<!-- leftside BEGIN -->
		<div id="fedora-side-left">
		<div id="fedora-side-nav-label">Site Navigation:</div>	<ul id="fedora-side-nav">
		<?php 
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
			
		?>

			</ul>
			
			<?php  if ($paypal=="OK"){?>
		<center>
			<br><br>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="info@areski.net">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="currency_code" value="EUR">
<input type="hidden" name="tax" value="0">
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
</form>
</center>
			<?php  } ?>
		</div>

		<!-- leftside END -->

		<!-- content BEGIN -->
		<div id="fedora-middle-two">
			<div class="fedora-corner-tr">&nbsp;</div>
			<div class="fedora-corner-tl">&nbsp;</div>
			<div id="fedora-content">



<?php if ($section=="section0"){?>

<h1>
 <center>Asterisk-Stat : CDR Analyser</center>
</h1>
<br>
Asterisk-Stat is providing different reports & Graph to allow <br>
the Asterisk-admin to analyse quickly and easily the traffic on their Asterisk server.<br/>
All the graphic & reports are based over the CDR database.
<br/>


<br/>


<br/><br/>

<br/>
<b>LAST RELEASE : V2.0 (03 March 2005)</b><br>

<br/><br/>

<b>FEATURES :</b><br>
<ul>
	<li>- CDR REPORT (MONTHLY or DAILY)<br/></li> 
	<li>- MONTHLY TRAFFIC<br/></li> 
	<li>- DAILY LOAD<br/></li> 
	<li>- COMPARE CALL LOAD WITH PREVIOUS DAYS<br/></li> 
	<li>- MANY CRITERIAS TO DEFINE THE REPORT<br/></li> 	
	<LI>- EXPORT CDR REPORT TO PDF<BR/></LI> 
	<LI>- EXPORT CDR REPORT TO CSV<BR/></LI> 		
	<LI>- SUPPORT MYSQL & POSTGRESQL<BR/></LI> 		
	<LI>- MANY OTHERS :)<BR/></LI>
</ul>
<br>
<b>REQUIREMENTS :</b><br>
<ul>
	<li>- APACHE / HTTP SERVER<BR/></LI> 
	<li>- PHP<br/></li> 
	<li>- POSTGRESQL OR MYSQL<br/></li> 	
	<li>- PHP-PGSQL OR PHP-MYSQL<br/></li> 
	<LI>- NEED GD LIBRARY <BR/></LI>
	<LI>- JPGRAPH_LIB (included)<BR/></LI>
</ul>

<br>
<b>ADVICES :</b><br>
<ul>
	<li>- IMPROVE SPEED RESULT WITH INDEX:<br>
			POSTGRESQL : <i>CREATE INDEX calldate_ind ON cdr USING btree (calldate)</i><BR/>
			MYSQL : <i>ALTER TABLE `cdr` ADD INDEX ( `calldate` )</i> <BR/></LI> 
	<li>-  [OPTIONAL]<br/>
			POSTGRESQL :  <i>CREATE INDEX dst_ind ON cdr USING btree (dst)</i><br/>
			POSTGRESQL :  <i>CREATE INDEX accountcode_ind ON cdr USING btree (accountcode)</i><br/>
			MYSQL :  <i>ALTER TABLE `cdr` ADD INDEX ( `dst` )</i><br/>
			MYSQL :  <i>ALTER TABLE `cdr` ADD INDEX ( `accountcode` ) </i><br/>
	</li> 
</ul>

<br>
<b>TESTED WITH :</b><br>

<ul>
	<li>- PSQL (PostgreSQL) 7.2.4<br></LI> 
	<li>- MYSQL  Ver 11.18 Distrib 3.23.58<br/>	</LI> 
</ul>


<br>
<b>INSTALL :</b>

<br><br>Edit defines.php files
<br/>



<ul>

<li><b>WEBROOT</b>: This is the root URL of the application.<br/>Example: http://youdomain.com/asterisk-stat/</li> 

<li><b>FSROOT</b>: This is the server path which contain the application.<br/>Example: /home/users/asterisk-stat/</li> 

<li><b>HOST</b>: This is the Database host name. <br/>Example: localhost</li> 
<li><b>PORT</b>: Database port.<br/>Example: 5432</li> 
<li><b>USER</b>: Username to access to the database.<br/>Example: username</li> 
<li><b>PASS</b>: Database password of the user.<br/>Example: password</li> 
<li><b>DBNAME</b>: Name of the Database.<br/>Example: asteriskcdr</li> 
<li><b>DB_TYPE</b>: Database type.<br/>support: mysql and postgres</li> 

<li><b>DB_TABLENAME</b>: Table of the database containing the CDR.<br/>Example: cdrtable</li> 

<li><b>appli_list</b>: PHP array used to associate extension to a name. This can be useful if you want to give more signification during the CDR browsing
<br/>$appli_list['4677']=array("Voicemail");<br>$appli_list['6544']=array("Conference-MeetMe");</li> 


</ul>


<br>
<h3>DOWNLOAD :</h3>
TAR-GZ : <a href="./asterisk-stat-v2.tar.gz">asterisk-stat V 2.0</a>
<br><br>

<hr>
If you have comments or ideas to improve the CDR-ANALYSER, please <a href='javascript:bite("3721 945 4728 2762 3565 3554 2008 1380 654 3721 3554 4468 3007 3877 4828 654",5123,2981)'>drop me an email</a> :)<br/>

<br/><br/>

<br>
<h3>Screen-shot</h3>

<a href="screenshot/screenshot01.png"><img src="screenshot/screenshot01.png" width="576"></a>
<br><br>
<a href="screenshot/screenshot02.png"><img src="screenshot/screenshot02.png" width="576"></a>
<br><br>
<a href="screenshot/screenshot03.png"><img src="screenshot/screenshot03.png" width="576"></a>
<br><br>
<a href="screenshot/screenshot04.png"><img src="screenshot/screenshot04.png" width="576"></a>
<br><br>
<a href="screenshot/screenshot05.png"><img src="screenshot/screenshot05.png" width="576"></a>
<br><br>
<a href="screenshot/screenshot06.png"><img src="screenshot/screenshot06.png" width="576"></a>

<br><br>


<?php }elseif ($section=="section1") {?>
		<h1>Contact</h1>        		
        <table width="90%">
          
		  <tr> 
            <td>
				<h3>Arezqui Bela&iuml;d <br> <i>Barcelona - Belgium</i></h3>				
				<br>
				<a href='javascript:bite("3721 945 4728 2762 3565 3554 2008 1380 654 3721 3554 4468 3007 3877 4828 654",5123,2981)'>Click to email me</a>
				<br><br><i>Feel free to send me your suggestions to improve the application ;)</i>
            </td>
          </tr>          
          
        </table>
		<br><br><em><strong>Last update:</strong></em> <?php echo $update?><br>



<?php }else{?>
	<h1>Coming soon ...</h1>
   
<?php }?>
		</div>

			<div class="fedora-corner-br">&nbsp;</div>
			<div class="fedora-corner-bl">&nbsp;</div>
		</div>
		<!-- content END -->
		
		<!-- footer BEGIN -->
		<div id="fedora-footer">

			<br>
			<?php 
				$fp = fopen("counter.txt","r");
				$count = fread ($fp, filesize ("counter.txt"));
				fclose($fp);
				$count = intval($count);
				$count++;
				$fp = fopen("counter.txt","w+");
				fputs($fp, $count);
				fclose($fp);
				echo "Hits: $count";
			?>


		</div>
		<!-- footer END -->
	</body>
</html>
