<?php

namespace Media\Driver\Drivers;
use Symfony\Component\Process\Process;

class AsteriskShell extends \Media\Driver\Driver {
	private $track;
	private $version;
	private $mime;
	private $extension;
	public $background = false;

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
		$c = \FreePBX::Codecs()->getAudio();
		if(isset($c['none'])) {unset($c['none']);}
		if(isset($c['testlaw'])) {unset($c['testlaw']);}
		$astman = \FreePBX::create()->astman;
		if(!$astman->mod_loaded("g729")) {
			if(isset($c['g729'])) {unset($c['g729']);}
		} else {
			$data = $astman->Command("g729 show licenses");
			if(!preg_match('/licensed channels are currently in use/',$data['data'])) {
				if(isset($c['g729'])) {unset($c['g729']);}
			}
		}
		foreach($c as $codec => $state) {
			$formats["in"][$codec] = $codec;
			$formats["out"][$codec] = $codec;
		}
		$formats["out"]["wav"] = "wav";
		$formats["out"]["WAV"] = "WAV";
		$formats["in"]["wav"] = "wav";
		$formats["in"]["WAV"] = "WAV";
		return $formats;
	}

	public static function isCodecSupported($codec,$direction) {
		$c = \FreePBX::Codecs()->getAudio();
		if(isset($c['none'])) {unset($c['none']);}
		if(isset($c['testlaw'])) {unset($c['testlaw']);}
		$astman = \FreePBX::create()->astman;
		if(!$astman->mod_loaded("g729")) {
			if(isset($c['g729'])) {unset($c['g729']);}
		} else {
			$data = $astman->Command("g729 show licenses");
			if(!preg_match('/licensed channels are currently in use/',$data['data'])) {
				if(isset($c['g729'])) {unset($c['g729']);}
			}
		}
		return isset($c[$codec]);
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
