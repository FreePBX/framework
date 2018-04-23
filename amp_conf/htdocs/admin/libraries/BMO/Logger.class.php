<?php
namespace FreePBX;
use Monolog as Mono;
use Monolog\Handler\StreamHandler;

class Logger {
	public function __construct() {
		$this->monoLog = new Mono\Logger('PBX');
		$this->customLog = null;	
		$this->FreePBX = \FreePBX::Create();
		$this->systemID = $this->FreePBX->Config->get('FREEPBX_SYSTEM_IDENT');
		$this->monoLog->pushHandler(new StreamHandler('/var/log/asterisk/freepbx.log', Mono\Logger::INFO));
		$this->attachHandlers();
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
	public function createCustomLog($channel='custom', $path = '/var/log/asterisk/custom.log',$truncate = false,$logLevel = ''){
			$this->customLog = new Mono\Logger($channel);
			if($truncate){
				@unlink($path);
			}
			$logLevel = (!empty($logLevel))?$logLevel:'INFO';
			if(is_object($path)){
				return $this->addHandler($this->customLog,$path);
			}
			$levels = [
				'DEBUG' => 100,
				'NOTICE' => 250,
				'WARNING' => 300,
				'ERROR' => 400,
				'CRITICAL' =>500,
				'ALERT' => 550,
				'INFO' => 200,
				'EMERGENCY' => 600
			];
			$obj = new StreamHandler($path,$levels[$logLevel]);
			$this->addHandler($this->customLog,$obj);
			return;
	}
	public function addHandler($logger,$obj){
		return $logger->pushHandler($obj);
	}
	
public function attachHandlers(){
		if(!is_object($this->loggingHooks)){
			$this->loggingHooks = new \SplObjectStorage();
		}
		$this->FreePBX->Hooks->processHooks($this->loggingHooks);
		foreach($this->loggingHooks as $hook){
			try{ 
				$this->addHandler($thid->monoLog,$hook);
			}catch(\Exception $e){
				//don't  let a bad apple mess it up for everyone
				dbug('Backup: custom handler skipped');
				dbug($e->getMessage());
				continue;
			}
		}
	}
}
