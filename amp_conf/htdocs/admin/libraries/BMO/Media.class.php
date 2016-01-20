<?php
namespace FreePBX;
/**
 * Temp, replace later
 */
spl_autoload_register(function ($class) {
	$path = str_replace("\\","/",$class);
	$path = dirname(__DIR__)."/media/".$path.".php";
	if(file_exists($path)) {
		include $path;
	}
});

/**
 * Media Class for FreePBX
 * Deals with converting to various formats
 * Also deals with generating HTML5 formats
 */
use Sinergi\BrowserDetector\Browser;
class Media extends DB_Helper{
	private $file;
	private $path;
	private $html5Path;
	private $supported;

	public function __construct($freepbx = null, $track = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;

		$dir = $this->FreePBX->Config->get("AMPPLAYBACK");
		if(!file_exists($dir)) {
			mkdir($dir);
			$ampowner = $this->FreePBX->Config->get('AMPASTERISKWEBUSER');
			$ampgroup =  $ampowner != $this->FreePBX->Config->get('AMPASTERISKUSER') ? $this->FreePBX->Config->get('AMPASTERISKGROUP') : $ampowner;
			chown($dir, $ampowner);
			chgrp($dir, $ampgroup);
		}
		$this->html5Path = $dir;
	}

	/**
	 * Get supported HTML5 formats
	 * @return array Return array of formats
	 */
	public function getSupportedHTML5Formats() {
		$browser = $this->detectSupportedFormats();
		$formats = $this->getSupportedFormats();
		$html5 = array("oga", "mp3", "m4a", "wav");
		$final = array();
		$missing = array();
		$unsupported = array();
		foreach($html5 as $i) {
			if(in_array($i,$browser) && in_array($i,$formats['out'])) {
				$final[] = $i;
			} elseif(in_array($i,$browser) && !in_array($i,$formats['out'])) {
				$missing[] = $i;
			} else {
				$unsupported[] = $i;
			}
		}

		$nt = notifications::create();
		$mmm = $this->getConfig('mediamissingmessage');
		if(!empty($missing) && empty($mmm)) {
			$brand = $this->FreePBX->Config->get("DASHBOARD_FREEPBX_BRAND");
			$nt->add_notice("framework", "missing_html5", _("Missing HTML5 format converters"), sprintf(_("You are missing support for the following HTML5 codecs: %s. To fully support HTML5 browser playback you will need to install programs that can not be distributed with %s. If you'd like to install the binaries needed for these conversions click 'Resolve' in the lower left corner of this message. You can also safely ignore this message but browser playback might not work in your browser."),implode(",",$missing),$brand), "http://wiki.freepbx.org/display/FOP/Installing+Media+Conversion+Libraries",true,true);
			$this->setConfig('mediamissingmessage',true);
		} elseif(empty($missing) && !empty($mmm)) {
			$nt->delete("framework", "missing_html5");
			$this->setConfig('mediamissingmessage', false);
		}
		return !empty($final[0]) ? array($final[0]) : array();
	}

	/**
	 * Get all supported formats
	 * @return array Array of all supported formats
	 */
	public function getSupportedFormats() {
		if(!empty($this->supported)) {
			return $this->supported;
		}
		$this->supported = \Media\Media::getSupportedFormats();
		return $this->supported;
	}

	/**
	 * Load file
	 * @param  string $filename Full path to audio file
	 */
	public function load($filename) {
		if(!file_exists($filename)) {
			throw new \Exception(_("File does not exist"));
		}
		$this->path = $filename;
		$this->file = new \Media\Media($filename);
	}

	/**
	 * Generate an image from this audio file
	 * @param  string $image Full path to image
	 */
	public function generateImage($image) {
		if(!isset($this->file)) {
			throw new \Exception("You must first load an audio file");
		}
		$this->file->image = $image;
	}

	/**
	 * Convert a file to another format
	 * @param  string $newFilename The full path to the new file
	 */
	public function convert($newFilename) {
		session_write_close();
		$this->file->convert($newFilename);
	}

	/**
	 * Convert one file into multiple formats
	 * @param  string $newFilename The new file name (extension will be replaced)
	 * @param  array  $formats      Array of supported formats
	 */
	public function convertMultiple($newFilename,$formats=array()) {
		session_write_close();
		$this->file->convertMultiple($newFilename,$formats);
	}

