<?php

namespace Media\Driver\Drivers;
use Symfony\Component\Process\Process;

class AsteriskShell extends \Media\Driver\Driver {
	private $track;
	private $version;
	private $mime;
	private $extension;
	public $background = false;
	static $supported;

	public function __construct($filename,$extension,$mime) {
		$this->loadTrack($filename);
		$this->version = $this->getVersion();
		$this->mime = $mime;
		$this->extension = $extension;
	}

	public static function installed() {
		$process = new Process('asterisk -V');
		$process->run();

		// executes after the command finishes
		if (!$process->isSuccessful()) {
			return false;
		}
		return true;
	}

	public static function supportedCodecs(&$formats) {
		if(!empty(self::$supported)) {
			return self::$supported;
		}
		exec("asterisk -rx 'core show codecs audio'",$lines,$ret);
		$c = array();
		foreach($lines as $line) {
			if(preg_match('/\d{1,6}\s*audio\s*([a-z0-9]*)\s/i',$line,$matches)) {
				$codec = trim($matches[1]);
				if(in_array($codec,array('none','testlaw','g729'))) {
					continue;
				}
				$formats["in"][$codec] = $codec;
				$formats["out"][$codec] = $codec;
			}
		}
		$lines = null;
		exec("asterisk -rx 'g729 show licenses'",$lines,$ret);
		foreach($lines as $line) {
			if(!preg_match('/licensed channels are currently in use/',$data['data'])) {
				$formats["in"]['g729'] = 'g729';
				$formats["out"]['g729'] = 'g729';
			}
		}
		$formats["out"]["wav"] = "wav";
		$formats["out"]["WAV"] = "WAV";
		$formats["in"]["wav"] = "wav";
		$formats["in"]["WAV"] = "WAV";
		self::$supported = $formats;
		return self::$supported;
	}

	public static function isCodecSupported($codec,$direction) {
		$formats = array();
		$formats = self::supportedCodecs($formats);
		return in_array($codec, $formats[$direction]);
	}

	public function loadTrack($track) {
		if(empty($track)) {
			throw new \Exception("A track must be supplied");
		}
		if(!file_exists($track)) {
			throw new \Exception("Track [$track] not found");
		}
		if(!is_readable($track)) {
			throw new \Exception("Track [$track] not readable");
		}
		$this->track = $track;
	}

	public function getVersion() {
		$process = new Process('asterisk -V');
		$process->run();

		// executes after the command finishes
		if (!$process->isSuccessful()) {
			throw new \RuntimeException($process->getErrorOutput());
		}
		//sox: Asterisk 13.5.0
		if(preg_match("/Asterisk (.*)/",$process->getOutput(),$matches)) {
			return $matches[1];
		} else {
			throw new \Exception("Unable to parse version");
		}
	}

	public function convert($newFilename,$extension,$mime) {
		$process = new Process("asterisk -rx 'file convert ".$this->track." ".$newFilename."'");
		if(!$this->background) {
			$process->run();
			if (!$process->isSuccessful()) {
				throw new \RuntimeException($process->getErrorOutput());
			}
		} else {
			$process->start();
			if (!$process->isRunning()) {
				throw new \RuntimeException($process->getErrorOutput());
			}
		}
	}
}
