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
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
		'dbhost' => array(
			'default' => 'localhost',
			'description' => 'Database server address'
		),
		'dbport' => array(
			'default' => '3306',
			'description' => 'Database server port'
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
		'skip-install' => array(
			'description' => 'Skip installing local modules (except Framework, Core, Dashboard, Voicemail and Sip Settings)'
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
		'ampcgibin' => array(
			'default' => '/var/www/cgi-bin',
			'description' => 'Location of the Apache cgi-bin executables'
		),
		'ampplayback' => array(
			'default' => '/var/lib/asterisk/playback',
			'description' => 'Directory for FreePBX html5 playback files'
		),
	);
	private $isTtySupported;
	private $output;
	private $input;

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
			$this->settings['ampcgibin']['default'] = '/usr/local/www/apache24/cgi-bin';
			$this->settings['ampplayback']['default'] = '/var/spool/asterisk/playback';
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
		$this->output = $output;
		$this->input = $input;

		$this->rootPath = dirname(__DIR__);
		date_default_timezone_set('America/Los_Angeles');

		$style = new OutputFormatterStyle('white', 'black', array('bold'));
		$output->getFormatter()->setStyle('bold', $style);

		//STATIC???
		define("AMP_CONF", "/etc/amportal.conf");
		define("ODBC_INI", "/etc/odbc.ini");
		if (PHP_OS == "FreeBSD") {
			define("ASTERISK_CONF", "/usr/local/etc/asterisk/asterisk.conf");
		} else {
			define("ASTERISK_CONF", "/etc/asterisk/asterisk.conf");
		}
		$freepbx_conf_path = "/etc/freepbx.conf";
		define("FILES_DIR",$this->rootPath."/installlib/files");
		define("SQL_DIR", $this->rootPath."/installlib/SQL");
		define("MODULE_DIR", $this->rootPath."/amp_conf/htdocs/admin/modules");
		define("UPGRADE_DIR", $this->rootPath . "/upgrades");

		// Fail if !root
		if (posix_geteuid() !== 0) {
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
			$output->writeln("<info>"._("Force Install. This will reset everything!")."</info>");
		}

		if($dbroot || $answers['dbuser'] == 'root') {
			$output->writeln("<info>"._("Assuming you are Database Root")."</info>");
			$dbroot = true;
		}

		// Make sure SELinux is disabled
		$output->write("Checking if SELinux is enabled...");
		exec("getenforce 2>/dev/null", $tmpout, $ret);
		if (isset($tmpout[0]) && ($tmpout[0] === "Enabled" || $tmpout[0] === "Enforcing")) {
			$output->writeln("<error>"._("Error!")."</error>");
			$output->writeln("<error>"._("SELinux is enabled. Please disable SELinux before installing FreePBX.")."</error>");
			exit(1);
		}
		$output->writeln(_("Its not (good)!"));
		unset($tmpout);

		require_once(__DIR__.'/installer.class.php');
		$installer = new Installer($input, $output);

		// Copy asterisk.conf
		if (!file_exists(ASTERISK_CONF)) {
			$output->write("No ".ASTERISK_CONF." file detected. Installing...");
			$aconf = $installer->asterisk_conf_read(FILES_DIR . "/asterisk.conf");
			if(empty($aconf['directories'])) {
				$output->writeln("<error>"._("Error!")."</error>");
				$output->writeln("<error>".sprintf(_("Unable to read %s/asterisk.conf or it was missing a directories section"), FILES_DIR)."</error>");
				exit(1);
			}
		} else {
			$output->write("Reading ".ASTERISK_CONF."...");
			$aconf = $installer->asterisk_conf_read(ASTERISK_CONF);
			if(empty($aconf['directories'])) {
				$output->writeln("<error>"._("Error!")."</error>");
				$output->writeln("<error>".sprintf(_("Unable to read %s or it was missing a directories section"), ASTERISK_CONF)."</error>");
				exit(1);
			}
			$output->writeln(_("Done"));
		}

		if(!file_exists(ASTERISK_CONF) || $force) {
			if(isset($aconf['directories']['astetcdir'])) {
				$aconf['directories']['astetcdir'] = !empty($answers['astetcdir']) ? $answers['astetcdir'] : "/etc/asterisk";
			}
			if(isset($aconf['directories']['astmoddir'])) {
				$aconf['directories']['astmoddir'] = !empty($answers['astmoddir']) ? $answers['astmoddir'] : (file_exists('/usr/lib64/asterisk/modules') ? '/usr/lib64/asterisk/modules' : '/usr/lib/asterisk/modules');
			}
			if(isset($aconf['directories']['astvarlibdir'])) {
				$aconf['directories']['astvarlibdir'] = !empty($answers['astvarlibdir']) ? $answers['astvarlibdir'] : "/var/lib/asterisk";
			}
			if(isset($aconf['directories']['astagidir'])) {
				$aconf['directories']['astagidir'] = !empty($answers['astagidir']) ? $answers['astagidir'] : "/var/lib/asterisk/agi-bin";
			}
			if(isset($aconf['directories']['astspooldir'])) {
				$aconf['directories']['astspooldir'] = !empty($answers['astspooldir']) ? $answers['astspooldir'] : "/var/spool/asterisk";
			}
			if(isset($aconf['directories']['astrundir'])) {
				$aconf['directories']['astrundir'] = !empty($answers['astrundir']) ? $answers['astrundir'] : "/var/run/asterisk";
			}
			if(isset($aconf['directories']['astlogdir'])) {
				$aconf['directories']['astlogdir'] = !empty($answers['astlogdir']) ? $answers['astlogdir'] : "/var/log/asterisk";
			}

			$output->write("Writing ".ASTERISK_CONF."...");
			$installer->asterisk_conf_write(ASTERISK_CONF, $aconf);
			$output->writeln(_("Done"));
		}

		$asterisk_conf = $aconf['directories'];

		//Check Asterisk (before file writes)
		$output->write("Checking if Asterisk is running and we can talk to it as the '".$answers['user']."' user...");
		$c = 0;
		$determined = false;
		While($c < 5) {
			// Ensure $tmpout is empty
			$tmpout = [];
			$lastline = exec("runuser " . $answers['user'] . ' -s /bin/bash -c "cd ~/ && asterisk -rx \'core show version\' 2>&1"', $tmpout, $ret);
			if ($ret != 0) {
				$output->writeln("<error>"._("Error!")."</error>");
				$output->writeln("<error>".sprintf(_("Error communicating with Asterisk. Ensure that Asterisk is properly installed and running as the %s user"), $answers['user'] )."</error>");
				if(file_exists($asterisk_conf['astrundir']."/asterisk.ctl")) {
					$info = posix_getpwuid(fileowner($asterisk_conf['astrundir']."/asterisk.ctl"));
					$output->writeln("<error>"._("Asterisk appears to be running as ").$info['name']."</error>");
				} else {
					$output->writeln("<error>"._("Asterisk does not appear to be running")."</error>");
				}
				$output->writeln("<error>"._("Try starting Asterisk with the './start_asterisk start' command in this directory")."</error>");
				exit(1);
			}
			// If this machine doesn't have an ethernet interface (which opens a WHOLE NEW can of worms),
			// asterisk will say "No ethernet interface detected". There may, also, be other errors about
			// other modules or configuration issues. The last line, however, is always the version.
			$astver = trim(array_pop($tmpout));

			// Parse Asterisk version.
			if (preg_match('/^Asterisk (?:SVN-|GIT-)?(?:branch-)?(\d+(\.\d+)*)(-?(.*)) built/', $astver, $matches)) {
				$determined = true;
				if(file_exists("/etc/asterisk/asterisk_validate.conf")){
					 $asterisk_validate = parse_ini_file("/etc/asterisk/asterisk_validate.conf");
					 if(empty($asterisk_validate["min"])){
						$output->writeln("<error>"._("Error!")."</error>");
						$output->writeln("<error>"._("min version is missing in")." /etc/asterisk/asterisk_validate.conf</error>");
						exit(1);
					 }
					 if(empty($asterisk_validate["max"])){
						$output->writeln("<error>"._("Error!")."</error>");
						$output->writeln("<error>"._("max version is missing in")." /etc/asterisk/asterisk_validate.conf.</error>");
						exit(1);
					 }
					 $asterisk_vmin = $asterisk_validate["min"];
					 $asterisk_vmax = $asterisk_validate["max"];
				}
				else{
					$output->writeln("<error>"._("Error!")."</error>");
					$output->writeln("<error>/etc/asterisk/asterisk_validate.conf "._("not found.")."</error>");
					exit(1);
				}
				
				if (version_compare($matches[1], $asterisk_vmin, "lt") || version_compare($matches[1], $asterisk_vmax, "gt")) {
					$output->writeln("<error>"._("Error!")."</error>");
					$output->writeln("<error>"._("Unsupported Version of")." ".$matches[1]."</error>");
					$output->writeln("<error>".sprintf(_("Supported Asterisk versions: %s to %s."), $asterisk_vmin, $asterisk_vmax)."</error>");
					exit(1);
				}
				$output->writeln(_("Yes. Determined Asterisk version to be: ").$matches[1]);
				break;
			}
			sleep(1);
			$c++;
		}
		if(!$determined) {
			if(!empty($astver)) {
				$output->writeln("<error>"._("Error!")."</error>");
				$output->writeln("<error>".sprintf( _("Could not determine Asterisk version (got: %s). Please report this."), $astver)."</error>");
			} else {
				$output->writeln("<error>"._("Error!")."</error>");
				$output->writeln("<error>".sprintf(_("Could not determine Asterisk version. Error was '%s'"), $lastline)."</error>");
			}
			exit(1);
		}

		$output->write("Checking if NodeJS is installed and we can get a version from it...");
		$nodejsout = exec("node --version"); //v0.10.29
		$nodejsout = str_replace("v","",trim($nodejsout));
		if(empty($nodejsout)) {
			$output->writeln("<error>"._("NodeJS 8 or higher is not installed. This is now a requirement")."</error>");
			return false;
		}
		if(version_compare($nodejsout,'8.0.0',"<")) {
			$output->writeln(sprintf(_("NodeJS version is: %s requirement is %s or higher"),$nodejsout,'8.0.0'));
			return false;
		}

		if((file_exists($freepbx_conf_path) && !file_exists(AMP_CONF)) || (!file_exists($freepbx_conf_path) && file_exists(AMP_CONF))) {
			if(file_exists($freepbx_conf_path)) {
				$output->writeln("<error>"._("Error!")."</error>");
				$output->writeln("<error>".sprintf( _("Half-baked install previously detected. %s should not exist if %s does not exist"), $freepbx_conf_path, AMP_CONF)."</error>");
			} else {
				$output->writeln("<error>"._("Error!")."</error>");
				$output->writeln("<error>".sprintf( _("Half-baked install previously detected. %s should not exist if %s does not exist"), AMP_CONF, $freepbx_conf_path)."</error>");
			}
			exit(1);
		}
		$output->writeln(_("Yes. Determined NodeJS version to be: ").$nodejsout);

		$output->writeln(_("Preliminary checks done. Starting FreePBX Installation"));

		$output->write(_("Checking if this is a new install..."));
		if(file_exists($answers['webroot']."/admin/bootstrap.php") && !is_link($answers['webroot']."/admin/bootstrap.php") && $answers['dev-links']) {
			//Previous install, not in dev mode. We need to do cleanup
			$output->writeln(_("No (Forcing dev-links)"));
			$bootstrap_settings['returnimmediately'] = true;
			include_once $freepbx_conf_path;
			unset($bootstrap_settings['returnimmediately']);
			$newinstall = false;
			require_once('amp_conf/htdocs/admin/functions.inc.php');
		} elseif(file_exists($answers['webroot']."/admin/bootstrap.php") && is_link($answers['webroot']."/admin/bootstrap.php") && !$answers['dev-links']) {
			//Previous install, was in dev mode. Now we need to do cleanup
			$output->writeln(_("No (Un dev-linking this machine)"));
			$bootstrap_settings['returnimmediately'] = true;
			include_once $freepbx_conf_path;
			unset($bootstrap_settings['returnimmediately']);
			$newinstall = false;
			require_once('amp_conf/htdocs/admin/functions.inc.php');
		} elseif(file_exists($freepbx_conf_path) && !file_exists($answers['webroot']."/admin/bootstrap.php")) {
			if(!file_exists($answers['webroot']."/admin")) {
				mkdir($answers['webroot']."/admin");
			}
			touch($answers['webroot']."/admin/bootstrap.php");
			$output->writeln(_("Partial"));
			$bootstrap_settings['returnimmediately'] = true;
			include_once $freepbx_conf_path;
			unlink($answers['webroot']."/admin/bootstrap.php");
			unset($bootstrap_settings['returnimmediately']);
			$newinstall = true;
			$answers['skip-install'] = true;
			require_once('amp_conf/htdocs/admin/functions.inc.php');
		} elseif (!file_exists($freepbx_conf_path) || $force) {
			$output->writeln(sprintf(_("Yes (No %s file detected)"), $freepbx_conf_path));
			$newinstall = true;
			require_once('amp_conf/htdocs/admin/functions.inc.php');
		} else {
			$output->writeln(sprintf(_("No (%s file detected)"), $freepbx_conf_path));
			$bootstrap_settings['freepbx_auth'] = false;
			$restrict_mods = true;
			include_once $freepbx_conf_path;
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
		if (isset($answers['ampcgibin'])) {
			$amp_conf['AMPCGIBIN'] = $answers['ampcgibin'];
		}
		if (isset($answers['ampplayback'])) {
			$amp_conf['AMPPLAYBACK'] = $answers['ampplayback'];
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

		$pdodrivers = \PDO::getAvailableDrivers();
		if(!in_array($amp_conf['AMPDBENGINE'],$pdodrivers)) {
			$output->writeln("<error>"._("Error!")."</error>");
			$output->writeln("<error>".sprintf(_("PDO Driver '%s' is missing from the system"), $amp_conf['AMPDBENGINE'])."</error>");
			exit(1);
		}

		$dbencoding = 'utf8'; //jic
		if ($newinstall || $force) {
			$amp_conf['AMPMGRUSER'] = 'admin';
			$amp_conf['AMPMGRPASS'] = md5(uniqid());

			$amp_conf['AMPDBUSER'] = $answers['dbuser'];
			$amp_conf['AMPDBPASS'] = $answers['dbpass'];
			$amp_conf['AMPDBHOST'] = $answers['dbhost'];
			$amp_conf['AMPDBPORT'] = $answers['dbport'];

			if($dbroot) {
				$output->write("Database Root installation checking credentials and permissions..");
			} else {
				$output->write("Database installation checking credentials and permissions..");
			}

			$dsn = $amp_conf['AMPDBENGINE'] . ":host=" . $amp_conf['AMPDBHOST'];
			try {
				$pdodb = new \PDO($dsn, $amp_conf['AMPDBUSER'], $amp_conf['AMPDBPASS']);
			} catch(\Exception $e) {
				$output->writeln("<error>"._("Error!")."</error>");
				$output->writeln("<error>"._("Invalid Database Permissions. The error was:")." ".$e->getMessage()."</error>");
				exit(1);
			}
			$dbencoding = version_compare($pdodb->getAttribute(\PDO::ATTR_SERVER_VERSION), "5.5.3", "ge") ? "utf8mb4" : "utf8";
			$output->writeln("Connected!");
		}

		if(!file_exists(ODBC_INI)) {
			$output->write("No ".ODBC_INI." file detected. Installing...");
			if(!copy(FILES_DIR . "/odbc.ini", ODBC_INI)) {
				$output->writeln("<error>"._("Error!")."</error>");
				$output->writeln("<error>".sprintf(_("Unable to copy %s/odbc.ini to %s"), FILES_DIR , ODBC_INI)."</error>");
				exit(1);
			}
			$output->writeln(_("Done"));
		} elseif(file_exists(ODBC_INI)) {
			$conf = file_get_contents(ODBC_INI);
			$conf = trim($conf);
			if(empty($conf)) {
				$output->write(sprintf( _("Blank %s file detected. Installing..."), ODBC_INI));
				if(!copy(FILES_DIR . "/odbc.ini", ODBC_INI)) {
					$output->writeln("<error>"._("Error!")."</error>");
					$output->writeln("<error>".sprintf( _("Unable to copy %s/odbc.ini to %s"), FILES_DIR , ODBC_INI)."</error>");
					exit(1);
				}
				$output->writeln(_("Done"));
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

		$fwxml = simplexml_load_file($this->rootPath.'/module.xml');
		//setversion to whatever is in framework.xml forever for here on out.
		$fwver = (string)$fwxml->version;

		// Create database(s).
		if ($newinstall) {
			global $db;

			require_once('amp_conf/htdocs/admin/libraries/BMO/FreePBX.class.php');
			require_once('amp_conf/htdocs/admin/libraries/DB.class.php');

			if($dbroot) {
				$amp_conf['AMPDBUSER'] = 'freepbxuser';
				$amp_conf['AMPDBPASS'] = md5(uniqid());
			} elseif((empty($amp_conf['AMPDBUSER'])) && empty($amp_conf['AMPDBPASS'])) {
				$amp_conf['AMPDBUSER'] = $answers['dbuser'];
				$amp_conf['AMPDBPASS'] = $answers['dbpass'];
			}

			if($dbroot) {
				if($force) {
					$pdodb->query("DROP DATABASE IF EXISTS ".$amp_conf['AMPDBNAME']);
				}
				$pdodb->query("CREATE DATABASE IF NOT EXISTS ".$amp_conf['AMPDBNAME']." DEFAULT CHARACTER SET ".$dbencoding." DEFAULT COLLATE ".$dbencoding."_unicode_ci");
				$sql = "GRANT ALL PRIVILEGES ON ".$amp_conf['AMPDBNAME'].".* TO '" . $amp_conf['AMPDBUSER'] . "'@'".$amp_conf['AMPDBHOST']."' IDENTIFIED BY '" . $amp_conf['AMPDBPASS'] . "'";
				$pdodb->query($sql);
			} else {
				//check collate
			}

			$bmo = new \FreePBX($amp_conf);

			$dsn = $amp_conf['AMPDBENGINE'] . ":host=" . $amp_conf['AMPDBHOST'];
			$fbxdb = new \FreePBX\Database($dsn, $answers['dbuser'], $answers['dbpass']);
			$db = new \DB($fbxdb);

			$db->query("USE ".$amp_conf['AMPDBNAME']);
			$sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$amp_conf['AMPDBNAME']."';";
			if (!$db->getOne($sql)) {
				$output->writeln(sprintf(_("Empty %s Database going to populate it"), $amp_conf['AMPDBNAME']));

				$xml = simplexml_load_file(dirname(__DIR__).'/module.xml');
				if(!empty($xml->database)) {
					$dbtables = array();
					foreach($xml->database->table as $table) {
						$tname = (string)$table->attributes()->name;
						$dbtables[] = $tname;
					}
					outn(sprintf(_("Updating tables %s..."),implode(", ",$dbtables)));
					$fbxdb->migrateMultipleXML($xml->database->table);
					out(_("Done"));
				} else {
					throw new \Exception("There's no default database information!");
				}

				$installer->install_sql_file(SQL_DIR . '/asterisk.sql');
				$db->query("INSERT INTO admin (variable,value) VALUES ('version','".$fwver."')");
			}

			if($dbroot) {
				if($force) {
					$db->query("DROP DATABASE IF EXISTS ".$amp_conf['CDRDBNAME']);
				}
				$db->query("CREATE DATABASE IF NOT EXISTS ".$amp_conf['CDRDBNAME']." DEFAULT CHARACTER SET ".$dbencoding." DEFAULT COLLATE ".$dbencoding."_unicode_ci");
				$sql = "GRANT ALL PRIVILEGES ON ".$amp_conf['CDRDBNAME'].".* TO '" . $amp_conf['AMPDBUSER'] . "'@'".$amp_conf['AMPDBHOST']."' IDENTIFIED BY '" . $amp_conf['AMPDBPASS'] . "'";
				$db->query($sql);
			} else {
				//check collate
			}
			$db->query("USE ".$amp_conf['CDRDBNAME']);
			$sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$amp_conf['CDRDBNAME']."';";
			if (!$db->getOne($sql)) {
				$output->writeln(sprintf( _("Empty % Database going to populate it"),$amp_conf['CDRDBNAME']));
				$installer->install_sql_file(SQL_DIR . '/cdr.sql');
			}
			unset($amp_conf['CDRDBNAME']);

			$db->query("USE ".$amp_conf['AMPDBNAME']);
		} else {
			$xml = simplexml_load_file(dirname(__DIR__).'/module.xml');
			if(!empty($xml->database)) {
				$dbtables = array();
				foreach($xml->database->table as $table) {
					$tname = (string)$table->attributes()->name;
					$dbtables[] = $tname;
				}
				outn(sprintf(_("Updating tables %s..."),implode(", ",$dbtables)));
				\FreePBX::Database()->migrateMultipleXML($xml->database->table, false, (string)$xml->version);
				out(_("Done"));
			} else {
				throw new \Exception("There's no default database information!");
			}
		}

		// Get version of FreePBX.
		$version = $installer->get_version();

		$output->writeln(_("Initializing FreePBX Settings"));
		$installer_amp_conf = $amp_conf;
		// freepbx_settings_init();
		$installer->freepbx_settings_init(true);

		// Use the installer defined amp_conf settings
		$freepbx_conf = \freepbx_conf::create();
		foreach ($installer_amp_conf as $keyword => $value) {
			if ($freepbx_conf->conf_setting_exists($keyword) && $amp_conf[$keyword] != $value) {
				$output->writeln("\t".sprintf(_("Changing %s [%s] to match what was given at install time: %s"), $keyword, $amp_conf[$keyword], $value));
				$freepbx_conf->set_conf_values(array($keyword => $value), false, true);
			}
		}
		$freepbx_conf->commit_conf_settings();
		$output->writeln(_("Finished initalizing settings"));

		if(!file_exists($amp_conf['AMPWEBROOT'])) {
			@mkdir($amp_conf['AMPWEBROOT'], 0777, true);
		}
		if(!is_writeable($amp_conf['AMPWEBROOT'])) {
			throw new \Exception($amp_conf['AMPWEBROOT'] . " is NOT writable!");
		}
		chown($amp_conf['AMPWEBROOT'], $amp_conf['AMPASTERISKWEBUSER']);

		// Copy amp_conf/
		$verb = $answers['dev-links'] ? "Linking" : "Copying";
		$output->writeln(sprintf(_("%s files (this may take a bit)...."),$verb));
		if (is_dir($this->rootPath."/amp_conf")) {
			$iterator = $this->getFilesToCopy($this->rootPath."/amp_conf", $newinstall);
			$progress = new ProgressBar($output, iterator_count($iterator));
			$progress->setRedrawFrequency(100);
			$progress->start();
			$this->recursive_copy($input, $output, $progress, $iterator, $answers['dev-links']);
			$progress->finish();
		}
		$output->writeln("");
		$output->writeln(_("Done"));

		//Last minute symlinks
		$sbin = \FreePBX::Config()->get("AMPSBIN");
		$bin = \FreePBX::Config()->get("AMPBIN");

		$output->writeln(sprint( _("bin is: %s"),$bin));
		if(!file_exists($bin)) {
			$output->writeln(sprintf( _("Directory %s missing, creating."),$bin ));
			mkdir($bin, 0755);
		}
		$output->writeln("sbin is: $sbin");
		if(!file_exists($sbin)) {
			$output->writeln(sprintf( _("Directory %s missing, creating."),$sbin ));
			mkdir($sbin, 0755);
		}

		//Put new fwconsole into place
		if(!file_exists($sbin."/fwconsole")) {
			$output->write("Symlinking ".$bin."/fwconsole to ".$sbin."/fwconsole ...");
			if(!symlink($bin."/fwconsole", $sbin."/fwconsole")) {
				$output->writeln("<error>"._("Error!")."</error>");
			}
			$output->writeln(_("Done"));
		} elseif(file_exists($sbin."/fwconsole") && (!is_link($sbin."/fwconsole") || readlink($sbin."/fwconsole") != $bin."/fwconsole")) {
			unlink($sbin."/fwconsole");
			$output->write("Symlinking ".$bin."/fwconsole to ".$sbin."/fwconsole ...");
			if(!symlink($bin."/fwconsole", $sbin."/fwconsole")) {
				$output->writeln("<error>"._("Error!")."</error>");
			}
			$output->writeln(_("Done"));
		}

		//put old amportal into place
		if(!file_exists($sbin."/amportal")) {
			if(is_link($sbin."/amportal")) {
				unlink($sbin."/amportal");
			}
			$output->write("Symlinking ".$bin."/amportal to ".$sbin."/amportal ...");
			if(!symlink($bin."/amportal", $sbin."/amportal")) {
				$output->writeln("<error>"._("Error!")."</error>");
			}
			$output->writeln(_("Done"));
		} elseif(file_exists($sbin."/amportal") && (!is_link($sbin."/amportal") || readlink($sbin."/amportal") != $bin."/amportal")) {
			unlink($sbin."/amportal");
			$output->write("Symlinking ".$bin."/amportal to ".$sbin."/amportal ...");
			if(!symlink($bin."/amportal", $sbin."/amportal")) {
				$output->writeln("<error>"._("Error!")."</error>");
			}
			$output->writeln(_("Done"));
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

		// Create additional dirs
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
			"phpunit.xml",
			"README.md"
		);
		foreach($copyFrameworkFiles as $file) {
			if(file_exists($this->rootPath . "/" . $file)) {
				copy($this->rootPath . "/" . $file, $amp_conf['AMPWEBROOT'] . "/admin/modules/framework/" . $file);
			}
		}
		exec("cp -Rf ".$this->rootPath . "/hooks ". $amp_conf['AMPWEBROOT'] . "/admin/modules/framework/");

		// Copy /etc/asterisk/voicemail.conf.template
		// ... to /etc/asterisk/voicemail.conf
		if(!file_exists($amp_conf['ASTETCDIR'] . "/voicemail.conf")) {
			copy($amp_conf['ASTETCDIR'] . "/voicemail.conf.template", $amp_conf['ASTETCDIR'] . "/voicemail.conf");
		}
		$output->writeln(_("Done!"));

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
				'CDRDBNAME' => !empty($amp_conf['CDRDBNAME']) ? $amp_conf['CDRDBNAME'] : 'asteriskcdrdb',
				'AMPDBUSER' => $amp_conf['AMPDBUSER'],
				'AMPDBPASS' => $amp_conf['AMPDBPASS']
			);
			$conf = str_replace(array_keys($replace), array_values($replace), $conf);
			file_put_contents($file, $conf);
		}
		$output->writeln(_("Done"));

		// Create missing #include files.
		$output->write("Creating missing #include files...");
		foreach(glob($amp_conf['ASTETCDIR'] . "/*.conf") as $file) {
			$data = file_get_contents($file);
			if(preg_match_all("/^\s*#include\s*(.*)/mi",$data,$matches)) {
				if(!empty($matches[1])) {
					foreach($matches[1] as $include) {
						if (!file_exists($amp_conf['ASTETCDIR'] . "/".$include)) {
							touch($amp_conf['ASTETCDIR'] . "/".$include);
						}
					}
				}
			}
		}
		$output->writeln(_("Done"));

		//setup and get manager working
		$output->write("Setting up Asterisk Manager Connection...");
		exec("runuser " . $answers['user'] . ' -s /bin/bash -c "cd ~/ && asterisk -rx \'module reload manager\' 2>&1"',$o,$r);
		if($r !== 0) {
			$output->writeln("<error>"._("Unable to reload Asterisk Manager")."</error>");
			exit(127);
		}
		//TODO: we should check to make sure manager worked at this stage..
		$output->writeln(_("Done"));

		$output->writeln(_("Running through upgrades..."));
		// Upgrade framework (upgrades/ dir)
		$installer->install_upgrades($version);
		$output->writeln(_("Finished upgrades"));

		$output->write("Setting FreePBX version to ".$fwver."...");
		$installer->set_version($fwver);
		$output->writeln(_("Done"));

		$output->write("Writing out ".AMP_CONF."...");
		if(!file_put_contents(AMP_CONF, $freepbx_conf->amportal_generate(true))) {
			$output->writeln("<error>"._("Error!")."</error>");
			$output->writeln("<error>"._("Unable to write to file")."</error>");
			exit(1);
		}
		$output->writeln(_("Done"));

		if ($newinstall) {
			/* Write freepbx.conf */
			$conf = "<?php
\$amp_conf['AMPDBUSER'] = '{$amp_conf['AMPDBUSER']}';
\$amp_conf['AMPDBPASS'] = '{$amp_conf['AMPDBPASS']}';
\$amp_conf['AMPDBHOST'] = '{$amp_conf['AMPDBHOST']}';
\$amp_conf['AMPDBPORT'] = '{$amp_conf['AMPDBPORT']}';
\$amp_conf['AMPDBNAME'] = '{$amp_conf['AMPDBNAME']}';
\$amp_conf['AMPDBENGINE'] = '{$amp_conf['AMPDBENGINE']}';
\$amp_conf['datasource'] = ''; //for sqlite3

require_once('{$amp_conf['AMPWEBROOT']}/admin/bootstrap.php');
?>
";
			$output->write("Writing out ".$freepbx_conf_path."...");
			if(!file_put_contents($freepbx_conf_path, $conf)) {
				$output->writeln("<error>"._("Error!")."</error>");
				$output->writeln("<error>"._("Unable to write to file")."</error>");
				exit(1);
			}
			$output->writeln(_("Done"));
		}

		// Sanity check - trap error as reported in
		// http://issues.freepbx.org/browse/FREEPBX-9898
		if (!array_key_exists("AMPBIN", $amp_conf)) {
			$output->writeln(_("No amp_conf[AMPBIN] value exists!"));
			$output->writeln(_("Giving up!"));
			$output->writeln("");
			exit;
		}

		//run this here so that we make sure everything is square for module installs
		$output->writeln(_("Chowning directories..."));
		system($amp_conf['AMPSBIN']."/fwconsole chown");
		$output->writeln(_("Done"));

		// module_admin install framework
		$output->writeln(_("Installing framework..."));
		$this->executeSystemCommand($amp_conf['AMPSBIN']."/fwconsole ma install framework", 600);
		$output->writeln(_("Done"));

		if(method_exists(\FreePBX::create()->View,'getScripts')) {
			$output->write("Building Packaged Scripts...");
			\FreePBX::View()->getScripts();
			$output->writeln(_("Done"));
		}

		// GPG setup - trustFreePBX();
		$output->write("Trusting FreePBX...");
		try {
			\FreePBX::GPG()->trustFreePBX();
		} catch(\Exception $e) {
			$output->writeln("<error>"._("Error!")."</error>");
			$output->writeln("<error>"._("Error while trusting FreePBX:")." ".$e->getMessage()."</error>");
			exit(1);
		}

		// Make sure we have the latest keys, if this install is out of date.
		\FreePBX::GPG()->refreshKeys();

		$output->writeln(_("Trusted"));

		if($answers['dev-links']) {
			$output->writeln(_("Enabling Developer mode"));
			$this->executeSystemCommand($amp_conf['AMPSBIN'] . "/fwconsole setting DEVEL 1 > /dev/null");
		}

		/* read modules list from MODULE_DIR */
		if(file_exists(MODULE_DIR) && $newinstall) {
			$output->write("Installing base modules...");
			$this->executeSystemCommand($amp_conf['AMPSBIN']."/fwconsole ma install core dashboard sipsettings voicemail certman");
			$output->writeln(_("Done installing base modules"));
			if(!$answers['skip-install']) {
				$output->write("Installing all modules...");
				system($amp_conf['AMPSBIN']."/fwconsole ma installlocal");
				$output->writeln(_("Done installing all modules"));
			}

		}

		//run this here so that we make sure everything is square for asterisk
		$this->executeSystemCommand($amp_conf['AMPSBIN'] . "/fwconsole chown");

		if($answers['dev-links'] && $newinstall) {
			$this->executeSystemCommand($amp_conf['AMPSBIN']."/fwconsole setting AMPMGRPASS ".$amp_conf['AMPMGRPASS']." > /dev/null");
		}

		// generate_configs();
		$output->writeln(_("Generating default configurations..."));
		system("runuser " . $amp_conf['AMPASTERISKUSER'] . ' -s /bin/bash -c "cd ~/ && '.$amp_conf["AMPSBIN"].'/fwconsole reload &>/dev/null"');
		$output->writeln(_("Finished generating default configurations"));

		$output->writeln("<info>"._("You have successfully installed FreePBX")."</info>");
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
			$output->writeln(_("-> Original file:")." ".$file2);
			$output->writeln(_("-> New file:")."       ".$file1);
			$output->writeln(_("Exiting install program."));
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

	public function getFilesToCopy($path, $newinstall) {
		// PHP 5.4+ Required
		$dir = new \RecursiveDirectoryIterator($path, \FilesystemIterator::FOLLOW_SYMLINKS);
		$moh_subdir = $path."/moh";

		$filter = new \RecursiveCallbackFilterIterator($dir, function ($current, $key, $iterator) use ($newinstall, $moh_subdir) {
			// Skip files we never want to copy
			$file = $current->getFilename();
			switch ($file) {
			case ".":
			case "..":
			case "CVS":
			case ".svn":
			case ".git":
				return false;
			}

			// Is this file in the MOH folder?
			if ($current->getBasename() === $moh_subdir) {
				// Only copy it if it's NOT a new install.
				return !$newinstall;
			}

			// Everything else, we're fine.
			return true;
		});

		// Now return our iterator thats using the filter
		return new \RecursiveIteratorIterator($filter);
	}

	private function recursive_copy(InputInterface $input, OutputInterface $output, ProgressBar $progress, $iterator, $make_links = false) {
		global $amp_conf;
		$num_files = 0;

		// These are modified by apply_conf.sh, and should never be symlinked
		$never_symlink = array(
			"/etc/asterisk/cdr_adaptive_odbc.conf" => true,
			"/etc/asterisk/indications.conf" => true,
			"/etc/asterisk/manager.conf" => true,
			"/etc/asterisk/modules.conf" => true,
		);

		// Our installer knows where everything should go.
		$bmoinst = \FreePBX::create()->Installer;

		// And now do the magic.
		foreach ($iterator as $z) {
			$src = $z->getPathname();
			// Note: I did some performance testing with substr vs str_replace, and str_replace was faster. By 100msec.
			$dest = $bmoinst->getDestination('framework', str_replace($this->rootPath."/","",$src));

			// Does the directory for this file already exist?
			if (!is_dir(dirname($dest))) {
				mkdir(dirname($dest), 0755, true);
			}

			//TODO: modules that symlink break everything
			// Is the source ALREADY a link? If so, we don't want to link to a link.
			//if (is_link($src)) {
				//$src = readlink($src);
			//}

			// Delete the file we're about to replace
			if (file_exists($dest)) {
				if (is_dir($dest)) {
					`/bin/rm -rf $dest`; // TODO: Directoryiterator again?
				} else {
					unlink($dest);
				}
			}

			// Copy, or link, the source to the destination.
			if(file_exists($src)) {
				if ($make_links && !isset($never_symlink[$dest])) {
					symlink($src, $dest);
				} else {
					copy($src, $dest);
				}
			}

			if($progress->getProgress() < $progress->getMaxSteps()) {
				$progress->advance();
			}
		}
	}

	/**
	 * Execute system command, check for tty first.
	 *
	 * @param string $command
	 * @return void
	 */
	private function executeSystemCommand($command,$timeout =180) {
		$process = new Process($command);
		$process->setTimeout($timeout);
		if($this->isTtySupported()) {
			$process->setTty(true);
			$process->mustRun();
			if(!posix_isatty(STDIN)) {
				$this->output->write($process->getOutput());
			} else {
				$process->mustRun();
			}
		} else {
			$process->mustRun();
		}
	}

	/**
	 * https://github.com/symfony/symfony/blob/4.2/src/Symfony/Component/Process/Process.php#L1249
	 *
	 * @return boolean
	 */
	private function isTtySupported() {
		if(isset($this->isTtySupported)) {
			return $this->isTtySupported;
		}
		$this->isTtySupported = (bool) @proc_open('echo 1 >/dev/null', array(array('file', '/dev/tty', 'r'), array('file', '/dev/tty', 'w'), array('file', '/dev/tty', 'w')), $pipes);
		return $this->isTtySupported;
	}
}