	/**
	 * Generate HTML5 formats
	 * @param  string $dir Directory to output to, if not set will use default
	 * @return array      Array of converted files
	 */
	public function generateHTML5($dir='') {
		session_write_close();
		$dir = !empty($dir) ? $dir : $this->html5Path;
		if(!is_writable($dir)) {
			throw new \Exception("Path $dir is not writable");
		}
		$md5 = md5_file($this->path);
		$path_parts = pathinfo(basename($this->path));
		$name = $path_parts['filename'];
		$supportedFormats = $this->getSupportedHTML5Formats();
		//because ogg and oga are interchangeable
		if(in_array('oga',$supportedFormats)) {
			$supportedFormats = array("ogg");
		}
		$formats = $f = array("mp3" => "mp3","wav" => "wav","ogg" => "ogg","mp4" => "mp4");
		$file = $dir."/".$name."-".$md5;
		$file = str_replace(".","_",$file);
		$converted = array();
		foreach($f as $format) {
			if(in_array($format,$supportedFormats)) {
				if(file_exists($file.".".$format)) {
					unset($formats[$format]);
				}
				$converted[$format] = basename($file.".".$format);
			}
		}
		//because ogg and oga are interchangeable
		if(isset($converted['ogg'])) {
			$converted['oga'] = $converted['ogg'];
			unset($converted['ogg']);
		}

		/** This is broken for some stupid reason **/
		//$this->generateImage($dir."/".$name."-".$md5.".png");
		$convert = array_intersect($formats,$supportedFormats);
		if(!empty($convert)) {
			$this->convertMultiple($file,$convert);
		}
		return $converted;
	}

