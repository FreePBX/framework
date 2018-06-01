<?php
namespace FreePBX\Dialplan;

class Extensions{
	/** The config
	 * array(section=>array(extension=>array( array('basetag'=>basetag,'tag'=>tag,'addpri'=>addpri,'cmd'=>cmd) )))
	 * ( $_exts[$section][$extension][$priority] )
	 */
	var $_exts;

	/** Hints
	 * special cases of priorities
	 */
	var $_hints;
	var $_sorted;
	var $_section_comment = array();
	var $_section_no_custom = array();
	var $_disable_custom_contexts = false;

	/** The filename to write this configuration to
	 */
	function get_filename(){
		return "extensions_additional.conf";
	}

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
	function add($section, $extension, $tag, $command, $basetag = false, $addpriority = false){

		$extension = ' ' . $extension . ' ';

		if ($basetag || $addpriority) {
			if (!is_int($addpriority) || ($addpriority < 1)) {
				trigger_error("\$addpriority must be an integer >= 1 in extensions::add()");
				return false;
			}
			if (empty($basetag)) {
				trigger_error("\$basetag is required with \$addpriority in extensions::add()");
				return false;
			}
		}

		if (empty($basetag)) {
			// no basetag, we need to make one

			if (empty($this->_exts[$section][$extension])) {
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
	function sort(){
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
	function addSectionComment($section, $comment){
		$this->_section_comment[$section] = $comment;
	}

	function addSectionNoCustom($section, $setting){
		$this->_section_no_custom[$section] = $setting ? true : false;
	}

	function disableCustomContexts($setting){
		$this->_disable_custom_contexts = $setting ? true : false;
	}

	function addHint($section, $extension, $hintvalue){

		$extension = ' ' . $extension . ' ';

		$this->_hints[$section][$extension][] = $hintvalue;
	}

	function addGlobal($globvar, $globval){
		$this->_globals[$globvar] = $globval;
	}

	function addInclude($section, $incsection, $comment = ''){
		$this->_includes[$section][] = array('include' => $incsection, 'comment' => $comment);
	}

	function spliceInclude($section, $splicesection, $splicecomment, $incsection, $comment = ''){
		$key = array_search(array('include' => $splicesection, 'comment' => $splicecomment), $this->_includes[$section]);
		if ($key === false) {
			$this->addInclude($section, $incsection, $comment);
		} else {
			array_splice($this->_includes[$section], $key, 0, array(array('include' => $incsection, 'comment' => $comment)));
		}
	}

	function addSwitch($section, $incsection){
		$this->_switches[$section][] = $incsection;
	}

	function addExec($section, $incsection){
		$this->_exec[$section][] = $incsection;
	}
	function is_priority($num){
		return ctype_digit((string)$num);
	}

	function section_exists($section){
		return isset($this->_exts[$section]);
	}

	/**
	 * This function allows new priorities to be injected into already generated dialplan
	 * usage: $ext->splice($context, $exten, $priority_number, new ext_goto('1','s','ext-did'));
	 *         if $priority is not numeric, it will interpret it as a tag and try to inject
	 *         the command just prior to  the first instruction it finds with the specified tag
	 *         if it can't find the tag, it will inject it after the last instruction
	 * @method splice
	 * @param  string  $section           The context to splice
	 * @param  string  $extension         The extension to splice
	 * @param  string  $priority          if $priority is not numeric, it will
	 *                                    interpret it as a tag and try to inject
	 *                                    the command just prior to  the first instruction
	 *                                    it finds with the specified tag if it
	 *                                    can't find the tag, it will inject it after the last instruction
	 * @param  object  $command           Object of Extension
	 * @param  string  $new_tag           New Priority tag to insert
	 * @param  integer $offset            Offset of label
	 * @param  boolean $fixmultiplelabels [description]
	 */
	function splice($section, $extension, $priority, $command, $new_tag = "", $offset = 0, $fixmultiplelabels = false){

		$extension = ' ' . $extension . ' ';

		// if the priority is a tag, then we look for the real priority to insert it before that
		// tag. If the tag does not exists, then we put it at the very end which may not be
		// desired but it puts it somewhere
		//
		if (!$this->is_priority(trim($priority))) {
			$new_priority = false;
			$count = 0;
			$label = $priority;
			if (isset($this->_exts[$section][$extension])) {
				foreach ($this->_exts[$section][$extension] as $pri => $curr_command) {
					if ($curr_command['tag'] == $priority) {
						$new_priority = $count;
						break;
					}
					$count++;
				}
			}
			if ($new_priority === false) {
				$priority = $count;
			} else {
				$priority = $new_priority + $offset;
				if ($priority < 0) {
					$priority = 0;
				}
			}
		}
		if ($priority == 0) {
			$basetag = '1';
			if (!isset($this->_exts[$section][$extension][0])) {
				$db = debug_backtrace();
				throw new Exception("died in splice $section $extension");
			}
			// we'll be defining a new pri "1", so change existing "1" to "n"
			$this->_exts[$section][$extension][0]['basetag'] = 'n';
		} else {
			$basetag = 'n';
		}
		$newcommand = array(
			'basetag' => $basetag,
			'tag' => $new_tag,
			'addpri' => '',
			'cmd' => $command
		);

		/* This will remove the tag value if entry is being added after an existing tag as
		 *  if we do not do this then asterisk will jump to the last label
		 */
		if ($fixmultiplelabels && $offset > 0 && $new_tag == $label && $new_priority && isset($this->_exts[$section][$extension])) {
			$newcommand['new_tag'] = '';
		}

		/* This will fix an issue with having multiple entrypoint labels by the same name,
		 *  asterisk by default seems to pick the last one. This will remove all labels by
		 *  the same name so that the new entry will go in at the top correctly
		 */
		if ($fixmultiplelabels && $offset <= 0 && $new_tag == $label && isset($this->_exts[$section][$extension])) {
			foreach ($this->_exts[$section][$extension] as $_ext_k => &$_ext_v) {
				if ($_ext_v['tag'] != $label){
					 continue;
				}
				$_ext_v['tag'] = '';
			}
		}

		/* This little routine from http://ca.php.net/array_splice overcomes
		 *  problems that array_splice has with multidmentional arrays
		 */
		$array = isset($this->_exts[$section][$extension]) ? $this->_exts[$section][$extension] : array();
		$ky = $priority;
		$val = $newcommand;
		$n = $ky;
		foreach ($array as $key => $value) {
			$backup_array[$key] = $array[$key];
		}
		$upper_limit = count($array);
		if ($upper_limit === 0) {
			// We've been asked to splice into an empty section. This is PROBABLY
			// a bug in the module, but may not be. Either way, set it to the
			// priority requested, and then add it to the beginning.
			if ($priority > 1) {
				freepbx_log(FPBX_LOG_WARNING, sprintf(_("Critical error when splicing into %s. I was asked to splice into an empty section with a priority greater than 1. This is always a bug in a module. I was asked to add %s"), $section, json_encode($val)));
				throw new \OutOfRangeException(sprintf(_("Critical error when splicing into %s. I was asked to splice into an empty section with a priority greater than 1. This is always a bug in a module. I was asked to add %s"), $section, json_encode($val)));
			}
			$val['basetag'] = $priority;
			$this->_exts[$section][$extension][$priority] = $val;
		} else {
			while ($n <= $upper_limit) {
				if ($n == $ky) {
					$array[$n] = $val;
				} else {
					$i = $n - "1";
					$array[$n] = $backup_array[$i];
				}
				$n++;
			}

			// apply our newly modified array
			$this->_exts[$section][$extension] = $array;
		}
	}

	/* This function allows dial plan to be replaced.  This is most useful for modules that
	 *  would like to hook into other modules and modify dialplan.
	 *  usage: $ext->replace($context, $exten, $priority_number, new ext_goto('1','s','ext-did'));
	 *         if $priority is not numeric, it will interpret it as a tag
	 */
	function replace($section, $extension, $priority, $command){

		$extension = ' ' . $extension . ' ';

		// if the priority is a tag, then we look for the real priority to replace it with
		// If the tag does not exists, then we put it at the very end which may not be
		// desired but it puts it somewhere
		//
		if (!$this->is_priority(trim($priority))) {
			$existing_priority = false;
			$count = 0;
			if (isset($this->_exts[$section][$extension])) {
				foreach ($this->_exts[$section][$extension] as $pri => $curr_command) {
					if ($curr_command['tag'] == $priority) {
						$existing_priority = $count;
						break;
					}
					$count++;
				}
			}
			$priority = ($existing_priority === false) ? $count : $existing_priority;
		} else {
			$priority -= 1;
		}
		$newcommand = array(
			'basetag' => $this->_exts[$section][$extension][$priority]['basetag'],
			'tag' => $this->_exts[$section][$extension][$priority]['tag'],
			'addpri' => '',
			'cmd' => $command
		);
		$this->_exts[$section][$extension][$priority] = $newcommand;

	}

	/* This function allows dial plan to be removed.  This is most useful
	 * for modules that
	 *  would like to hook into other modules and delete dialplan.
	 *  usage: $ext->remove($context, $exten, $priority_number);
	 *         if $priority is not numeric, it will interpret it as a tag
	 */
	function remove($section, $extension, $priority){

		$extension = ' ' . $extension . ' ';

		// if the priority is a tag, then we look for the real priority to
		//replace it with If the tag does not exists, then we put it at the very
		//end which may not be desired but it puts it somewhere
		if (!$this->is_priority(trim($priority))) {
			$existing_priority = false;
			$count = 0;
			if (isset($this->_exts[$section][$extension])) {
				foreach ($this->_exts[$section][$extension]
					as $pri => $curr_command) {
					if ($curr_command['tag'] == $priority) {
						$existing_priority = $count;
						break;
					}
					$count++;
				}
			}
			$priority = ($existing_priority === false) ? false : $existing_priority;
		} else {
			$priority -= 1;
		}
		if ($this->is_priority($priority)) {
			if (isset($this->_exts[$section][$extension][$priority])) {
				unset($this->_exts[$section][$extension][$priority]);
				$this->_exts[$section][$extension] = array_values($this->_exts[$section][$extension]);
			}
			if ($priority === 0 && isset($this->_exts[$section][$extension][0])) {
				$this->_exts[$section][$extension][0]['basetag'] = 1;
			}
			return true;
		} else {
			return false;
		}
	}

	/** Generate the file
	 * @return A string containing the extensions.conf file
	 */
	function generateConf(){
		$output = "";

		//take care of globals first
		if (isset($this->_globals) && is_array($this->_globals)) {
			$output .= "[globals]\n";
			foreach (array_keys($this->_globals) as $global) {
				$output .= $global . " = " . $this->_globals[$global] . "\n";
			}
			$output .= "#include globals_custom.conf\n";
			$output .= "\n;end of [globals]\n\n";
		}

		if (!empty($this->_exts) && is_array($this->_exts)) {
			foreach ($this->_exts as $section => $extensions) {
				$comment = isset($this->_section_comment[$section]) ? ' ; ' . $this->_section_comment[$section] : '';
				$output .= "[$section]$comment\n";

				//automatically include a -custom context unless no_custom is true
				if (!$this->_disable_custom_contexts && (!isset($this->_section_no_custom[$section]) || $this->_section_no_custom[$section] == false)) {
					$output .= "include => {$section}-custom\n";
				}

				//add requested includes for this context
				if (isset($this->_includes[$section]) && is_array($this->_includes[$section])) {
					foreach ($this->_includes[$section] as $include) {
						$output .= "include => " . $include['include'] . ($include['comment'] != '' ? ' ; ' . $include['comment'] : '') . "\n";
					}
				}

				if (isset($this->_switches[$section]) && is_array($this->_switches[$section])) {
					foreach ($this->_switches[$section] as $include) {
						$output .= "switch => " . $include . "\n";
					}
				}

				//add requested #exec scripts for this context
				if (isset($this->_exec[$section]) && is_array($this->_exec[$section])) {
					foreach ($this->_exec[$section] as $include) {
						$output .= "#exec " . $include . "\n";
					}
				}

				// probably a better way to do this. But ... if an extension happens to be the pri 1 extension, and then
				// it outputs false (e.g. noop_trace), we need a pri 1 extension as the next one.
				//
				$last_base_tag = false;
				if (is_array($extensions)) {
					foreach ($extensions as $extension => $idxs) {
						if (is_array($idxs)) {
							foreach ($idxs as $ext) {
								if ($last_base_tag && $ext['basetag'] = 'n') {
									$ext['basetag'] = $last_base_tag;
									$last_base_tag = false;
								}
								$this_cmd = $ext['cmd']->output();
								if ($this_cmd !== false) {
									$output .= "exten => " . trim($extension) . "," .
										$ext['basetag'] . ($ext['addpri'] ? '+' . $ext['addpri'] : '') . ($ext['tag'] ? '(' . $ext['tag'] . ')' : '') .
										"," . $this_cmd . "\n";
								} else {
									$last_base_tag = $ext['basetag'] == 1 ? 1 : false;
								}
							}
						}

						if (!empty($this->_hints[$section][$extension]) && is_array($this->_hints[$section][$extension])) {
							foreach ($this->_hints[$section][$extension] as $hint) {
								$output .= "exten => " . trim($extension) . ",hint," . $hint . "\n";
							}
							unset($this->_hints[$section][$extension]);
						}
						$output .= "\n";
					}
				}
				//get orphan hints
				if (!empty($this->_hints[$section]) && is_array($this->_hints[$section])) {
					foreach ($this->_hints[$section] as $extension => $extensions) {
						if (!empty($extensions) && is_array($extensions)) {
							foreach ($extensions as $hint) {
								$output .= "exten => " . trim($extension) . ",hint," . $hint . "\n";
							}
						}
					}
				}

				$output .= ";--== end of [" . $section . "] ==--;\n\n\n";
			}
		}
		return $output;
	}
}