<?php
namespace FreePBX\Install;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\ProgressBar;

class FreePBXInstallCommand extends Command {
	private $rootPath = null;
	private $settings = array(
		'dbengine' => array(
			'default' => 'mysql',
	 		'description' => 'Database engine'
		),
		'dbname' => array(
			'default' => 'asterisk',
	 		'description' => 'Database name'
		),
		'cdrdbname' => array(
			'default' => 'asteriskcdrdb',
			'description' => 'CDR Database name'
		),
		'dbuser' => array(
			'default' => 'root',
	 		'description' => 'Database username'
		),
		'dbpass' => array(
			'default' => '',
	 		'description' => 'Database password',
		),
		'user' => array(
			'default' => 'asterisk',
	 		'description' => 'File owner user'
		),
		'group' => array(
			'default' => 'asterisk',
	 		'description' => 'File owner group'
		),
		'dev-links' => array(
	 		'description' => 'Make links to files in the source directory instead of copying (developer option)'
		),
		'webroot' => array(
			'default' => '/var/www/html',
	 		'description' => 'Filesystem location from which FreePBX files will be served'
		),
		'astetcdir' => array(
			'default' => '/etc/asterisk',
			'description' => 'Filesystem location from which Asterisk configuration files will be served'
		),
		'astmoddir' => array(
			'default' => '/usr/lib/asterisk/modules',
			'description' => 'Filesystem location for Asterisk modules'
		),
		'astvarlibdir' => array(
			'default' => '/var/lib/asterisk',
			'description' => 'Filesystem location for Asterisk lib files'
		),
		'astagidir' => array(
			'default' => '/var/lib/asterisk/agi-bin',
			'description' => 'Filesystem location for Asterisk agi files'
		),
		'astspooldir' => array(
			'default' => '/var/spool/asterisk',
			'description' => 'Location of the Asterisk spool directory'
		),
		'astrundir' => array(
			'default' => '/var/run/asterisk',
			'description' => 'Location of the Asterisk run directory'
		),
		'astlogdir' => array(
			'default' => '/var/log/asterisk',
			'description' => 'Location of the Asterisk log files'
		),
		'ampbin' => array(
			'default' => '/var/lib/asterisk/bin',
			'description' => 'Location of the FreePBX command line scripts'
		),
		'ampsbin' => array(
			'default' => '/usr/sbin',
			'description' => 'Location of the FreePBX (root) command line scripts'
		),
	);

	protected function configure() {
		$this
			->setName('install')
			->setDescription('FreePBX Installation Utility')
			;

		if (PHP_OS == 'FreeBSD') {
			$this->settings['astetcdir']['default'] = '/usr/local/etc/asterisk';
			$this->settings['astmoddir']['default'] = '/usr/local/lib/asterisk/modules';
			$this->settings['astvarlibdir']['default'] = '/usr/local/share/asterisk';
			$this->settings['astagidir']['default'] = '/usr/local/share/asterisk/agi-bin';
			$this->settings['ampbin']['default'] = '/usr/local/freepbx/bin';
			$this->settings['ampsbin']['default'] = '/usr/local/freepbx/sbin';
			$this->settings['webroot']['default'] = '/usr/local/www/freepbx';
		} else {
			$this->settings['astmoddir']['default'] = file_exists('/usr/lib64/asterisk/modules') ? '/usr/lib64/asterisk/modules' : '/usr/lib/asterisk/modules';
		}

		foreach ($this->settings as $key => $setting) {
			if (isset($setting['default'])) {
				$this->addOption($key, null, InputOption::VALUE_REQUIRED, $setting['description'], $setting['default']);
			} else {
				$this->addOption($key, null, InputOption::VALUE_NONE, $setting['description']);
			}
		}
		$this->addOption('rootdb', 'r', InputOption::VALUE_NONE, 'Database Root Based Install. Will create the database user and password automatically along with the databases');
		$this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force an install. Rewriting all databases with default information');
	}

