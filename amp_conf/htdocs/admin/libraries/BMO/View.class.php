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
