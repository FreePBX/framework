<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
* This is the FreePBX Big Module Object.
*
* License for all code of this FreePBX module can be found in the license file inside the module directory
* Copyright 2006-2014 Schmooze Com Inc.
*/
namespace FreePBX;
class View {
	private $queryString = "";
	private $replaceState = false;
	private $lang = array();

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->freepbx = $freepbx;
	}

	/**
	 * This is a replace of the old redirect standard.
	 * It emulates the same functionality but instead using HTML5 pushState
	 * The javascript part of the code is in footer.php
	 * @url https://developer.mozilla.org/en-US/docs/Web/Guide/API/DOM/Manipulating_the_browser_history
	 * @return bool True if the URL will be replaced, false if the URL won't be changed
	 */
	public function redirect_standard() {
		if($this->replaceState) {
			throw new \Exception("Redirect Standard was called twice in the same page load. This is wrong");
		}
		$replace = false;
		$args = func_get_args();
		if(empty($args)) {
			return false;
		}
		parse_str($_SERVER['QUERY_STRING'], $params);
		$vars = array_keys($params);
		foreach($args as $arg) {
			if((!in_array($arg,$vars) && isset($_REQUEST[$arg])) || (in_array($arg,$vars) && isset($_REQUEST[$arg]) && $_REQUEST[$arg] != $params[$arg])) {
				$params[$arg] = $_REQUEST[$arg];
				$replace = true;
			}
		}
		if(!$replace) {
			return false;
		}
		$base = basename($_SERVER['PHP_SELF']);
		$this->queryString = $base."?".http_build_query($params);
		$this->replaceState = true;
		return true;
	}

	/**
	 * To run the javascript or not? A simple method to check the state
	 */
	public function replaceState() {
		return $this->replaceState;
	}

	/**
	 * Get the finalized Query String for replacement
	 */
	public function getQueryString() {
		return $this->queryString;
	}

	/**
	 * Set System Language
	 * @param boolean $details If we should return details or just the name
	 */
	public function setLanguage($details=false) {
		if(!empty($this->lang)) {
			return $details ? $this->lang : $this->lang['name'];
		}
		$UIDEFAULTLANG = $this->freepbx->Config->get("UIDEFAULTLANG");
		$expression = '/^([a-z]*(?:_[A-Z]{2})?)(?:\.([a-z1-9]*))?(?:@([a-z1-9]*))?$/';
		$default = "en_US";
		$defaultParts = array(
			'en_US',
			'en_US'
		);

		$nt = $this->freepbx->Notifications;
		if (!extension_loaded('gettext')) {
			$this->lang = array("full" => $default, "name" => $default, "charmap" => "", "modifiers" => "");
			$nt->add_warning('core', 'GETTEXT', _("Gettext is not installed"), _("Please install gettext so that the PBX can properly translate itself"),'https://www.gnu.org/software/gettext/');
			return $details ? $this->lang : $this->lang['name'];
		}
		$nt->delete('core', 'GETTEXT');
		if(php_sapi_name() !== 'cli') {
			if (empty($_COOKIE['lang']) || !preg_match($expression, $_COOKIE['lang'])) {
				$lang = !empty($UIDEFAULTLANG) ? $UIDEFAULTLANG : $default;
				if (empty($_COOKIE['lang'])) {
					setcookie("lang", $lang);
				} else {
					$_COOKIE['lang'] = $lang;
				}
			} else {
				$lang = $_COOKIE['lang'];
			}
		} else {
			$lang = !empty($UIDEFAULTLANG) ? $UIDEFAULTLANG : $default;
		}

		//Break Locales apart for processing
		if(!preg_match($expression, $lang, $langParts)) {
			$this->lang = array("full" => $default, "name" => $default, "charmap" => "", "modifiers" => "");
			$nt->add_warning('framework', 'LANG_INVALID1', _("Invalid Language"), sprintf(_("You have selected an invalid language '%s' this has been automatically switched back to '%s' please resolve this in advanced settings [%s]"),$lang,$default, "Expression Failure"), "?display=advancedsettings");
			$lang = $default;
			$langParts = $defaultParts;
		} else {
			$nt->delete('framework', 'LANG_INVALID1');
		}

		//Get locale list
		exec('locale -a',$locales, $out);
		if($out != 0) { //could not execute locale -a
			$this->lang = array("full" => $default, "name" => $default, "charmap" => "", "modifiers" => "");
			$nt->add_warning('framework', 'LANG_MISSING', _("Language Support Unknown"), _("Unable to find the Locale binary. Your system may not support language changes!"), "?display=advancedsettings");
			return $details ? $this->lang : $this->lang['name'];
		} else {
			$nt->delete('framework', 'LANG_MISSING');
		}
		$locales = is_array($locales) ? $locales : array();

		if(php_sapi_name() !== 'cli') {
			//Adjust for RTL languages
			$rtl_locales = array( 'ar', 'ckb', 'fa_IR', 'he_IL', 'ug_CN' );
			$_SESSION['langdirection'] = in_array($langParts[1],$rtl_locales) ? 'rtl' : 'ltr';
		}

		//Lets see if utf8 codeset exists if not previously defined
		if(empty($langParts[2])) {
			$testString = !empty($langParts[3]) ? $langParts[1].".utf8@".$langParts[3] : $langParts[1].".utf8";
			if(in_array($testString,$locales)) {
				$langParts[2] = 'utf8';
				$lang = $testString;
			} else {
				$testString = !empty($langParts[3]) ? $langParts[1].".UTF8@".$langParts[3] : $langParts[1].".UTF8";
				if(in_array($testString,$locales)) {
					$langParts[2] = 'UTF8';
					$lang = $testString;
				} else {
					$langParts[2] = '';
				}
			}
		}

		if(!empty($locales) && !in_array($lang,$locales)) {
			if(in_array($default,$locales)) { //found en_US in the array!
				$elang = $lang;
				$lang = $default;
				$langParts = $defaultParts;
				$this->lang = array("full" => $default, "name" => $default, "charmap" => "", "modifiers" => "");
				$nt->add_warning('framework', 'LANG_INVALID2', _("Invalid Language"), sprintf(_("You have selected an invalid language '%s' this has been automatically switched back to '%s' please resolve this in advanced settings [%s]"),$elang,$default, "Nonexistent in Locale"), "?display=advancedsettings");
			} elseif($lang == $default) {
				$this->lang = array("full" => $default, "name" => $default, "charmap" => "", "modifiers" => "");
				$nt->add_warning('framework', 'LANG_INVALID2', _("Invalid Language"), sprintf(_("The default language of '%s' or '%s' was not found on this system. Please resolve this in advanced settings by changing the system language or installing the default locales [%s]"),$default,$default.".utf8", "Nonexistent in Locale, Missing ".$default), "?display=advancedsettings");
				return $details ? $this->lang : $this->lang['name'];
			} else {
				$this->lang = array("full" => $default, "name" => $default, "charmap" => "", "modifiers" => "");
				$nt->add_warning('framework', 'LANG_INVALID2', _("Invalid Language"), sprintf(_("You have selected an invalid language '%s' and we were unable to fallback to '%s' or '%s' please resolve this in advanced settings [%s]"),$lang,$default,$default.".utf8", "Nonexistent in Locale, Missing ".$default), "?display=advancedsettings");
				return $details ? $this->lang : $this->lang['name'];
			}
		} else {
			$nt->delete('framework', 'LANG_INVALID2');
		}

		if(empty($langParts[3])) {
			$langParts[3] = '';
		}

		putenv('LC_ALL='.$lang);
		putenv('LANG='.$lang);
		putenv('LANGUAGE='.$lang);
		setlocale(LC_ALL, $lang);

		bindtextdomain('amp',$this->freepbx->Config->get("AMPWEBROOT").'/admin/i18n');
		bind_textdomain_codeset('amp', 'utf8');
		textdomain('amp');

		$this->lang = array("full" => $lang, "name" => $langParts[1], "charmap" => $langParts[2], "modifiers" => $langParts[3]);

		return $details ? $this->lang : $this->lang['name'];
	}

	/**
	 * Alert Info Hookable Draw Select
	 * @param  sting $id        The id of the select box and javascripts
	 * @param  string $value     The selected value
	 * @param  string $class     Additional classes to add
	 * @param  bool $allowNone Allow none to be a selectable item
	 * @param  bool $disable   Disable the element
	 * @param  bool $required  Is this a required element
	 */
	public function alertInfoDrawSelect($id, $value = '', $class = '', $allowNone = true, $disable = false, $required = false) {
		$display = !empty($_REQUEST['display']) ? $_REQUEST['display'] : '';
		$optionshtml = '';

		$value = trim($value);

		$hooks = $this->freepbx->Hooks->returnHooks();
		$selected = false;
		$hooks = is_array($hooks) ? $hooks : array();
		foreach($hooks as $hook) {
			$mod = $hook['module'];
			$meth = $hook['method'];
			$items = $this->freepbx->$mod->$meth($display);
			if(!is_array($items)) {
				continue;
			}
			foreach($items as $key => $item) {
				if($item['value'] == $value) {
					$selected = true;
				}
				$optionshtml .= '<option label="'.$item['name'].'" value="'.$item['value'].'" data-id="'.$mod.'-'.$key.'" data-playback="'.(!empty($item['playback']) ? 'true' : 'false').'">'.$item['name'].'</option>';
			}

		}

		return '<input id="'.$id.'" type="search" name="'.$id.'" placeholder="'._("Double-Click to see options or type freeform").'" class="form-control '.$class.'" list="'.$id.'-list" '.($required ? 'required' : '').' '.($disable ? 'disabled' : '').' value="'.$value.'"><datalist id="'.$id.'-list">'.$optionshtml.'</datalist>';
	}

	public function mediaControls($id, $title='', $class='', $hidden=false, $record=false) {
		$class .= ($hidden) ? " hidden" : "";
		if(empty($title) && $record) {
			$title = _("Hit the red record button to start recording from your browser");
		} elseif(empty($title) && !$record) {
			$title = _("Hit the play symbol to listen");
		}
		$html = '';
		$type = (!$record) ? "player" : "recorder";
		$html .= '<div id="'.$id.'-media-container" class="media-'.$type.'-container '.$class.'">';
			$html .= '<div id="'.$id.'-media-controls" class="controls">';
				$html .= '<div id="'.$id.'-player" class="jp-jplayer"></div>';
				$html .= '<div id="'.$id.'-player-container" data-player="'.$id.'-player" class="jp-audio-freepbx" role="application" aria-label="media player">';
					$html .= '<div class="jp-type-single">';
						$html .= '<div class="jp-gui jp-interface">';
							$html .= '<div class="jp-controls">';
								$html .= '<i class="fa fa-play jp-play"></i>';
								if($record) {
									$html .= '<i id="record" class="fa fa-circle"></i>';
								} else {
									$html .= '<i class="fa fa-undo"></i>';
								}
							$html .= '</div>';
						$html .= '</div>';
						$html .= '<div class="jp-progress">';
							$html .= '<div class="jp-seek-bar progress">';
								$html .= '<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>';
								$html .= '<div class="progress-bar progress-bar-striped active" style="width: 100%;"></div>';
								$html .= '<div class="jp-play-bar progress-bar"></div><div class="jp-ball"></div></div>';
								$html .= '<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>';
							$html .= '</div>';
						$html .= '</div>';
						$html .= '<div class="jp-volume-controls">';
							$html .= '<i class="fa fa-volume-up jp-mute"></i>';
							$html .= '<i class="fa fa-volume-off jp-unmute"></i>';
						$html .= '</div>';
						$html .= '<div class="jp-details">';
							$html .= '<div class="jp-title" aria-label="title">'.$title.'</div>';
						$html .= '</div>';
						$html .= '<div class="jp-no-solution">';
							$html .= '<span>'._("Update Required").'</span>';
							$html .= sprintf(_("To play the media you will need to either update your browser to a recent version or update your %s"),'<a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>');
						$html .= '</div>';
					$html .= '</div>';
				$html .= '</div>';
			$html .= '</div>';
			if($record) {
				$html .= '<div id="'.$id.'-media-progress" class="progress fade hidden">';
					$html .= '<div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">';
					$html .= '</div>';
				$html .= '</div>';
			}
		$html .= '</div>';
		return $html;
	}
}
