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
	* @param $value      The value to put at that extension
	* @param $priority   The priority to use. False for next
	*                    priority. Defaults to false.
	* @param $insert     If we're allowed to insert priorities. If $priority is 
	*			used up, everything higher will be incremented by 1
	*			(including Goto's, If's, etc)
	* @return The priority the extension was inserted at, or
	*         false if there is already a value at that extension
	*/
	function add($section, $extension, $value, $basepriority = false, $addpriority = false, $insert = false) {
		if ($basepriority || $addpriority) {
			if (!is_int($basepriority) || !is_int($addpriority)) {
				// not supported. for non-integers (for 'hint' priority, use addHint() )
				trigger_error("Only integers are permitted for \$priority to extensions::add()", E_ERROR);
				return false;
			}
			$priority = $basepriority + $addpriority;
		} else {
			// no priority specified, pick the next one
			$priority = $this->_lastpri[$section][$extension] + 1;
		}
		
		if (!is_subclass_of($value, "extension")) {
			trigger_error(E_ERROR, "Only instances and subclasses of the 'extension' class can be passed as \$value to extensions::add()");
			return false;
		}
		
		echo "adding at [$section][$extension][$priority]";
		
		if (isset($this->_exts[$section][$extension][$priority])) {
			// priority is already used
			if ($insert) {
				// we can insert, so increment everything before inserting
				$this->increment($section, $extension, $priority);
			} else {
				// we can't increment
				return false;
			}
		}

		$this->_exts[$section][$extension][$priority] = $value;
		if ($basepriority) {
			// store a pointer relating this priority to the basepriority (for incrementing purposes)
			$this->_related[$section][$extension][$priority] = array('base'=>$basepriority, 'add'=>$addpriority);
		}
		
		// it may still be sorted, but thats a lot of work to figure out
		$this->_sorted = false;
		
		$this->_lastpri[$section][$extension] = $priority;
		return $priority;
	}
	
	function add2($section, $extension, $value, $base = ".") {
		
		if (!is_subclass_of($value, "extension")) {
			trigger_error(E_ERROR, "Only instances and subclasses of the 'extension' class can be passed as \$value to extensions::add()");
			return false;
		}
		
		echo "adding at [$section][$extension][$priority]";
		if (isset($this->_exts[$section][$extension][$priority])) {
			// priority is already used
			if ($insert) {
				// we can insert, so increment everything before inserting
				//$this->increment($section, $extension, $priority);
			} else {
				// we can't increment
				return false;
			}
		}

		$this->_exts[$section][$extension][$priority] = array(
			'tag' => $base,
			'cmd' => $value,
		);
		
		// it may still be sorted, but thats a lot of work to figure out
		$this->_sorted = false;
		
		$this->_lastpri[$section][$extension] = $priority;
		return $priority;
	}
	
	function increment($section, $extension, $start_priority, $increment = 1) {
		if ($increment < 1) {
			// not supported
			return false;
		}
		
		// sort this extension by priority
		//not important with for loop!    ksort($this->_exts[$section][$extension]);  
		
		$num_incremented = 0;
		
		// iterate through sequential priorities, until the next one is not set
		// at the end of the loop $i will be the next available priority
		for ($i = $start_priority; isset($this->_exts[$section][$extension][$priority]); $i++) {
			$this->_exts[$section][$extension][$priority]->increment($increment);
			$reassign[$priority] = $priority+$increment;
		}
		
		$do_increment = false;
		$keys = array_keys($this->_exts[$section][$extension]);
		
		foreach ($keys as $priority) {
		
			if ($do_increment || ($priority == $start_priority)) {
				$do_increment = true;
				$num_incremented += 1;
				// increment the object inside this priority
				$this->_exts[$section][$extension][$priority]->increment($increment);
				
				// then move the actual priority. since we're going backwards, there's never going to be something bigger
				echo "copy to [$section][$extension][$priority+$increment]";
				echo "unset [$section][$extension][$priority]";
				
				$reassign[$priority] = $priority+$increment;
			}
		}
		
		krsort($reassign);
		foreach ($reassign as $oldpri => $newpri) {
			// we move variables around in the array with references. this works a lot like hard links in a *nix file system
			
			// reference each value first (this does not allocate any new memory)
			$this->_exts[$section][$extension][$newpri] &= $this->_exts[$section][$extension][$oldpri];
			// then unset the original, leaving only one reference to the value
			unset($this->_exts[$section][$extension][$oldpri]);
		}
		
		//array(1,2,3,101,102)
		// array(1,2,4,101,102) oh FUCK!
		
				$this->_exts[$section][$extension][$priority+$increment] &= $this->_exts[$section][$extension][$priority];
				unset($this->_exts[$section][$extension][$priority]);

		return $num_incremented;
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
		
		if (!$this->_sorted) {
			$this->sort();
		}
		
		var_dump($this->_exts);
		
		foreach (array_keys($this->_exts) as $section) {
			$output .= "[".$section."]\n";
			
			foreach (array_keys($this->_exts[$section]) as $extension) {
				foreach (array_keys($this->_exts[$section][$extension]) as $priority) {
					var_dump($this->_exts[$section][$extension][$priority]);
					$output .= "exten => ".$extension.",".$priority.",".$this->_exts[$section][$extension][$priority]->output()."\n";
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
	var $_priadd;
	
	var $data;
	
	function extension($data) {
		$this->data = $data;
	}
	
	function increment($value) {
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
	
	function increment($value) {
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
	function increment($value) {
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

$dialpri = $ext->add('default','123',new ext_dial('ZAP/1234'));
$ext->add('default','123',new ext_noop('test1'));
$ext->add('default','123',new ext_noop('test2'));
$ext->add('default','123',new ext_noop('test at +101'), $dialpri+101);

$ext->add('default','100',new ext_noop('test100'));
$ext->add('default','101',new ext_noop('test101'));
$ext->add('default','102',new ext_noop('test102'));
$ext->add('default','103',new ext_noop('test103'));

$ext->add('testgoto','100',new ext_noop('test1'));
$ext->add('testgoto','100',new ext_goto(3));
$ext->add('testgoto','100',new ext_noop('test3'));

$ext->add('testgoto','101',new ext_noop('test1'));
$ext->add('testgoto','101',new ext_goto(3));
$ext->add('testgoto','101',new ext_noop('test3'));

$ext->add('testgoto','101',new ext_noop('test4'),2,true);

echo "<pre>";
echo $ext->generateConf();


[default][123][1] = dial
[default][123][2] = noop test1
[default][123][3] = noop test2
[default][123][102] = noop test101
[default][123][103] = noop test101

related[default][123][101] = array(1,102)

increment @ 1 --

for ($i = 1; isset($ext[$i]); $i++)
related

foreach related {
  if $rel[0] == 1    increment($key)
}  


?>