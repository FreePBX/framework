<?php

class Performance {

	private $doperf = true;

	public function Stamp($str) {
		if ($this->doperf)
			print "PERF/$str/".microtime()."/".memory_get_usage()."\n";
	}
}

