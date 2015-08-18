<?php

namespace Media\Driver\Drivers;
use Symfony\Component\Process\Process;

class SoxShell extends \Media\Driver\Driver {
	private $track;
	private $version;
	private $mime;
	private $extension;
	private $optons = array(
		"samplerate" => 48000, //-r
		"channels" => 2, //-c
		"bitdepth" => 16
	);
	public $background = false;
	static $supported;

	public function __construct($filename,$extension,$mime) {
		$this->loadTrack($filename);
		$this->version = $this->getVersion();
		$this->mime = $mime;
		$this->extension = $extension;
	}

	public static function supportedCodecs(&$formats) {
		if(!empty(self::$supported)) {
			return self::$supported;
		}
		$process = new Process('sox -h');
		$process->run();
		if(preg_match("/AUDIO FILE FORMATS: (.*)/",$process->getOutput(),$matches)) {
			$codecs = explode(" ",$matches[1]);
			foreach($codecs as $codec) {
				$formats["in"][$codec] = $codec;
				$formats["out"][$codec] = $codec;
			}
			$formats["in"]["oga"] = "oga";
			$formats["out"]["oga"] = "oga";
		} else {
			$formats["in"]["ogg"] = "ogg";
			$formats["in"]["oga"] = "oga";
			$formats["out"]["ogg"] = "ogg";
			$formats["out"]["oga"] = "oga";
		}
		self::$supported = $formats;
		return self::$supported;
	}

	public static function isCodecSupported($codec,$direction) {
		$formats = array();
		$formats = self::supportedCodecs($formats);
		return in_array($codec,$formats[$direction]);
	}

	public static function installed() {
		$process = new Process('sox --version');
		$process->run();

		// executes after the command finishes
		if (!$process->isSuccessful()) {
			return false;
		}
		return true;
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
		$process = new Process('sox --version');
		$process->run();

		// executes after the command finishes
		if (!$process->isSuccessful()) {
			throw new \RuntimeException($process->getErrorOutput());
		}
		//sox: SoX v14.2.0
		if(preg_match("/v(.*)/",$process->getOutput(),$matches)) {
			return $matches[1];
		} else {
			throw new \Exception("Unable to parse version");
		}
	}

	public function convert($newFilename,$extension,$mime) {
		$process = new Process('sox '.$this->track.' '.$newFilename);
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
