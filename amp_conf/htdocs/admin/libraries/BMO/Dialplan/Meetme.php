<?php
namespace FreePBX\Dialplan;
class Meetme{
	var $confno;
	var $options;
	var $pin;
	var $app;

	function __construct($confno, $options='', $pin='') {
		global $amp_conf;
		$this->confno = $confno;
		$this->options = $options;
		$this->pin = $pin ? $pin : ',';

		//use confbridge if requested, pruning meetme only options
		switch ($amp_conf['ASTCONFAPP']) {
			case 'app_confbridge':
				$this->app = 'ConfBridge';

				//remove invalid options
				$meetme_only = array('b', 'C', 'd', 'D',
									'e', 'E', 'F', 'i',
									'I', 'l', 'o', 'P',
									'r', 's', 't', 'T',
									'x', 'X', 'q');

				//find asterisk variables in $this->options, if any
				//TODO: if possible, the search AND he replace should be done in one regex
				if (preg_match_all('/\$|}/', $this->options, $matches, PREG_OFFSET_CAPTURE)) {
					$matches = $matches[0];
					//build a range of start and endpoints of any asterisk variables
					for($i = 0; $i < count($matches); $i += 2) {
						if ($matches[$i][0] == '$') {
							$range[] = array( $matches[$i][1], $matches[$i + 1][1]);
						}
					}

					//loop through each charachter in $this->options. If its not in the
					//range of asterisk variables $range, replace its charachter it its in $meetme_only
					$str_array = str_split($this->options);
					for ($i = 0; $i < count($str_array); $i++) {
						if (!$this->in_ast_var_range($i, $range)) {
							$str_array[$i] = str_replace($meetme_only, '', $stra[$i]);
						}
					}
					$this->options = implode($str_array);
				} else {//no variables, just do a normal repalce
					$this->options = str_replace($meetme_only, '', $this->options);
				}

				$this->options = preg_replace('/[GpSL]\(.*\)/', '', $this->options);
				$this->options = preg_replace('/w\(.*\)/', 'w', $this->options);
				break;
			case 'app_meetme':
			default:
				$this->app = 'MeetMe';
				break;
		}
	}

	function output() {
		return $this->app . "(".$this->confno.",".$this->options.",".$this->pin.")";
	}

	/**
	 * @pram int
	 * @pram array - multi dimensional with ranges
	 * i.e. array(
	 *	array(4 => 6),
	 *  array(8 => 9)
	 * )
	 *
	 * @return true if $pos is not in range
	 */
	function in_ast_var_range($pos, $range) {
		foreach ($range as $r) {
			if ($pos >= $r[0] && $pos <= $r[1]) {
				return true;
			}
		}

		return false;
	}
}