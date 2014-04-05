<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * Copyright (C) 2013 Schmooze Com, INC
 * Copyright (C) 2013 Rob Thomas <rob.thomas@schmoozecom.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   FreePBX BMO
 * @author    Rob Thomas <rob.thomas@schmoozecom.com>
 * @license   AGPL v3
 */
class Codecs {

	public function getAll() {
		$codecs['audio'] = $this->getAudio();
		$codecs['video'] = $this->getVideo();
		$codecs['text'] = $this->getText();
		$codecs['image'] = $this->getImage();
		$codecs['all'] = array_merge($this->getAudio(),  $this->getVideo(),  $this->getText(), $this->getImage());
		return $codecs;
	}

	public function getVideo($defaults = false) {
		$ret = array("h261" => false, "h263" => false, "h263p" => false, "h264" => false, "mpeg4" => false, "vp8" => false);
		if ($defaults) {
			$ret['h264'] = "1";
			$ret['mpeg4'] = "2";
		}

		return $ret;
	}

	public function getAudio($defaults = false) {
		$ret = array(
			"g722" => false,
			"ulaw" => false,
			"alaw" => false,
			"gsm" => false,
			"g729" => false,
			"g723" => false,
			"g726" => false,
			"adpcm" => false,
			"slin" => false,
			"lpc10" => false,
			"speex" => false,
			"speex16" => false,
			"ilbc" => false,
			"g726aal2" => false,
			"slin16" => false,
			"siren7" => false,
			"siren14" => false,
			"testlaw" => false,
			"g719" => false,
			"speex32" => false,
			"slin12" => false,
			"slin24" => false,
			"slin32" => false,
			"slin44" => false,
			"slin48" => false,
			"slin96" => false,
			"slin192" => false,
			"opus" => false
		);

		if ($defaults) {
			$ret['ulaw'] = "1";
			$ret['alaw'] = "2";
			$ret['gsm'] = "3";
		}

		return $ret;
	}

	public function getText() {
		return array("red" => false, "t140" => false);
	}

	public function getImage() {
		return array("jpeg" => false, "png" => false);
	}
}

