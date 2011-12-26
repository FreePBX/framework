<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <TITLE>User Portal</TITLE>
   	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="chrome=1">
	<meta name="robots" content="noindex" />
	<link rel="shortcut icon" href="images/favicon.ico">
    <link rel="stylesheet" href="theme/main.css" type="text/css" />	
	<?php 
	global $amp_conf;
	$version			= get_framework_version();
	$version_tag		= '?load_version=' . urlencode($version);
	$html = '';
	if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
	  $this_time_append	= '.' . time();
	  $version_tag 		.= $this_time_append;
	} else {
		$this_time_append = '';
	}
	$html .= '<link href="/admin/' . framework_css().$version_tag . '" rel="stylesheet" type="text/css">';
	if ($amp_conf['DISABLE_CSS_AUTOGEN'] == true) {
		$html .= '<link href="/admin/' . $amp_conf['JQUERY_CSS'].$version_tag . '" rel="stylesheet" type="text/css">';
	} 
	//include rtl stylesheet if using a rtl langauge
	if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('he_IL'))) {
		$html .= '<link href="assets/css/mainstyle-rtl.css" rel="stylesheet" type="text/css" />';
	}
	// Insert a custom CSS sheet if specified (this can change what is in the main CSS)
	if ($amp_conf['BRAND_CSS_CUSTOM']) {
		$html .= '<link href="/admin/' . $amp_conf['BRAND_CSS_CUSTOM'] 
				. $version_tag . '" rel="stylesheet" type="text/css">';
	}
	//include jquery
	if ($amp_conf['USE_GOOGLE_CDN_JS']) {
		$html .= '<script src="//ajax.googleapis.com/ajax/libs/jquery/' . $amp_conf['JQUERY_VER'] . '/jquery.min.js"></script>';
		$html .= '<script>window.jQuery || document.write(\'<script src="/admin/assets/js/jquery-' . $amp_conf['JQUERY_VER'] . '.min.js"><\/script>\')</script>';
	} else {
		$html .= '<script type="text/javascript" src="/admin/assets/js/jquery-' . $amp_conf['JQUERY_VER'] . '.min.js"></script>';
	}
	echo $html;
	$html = '';
	?>
  </head>
  <body>
  <div id="page">
  <div class="minwidth">
  <div class="container">
    
	<br />
    <div id="headerspacer"><img src="theme/spacer.gif" alt=""></div>
    <div id="main">
    <div class="minwidth">
    <div class="container">
      <div class="spacer"></div>
      <span class="left">
        <div id="menu">
          <div><img height=4 src="theme/spacer.gif" alt=""></div> 
          <div class="nav">
            <?php if (isset($nav_menu) && $nav_menu != '') { ?>
              <b class='nav_b1'></b><b class='nav_b2'></b><b class='nav_b3'></b><b class='nav_b4'></b>
              <div id='nav_menu'>
                  <?php print($nav_menu) ?>
              </div>
              <b class='nav_b4'></b><b class='nav_b3'></b><b class='nav_b2'></b><b class='nav_b1'></b>
            <?php } ?>
          </div>
          <div><img height=14 src="theme/spacer.gif" alt=""></div> 
          <?php if (isset($subnav_menu) && $subnav_menu != '') { ?>
            <div class="subnav">
              <div class="subnav_title"><?php echo _("Folders")?>:</div>
              <b class='subnav_b1'></b><b class='subnav_b2'></b><b class='subnav_b3'></b><b class='subnav_b4'></b>
              <div id='subnav_menu'>
                <?php print($subnav_menu) ?>
              </div>
              <b class='subnav_b4'></b><b class='subnav_b3'></b><b class='subnav_b2'></b><b class='subnav_b1'></b>
            </div>
          <?php } ?>
        </div>
      </span>
      <span class="right">
        <div id="center">
          <?php if (isset($login) && $login != "") { ?>
            <?php print($login) ?>
          <?php } ?>
          <div id="content">
            <!-- begin main content -->
              <?php print($content) ?>
            <!-- end main content -->
          </div>
        </div>
      </span>
      <div class="spacer"></div>
    </div>
    </div>
    </div>
  </div>
  </div>
  </div>
	<?php
		if (!isset($no_auth)) {
			$fpbx['conf']				= $amp_conf;
			unset($fpbx['conf']['AMPMGRPASS'], 
				$fpbx['conf']['AMPMGRUSER'], 
				$fpbx['conf']['AMPDBUSER'], 
				$fpbx['conf']['AMPDBPASS']);
		}

		$fpbx['conf']['text_dir']		= isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('he_IL'))
											? 'rtl' : 'ltr';
		$fpbx['conf']['uniqueid']		= sql('SELECT data FROM module_xml WHERE id = "installid"', 'getOne');
		$fpbx['conf']['dist']			= _module_distro_id();
		$fpbx['conf']['ver']			= get_framework_version();
		//$fpbx['conf']['reload_needed']  = $reload_needed; 
		$fpbx['msg']['framework']['reload_unidentified_error'] = _(" error(s) occurred, you should view the notification log on the dashboard or main screen to check for more details.");
		$fpbx['msg']['framework']['close'] = _("Close");
		$fpbx['msg']['framework']['continuemsg'] = _("Continue");//continue is a resorved word!
		$fpbx['msg']['framework']['cancel'] = _("Cancel");
		$fpbx['msg']['framework']['retry'] = _("Retry");
		$fpbx['msg']['framework']['invalid_responce'] = _("Error: Did not receive valid response from server");
		$fpbx['msg']['framework']['validateSingleDestination']['required'] = _('Please select a "Destination"');
		$fpbx['msg']['framework']['validateSingleDestination']['error'] = _('Custom Goto contexts must contain the string "custom-".  ie: custom-app,s,1'); 
		$fpbx['msg']['framework']['weakSecret']['length'] = _("The secret must be at minimum six characters in length.");
		$fpbx['msg']['framework']['weakSecret']['types'] = _("The secret must contain at least two numbers and two letters.");
		$html .= "\n" . '<script type="text/javascript">'
				. 'var fpbx='
				. json_encode($fpbx)
		 		. '</script>';

		if ($amp_conf['USE_GOOGLE_CDN_JS']) {
			$html .= '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/'
					. $amp_conf['JQUERYUI_VER'] . '/jquery-ui.min.js"></script>';
			$html .= '<script type="text/javascript" >window.jQuery.ui || document.write(\'<script src="/admin/assets/js/jquery-ui-'
					. $amp_conf['JQUERYUI_VER'] .'.min.js"><\/script>\')</script>';
		} else {
			$html .= '<script type="text/javascript" src="/admin/assets/js/jquery-ui-'
					. $amp_conf['JQUERYUI_VER'] . '.min.js"></script>';
		}

		// Production versions should include the packed consolidated javascript library but if it
		// is not present (useful for development, then include each individual library below
		if ($amp_conf['USE_PACKAGED_JS'] && file_exists("../admin/assets/js/pbxlib.js")) {
			$html .= '<script type="text/javascript" src="/admin/assets/js/pbxlib.js' 
					. $version_tag . '"></script>';
		} else {
			/*
			 * files below:
			 * jquery.cookie.js - for setting cookies
			 * script.legacy.js - freepbx library
			 * jquery.toggleval.3.0.js - similar to html5 form's placeholder. depreciated
			 * tabber-minimized.js - sed for module admin (hiding content) 
			 */
			$html .= ' <script type="text/javascript" src="/admin/assets/js/menu.js"></script>'
				. '<script type="text/javascript" src="/admin/assets/js/jquery.hotkeys.js"></script>'
			 	. '<script type="text/javascript" src="/admin/assets/js/jquery.cookie.js"></script>'
			 	. '<script type="text/javascript" src="/admin/assets/js/script.legacy.js"></script>'
			 	. '<script type="text/javascript" src="/admin/assets/js/jquery.toggleval.3.0.js"></script>'
			 	. '<script type="text/javascript" src="/admin/assets/js/tabber-minimized.js"></script>';
		}
		if ($amp_conf['BRAND_ALT_JS']) {
			$html .= '<script type="text/javascript" src="/admin/' . $amp_conf['BRAND_ALT_JS'] . '"></script>';
		}

		if ($amp_conf['BROWSER_STATS']) {
			$ga = "<script type=\"text/javascript\">
					var _gaq=_gaq||[];
					_gaq.push(['_setAccount','UA-25724109-1'],
							['_setCustomVar',1,'type',fpbx.conf.dist.pbx_type,2],
							['_setCustomVar',2,'typever',fpbx.conf.dist.pbx_version,3],
							['_setCustomVar',3,'astver',fpbx.conf.ASTVERSION,3],
							['_setCustomVar',4,'fpbxver',fpbx.conf.ver,3],
							['_setCustomVar',5,'display','ari',3],
							/*['_setCustomVar',1,'uniqueid',fpbx.conf.uniqueid,1],
							['_setCustomVar',1,'lang',$.cookie('lang')||'en_US',3],
							*/['_trackPageview']);
					(function(){
						var ga=document.createElement('script');ga.type='text/javascript';ga.async=true;
						ga.src=('https:'==document.location.protocol
									?'https://ssl':'http://www') 
									+'.google-analytics.com/ga.js';
						var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(ga,s);
					})();</script>";
			$html .= str_replace(array("\t", "\n"), '', $ga);
		}

		//add IE specifc styling polyfills
		$html .= '<!--[if lte IE 10]>';
		$html .= '<link rel="stylesheet" href="/admin/assets/css/progress-polyfill.css" type="text/css">';
		$html .= '<script type="text/javascript" src="/admin/assets/js/progress-polyfill.min.js"></script>';
		$html .= '<![endif]-->';
		echo $html;
	?>
  </body>
</html>
