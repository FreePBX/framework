<?php
global $amp_conf;
global $_item_sort;

$language = array(
	'en_US' => _('English'),
	'bg_BG' => _('Bulgarian'),
	'zh_CN' => _('Chinese'),
	'de_DE' => _('German'),
	'fr_FR' => _('French'),
	'he_IL' => _('Hebrew'),
	'hu_HU' => _('Hungarian'),
	'it_IT' => _('Italian'),
	'pt_PT' => _('Portuguese'),
	'pt_BR' => _('Portuguese (Brasil)'),
	'ru_RU' => _('Russian'),
	'sv_SE' => _('Swedish'),
	'es_ES' => _('Spanish'),
	'ja_JP' => _('Japanese'),
);
?>

<nav class="navbar navbar-default" role="navigation">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle navbar-left collapsed" data-toggle="collapse" data-target="#fpbx-menu-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="#">
					<img src="<?php echo $amp_conf['BRAND_IMAGE_TANGO_LEFT']; ?>" alt="<?php echo $amp_conf['BRAND_FREEPBX_ALT_LEFT']; ?>" title="<?php echo $amp_conf['BRAND_FREEPBX_ALT_LEFT']; ?>" id="MENU_BRAND_IMAGE_TANGO_LEFT" data-BRAND_IMAGE_FREEPBX_LINK_LEFT="<?php echo $amp_conf['BRAND_IMAGE_FREEPBX_LINK_LEFT']; ?>" />
				</a>
			</div>
				<div class="collapse navbar-collapse" id="fpbx-menu-collapse">

					<ul class="nav navbar-nav navbar-left">
						<?php include_once(__DIR__ . '/menu_items.php'); ?>
						<li><button id="button_reload" class="btn btn-default navbar-btn"><?php echo _('Apply Config'); ?></button></li>
					</ul>
					<?php if ( isset($_SESSION['AMP_user']) && ($authtype != 'none')) { ?>
						<ul class="nav navbar-nav navbar-right">
							<li >
								<form class="navbar-form" role="search">
									<div class="form-group" id='fpbxsearch'>
										<input type="text" class="form-control typeahead" placeholder="Search">
									</div>
								</form>
							</li>
							<?php if($amp_conf['SHOWLANGUAGE']) { ?>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _('Language'); ?></a>
								<ul id="fpbx_lang" class="dropdown-menu" role="menu">
									<?php foreach ($language as $langKey => $lang) {
											$class = '';
											if ($langKey === $_COOKIE['lang']) {
												$class = 'class="disabled"';
											} ?>
										<li <?php echo $class; ?> data-lang="<?php echo $langKey; ?>"><a href="#"><?php echo $lang; ?></a></li>
									<?php } ?>
								</ul>
							</li>
							<?php } ?>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-cog"></i></a>
								<ul class="dropdown-menu" role="menu">
									<li role="presentation" ><?php echo _('Hello, ') . (isset($_SESSION['AMP_user']->username) ? $_SESSION['AMP_user']->username : 'ERROR'); ?></li>
									<li role="presentation" class="divider"></li>
									<li><a id="user_logout" href="#" title="logout"><?php echo _('Logout'); ?></a>
								</ul>
							</li>
						</ul>
					<?php } ?>
		</div>
	</div>
</nav>
<div id="page_body">