	public function getHelp() {
		return '';
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		global $amp_conf; /* This makes pandas sad. :( */

		if (version_compare(PHP_VERSION, '5.3.3', '<')) {
			//charset=utf8 requires php 5.3.6 (http://php.net/manual/en/mysqlinfo.concepts.charset.php)
			$output->writeln("<error>FreePBX Requires PHP Version 5.3.3 or Higher, you have: ".PHP_VERSION."</error>");
			return false;
		}

		//still needed for module_admin and retrieve_conf
		$filePath = stream_resolve_include_path('Console/Getopt.php');
		if ($filePath === false) {
			$output->writeln("<error>PEAR must be installed (requires Console/Getopt.php)</error>");
			return false;
		}

		$this->rootPath = dirname(__DIR__);
		date_default_timezone_set('America/Los_Angeles');

		$style = new OutputFormatterStyle('white', 'black', array('bold'));
		$output->getFormatter()->setStyle('bold', $style);

		//STATIC???
		define("AMP_CONF", "/etc/amportal.conf");
		define("ODBC_INI", "/etc/odbc.ini");
		define("ASTERISK_CONF", "/etc/asterisk/asterisk.conf");
		define("FREEPBX_CONF", "/etc/freepbx.conf");
		define("FILES_DIR",$this->rootPath."/installlib/files");
		define("SQL_DIR", $this->rootPath."/installlib/SQL");
		define("MODULE_DIR", $this->rootPath."/amp_conf/htdocs/admin/modules");
		define("UPGRADE_DIR", $this->rootPath . "/upgrades");

		// Fail if !root
		$euid = posix_getpwuid(posix_geteuid());
		if ($euid['name'] != 'root') {
			$output->writeln("<error>".$this->getName() . " must be run as root</error>");
			exit(1);
		}

		foreach ($this->settings as $key => $setting) {
			$answers[$key] = $input->getOption($key);
		}

		if ($input->isInteractive()) {
			$helper = $this->getHelper('question');

			foreach ($this->settings as $key => $setting) {
				if (isset($setting['default'])) {
					$question = new Question($setting['description'] . ($setting['default'] ? ' [' . $setting['default'] . ']' : '') . ': ', $answers[$key]);
					$answers[$key] = $helper->ask($input, $output, $question);
				}
			}
		}

		$dbroot = $input->getOption('rootdb');
		$force = $input->getOption('force');
		if($force) {
			$output->writeln("<info>Force Install. This will reset everything!</info>");
		}

		if($dbroot || $answers['dbuser'] == 'root') {
			$output->writeln("<info>Assuming you are Database Root</info>");
			$dbroot = true;
		}

		// Make sure SELinux is disabled
		$output->write("Checking if SELinux is enabled...");
		exec("getenforce 2>/dev/null", $tmpout, $ret);
		if (isset($tmpout[0]) && ($tmpout[0] === "Enabled" || $tmpout[0] === "Enforcing")) {
			$output->writeln("<error>Error!</error>");
			$output->writeln("<error>SELinux is enabled.  Please disable SELinux before installing FreePBX.</error>");
			exit(1);
		}
		$output->writeln("Its not (good)!");
		unset($tmpout);

		require_once('installlib/installer.class.php');
		$installer = new Installer($input, $output);

		// Copy asterisk.conf
		if (!file_exists(ASTERISK_CONF)) {
			$output->write("No ".ASTERISK_CONF." file detected. Installing...");
			$aconf = $installer->asterisk_conf_read(FILES_DIR . "/asterisk.conf");
			if(empty($aconf['directories'])) {
				$output->writeln("<error>Error!</error>");
				$output->writeln("<error>Unable to read " . FILES_DIR . "/asterisk.conf or it was missing a directories section</error>");
				exit(1);
			}
		} else {
			$output->write("Reading ".ASTERISK_CONF."...");
			$aconf = $installer->asterisk_conf_read(ASTERISK_CONF);
			if(empty($aconf['directories'])) {
				$output->writeln("<error>Error!</error>");
				$output->writeln("<error>Unable to read " . ASTERISK_CONF . " or it was missing a directories section</error>");
				exit(1);
			}
			$output->writeln("Done");
		}

		if(!file_exists(ASTERISK_CONF) || $force) {
			if(isset($astconf['ASTETCDIR'])) {
				$aconf['directories']['astetcdir'] = !empty($answers['ASTETCDIR']) ? $answers['ASTETCDIR'] : "/etc/asterisk";
			}
			if(isset($astconf['ASTMODDIR'])) {
				$aconf['directories']['astmoddir'] = !empty($answers['ASTMODDIR']) ? $answers['ASTMODDIR'] : (file_exists('/usr/lib64/asterisk/modules') ? '/usr/lib64/asterisk/modules' : '/usr/lib/asterisk/modules');
			}
			if(isset($astconf['ASTVARLIBDIR'])) {
				$aconf['directories']['astvarlibdir'] = !empty($answers['ASTVARLIBDIR']) ? $answers['ASTVARLIBDIR'] : "/var/lib/asterisk";
			}
			if(isset($astconf['ASTAGIDIR'])) {
				$aconf['directories']['astagidir'] = !empty($answers['ASTAGIDIR']) ? $answers['ASTAGIDIR'] : "/var/lib/asterisk/agi-bin";
			}
			if(isset($astconf['ASTSPOOLDIR'])) {
				$aconf['directories']['astspooldir'] = !empty($answers['ASTSPOOLDIR']) ? $answers['ASTSPOOLDIR'] : "/var/spool/asterisk";
			}
			if(isset($astconf['ASTRUNDIR'])) {
				$aconf['directories']['astrundir'] = !empty($answers['ASTRUNDIR']) ? $answers['ASTRUNDIR'] : "/var/run/asterisk";
			}
			if(isset($astconf['ASTLOGDIR'])) {
				$aconf['directories']['astlogdir'] = !empty($answers['ASTLOGDIR']) ? $answers['ASTLOGDIR'] : "/var/log/asterisk";
			}

			$output->write("Writing ".ASTERISK_CONF."...");
			$installer->asterisk_conf_write(ASTERISK_CONF, $aconf);
			$output->writeln("Done");
		}

		$asterisk_conf = $aconf['directories'];

		//Check Asterisk (before file writes)
		$output->write("Checking if Asterisk is running and we can talk to it as the '".$answers['user']."' user...");
		$c = 0;
		$determined = false;
		while($c < 5) {
			exec("sudo -u " . $answers['user'] . " asterisk -rx 'core show version' 2>&1", $tmpout, $ret);
			if ($ret != 0) {
				$output->writeln("<error>Error!</error>");
				$output->writeln("<error>Error communicating with Asterisk.  Ensure that Asterisk is properly installed and running as the ".$answers['user']." user</error>");
				if(file_exists($asterisk_conf['astrundir']."/asterisk.ctl")) {
					$info = posix_getpwuid(fileowner($asterisk_conf['astrundir']."/asterisk.ctl"));
					$output->writeln("<error>Asterisk appears to be running as ".$info['name']."</error>");
				} else {
					$output->writeln("<error>Asterisk does not appear to be running</error>");
				}
				$output->writeln("<error>Try starting Asterisk with the './start_asterisk start' command in this directory</error>");
				exit(1);
			} else {
				$astver = $tmpout[0];
				unset($tmpout);
				// Parse Asterisk version.
				if (preg_match('/^Asterisk (?:SVN-|GIT-)?(?:branch-)?(\d+(\.\d+)*)(-?(.*)) built/', $astver, $matches)) {
					$determined = true;
					if ((version_compare($matches[1], "11") < 0) || version_compare($matches[1], "15", "ge")) {
						$output->writeln("<error>Error!</error>");
						$output->writeln("<error>Unsupported Version of ". $matches[1]."</error>");
						$output->writeln("<error>Supported Asterisk versions: 11, 12, 13, 14</error>");
						exit(1);
					}
					break;
				}
			}
			sleep(1);
			$c++;
		}
		if(!$determined) {
			$output->writeln("<error>Error!</error>");
			$output->writeln("<error>Could not determine Asterisk version (got: " . $astver . "). Please report this.</error>");
		}
		$output->writeln("Done");

		if((file_exists(FREEPBX_CONF) && !file_exists(AMP_CONF)) || (!file_exists(FREEPBX_CONF) && file_exists(AMP_CONF))) {
			if(file_exists(FREEPBX_CONF)) {
				$output->writeln("<error>Error!</error>");
				$output->writeln("<error>Half-baked install previously detected. ".FREEPBX_CONF." should not exist if ".AMP_CONF." does not exist</error>");
			} else {
				$output->writeln("<error>Error!</error>");
				$output->writeln("<error>Half-baked install previously detected. ".AMP_CONF." should not exist if ".FREEPBX_CONF." does not exist</error>");
			}
			exit(1);
		}

		$output->writeln("Preliminary checks done. Starting FreePBX Installation");
		// Copy default amportal.conf
		$output->write("Checking if this is a new install...");
		if (!file_exists(AMP_CONF) || $force) {
			$output->writeln("Yes (No ".AMP_CONF." file detected)");
			$newinstall = true;
			require_once('amp_conf/htdocs/admin/functions.inc.php');
		} else {
			$output->writeln("No (".AMP_CONF." file detected)");
			$bootstrap_settings['freepbx_auth'] = false;
			$restrict_mods = true;
			if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
				include_once('/etc/asterisk/freepbx.conf');
			}
		}

