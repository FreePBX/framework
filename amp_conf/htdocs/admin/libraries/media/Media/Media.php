<?php
namespace Media;
use mm\Mime\Type;

class Media {
	private $track;

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
		$this->setupDrivers();
	}

	private function setupDrivers() {
		Type::config('magic', array(
			'adapter' => 'Freedesktop',
			'file' => dirname(__DIR__).'/resources/magic.db'
		));
		Type::config('glob', array(
			'adapter' => 'Freedesktop',
			'file' => dirname(__DIR__).'/resources/glob.db'
		));
		echo Type::guessExtension($this->track);
		echo Type::guessType($this->track);
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
	public function convert($filename) {
		return new static($filename);
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
