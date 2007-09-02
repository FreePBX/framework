<?php
// admin interface

// Printing menu
?>
<!-- begin menu -->
<?php
$prev_category = '';

if (is_array($fpbx_menu)) {
	$category = Array();
	$sort = Array();
	$sort_name = Array();
	$sort_type = Array();
	// Sorting menu by category and name
	foreach ($fpbx_menu as $key => $row) {
		$category[$key] = $row['category'];
		$sort[$key] = $row['sort'];
		$sort_name[$key] = $row['name'];
		$sort_type[$key] = $row['type'];
	}
	
	if ($fpbx_usecategories) {
		array_multisort(
			$sort_type, SORT_ASC,
			$category, SORT_ASC, 
			$sort, SORT_ASC, SORT_NUMERIC, 
			$sort_name, SORT_ASC, 
			$fpbx_menu
		);
	} else {
		array_multisort(
			$sort_type, SORT_ASC,
			$sort, SORT_ASC, SORT_NUMERIC, 
			$sort_name, SORT_ASC, 
			$fpbx_menu
		);
	}
	
	// navigation menu
	echo "<div id=\"nav\">\n";
	
	// tab menu
	echo "<ul id=\"nav-tabs\">\n";
	$tab_num = 1;
	foreach ($fpbx_types as $key=>$val) {
		$type_name = (isset($fpbx_type_names[$val]) ? $fpbx_type_names[$val] : ucfirst($val));
		echo '<li><a href="#nav-'.str_replace(' ','_',$val).'"><span>'.$type_name.'</span></a></li>';
		if ($val == $fpbx_type) {
			$tab_num = $key+1;
		}
	}
	echo "<li class=\"last\"><a><span> </span></a></li>";
	echo "</ul>\n";
	
	// create tabs, and set the proper one active
	echo "<script type=\"text/javascript\">\n";
	echo " $(function() {\n";
	echo "   $('#nav').tabs(".$tab_num.");\n";
	echo " });\n";
	echo "</script>\n";
	
	// menu items
	$prev_category = false;
	$prev_type = false;
	$started_div = false;
	foreach ($fpbx_menu as $key => $row) {
		if ($prev_type != $row['type']) {
			if ($started_div) {
				echo '</ul></div>';
			}
			echo '<div id="nav-'.$row['type'].'"><ul>';
			$prev_type = $row['type'];	
			$started_div = true;
		}
		
		if ($fpbx_usecategories && ($row['category'] != $prev_category)) {
			echo "\t\t<li class=\"category\">"._($row['category'])."</li>\n";
			$prev_category = $row['category'];
		}
		
		$href = isset($row['href']) ? $row['href'] : "config.php?type=".$row['type']."&amp;display=".$row['display'];
		$extra_attributes = '';
		if (isset($row['target'])) {
			$extra_attributes .= ' target="'.$row['target'].'"';
		}

		$li_classes = array('menuitem');
		if ($display == $row['display']) {
			$li_classes[] = 'current';
		}
		if (isset($row['disabled']) && $row['disabled']) {
			$li_classes[] = 'disabled';
		}

		echo "\t<li class=\"".implode(' ',$li_classes)."\">";
		if (isset($row['disabled']) && $row['disabled']) {
			echo _($row['name']);
		} else {
			echo '<a href="'.$href.'" '.$extra_attributes.' >'._($row['name'])."</a>";
		}
		echo "</li>\n";
	}
	echo "</ul></div>\n</div>\n\n";
}


?>
<!-- end menu -->

<div id="wrapper"><div id="background-wrapper">

<div id="left-corner"></div>
<div id="right-corner"></div>


<div id="language">
	
<?php	
// TODO: this is ugly, need to code this better!
//       mixing php + html is bad!
	if (extension_loaded('gettext')) {
		if (!isset($_COOKIE['lang'])) {
			$_COOKIE['lang'] = "en_US";
		} 
?>
&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<select onchange="javascript:changeLang(this.value)">
		<option value="en_US" <?php echo ($_COOKIE['lang']=="en_US" ? "selected" : "") ?> >English</option>
		<option value="fr_FR" <?php echo ($_COOKIE['lang']=="fr_FR" ? "selected" : "") ?> >Fran&ccedil;ais</option>
		<option value="de_DE" <?php echo ($_COOKIE['lang']=="de_DE" ? "selected" : "") ?> >Deutsch</option>
		<option value="it_IT" <?php echo ($_COOKIE['lang']=="it_IT" ? "selected" : "") ?> >Italiano</option>
		<option value="es_ES" <?php echo ($_COOKIE['lang']=="es_ES" ? "selected" : "") ?> >Espa&ntilde;ol</option>
		<option value="ru_RU" <?php echo ($_COOKIE['lang']=="ru_RU" ? "selected" : "") ?> >Russki</option>
		<option value="pt_PT" <?php echo ($_COOKIE['lang']=="pt_PT" ? "selected" : "") ?> >Portuguese</option>
		<option value="he_IL" <?php echo ($_COOKIE['lang']=="he_IL" ? "selected" : "") ?> >Hebrew</option>
		</select>
<?php
	}
?>

<script type="text/javascript">
<!--
function changeLang(lang) {
	document.cookie='lang='+lang;
	window.location.reload();
}
//-->
</script>

	</div>
	
	
<div class="content">

<noscript>
	<div class="attention"><?php _("WARNING: Javascript is disabled in your browser. The FreePBX administration interface requires Javascript to run properly. Please enable javascript or switch to another  browser that supports it.") ?></div>
</noscript>

<!-- begin generated page content  -->
<?php  echo $content; ?>
<!-- end generated page content -->

</div> <!-- .content -->

<div id="footer">
	<hr />
	<?php
	echo '<a target="_blank" href="http://www.freepbx.org"><img id="footer_logo" src="images/freepbx_small.png" alt="FreePBX&reg;"/></a>';
	echo '<h3>'.'Freedom to Connect<sup>&reg</sup>'.'</h3>';
	echo "\t\t".sprintf(_('%s is a registered trademark of %s'),
	     '<a href="http://www.freepbx.org" target="_blank">'._('FreePBX').'</a>',
	     '<a href="http://www.freepbx.org/copyright.html" target="_blank">Atengo, LLC.</a>')."<br/>\n";
	echo "\t\t".sprintf(_('%s is licensed under %s'),
	     '<a href="http://www.freepbx.org" target="_blank">'._('FreePBX').' '.getversion().'</a>',
	     '<a href="http://www.gnu.org/copyleft/gpl.html" target="_blank">GPL</a>');
	
	?>
</div>

</div></div> <!-- background-wrapper, background -->