		if (isset($answers['dbengine'])) {
			$amp_conf['AMPDBENGINE'] = $answers['dbengine'];
		}
		if (isset($answers['dbname'])) {
			$amp_conf['AMPDBNAME'] = $answers['dbname'];
		}
		if (isset($answers['cdrdbname'])) {
			$amp_conf['CDRDBNAME'] = $answers['cdrdbname'];
		}
		if (isset($answers['ampbin'])) {
			$amp_conf['AMPBIN'] = $answers['ampbin'];
		}
		if (isset($answers['ampsbin'])) {
			$amp_conf['AMPSBIN'] = $answers['ampsbin'];
		}
		if (isset($answers['webroot'])) {
			$amp_conf['AMPWEBROOT'] = $answers['webroot'];
		}
		if (isset($answers['user'])) {
			$amp_conf['AMPASTERISKUSER'] = $answers['user'];
			$amp_conf['AMPASTERISKWEBUSER'] = $answers['user'];
			$amp_conf['AMPDEVUSER'] = $answers['user'];
		}
		if (isset($answers['group'])) {
			$amp_conf['AMPASTERISKGROUP'] = $answers['group'];
			$amp_conf['AMPASTERISKWEBGROUP'] = $answers['group'];
			$amp_conf['AMPDEVGROUP'] = $answers['group'];
		}
		if (!isset($amp_conf['AMPMANAGERHOST'])) {
			$amp_conf['AMPMANAGERHOST'] = 'localhost';
		}

