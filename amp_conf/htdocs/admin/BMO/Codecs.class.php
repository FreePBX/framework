<?php
// vim: set ai ts=4 sw=4 ft=php:

class Codecs {

	public function getAll() {
		$codecs['audio'] = $this->getAudio();
		$codecs['video'] = $this->getVideo();
		$codecs['text'] = $this->getText();
		$codecs['image'] = $this->getImage();
		$codecs['all'] = array_merge($this->getAudio(),  $this->getVideo(),  $this->getText(), $this->getImage());
		return $codecs;
	}

	public function getVideo() {
		return array("h261", "h263", "h263p", "h264", "mpeg4", "vp8");
	}

	public function getAudio() {
		return array("g722","ulaw","alaw","gsm","g723","g726","adpcm","slin","lpc10","g729","speex","speex16","ilbc","g726aal2","slin16","siren7","siren14","testlaw","g719","speex32","slin12","slin24","slin32","slin44","slin48","slin96","slin192","opus");
	}

	public function getText() {
		return array("red", "t140");
	}

	public function getImage() {
		return array("jpeg", "png");
	}
}