	/**
	 * Stream HTML5 compatible file
	 * @param  string $filename The file name (relative)
	 * @param  boolean $download Whether to stream or download
	 */
	public function getHTML5File($filename, $download=false) {
		//Session write close because Safari slams us with requests
		//asking for 2 bytes before proceeding to then request the full file.
		//As is the case with PHP sessions are locked until the previous session
		//has completed. When the server is slammed multiple requests are
		//blocked, therefore we always close the session before streaming the file
		//http://konrness.com/php5/how-to-prevent-blocking-php-requests/
		session_write_close();
		$filename = $this->html5Path ."/".$filename;
		$format = pathinfo($filename, PATHINFO_EXTENSION);
		if (is_file($filename)){
			switch($format) {
				case "mp3":
					$ct = "audio/mpeg";
				break;
				case "m4a":
					$ct = "audio/mp4";
				break;
				case "wav":
					$ct = "audio/wav";
				break;
				case "oga":
				case "ogg":
					$ct = "audio/ogg";
					$format = "oga";
				break;
				default:
					throw new \Exception("I have no idea was this file is: $filename");
				break;
			}
			header("Content-type: ".$ct); // change mimetype
			if (!$download && isset($_SERVER['HTTP_RANGE'])){ // do it for any device that supports byte-ranges not only iPhone
				$size   = filesize($filename); // File size
				$length = $size;           // Content length
				$start  = 0;               // Start byte
				$end    = $size - 1;       // End byte

				// Now that we've gotten so far without errors we send the accept range header
				/* At the moment we only support single ranges.
				* Multiple ranges requires some more work to ensure it works correctly
				* and comply with the specifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
				*
				* Multirange support annouces itself with:
				* header('Accept-Ranges: bytes');
				*
				* Multirange content must be sent with multipart/byteranges mediatype,
				* (mediatype = mimetype)
				* as well as a boundry header to indicate the various chunks of data.
				*/
				header("Accept-Ranges: 0-$length");
				// header('Accept-Ranges: bytes');
				// multipart/byteranges
				// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
				if (isset($_SERVER['HTTP_RANGE'])){
					$c_start = $start;
					$c_end   = $end;

					// Extract the range string
					list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
					// Make sure the client hasn't sent us a multibyte range
					if (strpos($range, ',') !== false){
						header('HTTP/1.1 416 Requested Range Not Satisfiable');
						header("Content-Range: bytes $start-$end/$size");
						exit;
					}
					// If the range starts with an '-' we start from the beginning
					// If not, we forward the file pointer
					// And make sure to get the end byte if specified
					if ($range{0} == '-'){
						// The n-number of the last bytes is requested
						$c_start = $size - substr($range, 1);
					} else {
						$range  = explode('-', $range);
						$c_start = $range[0];
						$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
					}
					/* Check the range and make sure it's treated according to the specs.
					* http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
					*/
					// End bytes can not be larger than $end.
					$c_end = ($c_end > $end) ? $end : $c_end;
					// Validate the requested range and return an error if it's not correct.
					if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size){
						header('HTTP/1.1 416 Requested Range Not Satisfiable');
						header("Content-Range: bytes $start-$end/$size");
						// (?) Echo some info to the client?
						exit;
					}

					$start  = $c_start;
					$end    = $c_end;
					$length = $end - $start + 1; // Calculate new content length
					header('HTTP/1.1 206 Partial Content');
				}

				// Notify the client the byte range we'll be outputting
				header("Content-Range: bytes $start-$end/$size");
				header("Content-Length: $length");
				header('Content-Disposition: attachment;filename="' . basename($filename).'"');

				$buffer = 1024 * 8;
				ob_end_clean();
				ob_start();
				set_time_limit(0);
				while(true) {
					$fp = fopen($filename, "rb");
					fseek($fp, $start);
					if(!feof($fp) && ($p = ftell($fp)) <= $end) {
						if ($p + $buffer > $end) {
							$buffer = $end - $p + 1;
						}
						$contents = fread($fp, $buffer);
						$start = $start + $buffer;
						fclose($fp);
						echo $contents;
						ob_flush();
						flush();
					} else {
						fclose($fp);
						break;
					}
				}
			} else {
				header("Content-length: " . filesize($filename));
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header('Content-Disposition: attachment;filename="' . basename($filename).'"');
				set_time_limit(0);
				$fp = fopen($filename, "rb");
				fpassthru($fp);
				ob_flush();
				flush();
				fclose($fp);
			}
		}
	}

	/**
	 * Detect what this browser supports to minimize processing
	 * Sort by priority of codec to use
	 * -Patent Unencumbered first
	 * -Patent encumbered in the middle, first m4a as it's not as bad as mp3
	 * -Wav always last (as it's filesize is HUGE)
	 * Used: html5test.com/results/search.html
	 * @return array array of browser supported formats
	 */
	private function detectSupportedFormats() {
		$browser = new Browser();
		$formats = array();
		switch($browser->getName()) {
			case Browser::OPERA:
			case Browser::KONQUEROR:
			case Browser::FIREBIRD:
			case Browser::FIREFOX:
			case Browser::SEAMONKEY:
			case Browser::ICEWEASEL:
			case Browser::MOZILLA:
			case Browser::CHROME:
			case Browser::BLACKBERRY:
				$formats = array("oga", "m4a", "mp3", "wav");
			break;
			case Browser::ICAB:
			case Browser::NOKIA_S60:
			case Browser::NOKIA:
			case Browser::EDGE:
			case Browser::YANDEX:
			case Browser::PHOENIX:
			case Browser::SAFARI:
				$formats = array("m4a","mp3","wav");
			break;
			case Browser::VIVALDI:
			case Browser::SHIRETOKO:
			case Browser::ICECAT:
				$formats = array("oga","wav");
			break;
			case Browser::IE:
			case Browser::POCKET_IE:
				$formats = array("m4a","mp3");
			break;
			case Browser::OMNIWEB:
				$formats = array("mp3","wav");
			break;
			case Browser::WEBTV:
			case Browser::OPERA_MINI:
			case Browser::AMAYA:
			case Browser::LYNX:
			case Browser::NAVIGATOR:
			case Browser::NETSCAPE_NAVIGATOR:
			case Browser::GOOGLEBOT:
			case Browser::SLURP:
			case Browser::W3CVALIDATOR:
			case Browser::MSNBOT:
			case Browser::GALEON:
			case Browser::MSN:
			case Browser::NETPOSITIVE:
			case Browser::GSA:
				$formats = array();
			break;
			default: //not sure of the browser type so just do them all
				$formats = array("oga", "wav", "mp3", "m4a");
			break;
		}
		return $formats;
	}
}