		if ($newinstall || $force) {
			$amp_conf['AMPMGRUSER'] = 'admin';
			$amp_conf['AMPMGRPASS'] = md5(uniqid());

			$amp_conf['AMPDBUSER'] = $answers['dbuser'];
			$amp_conf['AMPDBPASS'] = $answers['dbpass'];
			$amp_conf['AMPDBHOST'] = 'localhost';

			if($dbroot) {
				$output->write("Database Root installation checking credentials and permissions..");
			} else {
				$output->write("Database installation checking credentials and permissions..");
			}
			$dsn = $amp_conf['AMPDBENGINE'] . ":host=" . $amp_conf['AMPDBHOST'];
			try {
				$pdodb = new \PDO($dsn, $amp_conf['AMPDBUSER'], $amp_conf['AMPDBPASS']);
			} catch(\Exception $e) {
				$output->writeln("<error>Error!</error>");
				$output->writeln("<error>Invalid Database Permissions. The error was: ".$e->getMessage()."</error>");
				exit(1);
			}
			$output->writeln("Connected!");
		}

		if(!file_exists(ODBC_INI)) {
			$output->write("No ".ODBC_INI." file detected. Installing...");
			if(!copy(FILES_DIR . "/odbc.ini", ODBC_INI)) {
				$output->writeln("<error>Error!</error>");
				$output->writeln("<error>Unable to copy " . FILES_DIR . "/odbc.ini to ".ODBC_INI."</error>");
				exit(1);
			}
			$output->writeln("Done");
		} elseif(file_exists(ODBC_INI)) {
			$conf = file_get_contents(ODBC_INI);
			$conf = trim($conf);
			if(empty($conf)) {
				$output->write("Blank ".ODBC_INI." file detected. Installing...");
				if(!copy(FILES_DIR . "/odbc.ini", ODBC_INI)) {
					$output->writeln("<error>Error!</error>");
					$output->writeln("<error>Unable to copy " . FILES_DIR . "/odbc.ini to ".ODBC_INI."</error>");
					exit(1);
				}
				$output->writeln("Done");
			}
		}

		if (isset($asterisk_conf['astetcdir'])) {
			$amp_conf['ASTETCDIR'] = $asterisk_conf['astetcdir'];
		}
		if (isset($asterisk_conf['astmoddir'])) {
			$amp_conf['ASTMODDIR'] = $asterisk_conf['astmoddir'];
		}
		if (isset($asterisk_conf['astvarlibdir'])) {
			$amp_conf['ASTVARLIBDIR'] = $asterisk_conf['astvarlibdir'];
		}
		if (isset($asterisk_conf['astagidir'])) {
			$amp_conf['ASTAGIDIR'] = $asterisk_conf['astagidir'];
		}
		if (isset($asterisk_conf['astspooldir'])) {
			$amp_conf['ASTSPOOLDIR'] = $asterisk_conf['astspooldir'];
		}
		if (isset($asterisk_conf['astrundir'])) {
			$amp_conf['ASTRUNDIR'] = $asterisk_conf['astrundir'];
		}
		if (isset($asterisk_conf['astlogdir'])) {
			$amp_conf['ASTLOGDIR'] = $asterisk_conf['astlogdir'];
		}

		// Create database(s).
		if ($newinstall) {
			global $db;

			require_once('amp_conf/htdocs/admin/libraries/BMO/FreePBX.class.php');
			require_once('amp_conf/htdocs/admin/libraries/DB.class.php');

			if($dbroot) {
				$amp_conf['AMPDBUSER'] = 'freepbxuser';
				$amp_conf['AMPDBPASS'] = md5(uniqid());
			} else {
				$amp_conf['AMPDBUSER'] = $answers['dbuser'];
				$amp_conf['AMPDBPASS'] = $answers['dbpass'];
			}

			if($dbroot) {
				if($force) {
					$pdodb->query("DROP DATABASE IF EXISTS ".$amp_conf['AMPDBNAME']);
				}
				$pdodb->query("CREATE DATABASE IF NOT EXISTS ".$amp_conf['AMPDBNAME']." DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_unicode_ci");
				$sql = "GRANT ALL PRIVILEGES ON ".$amp_conf['AMPDBNAME'].".* TO '" . $amp_conf['AMPDBUSER'] . "'@'localhost' IDENTIFIED BY '" . $amp_conf['AMPDBPASS'] . "'";
				$pdodb->query($sql);
			} else {
				//check collate
			}

			$bmo = new \FreePBX($amp_conf);

			$dsn = $amp_conf['AMPDBENGINE'] . ":host=" . $amp_conf['AMPDBHOST'];
			$db = new \DB(new \FreePBX\Database($dsn, $answers['dbuser'], $answers['dbpass']));

			$db->query("USE ".$amp_conf['AMPDBNAME']);
			$sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$amp_conf['AMPDBNAME']."';";
			if (!$db->getOne($sql)) {
				$output->writeln("Empty " . $amp_conf['AMPDBNAME'] . " Database going to populate it");
				$installer->install_sql_file(SQL_DIR . '/asterisk.sql');
			}

			if($dbroot) {
				if($force) {
					$db->query("DROP DATABASE IF EXISTS ".$amp_conf['CDRDBNAME']);
				}
				$db->query("CREATE DATABASE IF NOT EXISTS ".$amp_conf['CDRDBNAME']." DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_unicode_ci");
				$sql = "GRANT ALL PRIVILEGES ON ".$amp_conf['CDRDBNAME'].".* TO '" . $amp_conf['AMPDBUSER'] . "'@'localhost' IDENTIFIED BY '" . $amp_conf['AMPDBPASS'] . "'";
				$db->query($sql);
			} else {
				//check collate
			}
			$db->query("USE ".$amp_conf['CDRDBNAME']);
			$sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$amp_conf['CDRDBNAME']."';";
			if (!$db->getOne($sql)) {
				$output->writeln("Empty " . $amp_conf['CDRDBNAME'] . " Database going to populate it");
				$installer->install_sql_file(SQL_DIR . '/cdr.sql');
			}

			$db->query("USE ".$amp_conf['AMPDBNAME']);
		}

