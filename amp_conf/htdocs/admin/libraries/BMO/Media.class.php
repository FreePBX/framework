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
	public function __construct($freepbx = null, $track = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
	}

	public function load($filename) {
		return new \Media\Media($filename);
	}
}
