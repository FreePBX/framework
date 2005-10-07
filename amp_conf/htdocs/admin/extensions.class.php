<?php

class extensions {
	/** The config
	 * array(section=>array(extension=>array(priority=>value)))
	 * ( $_exts[$section][$extension][$priority] )
	 */
	var $_exts;
	
	/** Hints 
	 * special cases of priorities
	 */
	var $_hints;
	
	/** Last priority used
	 * array(section=>array(extension=>lastpriority))
	 * ( $_lastpri[$section][$extension] )
	 */
	var $_lastpri;
	
	var $_sorted;
	
	/** Add an entry to the extensions file
	* @param $section    The section to be added to
	* @param $extension  The extension used
	* @param $tag        A tag to use (to reference with basetag), use false or '' if none
	* @param $command    The command to execute
	* @param $basetag    The tag to base this on. Only used in conjunction with $addpriority
	*                    priority. Defaults to false.
	* @param $addpriority  Finds the priority of the tag called $basetag, and adds this 
	*			value to it to use as the priority for this command.
	* @return 
	*/
	function add($section, $extension, $tag, $command, $basetag = false, $addpriority = false) {
		
		if ($basetag || $addpriority) {
			if (!is_int($addpriority) || ($addpriority < 1)) {
				trigger_error(E_ERROR, "\$addpriority must be >= 1 in extensions::add()");
				return false;
			}
			if (empty($basetag)) {
				trigger_error(E_ERROR, "\$basetag is required with \$addpriority in extensions::add()");
				return false;
			}
		}
		
		if (empty($basetag)) {
			// no basetag, we need to make one
			
			if (!isset($this->_exts[$section][$extension])) {
				// first entry, use 1
				$basetag = '1';
			} else {
				// anything else just n
				$basetag = 'n';
			}
		}
		
		$new = array(
			'basetag' => $basetag,
			'tag' => $tag,
			'addpri' => $addpriority,
			'cmd' => $command,
		);
		
		$this->_exts[$section][$extension][] = $new;
	}
	
	/** Sort sections, extensions and priorities alphabetically
	 */
	function sort() {
		foreach (array_keys($this->_exts) as $section) {
			foreach (array_keys($this->_exts[$section]) as $extension) {
				// sort priorities
				ksort($this->_exts[$section][$extension]);
			}
			// sort extensions
			ksort($this->_exts[$section]);
		}
		// sort sections
		ksort($this->_exts);
		
		$this->_sorted = true;
	}
	
	function addHint($section, $extension, $hintvalue) {
		$this->_hints[$section][$extension][] = $hintvalue;
	}
	
	/** Generate the file
	* @return A string containing the extensions.conf file
	*/
	function generateConf() {
		$output = "";
		
		/* sorting is not necessary anymore
		if (!$this->_sorted) {
			$this->sort();
		}
		*/
		
		var_dump($this->_exts);
		
		foreach (array_keys($this->_exts) as $section) {
			$output .= "[".$section."]\n";
			
			foreach (array_keys($this->_exts[$section]) as $extension) {
				foreach (array_keys($this->_exts[$section][$extension]) as $idx) {
				
					$ext = $this->_exts[$section][$extension][$idx];
					
					var_dump($ext);
					
					$output .= "exten => ".$extension.",".
						$ext['basetag'].
						($ext['addpri'] ? '+'.$ext['addpri'] : '').
						($ext['tag'] ? '('.$ext['tag'].')' : '').
						",".$ext['cmd']->output()."\n";
				}
				if (isset($this->_hints[$section][$extension])) {
					foreach ($this->_hints[$section][$extension] as $hint) {
						$output .= "exten => ".$extension.",hint,".$hint;
					}
				}
			}
			
			$output .= "\n; end of [".$section."]\n\n\n";
		}
		
		return $output;
	}

