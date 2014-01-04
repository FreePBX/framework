<?php

class Performance extends BMO {

	private $doperf = false;

	public function On() { $this->doperf = true; }

	public function Off() { $this->doperf = false; }

	public function Stamp($str) {
		if ($this->doperf)
			print "PERF/$str/".microtime()."/".memory_get_usage()."\n";
	}
}

