<?php
namespace FreePBX\Dialplan;
class Noop_trace extends Extension{
  var $string;
  var $level;

  function __construct($string,$level=3) {
    $this->string = $string;
    $this->level = $level;
  }
	function output() {
    global $amp_conf;
    if ($amp_conf['NOOPTRACE'] != "" && ctype_digit($amp_conf['NOOPTRACE']) && $amp_conf['NOOPTRACE'] >= $this->level) {
      return "Noop([TRACE](".$this->level.") ".$this->string.")";
    } else {
		  return false;
    }
	}
}