		// Get version of FreePBX.
		$version = $installer->get_version();

		$output->writeln("Initializing FreePBX Settings");
		$installer_amp_conf = $amp_conf;
		// freepbx_settings_init();
		$installer->freepbx_settings_init(true);

		// Use the installer defined amp_conf settings
		$freepbx_conf =& \freepbx_conf::create();
		foreach ($installer_amp_conf as $keyword => $value) {
			if ($freepbx_conf->conf_setting_exists($keyword) && $amp_conf[$keyword] != $value) {
				$output->writeln("\tChanging ".$keyword." to match what was given at install time");
				$freepbx_conf->set_conf_values(array($keyword => $value), false, true);
			}
		}
		$freepbx_conf->commit_conf_settings();
		$output->writeln("Finished initalizing settings");

		if(!file_exists($amp_conf['AMPWEBROOT'])) {
			@mkdir($amp_conf['AMPWEBROOT'], 0777, true);
		}
		if(!is_writeable($amp_conf['AMPWEBROOT'])) {
			throw new \Exception($amp_conf['AMPWEBROOT'] . " is NOT writable!");
		}
		chown($amp_conf['AMPWEBROOT'], $amp_conf['AMPASTERISKWEBUSER']);
		// Copy amp_conf/
		$verb = $answers['dev-links'] ? "Linking" : "Copying";
		$output->writeln($verb." files (this may take a bit)....");
		if (is_dir($this->rootPath."/amp_conf")) {
			$total_files = $this->getFileCount($this->rootPath."/amp_conf");
			$progress = new ProgressBar($output, $total_files);
			$progress->setRedrawFrequency(100);
			$progress->start();
			$this->recursive_copy($input, $output, $progress, $this->rootPath."/amp_conf", "", $newinstall, $answers['dev-links']);
			$progress->finish();
		}
		$output->writeln("");
		$output->writeln("Done");

		//Last minute symlinks
		$sbin = \FreePBX::Config()->get("AMPSBIN");
		$bin = \FreePBX::Config()->get("AMPBIN");

		//Put new fwconsole into place
		if(!file_exists($sbin."/fwconsole")) {
			$output->write("Symlinking ".$bin."/fwconsole to ".$sbin."/fwconsole ...");
			if(!symlink($bin."/fwconsole", $sbin."/fwconsole")) {
				$output->writeln("<error>Error</error>");
			}
			$output->writeln("Done");
		} elseif(file_exists($sbin."/fwconsole") && (!is_link($sbin."/fwconsole") || readlink($sbin."/fwconsole") != $bin."/fwconsole")) {
			unlink($sbin."/fwconsole");
			$output->write("Symlinking ".$bin."/fwconsole to ".$sbin."/fwconsole ...");
			if(!symlink($bin."/fwconsole", $sbin."/fwconsole")) {
				$output->writeln("<error>Error</error>");
			}
			$output->writeln("Done");
		}

		//put old amportal into place
		if(!file_exists($sbin."/amportal")) {
			if(is_link($sbin."/amportal")) {
				unlink($sbin."/amportal");
			}
			$output->write("Symlinking ".$bin."/amportal to ".$sbin."/amportal ...");
			if(!symlink($bin."/amportal", $sbin."/amportal")) {
				$output->writeln("<error>Error</error>");
			}
			$output->writeln("Done");
		} elseif(file_exists($sbin."/amportal") && (!is_link($sbin."/amportal") || readlink($sbin."/amportal") != $bin."/amportal")) {
			unlink($sbin."/amportal");
			$output->write("Symlinking ".$bin."/amportal to ".$sbin."/amportal ...");
			if(!symlink($bin."/amportal", $sbin."/amportal")) {
				$output->writeln("<error>Error</error>");
			}
			$output->writeln("Done");
		}