	/** Generate the file
	* @return A string containing the extensions.conf file
	*/
	function generateOldConf() {
		$output = "";
		
		/* sorting is not necessary anymore
		if (!$this->_sorted) {
			$this->sort();
		}
		*/
		
		var_dump($this->_exts);
		
		foreach (array_keys($this->_exts) as $section) {
			$output .= "[".$section."]\n";
			
			foreach (array_keys($this->_exts[$section]) as $extension) {
				$priority = 0;
				$prioritytable = array();
				
				foreach (array_keys($this->_exts[$section][$extension]) as $idx) {
				
					$ext = $this->_exts[$section][$extension][$idx];
					
					//var_dump($ext);
					switch ($ext['basetag']) {
						case '1': $priority = 1; break;
						case 'n': $priority += 1; break;
						default:
							if (isset($prioritytable[$ext['basetag']])) {
								$priority = $prioritytable[$ext['basetag']];
							} else {
								$priority = 'unknown!!!';
							}
						break;
					}
					
					if ($ext['addpri']) {
						$priority += $ext['addpri'];
					}
					
					if ($ext['tag']) {
						$prioritytable[$ext['tag']] = $priority;
					}
					
					$output .= "exten => ".$extension.",".$priority.
						",".$ext['cmd']->output()."\n";
					
				}
				
				if (isset($this->_hints[$section][$extension])) {
					foreach ($this->_hints[$section][$extension] as $hint) {
						$output .= "exten => ".$extension.",hint,".$hint;
					}
				}
			}
			
			$output .= "\n; end of [".$section."]\n\n\n";
		}
		
		return $output;
	}
}

class extension { 
	var $data;
	
	function extension($data) {
		$this->data = $data;
	}
	
	function incrementContents($value) {
		return true;
	}
	
	function output() {
		return $data;
	}
}

class ext_goto extends extension {
	var $pri;
	var $ext;
	var $context;
	
	function ext_goto($pri, $ext = false, $context = false) {
		if ($context !== false && $ext === false) {
			trigger_error(E_ERROR, "\$ext is required when passing \$context in ext_goto::ext_goto()");
		}
		
		$this->pri = $pri;
		$this->ext = $ext;
		$this->context = $context;
	}
	
	function incrementContents($value) {
		$this->pri += $value;
	}
	
	function output() {
		return 'Goto('.($this->context ? $this->context.',' : '').($this->ext ? $this->ext.',' : '').$this->pri.')' ;
	}
}

class ext_if extends extension {
	var $true_priority;
	var $false_priority;
	var $condition;
	function ext_if($condition, $true_priority, $false_priority = false) {
		$this->true_priority = $true_priority;
		$this->false_priority = $false_priority;
		$this->condition = $condition;
	}
	function output() {
		return 'If(' .$condition. '?' .$true_priority.($false_priority ? ':' .$false_priority : '' ). ')' ;
	}
	function incrementContents($value) {
		$this->true_priority += $value;
		$this->false_priority += $value;
	}
}

class ext_noop extends extension {
	function output() {
		return "Noop(".$this->data.")";
	}
}

class ext_dial extends extension {
	var $number;
	var $options;
	
	function ext_dial($number, $options = "tr") {
		$this->number = $number;
		$this->options = $options;
	}
	
	function output() {
		return "Dial(".$this->number.",".$this->options.")";
	}
}

class ext_setvar {
	var $var;
	var $value;
	
	function ext_setvar($var, $value) {
		$this->var = $var;
		$this->value = $value;
	}
	
	function output() {
		return "SetVar(".$this->var."=".$this->value.")";
	}
}

$ext = new extensions;


$ext->add('default','123', 'dial1', new ext_dial('ZAP/1234'));
$ext->add('default','123', '', new ext_noop('test1'));
$ext->add('default','123', '', new ext_noop('test2'));
$ext->add('default','123', '', new ext_noop('test at +101'), 'dial1', 101);
$ext->add('default','123', '', new ext_noop('test at +102'));
echo "<pre>";
echo $ext->generateConf();
echo $ext->generateOldConf();
exit;

/*
exten => 123,1(dial1),Dial(ZAP/1234)
exten => 123,n,noop(test1)
exten => 123,n,noop(test2)
exten => 123,dial1+101,noop(test at 101)
exten => 123,n,noop(test at 102)
*/

?>




