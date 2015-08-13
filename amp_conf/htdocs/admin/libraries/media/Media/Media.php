<?php
namespace Media;
use mm\Mime\Type;

class Media {
	private $track;
	private $extension;
	private $mime;
	private $driver;

	public function __construct($filename) {
		$this->loadTrack($filename);
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
		switch($mime) {
			case "audio/x-wav":
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
					$driver->background = true;
					$driver->convert($newFilename,$extension,$mime);
				} else {
					throw new \Exception("Cant convert to $mime because Lame is not installed");
				}
			break;
			default:
				throw new \Exception("Unable to convert to $mime, no matching binary converter");
			break;
		}
		//return new static($newFilename);
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
