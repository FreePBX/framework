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
		$options = array(
			"create" => true,
			"allowEmptyOption" => $allowNone
		);
		$optionshtml = '';
		if($allowNone) {
			$optionshtml = '<option value=" ">'._("None").'</option>';
		}

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
				$optionshtml .= '<option value="'.$item['value'].'" data-id="'.$mod.'-'.$key.'" data-playback="'.(!empty($item['playback']) ? 'true' : 'false').'" '.($item['value'] == $value ? 'selected' : '').' '.($required ? 'required' : '').'>'.$item['name'].'</option>';
			}

		}

		if(!$selected && trim($value) != '') {
			$optionshtml = '<option value="'.$value.'" selected>'.$value.'</option>'.$optionshtml;
		}

		return '<div class="alertinfoselect"><select id="'.$id.'" name="'.$id.'" class="form-control '.$class.'" '.($required ? 'required' : '').' '.($disable ? 'disabled' : '').'>'.$optionshtml.'</select><div class="play hidden"><i class="fa fa-play"></i></div><script>$(function() {$("#'.$id.'").removeClass("form-control");$("#'.$id.'").selectize('.json_encode($options).');});</script></div>';
	}
}