		$output->write("Finishing up directory processes...");
		$binFiles = array(
			$bin."/freepbx_engine" => 0755,
			$bin."/freepbx_setting" => 0755,
			$bin."/fwconsole" => 0755,
			$bin."/gen_amp_conf" => 0755,
			$bin."/retrieve_conf" => 0755,
			$sbin."/amportal" => 0755,
			$sbin."/fwconsole" => 0755
		);
		foreach($binFiles as $file => $perms) {
			if(file_exists($file)) {
				chmod($file, $perms);
			}
		}

		// Create dirs
		// 	/var/www/html/admin/modules/framework/
		// 	/var/www/html/admin/modules/_cache/
		//	./amp_conf/htdocs/admin/modules/_cache/
		$extraDirs = array(
			$amp_conf['AMPWEBROOT'] . "/admin/modules/_cache" => 0777,
			$amp_conf['AMPWEBROOT'] . "/admin/modules/framework" => 0777,
			$amp_conf['ASTSPOOLDIR'] . "/voicemail/device" => 0755,
			$amp_conf['ASTSPOOLDIR'] . "/fax" => 0766,
			$amp_conf['ASTSPOOLDIR'] . "/monitor" => 0766
		);
		foreach($extraDirs as $dir => $perms) {
			if(!file_exists($dir)) {
				mkdir($dir, $perms, true);
			}
		}

		$copyFrameworkFiles = array(
			"module.xml",
			"module.sig",
			"install.php",
			"LICENSE",
			"README.md"
		);
		foreach($copyFrameworkFiles as $file) {
			if(file_exists($this->rootPath . "/" . $file)) {
				copy($this->rootPath . "/" . $file, $amp_conf['AMPWEBROOT'] . "/admin/modules/framework/" . $file);
			}
		}

		// Copy /etc/asterisk/voicemail.conf.template
		// ... to /etc/asterisk/voicemail.conf
		if(!file_exists($amp_conf['ASTETCDIR'] . "/voicemail.conf")) {
			copy($amp_conf['ASTETCDIR'] . "/voicemail.conf.template", $amp_conf['ASTETCDIR'] . "/voicemail.conf");
		}
		$output->writeln("Done!");

		// Create missing #include files.
		$output->write("Creating missing #include files...");
		foreach(glob($amp_conf['ASTETCDIR'] . "/*.conf") as $file) {
			$data = file_get_contents($file);
			if(preg_match_all("/#include\s(.*)/",$data,$matches)) {
				if(!empty($matches[1])) {
					foreach($matches[1] as $include) {
						if (!file_exists($amp_conf['ASTETCDIR'] . "/".$include)) {
							touch($amp_conf['ASTETCDIR'] . "/".$include);
						}
					}
				}
			}
		}
		$output->writeln("Done");

		//File variable replacement
		$rfiles = array(
			$amp_conf['ASTETCDIR'] . "/manager.conf",
			//$amp_conf['ASTETCDIR'] . "/voicemail.conf",
			$amp_conf['ASTETCDIR'] . "/cdr_adaptive_odbc.conf",
			ODBC_INI,
		);
		$output->write("Running variable replacement...");
		foreach($rfiles as $file) {
			if(!file_exists($file) || !is_writable($file)) {
				continue;
			}
			$conf = file_get_contents($file);
			$replace = array(
				'AMPMGRUSER' => $amp_conf['AMPMGRUSER'],
				'AMPMGRPASS' => $amp_conf['AMPMGRPASS'],
				'CDRDBNAME' => $amp_conf['CDRDBNAME'],
				'AMPDBUSER' => $amp_conf['AMPDBUSER'],
				'AMPDBPASS' => $amp_conf['AMPDBPASS']
			);
			$conf = str_replace(array_keys($replace), array_values($replace), $conf);
			file_put_contents($file, $conf);
		}
		$output->writeln("Done");

		//setup and get manager working
		$output->write("Setting up Asterisk Manager Connection...");
		exec("sudo -u " . $answers['user'] ." asterisk -rx 'module reload manager'",$o,$r);
		if($r !== 0) {
			$output->writeln("<error>Unable to reload Asterisk Manager</error>");
			exit(127);
		}
		//TODO: we should check to make sure manager worked at this stage..
		$output->writeln("Done");

		$output->writeln("Running through upgrades...");
		// Upgrade framework (upgrades/ dir)
		$installer->install_upgrades($version);
		$output->writeln("Finished upgrades");

		$fwxml = simplexml_load_file($this->rootPath.'/module.xml');
		//setversion to whatever is in framework.xml forever for here on out.
		$fwver = (string)$fwxml->version;
		$output->write("Setting FreePBX version to ".$fwver."...");
		$installer->set_version($fwver);
		$output->writeln("Done");

