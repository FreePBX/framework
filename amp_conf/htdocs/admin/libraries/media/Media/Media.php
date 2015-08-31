<?php
namespace Media;
use mm\Mime\Type;
/**
 * FreePBX multi-audio convert engine
 * This class will determine the best converting engine to use
 * for cross converting audio files
 */
class Media {
	private $track;
	private $extension;
	private $mime;
	private $driver;
	private $tempDir;
	private $drivers = array();
	public $image;

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
		if(empty($this->extension) || $this->extension == "bin") {
			$parts = pathinfo($this->track);
			$this->extension = $parts['extension'];
		}
		$this->mime = Type::guessType($this->track);
	}

	/**
	 * Get all known drivers
	 * @return array Array of driver names
	 */
	private function getDrivers() {
		if(!empty($this->drivers)) {
			return $this->drivers;
		}
		foreach(glob(__DIR__."/Driver/Drivers/*.php") as $file) {
			$this->drivers[] = basename($file,".php");
		}
		return $this->drivers;
	}

	/**
	 * Get all supported formats
	 * @return array Array of supported audio formats
	 */
	public static function getSupportedFormats() {
		$formats = array(
			"out" => array(),
			"in" => array()
		);
		if(Driver\Drivers\AsteriskShell::installed()) {
			$formats = Driver\Drivers\AsteriskShell::supportedCodecs($formats);
		}
		if(Driver\Drivers\SoxShell::installed()) {
			$formats = Driver\Drivers\SoxShell::supportedCodecs($formats);
		}
		if(Driver\Drivers\Mpg123Shell::installed()) {
			$formats = Driver\Drivers\Mpg123Shell::supportedCodecs($formats);
		}
		if(Driver\Drivers\FfmpegShell::installed()) {
			$formats = Driver\Drivers\FfmpegShell::supportedCodecs($formats);
		}
		if(Driver\Drivers\LameShell::installed()) {
			$formats = Driver\Drivers\LameShell::supportedCodecs($formats);
		}
		return $formats;
	}

	/**
	 * Convert the track using the best possible means
	 * @param  string $filename The new filename
	 * @return object           New Media Object
	 */
	public function convert($newFilename) {
		$skipAsterisk = false;
		//Asterisk can only deal with 8000k 1channel audio
		if($this->extension == "wav") {
			$fp = fopen($this->track, 'r');
			if (fread($fp,4) == "RIFF") {
				fseek($fp, 20);
				$rawheader = fread($fp, 16);
				$headers = unpack('vtype/vchannels/Vsamplerate/Vbytespersec/valignment/vbits',$rawheader);
				if($headers['channels'] != 1 || $headers['samplerate'] != 8000) {
					$skipAsterisk = true;
				}
			} else {
				$skipAsterisk = true;
			}
		}
		//generate intermediary file
		foreach($this->getDrivers() as $driver) {
			if($skipAsterisk && $driver == "AsteriskShell") {
				continue;
			}
			$class = "Media\\Driver\\Drivers\\".$driver;
			if($class::installed() && $class::isCodecSupported($this->extension,"in")) {
				$driver = new $class($this->track,$this->extension,$this->mime);
				$ts = time().rand(0,1000);
				$driver->convert($this->tempDir."/temp.".$ts.".wav","wav","audio/x-wav");
				$intermediary['path'] = $this->tempDir."/temp.".$ts.".wav";
				$intermediary['extension'] = "wav";
				$intermediary['mime'] = "audio/x-wav";
				break;
			}
		}
		if(empty($intermediary)) {
			throw new \Exception("No Driver found for ".$this->extension);
		}
		//generate wav form png
		if(isset($this->image)) {
			$waveform = new \Jasny\Audio\Waveform($intermediary['path'], array("width" => 700));
			$waveform->save("png",$this->image);
		}

		$extension = Type::guessExtension($newFilename);
		$parts = pathinfo($newFilename);
		if(empty($extension) || $extension == "bin") {
			$extension = $parts['extension'];
		}
		$mime = Type::guessType($newFilename);
		//generate final file
		foreach($this->getDrivers() as $driver) {
			$class = "Media\\Driver\\Drivers\\".$driver;
			if($class::installed() && $class::isCodecSupported($extension,"out")) {
				$driver = new $class($intermediary['path'],$intermediary['extension'],$intermediary['mime']);
				$driver->convert($newFilename,$extension,$mime);
				break;
			}
		}
		if(!empty($intermediary['path']) && file_exists($intermediary['path'])) {
			unlink($intermediary['path']);
			unset($intermediary);
		}

		return file_exists($newFilename);
	}

	/**
	 * Convert the track using the best possible means
	 * @param  string $filename The new filename
	 * @return object           New Media Object
	 */
	public function convertMultiple($newFilename,$codecs=array()) {
		if(empty($codecs)) {
			return false;
		}
		$skipAsterisk = false;
		//Asterisk can only deal with 8000k 1channel audio
		if($this->extension == "wav") {
			$fp = fopen($this->track, 'r');
			if (fread($fp,4) == "RIFF") {
				fseek($fp, 20);
				$rawheader = fread($fp, 16);
				$headers = unpack('vtype/vchannels/Vsamplerate/Vbytespersec/valignment/vbits',$rawheader);
				if($headers['channels'] != 1 || $headers['samplerate'] != 8000) {
					$skipAsterisk = true;
				}
			} else {
				$skipAsterisk = true;
			}
		}
		//generate intermediary file
		foreach($this->getDrivers() as $driver) {
			if($skipAsterisk && $driver == "AsteriskShell") {
				continue;
			}
			$class = "Media\\Driver\\Drivers\\".$driver;
			if($class::installed() && $class::isCodecSupported($this->extension,"in")) {
				$driver = new $class($this->track,$this->extension,$this->mime);
				$ts = time().rand(0,1000);
				$driver->convert($this->tempDir."/temp.".$ts.".wav","wav","audio/x-wav");
				$intermediary['path'] = $this->tempDir."/temp.".$ts.".wav";
				$intermediary['extension'] = "wav";
				$intermediary['mime'] = "audio/x-wav";
				break;
			}
		}
		//generate wav form png
		if(isset($this->image)) {
			$waveform = new \Jasny\Audio\Waveform($intermediary['path'], array("width" => 700));
			$waveform->save("png",$this->image);
		}

		//generate final file
		foreach($codecs as $codec) {
			$parts = pathinfo($newFilename);
			$base = dirname($newFilename);
			$file = $base."/".$parts['filename'].".".$codec;
			$extension = Type::guessExtension($file);
			if(empty($extension) || $extension == "bin") {
				$extension = $codec;
			}
			$mime = Type::guessType($file);
			foreach($this->getDrivers() as $driver) {
				$class = "Media\\Driver\\Drivers\\".$driver;
				if($class::installed() && $class::isCodecSupported($extension,"out")) {
					$driver = new $class($intermediary['path'],$intermediary['extension'],$intermediary['mime']);
					$driver->convert($file,$extension,$mime);
					break;
				}
			}
		}
		if(!empty($intermediary['path']) && file_exists($intermediary['path'])) {
			unlink($intermediary['path']);
			unset($intermediary);
		}

		return file_exists($newFilename);
	}
}
