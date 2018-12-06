<?php
namespace FreePBX;
use Monolog as Mono;
use Monolog\Handler\StreamHandler;

class Logger {
	private $logDrivers = array();
	private $systemID;
	private $FreePBX;
	private $defaultLogDir;
	private $configuredStreamPaths = array();

	const DEBUG = 100;
	const INFO = 200;
	const NOTICE = 250;
	const WARNING = 300;
	const ERROR = 400;
	const CRITICAL = 500;
	const ALERT = 550;
	const EMERGENCY = 600;

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new \Exception("Need to be instantiated with a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->systemID = $this->FreePBX->Config->get('FREEPBX_SYSTEM_IDENT');
		$this->defaultLogDir = $this->FreePBX->Config->get('ASTLOGDIR');
	}

	/**
	 * Write to freepbx.log as channel PBX
	 *
	 * @param string $message $message to log
	 * @param string $logLevel Level to log at
	 * @return void
	 */
	public function logWrite($message='',array $context = array(),$logLevel = self::INFO ){
		/** Anything with "LOG_" is added for backwards compatibility with FreePBX logging */
		switch ($logLevel) {
			case 'LOG_DEBUG':
			case 'DEBUG':
			case self::DEBUG:
				$logLevel = self::DEBUG;
			case 'LOG_NOTICE':
			case 'NOTICE':
			case self::NOTICE:
				$logLevel = self::NOTICE;
			case 'LOG_WARNING':
			case 'WARNING':
			case self::WARNING:
				$logLevel = self::WARNING;
			case 'LOG_ERR':
			case 'ERROR':
			case self::ERROR:
				$logLevel = self::ERROR;
			case 'LOG_CRIT':
			case 'CRITICAL':
			case self::CRITICAL:
				$logLevel = self::CRITICAL;
			case 'LOG_ALERT':
			case 'ALERT':
			case self::ALERT:
				$logLevel = self::ALERT;
			case 'EMERGENCY':
			case self::EMERGENCY:
				$logLevel = self::EMERGENCY;
			case 'LOG_INFO':
			case 'INFO':
			case self::INFO:
			default:
				$logLevel = self::INFO;
		}
		return $this->channelLogWrite('',$message, $context, $logLevel);
	}

	/**
	 * Write to freepbx.log as channel $channel
	 *
	 * @param string $channel channel to log to
	 * @param string $message $message to log
	 * @param string $logLevel Level to log at
	 * @return void
	 */
	public function channelLogWrite($channel,$message='',array $context = array(),$logLevel = self::INFO ){
		return $this->driverChannelLogWrite('default',$channel,$message,$context,$logLevel);
	}

	/**
	 * Write to $driver.log
	 *
	 * @param string $driver Driver to log to
	 * @param string $message $message to log
	 * @param string $logLevel Level to log at
	 * @return void
	 */
	public function driverLogWrite($driver,$message='',array $context = array(),$logLevel = self::INFO){
		return $this->driverChannelLogWrite($driver, '', $message, $context, $logLevel);
	}

	/**
	 * Write to $driver.log as channel $channel
	 *
	 * @param string $driver
	 * @param string $channel
	 * @param string $message
	 * @param string $logLevel
	 * @return void
	 */
	public function driverChannelLogWrite($driver,$channel='',$message='',array $context = array(),$logLevel = self::INFO){
		if(!isset($this->logDrivers[$driver])) {
			if($driver === 'default') {
				$this->createLogDriver('default', $this->defaultLogDir.'/freepbx.log');
			} else {
				$this->createLogDriver($driver, $this->defaultLogDir.'/'.$driver.'.log');
			}
		}
		$logger = !empty($channel) ? $this->logDrivers[$driver]->withName($channel) : $this->logDrivers[$driver];
		switch ($logLevel) {
			case self::DEBUG:
				return $logger->debug($message,$context);
			case self::NOTICE:
				return $logger->notice($message,$context);
			case self::WARNING:
				return $logger->warning($message,$context);
			case self::ERROR:
				return $logger->error($message,$context);
			case self::CRITICAL:
				return $logger->critical($message,$context);
			case self::ALERT:
				return $logger->alert($message,$context);
			case self::EMERGENCY:
				return $logger->emergency($message,$context);
			case self::INFO:
			default:
				return $logger->info($message,$context);
		}
	}

	/**
	 * Create a log driver that will log to $driver.log in the default log path
	 *
	 * @param string $driver
	 * @param string $path
	 * @param constant $minLogLevel
	 * @return object
	 */
	public function createLogDriver($driver, $path, $minLogLevel = self::INFO){
			if(isset($this->logDrivers[$driver])) {
				return $this->logDrivers[$driver];
			}
			if(in_array($path,$this->configuredStreamPaths)) {
				throw new \Exception("Multiple loggers for the same file isn't allowed");
			}
			$dateFormat = "Y-M-d H:i:s";
			$output = "[%datetime%] [%channel%.%level_name%]: %message% %context% %extra%\n";
			$formatter = new Mono\Formatter\LineFormatter($output, $dateFormat);
			$this->logDrivers[$driver] = new Mono\Logger($driver);
			$stream = new StreamHandler($path,$minLogLevel);
			$stream->setFormatter($formatter);
			$this->logDrivers[$driver]->pushHandler($stream);
			$this->configuredStreamPaths[$driver] = $path;
			return $this->logDrivers[$driver];
	}

	/**
	 * Get the Monologger driver object
	 *
	 * @param string $driver
	 * @return object
	 */
	public function getDriver($driver) {
		if(!isset($this->logDrivers[$driver])) {
			$this->createLogDriver($driver, $this->defaultLogDir.'/'.$driver.'.log');
		}
		return $this->logDrivers[$driver];
	}
}
