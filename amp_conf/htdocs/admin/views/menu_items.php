<?php
// If freepbx_menu.conf exists then use it to define/redefine categories
//
if ($amp_conf['USE_FREEPBX_MENU_CONF']) {
	$fd = $amp_conf['ASTETCDIR'].'/freepbx_menu.conf';
	if (file_exists($fd)) {
		$favorites = @parse_ini_file($fd,true);
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
		} else {
			freepbx_log('FPBX_LOG_ERROR', _("Syntax error in your freepbx_menu.conf file"));
		}
	}
}

// TODO: these categories are not localizable
//
if (isset($fpbx_menu) && is_array($fpbx_menu)) {	// && freepbx_menu.conf not defined
	$out = null;
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
	//if(FreePBX::Config()->get('AUTHTYPE') == "usermanager") {
	foreach($menu as $t => $cat) { //categories
		if (count($cat) == 1) {
			if (isset($cat[0]['hidden']) && $cat[0]['hidden'] == 'true') {
				continue;
			}
			$href = isset($cat[0]['href']) ? $cat[0]['href'] : 'config.php?display=' . $cat[0]['display'];
			$target = isset($cat[0]['target']) ? ' target="' . $cat[0]['target'] . '"'  : '';
			$class = $cat[0]['display'] == $display ? 'class="ui-state-highlight"' : '';
			$mods[$t] = '<li><a href="' . $href . '" ' . $target . $class . '>' . modgettext::_(ucwords($cat[0]['name']),$cat[0]['module']['rawname']) . '</a></li>';
			continue;
		}

		// $t is a heading so can't be isolated to a module, translation must come from amp
		$mods[$t] = '<li class="dropdown">
			<a href="#" class="dropdown-toggle" data-toggle="dropdown">' . _(ucwords($t)) . '</a>
			<ul class="dropdown-menu" role="menu">';

		foreach ($cat as $c => $mod) { //modules
			if (isset($mod['hidden']) && $mod['hidden'] == 'true') {
				continue;
			}
			//remove administrators, makes no sense in these modes
			if($mod['display'] == "ampusers" &&  in_array(FreePBX::Config()->get('AUTHTYPE'), array("none"))) {
				continue;
			}
			$classes = array();

			//build defualt module url
			$href = isset($mod['href'])
					? $mod['href']
					: "config.php?display=" . $mod['display'];

			$target = isset($mod['target'])
					? ' target="' . $mod['target'] . '" '  : '';

			// try the module's translation domain first
			$items[$mod['name']] = '<li><a href="' . $href . '"'
					. $target
					. (!empty($classes) ? ' class="' . implode(' ', $classes) . '">' : '>')
					. modgettext::_(ucwords($mod['name']), $mod['module']['rawname'])
					. '</a></li>';

			$_item_sort[$mod['name']] = $mod['sort'];
		}
		uksort($items,'_item_sort');
		$mods[$t] .= implode($items) . '</ul>';
		unset($items);
		unset($_item_sort);
	}
	$mods[$t] .= '</li>';
	uksort($mods,'_menu_sort');
	$out .= implode($mods);

	echo $out;
}

// key sort but keep Favorites on the far left, Other on the far right
//
function _menu_sort($a, $b) {
	$a = strtolower($a);
	$b = strtolower($b);
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
