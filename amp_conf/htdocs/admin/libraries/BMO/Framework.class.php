<?php
/**
 * This is the FreePBX Big Module Object.
 *
 * Framework built-in BMO Class.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Framework extends FreePBX_Helpers implements BMO {
	public function __construct($freepbx = null) {
		$this->freepbx = $freepbx;
	}
	/** BMO Required Interfaces */
	public function install() {
	}
	public function uninstall() {
	}
	public function backup() {
	}
	public function restore($backup) {
	}
	public function runTests($db) {
		return true;
	}
	public function doConfigPageInit() {
	}

	public function ajaxRequest($req, &$setting) {
		switch ($req) {
			case 'authping':
			case 'scheduler':
			case 'sysupdate':
			case 'reload':
			return true;
		}
		return false;
	}

	public function ajaxHandler() {
		switch ($_REQUEST['command']) {
		case 'authping':
			return 'authpong';
		case 'scheduler':
			$s = new Builtin\UpdateManager();
			return $s->ajax($_REQUEST);
		case 'sysupdate':
			$s = new Builtin\SystemUpdates();
			return $s->ajax($_REQUEST);
		case 'reload':
			return $this->doReload();
		}
		return false;
	}

	public function doReload($passthru=false) {
		$AMPSBIN = $this->freepbx->Config->get('AMPSBIN');
		$process = new \Symfony\Component\Process\Process($AMPSBIN.'/fwconsole reload --json');
		$process->setTimeout(1800);
		$process->run();
		$output = $process->getOutput();
		$code = $process->getExitCode();

		preg_match_all("/^({.*})$/m", $output, $array);
		$output = !empty($array[1][0]) ? json_decode($array[1][0],true) : array("error" => _('Unknown Error. Please Run: fwconsole reload --verbose '));

		if($code !== 0 || isset($output['error'])) {
			return [
				'status' => false,
				'message' => $output['error'],
				'code' => $code,
				'raw' => $output
			];
		}

		return [
			'status' => true,
			'message' => _('Successfully reloaded'),
			'code' => $code,
			'raw' => $output
		];
	}
}
