<?php
namespace FreePBX;
/**
 * Temp, replace later
 * @var [type]
 */
spl_autoload_register(function ($class) {
	$path = str_replace("\\","/",$class);
	$path = dirname(__DIR__)."/media/".$path.".php";
	if(file_exists($path)) {
		include $path;
	} else {
		echo $path;
		die();
	}
});

class Media {
	private $file;
	private $path;
	private $html5Path;
	private $supported;

	public function __construct($freepbx = null, $track = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;

		$dir = $this->FreePBX->Config->get("ASTVARLIBDIR") . "/fpbx_playback";
		if(!file_exists($dir)) {
			mkdir($dir);
		}
		$this->html5Path = $dir;
	}

	public function getSupportedFormats() {
		if(!empty($this->supported)) {
			return $this->supported;
		}
		$this->supported = \Media\Media::getSupportedFormats();
		return $this->supported;
	}

	public function load($filename) {
		if(!file_exists($filename)) {
			throw new \Exception(_("File does not exist"));
		}
		$this->path = $filename;
		$this->file = new \Media\Media($filename);
	}

	public function generateImage($image) {
		if(!isset($this->file)) {
			throw new \Exception("You must first load an audio file");
		}
		$this->file->image = $image;
	}

	public function convert($newFilename) {
		$this->file->convert($newFilename);
	}

	public function convertMultiple($newFilename,$codecs=array()) {
		$this->file->convertMultiple($newFilename,$codecs);
	}

	public function generateHTML5($dir='') {
		$dir = !empty($dir) ? $dir : $this->html5Path;
		if(!is_writable($dir)) {
			throw new \Exception("Path $dir is not writable");
		}
		$md5 = md5_file($this->path);
		$path_parts = pathinfo(basename($this->path));
		$name = $path_parts['filename'];
		$formats = $f = array("mp3" => "mp3","wav" => "wav","ogg" => "ogg","mp4" => "mp4");
		$file = $dir."/".$name."-".$md5;
		foreach($f as $format) {
			if(file_exists($file.".".$format)) {
				unset($formats[$format]);
			}
		}
		$supported = $this->getSupportedFormats();
		$this->generateImage($dir."/".$name."-".$md5.".png");
		$this->convertMultiple($file,array_diff_key($formats,$supported));
	}
}
