<?php /* $Id$ */

function getpost_ifset($test_vars)
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


getpost_ifset(array('s', 't'));


$array = array ("CDR", "CONTACT");
$s = $s ? $s : 0;
$section="section$s$t";

$racine=$PHP_SELF;
$update = "24 March 2004";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>		
		<title>Asterisk CDR</title>
		<meta http-equiv="Content-Type" content="text/html">
		<link rel="stylesheet" type="text/css" media="print" href="/css/print.css">
		<style type="text/css" media="screen">
			@import url("css/layout.css");
			@import url("css/content.css");
			@import url("css/docbook.css");
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
		</div>

		<!-- leftside END -->

		<!-- content BEGIN -->
		<div id="fedora-middle-two">
			<div class="fedora-corner-tr">&nbsp;</div>
			<div class="fedora-corner-tl">&nbsp;</div>
			<div id="fedora-content">



<?php if ($section=="section0"){?>

<h1>
 <center>CDR Application</center>
</h1>
<br>
This application is a Graphical Interface to display Asterisk CDR.<br/>
The aim of this application is to make easier the analyse of the Asterisk CDR.<br/>
You will be able to see quickly all your stat by days and  also to compare them.<br/>
<br/>
- Support mysql & postgresql<br/>
- need GD library and jpgraph_lib (included here)<br/>
<br/><br/>

<b>Updates :</b><br>

<ul>

	<li>Don't need register_globals anymore.<br/></li> 
	
	<li>Keep all the posted data, through all the link, now it's possible to use some criteria and browse them by pages.<br/></li> 
	
	<li>Possibility to get the result by minutes and by secondes!<br/></li> 
</ul>

<br/><br/>


<b>Installation :</b>

<br><br>Edit defines.php files and setup the different parameters .
<br/>



<ul>

<li><b>WEBROOT</b>: This is the root URL of the application.<br/>Example: http://youdomain.com/asterisk-stat/</li> 

<li><b>FSROOT</b>: This is the server path whose contain application.<br/>Example: /home/users/asterisk-stat/</li> 

<li><b>HOST</b>: This is the Database host name. <br/>Example: localhost</li> 
<li><b>PORT</b>: Database port.<br/>Example: 5432</li> 
<li><b>USER</b>: User with access to the database.<br/>Example: username</li> 
<li><b>PASS</b>: Database password of the user.<br/>Example: password</li> 
<li><b>DBNAME</b>: Name of the Database.<br/>Example: asteriskcdr</li> 
<li><b>DB_TYPE</b>: Database type.<br/>support: mysql and postgres</li> 

<li><b>DB_TABLENAME</b>: Table of the database containing the CDR.<br/>Example: cdrtable</li> 

<li><b>appli_list</b>: PHP array used to associate extension to a name used full if you want give more meaning when you display the stat
<br/>$appli_list['4677']=array("Voicemail");<br>$appli_list['6544']=array("Conference-MeetMe");</li> 


</ul>



<br/><br/>
If you have any comment/idea, please send it to me at "areski at e-group dot org" :)<br/>


<br>
<h3>Download</h3>
TAR-GZ : <a href="./asterisk-stat-v1_1.tar.gz">asterisk-stat</a>
<br><br>
<h3>Screen-shot</h3>
<a href="images/call-logs.png"><img src="images/th_call-logs.png" width="576" alt="CDR (Call Logs)"></a>
<br><br>
<a href="images/call-compare.png"><img src="images/th_call-compare.png"  alt="CDR (Call Compare)"></a>

<br><br>

						
						

<?php }elseif ($section=="section1") {?>
		<h1>Contact Section...</h1>        
		<br>
        <table width="90%">
          
          <tr> 
            <td background="./images/div_grad.gif">&nbsp;</td>
          </tr>
		  <tr> 
            <td>Arezqui Bela&iuml;d (areski at e-group dot org)</td>
          </tr>                    
          
        </table>
		<br><br><em><strong>Last update:</strong></em> <?php =$update?><br>


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
		</div>
		<!-- footer END -->
	</body>
</html>
