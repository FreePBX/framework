<?php
/**
 * mm: the PHP media library
 *
 * Copyright (c) 2007-2014 David Persson
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 */

namespace mm\Mime\Type\Magic\Adapter;

use InvalidArgumentException;

/**
 * This adapter supports files like the ones that come with the
 * `mod_mime_magic` Apache Webserver module. Most often you'll find
 * such a file containing MIME type to extension mappings within
 * your apache2 configuration directory as `magic`.
 *
 * @link http://httpd.apache.org/docs/2.2/en/mod/mod_mime_magic.html
 */
class Apache extends \mm\Mime\Type\Magic\Adapter {

	public function __construct($config) {
		$this->_read($config['file']);
	}

	public function analyze($handle, $options = []) {
		return $this->_test($handle, current($this->_items)); // no support for priority
	}

	public function to($type) {
		return $this->_to($type);
	}

	public function register($item) {
		return $this->_register($item);
	}

	protected function _read($file) {
		$handle = fopen($file, 'r');

		$itemRegex = '^(\>*)(\d+)\t+(\S+)\t+([\S^\040]+)\t*([-\w.\+]+\/[-\w.\+]+)*\t*(\S*)$';

		if (!preg_match("/{$itemRegex}/m", fread($handle, 4096))) {
			throw new InvalidArgumentException("File `{$file}` has wrong format");
		}
		fseek($handle, 0);

		while (!feof($handle)) {
			$line = trim(fgets($handle));

			if (empty($line) || $line{0} === '#') {
				continue;
			}

			$line = preg_replace('/(?!\B)\040+/', "\t", $line);

			if (!preg_match('/' . $itemRegex . '/', $line, $matches)) {
				continue;
			}

			$item = [
				'offset'       => intval($matches[2]),
				'value'        => $this->_formatValue($matches[4], $matches[3], true),
				'mask'         => null,
				'range_length' => 0,
				'mime_type'    => empty($matches[5]) ? null : $matches[5],
				'encoding'     => empty($matches[6]) ? null : $matches[6]
			];
			$item['value_length'] = strlen($item['value']);
			$this->_register($item, strlen($matches[1]), 80);
		}
		fclose($handle);
	}
}

?>