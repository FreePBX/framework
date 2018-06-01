<?php
namespace FreePBX\Dialplan;
class Mixmonitor extends Extension{
	var $file;
	var $options;
	var $postcommand;

	function __construct($file, $options = "", $postcommand = "") {
		$this->file = $file;
		$this->options = $options;
		$this->postcommand = $postcommand;
	}

	function output() {
		return "MixMonitor(".$this->file.",".$this->options.",".$this->postcommand.")";
	}
}