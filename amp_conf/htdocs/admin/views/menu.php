<?php
global $amp_conf;

$out = '';
$out .= '<div id="header">';
$out .= '<div class="menubar ui-widget-header ui-corner-all">';
/*$out .= '<a href="http://www.freepbx.org" target="_blank"'
	. 'data-button-icon-secondary="ui-icon-extlink">'
	. '<img src="' . $amp_conf['BRAND_IMAGE_FREEPBX_LEFT'] . '" alt="FreePBX" style="float:left;height:21px"/></a>';
*/
$out .= '<img src="' . $amp_conf['BRAND_IMAGE_FREEPBX_LEFT'] 
		. '" alt="FreePBX" id="BRAND_IMAGE_FREEPBX_LEFT" />';
		
if (isset($fpbx_menu) && is_array($fpbx_menu)) {	
	foreach ($fpbx_menu as $mod => $deets) {
		switch(strtolower($deets['category'])) {
			case 'admin':
			case 'applications':
			case 'connectivity':
			case 'reports':
			case 'settings':
				$menu[$deets['type']][strtolower($deets['category'])][] = $deets;
				break;
			default:
				$menu[$deets['type']]['other'][] = $deets;
				break;
		}
		
	}
	$menu = $menu['setup'] + $menu['tool'];
	
		$count = 0;
		foreach($menu as $t => $cat) { //catagories
			$mods[$t] = '<a href="#">'
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
						. 'class="' . implode(' ', $classes) . '">'
						. _(ucwords($mod['name']))
						. '</a></li>';
						
			}
			ksort($items);
			$mods[$t] .= implode($items) . '</ul>';
			unset($items);
		}
		ksort($mods);
		$out .= implode($mods);
}
$out .= '<a href="/recordings" target="_blank">' . _('User Panel') . '</a>';
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

?>