		$output->write("Writing out ".AMP_CONF."...");
		if(!file_put_contents(AMP_CONF, $freepbx_conf->amportal_generate(true))) {
			$output->writeln("<error>Error!</error>");
			$output->writeln("<error>Unable to write to file</error>");
			exit(1);
		}
		$output->writeln("Done");

		if ($newinstall) {
			/* Write freepbx.conf */
			$conf = "<?php
\$amp_conf['AMPDBUSER'] = '{$amp_conf['AMPDBUSER']}';
\$amp_conf['AMPDBPASS'] = '{$amp_conf['AMPDBPASS']}';
\$amp_conf['AMPDBHOST'] = '{$amp_conf['AMPDBHOST']}';
\$amp_conf['AMPDBNAME'] = '{$amp_conf['AMPDBNAME']}';
\$amp_conf['AMPDBENGINE'] = '{$amp_conf['AMPDBENGINE']}';
\$amp_conf['datasource'] = ''; //for sqlite3

require_once('{$amp_conf['AMPWEBROOT']}/admin/bootstrap.php');
";
			$output->write("Writing out ".FREEPBX_CONF."...");
			if(!file_put_contents(FREEPBX_CONF, $conf)) {
				$output->writeln("<error>Error!</error>");
				$output->writeln("<error>Unable to write to file</error>");
				exit(1);
			}
			$output->writeln("Done");
		}

		//run this here so that we make sure everything is square for asterisk
		passthru($amp_conf['AMPSBIN'] . "/fwconsole chown");

		if (!$answers['dev-links']) {
			// install_modules()
			$included_modules = array();
			/* read modules list from MODULE_DIR */
			if(file_exists(MODULE_DIR)) {
				$dir = opendir(MODULE_DIR);
				while ($file = readdir($dir)) {
					if ($file[0] != "." && $file[0] != "_" && is_dir(MODULE_DIR."/".$file)) {
						$included_modules[] = $file;
					}
				}
				closedir($dir);
				$output->write("Installing all modules...");
				$this->install_modules($included_modules);
				$output->writeln("Done installing modules");
			}
		}

		// module_admin install framework
		$output->writeln("Installing framework...");
		$this->install_modules(array('framework'));
		$output->writeln("Done");

		// generate_configs();
		$output->writeln("Generating default configurations...");
		passthru("sudo -u " . $amp_conf['AMPASTERISKUSER'] . " " . $amp_conf["AMPBIN"] . "/retrieve_conf --skip-registry-checks");
		$output->writeln("Finished generating default configurations");

		// GPG setup - trustFreePBX();
		$output->write("Trusting FreePBX...");
		try {
			\FreePBX::GPG()->trustFreePBX();
		} catch(\Exception $e) {
			$output->writeln("<error>Error!</error>");
			$output->writeln("<error>Error while trusting FreePBX: ".$e->getMessage()."</error>");
			exit(1);
		}
		$output->writeln("Trusted");

