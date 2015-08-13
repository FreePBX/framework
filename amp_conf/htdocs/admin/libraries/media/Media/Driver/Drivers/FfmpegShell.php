<?php

namespace Media\Driver\Drivers;
use Symfony\Component\Process\Process;

class FfmpegShell extends \Media\Driver\Driver {
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
		$process = new Process('ffmpeg -version');
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
		$process = new Process('ffmpeg -version');
		$process->run();

		// executes after the command finishes
		if (!$process->isSuccessful()) {
			throw new \RuntimeException($process->getErrorOutput());
		}
		//FFmpeg version 0.6.5,
		if(preg_match("/FFmpeg (.*)/",$process->getOutput(),$matches)) {
			return $matches[1];
		} else {
			throw new \Exception("Unable to parse version");
		}
	}

	public function convert($newFilename,$extension,$mime) {
		switch($mime) {
			case "video/mp4":
			case "audio/mp4":
				$process = new Process('ffmpeg -i '.$this->track.' -acodec libfaac -ar 48000 -y '.$newFilename);
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
			break;
		}
	}
}
