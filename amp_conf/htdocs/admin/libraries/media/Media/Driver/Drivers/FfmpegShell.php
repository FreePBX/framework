<?php

namespace Media\Driver\Drivers;
use Symfony\Component\Process\Process;

class FfmpegShell extends \Media\Driver\Driver {
	private $track;
	private $version;
	private $mime;
	private $extension;
	private $optons = array(
		"samplerate" => 48000, //-ar
		"channels" => 1, //-ac
	);
	private $binary = 'ffmpeg';
	public $background = false;

	public function __construct($filename,$extension,$mime,$samplerate=48000,$channels=1,$bitrate=16) {
		$this->loadTrack($filename);
		$this->version = $this->getVersion();
		$this->mime = $mime;
		$this->extension = $extension;
		$this->options['samplerate'] = $samplerate;
		$this->options['channels'] = $channels;
		$loc = fpbx_which("ffmpeg");
		if(!empty($loc)) {
			$this->binary = $loc;
		}
	}

	public static function supportedCodecs(&$formats) {
		$formats["in"]["m4a"] = "m4a";
		$formats["out"]["m4a"] = "m4a";
		$formats["in"]["mp4"] = "mp4";
		$formats["out"]["mp4"] = "mp4";
		return $formats;
	}

	public static function isCodecSupported($codec,$direction) {
		return in_array($codec,array("m4a","mp4"));
	}

	public static function installed() {
		$loc = fpbx_which("ffmpeg");
		$process = new Process($loc.' -version');
		$process->run();

		// executes after the command finishes
		if (!$process->isSuccessful()) {
			return false;
		}
		return true;
	}

	function setOptions($options=array()) {

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
		$process = new Process($this->binary.' -version');
		$process->run();

		// executes after the command finishes
		if (!$process->isSuccessful()) {
			throw new \RuntimeException($process->getErrorOutput());
		}
		//FFmpeg version 0.6.5,
		if(preg_match("/FFmpeg (.*)/i",$process->getOutput(),$matches)) {
			return $matches[1];
		} else {
			throw new \Exception("Unable to parse version");
		}
	}

	public function convert($newFilename,$extension,$mime) {
		switch($extension) {
			case "sln":
				$process = new Process($this->binary.' -i "'.$this->track.'" -ar 8000 -ac 1 -y -acodec pcm_s16le -f s16le "'.$newFilename.'"');
			break;
			case "sln12":
				$process = new Process($this->binary.' -i "'.$this->track.'" -ar 12000 -ac 1 -y -acodec pcm_s16le -f s16le "'.$newFilename.'"');
			break;
			case "sln16":
				$process = new Process($this->binary.' -i "'.$this->track.'" -ar 16000 -ac 1 -y -acodec pcm_s16le -f s16le "'.$newFilename.'"');
			break;
			case "sln24":
				$process = new Process($this->binary.' -i "'.$this->track.'" -ar 24000 -ac 1 -y -acodec pcm_s16le -f s16le "'.$newFilename.'"');
			break;
			case "sln32":
				$process = new Process($this->binary.' -i "'.$this->track.'" -ar 32000 -ac 1 -y -acodec pcm_s16le -f s16le "'.$newFilename.'"');
			break;
			case "sln44":
				$process = new Process($this->binary.' -i "'.$this->track.'" -ar 44000 -ac 1 -y -acodec pcm_s16le -f s16le "'.$newFilename.'"');
			break;
			case "sln48":
				$process = new Process($this->binary.' -i "'.$this->track.'" -ar 48000 -ac 1 -y -acodec pcm_s16le -f s16le "'.$newFilename.'"');
			break;
			case "sln96":
				$process = new Process($this->binary.' -i "'.$this->track.'" -ar 96000 -ac 1 -y -acodec pcm_s16le -f s16le "'.$newFilename.'"');
			break;
			case "sln192":
				$process = new Process($this->binary.' -i "'.$this->track.'" -ar 192000 -ac 1 -y -acodec pcm_s16le -f s16le "'.$newFilename.'"');
			break;
			case "wav":
				$process = new Process($this->binary.' -i "'.$this->track.'" -ar '.$this->options['samplerate'].' -ac 1 -y "'.$newFilename.'"');
			break;
			case "mp4":
			case "m4a":
				$process = new Process($this->binary.' -i "'.$this->track.'" -acodec libfaac -ar '.$this->options['samplerate'].' -y "'.$newFilename.'"');
			break;
			default:
				throw new \Exception("Invalid type of $extension sent to FFMPEG");
			break;
		}
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