		//run this here so that we make sure everything is square for asterisk
		passthru($amp_conf['AMPSBIN'] . "/fwconsole chown");
		$output->writeln("<info>You have successfully installed FreePBX</info>");
	}

	private function ask_overwrite(InputInterface $input, OutputInterface $output, $file1, $file2) {
		if (!$input->isInteractive()) {
			return true;
		}

		$helper = $this->getHelper('question');

		$question = new ChoiceQuestion('Overwrite: ', array('x' => 'Exit', 'y' => 'Yes', 'n' => 'No', 'd' => 'Diff'), 'x');
		$key = $helper->ask($input, $output, $question);

		switch ($key) {
		case "Exit":
		default:
			$output->writeln("-> Original file:  ".$file2);
			$output->writeln("-> New file:       ".$file1);
			$output->writeln("Exiting install program.");
			exit(1);
			break;
		case "Yes":
			return true;
		case "No":
			return false;
		case "Diff":
			$output->writeln("");
			// w = ignore whitespace, u = unified
			passthru("diff -wu ".escapeshellarg($file2)." ".escapeshellarg($file1));

			return $this->ask_overwrite($input, $output, $file1, $file2);
		}
	}

	function getFileCount($path) {
		$size = 0;
		$ignore = array('.','..','CVS','.svn','.git');
		$files = scandir($path);
		foreach($files as $t) {
				if(in_array($t, $ignore)) continue;
				if (is_dir(rtrim($path, '/') . '/' . $t)) {
						$size += $this->getFileCount(rtrim($path, '/') . '/' . $t);
				} else {
						$size++;
				}
		}
		return $size;
}

	private function recursive_copy(InputInterface $input, OutputInterface $output, ProgressBar $progress, $dirsourceparent, $dirsource = "", $newinstall = true, $make_links = false) {
		global $amp_conf;

		$bmoinst = \FreePBX::create()->Installer;

		$moh_subdir = isset($amp_conf['MOHDIR']) ? trim(trim($amp_conf['MOHDIR']),'/') : 'moh';

		// total # files, # actually copied
		$num_files = $num_copied = 0;

		if ($dirsource && ($dirsource[0] != "/")) $dirsource = "/".$dirsource;

		if (is_dir($dirsourceparent.$dirsource)) $dir_handle = opendir($dirsourceparent.$dirsource);

		while (isset($dir_handle) && ($file = readdir($dir_handle))) {
			if (($file==".") || ($file=="..") || ($file == "CVS") || ($file == ".svn") || ($file == ".git")) {
				continue;
			}

			if ($dirsource == "" && $file == "moh" && !$newinstall) {
				// skip to the next dir
				continue;
			}

			$source = $dirsourceparent.$dirsource."/".$file;

			if (!is_dir($source)) {
				$destination = $bmoinst->getDestination('framework', str_replace($this->rootPath."/","",$source));
				// These are modified by apply_conf.sh, there may be others that fit in this category also. This keeps these from
				// being symlinked and then developers inadvertently checking in the changes when they should not have.
				//
				$never_symlink = array(
					"cdr_adaptive_odbc.conf",
					"indications.conf",
					"manager.conf",
					"modules.conf"
				);

				$num_files++;
				if ($make_links && !in_array(basename($source),$never_symlink)) {
					// symlink, unlike copy, doesn't overwrite - have to delete first
					// ^^ lies! :(
					if (is_link($destination) || file_exists($destination)) {
						if(!is_dir($destination)) {
							unlink($destination);
						}
					}

					if(file_exists($source)) {
						if ($output->isDebug()) {
							$output->writeln("Linking ".basename($source)." to ".dirname($destination));
						}
						@symlink($source, $destination);
					}
				} else {
					$ow = false;
					if(file_exists($destination) && !is_link($destination)) {
						if ($input->isInteractive() && $this->check_diff($source, $destination) && !$make_links) {
							$output->writeln($destination." has been changed from the original version.");
							$ow = $this->ask_overwrite($input, $output, $source, $destination);
						} elseif (!$input->isInteractive() && $this->check_diff($source, $destination) && !$make_links) {
							if(basename($source) == "manager.conf") {
								$ow = false;
							} else {
								$output->writeln($destination." has been changed from the original version.");
								$ow = true;
							}
						}
					} else {
						$ow = true;
					}
					if($ow) {
						//Copy will not overwrite a symlink, phpnesssss
						if(file_exists($destination) && is_link($destination)) {
							if(!is_dir($destination)) {
								unlink($destination);
							}
						}
						if ($output->isDebug()) {
							$output->writeln("Copying ".basename($source)." to ".dirname($destination));
						}
						copy($source, $destination);
					} else {
						continue;
					}
				}
				$num_copied++;
			} else {
				$destination = $bmoinst->getDestination('framework', str_replace($this->rootPath."/","",$source) . "/");

				// if this is a directory, ensure destination exists
				if (!file_exists($destination)) {
					if ($destination != "") {
						if ($output->isDebug()) {
							$output->writeln("Creating ".$destination);
						}
						@mkdir($destination, "0750", true);
					}
				}

				list($tmp_num_files, $tmp_num_copied) = $this->recursive_copy($input, $output, $progress, $dirsourceparent, $dirsource."/".$file, $newinstall, $make_links);
				$num_files += $tmp_num_files;
				$num_copied += $tmp_num_copied;
			}
			if($progress->getStep() < $progress->getMaxSteps()) {
				$progress->advance();
			}
		}

		if (isset($dir_handle)) closedir($dir_handle);
		return array($num_files, $num_copied);
	}

	private function check_diff($file1, $file2) {
		// diff, ignore whitespace and be quiet
		exec("diff -wq ".escapeshellarg($file2)." ".escapeshellarg($file1), $tmpout, $retVal);
		return ($retVal != 0);
	}

	private function install_modules($modules) {
		global $amp_conf;

		$output = array();
		$keep_checking = true;
		$num_modules = false;
		while ($keep_checking && count($modules)) {
			if ($num_modules === count($modules)) {
				$keep_checking = false;
			} else {
				$num_modules = count($modules);
			}
			foreach ($modules as $id => $up_module) {
				// if $keep_checking then check dependencies first and skip if not met
				// otherwise we will install anyhow even if some dependencies are not met
				// since it is included. This keeps us strictly local
				//
				if ($keep_checking) {
					exec($amp_conf['AMPBIN']."/fwconsole ma checkdepends $up_module", $output, $retval);
					unset($output);
					if ($retval) {
						continue;
					}
				}
				// Framework modules cannot be enabled, only installed.
				//
				switch ($up_module) {
					case 'framework':
						system($amp_conf['AMPBIN']."/fwconsole ma --force install $up_module");
					break;
					default:
						system($amp_conf['AMPBIN']."/fwconsole ma --force install $up_module");

						exec($amp_conf['AMPBIN']."/fwconsole ma --force enable $up_module", $output, $retval);
						unset($output);
				}
				unset($modules[$id]);
			}
		}
	}
}
