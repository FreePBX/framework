<?php
namespace Media;
use mm\Mime\Type;

class Media {
	private $track;
	private $extension;
	private $mime;
	private $driver;
	private $temp; //temp file to unset on convert
	private $tempDir;

	public function __construct($filename) {
		$this->loadTrack($filename);
		$tempDir = sys_get_temp_dir();
		$this->tempDir = !empty($tempDir) ? $tempDir : "/tmp";
	}
	/**
	 * Cast the track to a string
	 *
	 * @return type
	 */
	public function __toString() {
		return $this->track;
	}

	/**
	 * Load a track for processing
	 * @param  string $track The full path to the track
	 */
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

		Type::config('magic', array(
			'adapter' => 'Freedesktop',
			'file' => dirname(__DIR__).'/resources/magic.db'
		));
		Type::config('glob', array(
			'adapter' => 'Freedesktop',
			'file' => dirname(__DIR__).'/resources/glob.db'
		));
		$this->extension = Type::guessExtension($this->track);
		$this->mime = Type::guessType($this->track);
	}

	private function setupDrivers() {

	}

	/**
	 * Get Stats of an audio file
	 * @return array The stats as an array
	 */
	public function getStats() {

	}

	/**
	 * Get the file comments (annotations)
	 *
	 * @param boolean $parse   Parse and return a value object
	 * @return string|object
	 */
	public function getAnnotations($parse=false) {

	}

	/**
	 * Convert the track using the best possible means
	 * @param  string $filename The new filename
	 * @return object           New Media Object
	 */
	public function convert($newFilename) {
		$extension = Type::guessExtension($newFilename);
		$mime = Type::guessType($newFilename);
		//Use Asterisk to get the original audio file to wav
		switch($this->mime) {
			case "audio/x-wav":
			case "audio/x-gsm":
			case "text/plain":
			case "application/octet-stream":
				$parts = pathinfo($this->track);
				switch($parts['extension']) {
					case "alaw":
					case "ulaw":
					case "gsm":
					case "g722":
					case "sln":
						if(Driver\Drivers\AsteriskShell::installed()) {
							$driver = new Driver\Drivers\AsteriskShell($this->track,$this->extension,$this->mime);
							$ts = time().base64_encode(openssl_random_pseudo_bytes(5));
							$driver->convert($this->tempDir."/temp.".$ts.".wav","wav","audio/x-wav");
							$this->track = $this->temp = $this->tempDir."/temp.".$ts.".wav";
							$this->extension = "wav";
							$this->mime = "audio/x-wave";
						} else {
							throw new \Exception("Cant convert to $mime because Asterisk is not installed");
						}
					break;
					default:
						throw new \Exception("Unable to convert to ".$this->mime." from ".$parts['extension'].", no matching binary converter");
					break;
				}
			break;
			case "audio/ogg":
				if(Driver\Drivers\SoxShell::installed()) {
					$driver = new Driver\Drivers\SoxShell($this->track,$this->extension,$this->mime);
					$ts = time().base64_encode(openssl_random_pseudo_bytes(5));
					$driver->convert($this->tempDir."/temp.".$ts.".wav","wav","audio/x-wav");
					$this->track = $this->temp = $this->tempDir."/temp.".$ts.".wav";
					$this->extension = "wav";
					$this->mime = "audio/x-wave";
				} else {
					throw new \Exception("Cant convert to $mime because Sox is not installed");
				}
			break;
			case "audio/mpeg":
				if(Driver\Drivers\Mpg123Shell::installed()) {
					$driver = new Driver\Drivers\Mpg123Shell($this->track,$this->extension,$this->mime);
					$ts = time().base64_encode(openssl_random_pseudo_bytes(5));
					$driver->convert($this->tempDir."/temp.".$ts.".wav","wav","audio/x-wav");
					$this->track = $this->temp = $this->tempDir."/temp.".$ts.".wav";
					$this->extension = "wav";
					$this->mime = "audio/x-wave";
				} else {
					throw new \Exception("Cant convert to $mime because mpg123 is not installed");
				}
			break;
			default:
				throw new \Exception("Unable to convert to ".$this->mime." from ".$mime.", no matching binary converter");
			break;
		}
		//From wav get it to other formats
		switch($mime) {
			case "audio/ogg":
				if(Driver\Drivers\SoxShell::installed()) {
					$driver = new Driver\Drivers\SoxShell($this->track,$this->extension,$this->mime);
					$driver->convert($newFilename,$extension,$mime);
				} else {
					throw new \Exception("Cant convert to $mime because Sox is not installed");
				}
			break;
			case "audio/mp4":
				if(Driver\Drivers\FfmpegShell::installed()) {
					$driver = new Driver\Drivers\FfmpegShell($this->track,$this->extension,$this->mime);
					$driver->convert($newFilename,$extension,$mime);
				} else {
					throw new \Exception("Cant convert to $mime because ffmpeg is not installed");
				}
			break;
			case "audio/mpeg":
				if(Driver\Drivers\LameShell::installed()) {
					$driver = new Driver\Drivers\LameShell($this->track,$this->extension,$this->mime);
					$driver->convert($newFilename,$extension,$mime);
				} else {
					throw new \Exception("Cant convert to $mime because Lame is not installed");
				}
			break;
			//Yes we go wav to wav. It's on purpose I swear!!
			case "audio/x-wav":
			case "audio/x-gsm":
			case "text/plain":
			case "application/octet-stream":
			default:
				$parts = pathinfo($newFilename);
				switch($parts['extension']) {
					case "alaw":
					case "ulaw":
					case "gsm":
					case "g722":
					case "sln":
					case "wav":
						if(Driver\Drivers\AsteriskShell::installed()) {
							$driver = new Driver\Drivers\AsteriskShell($this->track,$this->extension,$this->mime);
							$driver->convert($newFilename,$parts['extension'],$mime);
						} else {
							throw new \Exception("Cant convert to $mime because Asterisk is not installed");
						}
					break;
					default:
						throw new \Exception("Unable to convert to ".$this->mime." from ".$parts['extension'].", no matching binary converter");
					break;
				}
			break;
		}
		if(!empty($this->temp)) {
			unlink($this->temp);
		}
	}

	/**
	 * Combine two audio files
	 *
	 * @param string       $method  'concatenate', 'merge', 'mix', 'mix-power', 'multiply', 'sequence'
	 * @param string|Track $in      File to mix with
	 * @param string       $out     New filename
	 * @return Track
	 */
	public function combine($method, $in, $out) {
			if ($in instanceof self) {
				$in = $in->filename;
			}
			return new static($out);
	}
}
