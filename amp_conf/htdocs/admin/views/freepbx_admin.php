<?php
// admin interface

// Printing menu
?>
<!-- begin menu -->
<?php

// This is ugly, but it is the only way I can think of to get the menu sections localized. MC
if (false) {
_("Internal Options & Configuration");
_("Inbound Call Control");
_("Module Admin");
_("System Administration");
_("Third Party Addon");
}


global $amp_conf;
$fd = $amp_conf['ASTETCDIR'].'/freepbx_menu.conf';
if ($fpbx_usecategories && file_exists($fd)) {
  $favorites = parse_ini_file($fd,true);
  if ($favorites !== false) foreach ($favorites as $menuitem => $setting) {
    if (isset($fpbx_menu[$menuitem])) {
      foreach($setting as $key => $value) {
        switch ($key) {
          case 'category':
          case 'name':
            $fpbx_menu[$menuitem][$key] = htmlspecialchars($value);
          break;
          case 'type':
            if (strtolower($value)=='setup' || strtolower($value)=='tool') {
              $fpbx_menu[$menuitem][$key] = strtolower($value);
            }
          break;
          case 'sort':
            if (is_numeric($value) && $value > -10 && $value < 10) {
              $fpbx_menu[$menuitem][$key] = $value;
            }
          break;
          case 'remove':
            // parse_ini_file sets all forms of yes/true to 1 and no/false to nothing
            if ($value == '1') {
              unset($fpbx_menu[$menuitem]);
            }
          break;
        }
      }
    }
  }
}

$prev_category = '';

if (is_array($fpbx_menu)) {
	$category = Array();
	$sort = Array();
	$sort_name = Array();
	$sort_type = Array();
	$framework_text_domain = Array();
	// Sorting menu by category and name
	foreach ($fpbx_menu as $key => $row) {
		if(!isset($row['hidden']) || !$row['hidden']) {
			// Fake name to have it follow after Admin in the sort order
			$category[$key] = $row['category'] == 'Favorites'?'Admin Favorites':$row['category'];
			$sort[$key] = $row['sort'];
			$sort_name[$key] = $row['name'];
			$sort_type[$key] = $row['type'];

			if (extension_loaded('gettext') && is_dir("modules/".$row['module']['rawname']."/i18n")) {
				bindtextdomain($row['module']['rawname'],"modules/".$row['module']['rawname']."/i18n");
				bind_textdomain_codeset($row['module']['rawname'], 'utf8');
				$framework_text_domain[$key] = true;
			} else {
				$framework_text_domain[$key] = false;
			}
		} else {
			//dont include if page is set to be hidden
			unset($fpbx_menu[$key]);
		}
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
	echo "\t<ul id=\"nav-tabs\">\n";
	$tab_num = 1;
	foreach ($fpbx_types as $key=>$val) {
		$type_name = (isset($fpbx_type_names[$val]) ? $fpbx_type_names[$val] : ucfirst($val));
		echo "\t\t".'<li><a href="#nav-'.str_replace(' ','_',$val).'"><span>'._($type_name)."</span></a></li>\n";
		if ($val == $fpbx_type) {
			$tab_num = $key+1;
		}
	}
	echo "\t\t<li class=\"last\"><a><span>&nbsp;</span></a></li>\n";
	echo "\t</ul>\n";
	
	// menu items
	$prev_category = false;
	$prev_type = false;
	$prev_tab = false;
	$started_div = false;
	foreach ($fpbx_menu as $key => $row) {
		if ($prev_type != $row['type']) {
			if ($started_div) {
				echo "\t</ul></div>\n";
			}
			echo "\t".'<div id="nav-'.$row['type'].'"><ul>'."\n";
			$prev_type = $row['type'];	
			$started_div = true;
		}
		
		if ($fpbx_usecategories && ($row['category'] != $prev_category)) {
      $cat_id = $row['type'].'-'.md5($row['category']);
			echo "\t\t<li title='"._("click to expand/collapse section")."' id='$cat_id' class=\"category category-header\">".htmlspecialchars(_($row['category']), ENT_QUOTES)."</li>\n";
			$prev_category = $row['category'];
			$prev_tab = $row['type'];
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
    if (isset($cat_id)) {
			$li_classes[] = "id-$cat_id";
    }

		echo "\t\t\t<li class=\"".implode(' ',$li_classes)."\">";
		if ($framework_text_domain[$key]) {
			$label_text = dgettext($row['module']['rawname'],$row['name']);
			if ($label_text == $row['name']) {
			 	$label_text = _($label_text);
			}
		} else {
			$label_text = _($row['name']);
		}
		if (isset($row['disabled']) && $row['disabled']) {
			echo htmlspecialchars($label_text);
		} else {
			echo '<a href="'.$href.'" '.$extra_attributes.' >'. htmlspecialchars($label_text) . "</a>";
		}
		echo "</li>\n";
	}
  echo "\t</ul></div>\n</div>\n\n";
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
		} else {
		    setcookie("lang", $_COOKIE['lang'], time()+365*24*60*60);
		    }
?>
&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<select onchange="javascript:changeLang(this.value)">
		<option value="en_US" <?php echo ($_COOKIE['lang']=="en_US" ? "selected" : "") ?> >English</option>
		<option value="bg_BG" <?php echo ($_COOKIE['lang']=="bg_BG" ? "selected" : "") ?> >Bulgarian</option>
		<option value="zh_CN" <?php echo ($_COOKIE['lang']=="zh_CN" ? "selected" : "") ?> >Chinese</option>
		<option value="de_DE" <?php echo ($_COOKIE['lang']=="de_DE" ? "selected" : "") ?> >Deutsch</option>
		<option value="es_ES" <?php echo ($_COOKIE['lang']=="es_ES" ? "selected" : "") ?> >Espa&ntilde;ol</option>
		<option value="fr_FR" <?php echo ($_COOKIE['lang']=="fr_FR" ? "selected" : "") ?> >Fran&ccedil;ais</option>
		<option value="he_IL" <?php echo ($_COOKIE['lang']=="he_IL" ? "selected" : "") ?> >Hebrew</option>
		<option value="hu_HU" <?php echo ($_COOKIE['lang']=="hu_HU" ? "selected" : "") ?> >Hungarian</option>
		<option value="it_IT" <?php echo ($_COOKIE['lang']=="it_IT" ? "selected" : "") ?> >Italiano</option>
		<option value="pt_PT" <?php echo ($_COOKIE['lang']=="pt_PT" ? "selected" : "") ?> >Portuguese</option>
		<option value="pt_BR" <?php echo ($_COOKIE['lang']=="pt_BR" ? "selected" : "") ?> >Portuguese (Brasil)</option>
		<option value="ro_RO" <?php echo ($_COOKIE['lang']=="ro_RO" ? "selected" : "") ?> >Romanian</option>
		<option value="ru_RU" <?php echo ($_COOKIE['lang']=="ru_RU" ? "selected" : "") ?> >Russki</option>
		<option value="sv_SE" <?php echo ($_COOKIE['lang']=="sv_SE" ? "selected" : "") ?> >Svenska</option>
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
