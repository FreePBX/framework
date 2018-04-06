<?php
namespace FreePBX;
use Monolog as Mono;
use Monolog\Handler\StreamHandler;

class Logger {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new \ArgumentException("Not given a FreePBX Object");
		}
		$this->monoLog = new Mono\Logger('PBX');
		$this->systemID = $freepbx->Config->get('FREEPBX_SYSTEM_IDENT');
		$this->customLog = null;	
		$this->FreePBX = $freepbx;
		$this->monoLog->pushHandler(new StreamHandler('/var/log/asterisk/flogger.log', Mono\Logger::INFO));
	}
	public function install() {}
	public function uninstall() {}
	public function backup() {}
	public function restore($backup) {}
	public function doConfigPageInit($page) {}
	
	/**
	 * Write to log channel
	 *
	 * @param string $channel channel to log to
	 * @param string $message $message to log
	 * @param string $logLevel Level to log at
	 * @return void
	 * **LEVELS**
	 * DEBUG
	 * INFO
	 * NOTICE
	 * WARNING
	 * ERROR
	 * CRITICAL
	 * ALERT
	 * EMERGENCY
	 */
	public function logWrite($channel = '',$message='',$custom = false,$logLevel = 'INFO' ){
		$logger = $this->monoLog;
		if($custom){
			$logger = $this->customLog;
		}
		$channel = (!empty($channel))?$channel:'freepbx';
		switch ($logLevel) {
			case 'DEBUG':
				return$logger->debug($message,['serverId' => $this->systemID]);
			case 'NOTICE':
				return $logger->notice($message,['serverId' => $this->systemID]);
			case 'WARNING':
				return $logger->warning($message,['serverId' => $this->systemID]);
			case 'ERROR':
				return $logger->error($message,['serverId' => $this->systemID]);
			case 'CRITICAL':
				return $logger->critical($message,['serverId' => $this->systemID]);
			case 'ALERT':
				return $logger->alert($message,['serverId' => $this->systemID]);
			case 'EMERGENCY':
				return $logger->emergency($message,['serverId' => $this->systemID]);
			case 'INFO':
			default:
				return $logger->info($message,['serverId' => $this->systemID]);
		}
	}
	public function createCustomLog($channel='custom', $path = '/var/log/asterisk/custom.log',$logLevel = ''){
			$this->customLog = new Logger($channel);
			$logLevel = (!empty($logLevel))?$logLevel:'INFO';
			if(is_object($path)){
				return $this->addHandler($this->customLogger,$obj);
			}
			$obj = new StreamHandler($path,Mono\Logger::$logLevel);
			$this->addHandler($this->customLogger,$obj);
			return;
	}
	public function addHandler($logger,$obj){
		return $logger->pushHandler($obj);
	}
}
