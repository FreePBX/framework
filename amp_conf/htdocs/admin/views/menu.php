<?php
$out = '';
$out .= '<div id="header">';
$out .= '<div class="menubar ui-widget-header ui-corner-all">';

if (isset($fpbx_menu) && is_array($fpbx_menu)) {	
	//set roduct type for use in the logo. Default to pbxact
/*	if (function_exists('sysadmin_get_license')) {
		$lic = sysadmin_get_license();
		if (isset($lic['Product-Name']) && $lic['Product-Name'] == 'PBXtended') {
			$logo = '<a id="fpbx_link_button" href="http://www.pbxtended.com" target="_blank" data-button-icon-secondary="ui-icon-extlink"><img src="/admin/modules/shmzskin/assets/images/pbxtended-logo.png" alt="PBXtended" style="float:left"/></a>';
		} else {
			$logo = '<a id="fpbx_link_button" href="http://www.pbxact.com" target="_blank" data-button-icon-secondary="ui-icon-extlink"><img src="/admin/modules/shmzskin/assets/images/pbxact-logo.png" alt="PBXact" style="float:left"/></a>';
		}
	} else {
		$logo = '<a id="fpbx_link_button" href="http://www.pbxact.com" target="_blank" data-button-icon-secondary="ui-icon-extlink"><img src="/admin/modules/shmzskin/assets/images/pbxact-logo.png" alt="PBXact" style="float:left"/></a>';
	}*/

	foreach ($fpbx_menu as $mod => $deets) {
		$menu[$deets['type']][$deets['category']][] = $deets;
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
$out .= '<a href="/recordings" target="_blank">User Panel</a>';
$out .= '<a id="language-menu-button" class="button-right ui-widget-content ui-state-default">Language</a>';
$out .= '<ul id="fpbx_lang" style="display:none;">';
	$out .= '<li data-lang="en_US"><a href="#">English</a></li>';
	$out .= '<li data-lang="bg_BG"><a href="#">Bulgarian</a></li>';
	$out .= '<li data-lang="zh_CN"><a href="#">Chinese</a></li>';
	$out .= '<li data-lang="de_DE"><a href="#">Deutsch</a></li>';
	$out .= '<li data-lang="es_ES"><a href="#">Espa&ntilde;ol</a></li>';
	$out .= '<li data-lang="fr_FR"><a href="#">Fran&ccedil;ais</a></li>';
	$out .= '<li data-lang="he_IL"><a href="#">Hebrew</a></li>';
	$out .= '<li data-lang="hu_HU"><a href="#">Hungarian</a></li>';
	$out .= '<li data-lang="it_IT"><a href="#">Italiano</a></li>';
	$out .= '<li data-lang="pt_PT"><a href="#">Portuguese</a></li>';
	$out .= '<li data-lang="pt_BR"><a href="#">Portuguese (Brasil)</a></li>';
	$out .= '<li data-lang="ru_RU"><a href="#">Russki</a></li>';
	$out .= '<li data-lang="sv_SE"><a href="#">Svenska</a></li>';
$out .= '</ul>';

if ( isset($_SESSION['AMP_user']) && ($authtype != 'none')) {
	$out .= '<button id="user_logout"'
			. ' class="button-right ui-widget-content ui-state-default" title="logout">'
			. _('Logout: ') . (isset($_SESSION['AMP_user']->username) ? $_SESSION['AMP_user']->username : 'ERROR')
			. '</button>';
}

$out .= '<button id="button_reload" data-button-icon-primary="ui-icon-gear" class="ui-state-error ">'
		. _("Apply Config") .'</button>';

$out .= '</div>';//'<div style="width:100%;height:0px;clear:both"></div>';
$out .= '</div>';//header
$out .= '<div id="page_body">';
echo $out;

?>