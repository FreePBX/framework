<?php
global $amp_conf;
global $_item_sort;

$out = '';
$out .= '<div id="header">';
$out .= '<div class="menubar ui-widget-header ui-corner-all">';
//left hand logo
$out .= '<img src="' . $amp_conf['BRAND_IMAGE_FREEPBX_LEFT'] 
		. '" alt="FreePBX" title="FreePBX" id="BRAND_IMAGE_FREEPBX_LEFT" '
		. 'data-BRAND_IMAGE_FREEPBX_LINK_LEFT="' . $amp_conf['BRAND_IMAGE_FREEPBX_LINK_LEFT'] . '"/ />';
		
// If freepbx_menu.conf exists then use it to define/redefine categories
//
$fd = $amp_conf['ASTETCDIR'].'/freepbx_menu.conf';
if (file_exists($fd)) {
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
            // this is really deprecated but ???
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


// TODO: these categories are not localizable
//
if (isset($fpbx_menu) && is_array($fpbx_menu)) {	// && freepbx_menu.conf not defined
	if (empty($favorites)) foreach ($fpbx_menu as $mod => $deets) {
		switch(strtolower($deets['category'])) {
			case 'admin':
			case 'applications':
			case 'connectivity':
			case 'reports':
			case 'settings':
			case 'user panel':
				$menu[strtolower($deets['category'])][] = $deets;
				break;
			default:
				$menu['other'][] = $deets;
				break;
		}
  } else {
	  foreach ($fpbx_menu as $mod => $deets) {
			$menu[$deets['category']][] = $deets;
		}
  }
	
	$count = 0;
	foreach($menu as $t => $cat) { //catagories
    //TODO: this is broken, not getting translation from modules
    //      see old code from freepbx_admin as to how to get it, requires a lot of hoops
    //      first checking in the module owner's i18n, then in the core i18n
    //
    if (count($cat) == 1) {
			if (isset($cat[0]['hidden']) && $cat[0]['hidden'] == 'true') {
				continue;
			}
      $href = isset($cat[0]['href']) ? $cat[0]['href'] : 'config.php?display=' . $cat[0]['display'];
      $target = isset($cat[0]['target']) ? ' target="' . $cat[0]['target'] . '"'  : '';
      $class = $cat[0]['display'] == $display ? 'class="ui-state-highlight"' : '';
      $mods[$t] = '<a href="' . $href . '" ' . $target . $class . '>' . _(ucwords($t)) . '</a>';
      continue;
    }
		$mods[$t] = '<a href="#" class="module_menu_button">'
				. _(ucwords($t))
				. '</a><ul>';
		foreach ($cat as $c => $mod) { //modules
			if (isset($mod['hidden']) && $mod['hidden'] == 'true') {
				continue;
			}
			$classes = array();
				
			//build defualt module url
			$href = isset($mod['href'])
					? $mod['href']
					: "config.php?display=" . $mod['display'];

      $target = isset($mod['target']) 
          ? ' target="' . $mod['target'] . '" '  : '';

			//highlight currently in-use module
			if ($display == $mod['display']) {
				$classes[] = 'ui-state-highlight';
				$classes[] = 'ui-corner-all';
			}

			//highlight disabled modules
			if (isset($mod['disabled']) && $mod['disabled']) {
				$classes[] = 'ui-state-disabled';
				$classes[] = 'ui-corner-all';
			}

			$items[$mod['name']] = '<li><a href="' . $href . '"'
          . $target
					. 'class="' . implode(' ', $classes) . '">'
					. _(ucwords($mod['name']))
					. '</a></li>';

       $_item_sort[$mod['name']] = $mod['sort'];
		}
		uksort($items,'_item_sort');
		$mods[$t] .= implode($items) . '</ul>';
		unset($items);
		unset($_item_sort);
	}
	uksort($mods,'_menu_sort');
	$out .= implode($mods);
}
$out .= '<a id="language-menu-button" class="button-right ui-widget-content ui-state-default">' . _('Language') . '</a>';
$out .= '<ul id="fpbx_lang" style="display:none;">';
	$out .= '<li data-lang="en_US"><a href="#">'. _('English') . '</a></li>';
	$out .= '<li data-lang="bg_BG"><a href="#">' . _('Bulgarian') . '</a></li>';
	$out .= '<li data-lang="zh_CN"><a href="#">' . _('Chinese') . '</a></li>';
	$out .= '<li data-lang="de_DE"><a href="#">' . _('German') . '</a></li>';
	$out .= '<li data-lang="fr_FR"><a href="#">' . _('French') . '</a></li>';
	$out .= '<li data-lang="he_IL"><a href="#">' . _('Hebrew') . '</a></li>';
	$out .= '<li data-lang="hu_HU"><a href="#">' . _('Hungarian') . '</a></li>';
	$out .= '<li data-lang="it_IT"><a href="#">' . _('Italian') . '</a></li>';
	$out .= '<li data-lang="pt_PT"><a href="#">' . _('Portuguese') . '</a></li>';
	$out .= '<li data-lang="pt_BR"><a href="#">' . _('Portuguese (Brasil)') . '</a></li>';
	$out .= '<li data-lang="ru_RU"><a href="#">' . _('Russian') . '</a></li>';
	$out .= '<li data-lang="sv_SE"><a href="#">' . _('Swedish') . '</a></li>';
	$out .= '<li data-lang="es_ES"><a href="#">' . _('Spanish') . '</a></li>';
$out .= '</ul>';

if ( isset($_SESSION['AMP_user']) && ($authtype != 'none')) {
	$out .= '<a id="user_logout" href="#"'
			. ' class="button-right ui-widget-content ui-state-default" title="logout">'
			. _('Logout') . ': ' . (isset($_SESSION['AMP_user']->username) ? $_SESSION['AMP_user']->username : 'ERROR')
			. '</a>';
}

$out .= '<a id="button_reload" href="#" data-button-icon-primary="ui-icon-gear" class="ui-state-error ">'
		. _("Apply Config") .'</a>';

$out .= '</div>';
$out .= '</div>';//header
$out .= '<div id="page_body">';
echo $out;

// key sort but keep Favorites on the far left, Other on the far right
//
function _menu_sort($a, $b) {
  if ($a == 'favorites')
    return false;
  else if ($b == 'favorites')
    return true;
  else if ($a == 'other')
    return true;
  else if ($b == 'other')
    return false;
  else
    return $a > $b;
}

function _item_sort($a, $b) {
  global $_item_sort;

  if (!empty($_item_sort[$a]) && !empty($_item_sort[$a]) && $_item_sort[$a] != $_item_sort[$b])
    return $_item_sort[$a] > $_item_sort[$b];
  else
    return $a > $b;
}
?>
