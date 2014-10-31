<?php
namespace FreePBX\Install;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class FreePBXInstallCommand extends Command {
	private $settings = array(
		'dbengine' => array(
			'default' => 'mysql',
	 		'description' => 'Database engine'
		),
		'dbname' => array(
			'default' => 'asterisk',
	 		'description' => 'Database name'
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
		'webroot' => array(
			'default' => '/var/www/html',
	 		'description' => 'Filesystem location from which FreePBX files will be served'
		),
	);

	protected function configure() {
		$this
			->setName('install')
			->setDescription('FreePBX Installation Utility')
			;

		foreach ($this->settings as $key => $setting) {
			$this->addOption($key, null, InputOption::VALUE_REQUIRED, $setting['description'], $setting['default']);
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		global $amp_conf; /* This makes pandas sad. :( */
		global $db;

		$style = new OutputFormatterStyle('white', 'red', array('bold'));
		$output->getFormatter()->setStyle('fire', $style);

		define("AMP_CONF", "/etc/amportal.conf");
		define("ASTERISK_CONF", "/etc/asterisk/asterisk.conf");
		define("MODULE_DIR", dirname(__FILE__)."/amp_conf/htdocs/admin/modules");
		define("UPGRADE_DIR", dirname(__FILE__) . "/upgrades");

		// Fail if !root
		$euid = posix_getpwuid(posix_geteuid());
		if ($euid['name'] != 'root') {
			$output->writeln($this->getName() . " must be run as root.");
			exit(1);
		}

		foreach ($this->settings as $key => $setting) {
			$answers[$key] = $input->getOption($key);
		}

		if ($input->isInteractive()) {
			$helper = $this->getHelper('question');

			foreach ($this->settings as $key => $setting) {
				$question = new Question($setting['description'] . ($setting['default'] ? ' [' . $setting['default'] . ']' : '') . ': ', isset($answers[$key]) ? $answers[$key] : $setting['default']);
				$answers[$key] = $helper->ask($input, $output, $question);
			}
		}

		require_once('installer.class.php');
		$installer = new Installer($input, $output);

		// Copy default amportal.conf
		if (!file_exists(AMP_CONF)) {
			$newinstall = true;
			$amp_conf = $installer->amportal_conf_read(dirname(__FILE__) . "/amportal.conf");
		} else {
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

		if ($newinstall) {
			$amp_conf['AMPMGRUSER'] = 'admin';
			$amp_conf['AMPMGRPASS'] = md5(uniqid());
		}

		// ... and then write amportal.conf?
		// Read/parse amportal.conf

		// Copy asterisk.conf
		if (!file_exists(ASTERISK_CONF)) {
			$asterisk_conf = $installer->asterisk_conf_read(dirname(__FILE__) . "/asterisk.conf");

			$installer->asterisk_conf_write(dirname(__FILE__) . "/asterisk_new.conf", $asterisk_conf);
		} else {
			$asterisk_conf = $installer->asterisk_conf_read(ASTERISK_CONF);

			$asterisk_defaults_conf = array(
				'astetcdir' => '/etc/asterisk',
				'astmoddir' => '/usr/lib/asterisk/modules',
				'astvarlibdir' => '/var/lib/asterisk',
				'astagidir' => '/var/lib/asterisk/agi-bin',
				'astspooldir' => '/var/spool/asterisk',
				'astrundir' => '/var/run/asterisk',
				'astlogdir' => '/var/log/asterisk',
			);

			foreach ($asterisk_defaults_conf as $key => $value) {
				if (!isset($asterisk_conf[$key])) {
					$asterisk_conf[$key] = $value;
				}
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

		// Read/parse asterisk.conf
		// ... and then write amportal.conf, again?!

		// Write /etc/asterisk/version ?
		exec("asterisk -V", $tmpout, $ret);
		if ($ret != 0) {
			$output->writeln("Error executing Asterisk.  Ensure that Asterisk is properly installed.");
			exit(1);
		}
		$astver = $tmpout[0];
		unset($tmpout);

		file_put_contents($amp_conf['ASTETCDIR'] . '/version', $astver);

		// Parse Asterisk version.
		if (preg_match('/^Asterisk (?:SVN-)?(\d+(\.\d+)*)(-?(.*))$/', $astver, $matches)) {
			if ((version_compare($matches[1], "1.8") < 0) || 
			     version_compare($matches[1], "14", "ge")) {
				$output->writeln("Supported Asterisk versions: 1.8, 11, 12, 13");
				$output->writeln("Detected Asterisk version: " . $matches[1]);
				exit(1);
			}
		} else {
			$output->writeln("Could not determine Asterisk version (got: " . $astver . "). Please report this.");
			exit(1);
		}

		// Make sure SELinux is disabled
		exec("getenforce 2>/dev/null", $tmpout, $ret);
		if (isset($tmpout[0]) && $tmpout[0] === "Enabled") {
			$output->writeln("SELinux is enabled.  Please disable SELinux before installing FreePBX.");
			exit(1);
		}
		unset($tmpout);

		// Create database(s).
		if ($newinstall) {
			require_once('amp_conf/htdocs/admin/libraries/BMO/Database.class.php');

			$dsn = $amp_conf['AMPDBENGINE'] . ":host=" . $amp_conf['AMPDBHOST'] . ";dbname=asteriskcdrdb";
			$db = new \Database($amp_conf, $dsn);
			$sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'asteriskcdrdb';";
			if (!$db->getOne($sql)) {
				$installer->install_sql_file(dirname(__FILE__) . 'SQL/cdr_mysql_table.sql');
			}
			unset($db);

			$dsn = $amp_conf['AMPDBENGINE'] . ":host=" . $amp_conf['AMPDBHOST'] . ";dbname=" . $amp_conf['AMPDBNAME'];
			$db = new \Database($amp_conf, $dsn);
			$sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . $amp_conf['AMPDBNAME'] . "';";
			if (!$db->getOne($sql)) {
				$installer->install_sql_file(dirname(__FILE__) . 'SQL/newinstall.sql');
			}
		}

		// Get version of FreePBX.
		$version = $installer->get_version();

		// Copy amp_conf/
		$this->recursive_copy($input, $output, "amp_conf", "", $newinstall, false);

		// Create dirs
		// 	/var/www/html/admin/modules/framework/
		// 	/var/www/html/admin/modules/_cache/
		//	./amp_conf/htdocs/admin/modules/_cache/
		@mkdir($amp_conf['AMPWEBROOT'] . "/admin/modules/_cache", 0777, true);

		// Copy /var/www/html/admin/modules/framework/module.xml
		copy(dirname(__FILE__) . "/module.xml", $amp_conf['AMPWEBROOT'] . "/admin/modules/framework/module.xml");

		// Create dirs
		//	/var/spool/asterisk/voicemail/device/
		@mkdir($amp_conf['ASTSPOOLDIR'] . "/voicemail/device", 0755, true);
		// Copy /etc/asterisk/voicemail.conf.template
		// ... to /etc/asterisk/voicemail.conf

		//	/var/spool/asterisk/fax/
		@mkdir($amp_conf['ASTSPOOLDIR'] . "/fax", 0766, true);

		//	/var/spool/asterisk/monitor/
		@mkdir($amp_conf['ASTSPOOLDIR'] . "/monitor", 0766, true);

		//	/var/www/html/recordings/themes/js/
		@mkdir($amp_conf['AMPWEBROOT'] . "/recordings/themes/js", 0755, true);

		// Link /var/www/html/admin/common/libfreepbx.javascripts.js
		// ... to /var/www/html/recordings/themes/js/
		$js = $amp_conf['AMPWEBROOT'] . "/admin/common/libfreepbx.javascripts.js";
		$js_link = $amp_conf['AMPWEBROOT'] . "/recordings/themes/js/libfreepbx.javascripts.js";
		if (file_exists($js) && !file_exists($js_link)) {
			link($js, $js_link);
		}

		// Set User/Group settings
		//	AMPASTERISKWEBGROUP
		//	AMPASTERISKWEBUSER
		//	AMPASTERISKGROUP
		//	AMPASTERISKUSER
		//	AMPDEVGROUP
		//	AMPDEVUSER
		//	ASTMANAGERHOST - should this default to localhost?  Yes.

		// apply_conf.sh
		$manager_conf = file_get_contents($amp_conf['ASTETCDIR'] . "/manager.conf");
		$replace = array(
			'AMPMGRUSER' => $amp_conf['AMPMGRUSER'],
			'AMPMGRPASS' => $amp_conf['AMPMGRPASS'],
		);
		$manager_conf = str_replace(array_keys($replace), array_values($replace), $manager_conf);
		file_put_contents($amp_conf['ASTETCDIR'] . "/manager.conf", $manager_conf);

		// Create missing #include files.
		exec("grep -h '^#include' " . $amp_conf['ASTETCDIR'] . "/*.conf | sed 's/\s*;.*//;s/#include\s*//'", $tmpout, $ret);
		if ($ret != 0) {
			$output->writeln("Error finding #include files.");
			exit(1);
		}

		foreach ($tmpout as $file) {
			if ($file[0] != "/") {
				$file = $amp_conf['ASTETCDIR'] . "/" . $file;
			}
			if (!file_exists($file)) {
				touch($file);
			}
		}
		unset($tmpout);

		// Upgrade framework (upgrades/ dir)
		$installer->install_upgrades($version);

		// freepbx_settings_init();
		$installer->freepbx_settings_init(true);

		// freepbx_conf set_conf_values()
		$freepbx_conf =& \freepbx_conf::create();
		foreach ($amp_conf as $keyword => $value) {
			if ($freepbx_conf->conf_setting_exists($keyword)) {
				$freepbx_conf->set_conf_values(array($keyword => $value), false, true);
			}
		}
		$freepbx_conf->commit_conf_settings();
		file_put_contents(AMP_CONF, $freepbx_conf->amportal_generate(true));

		// generate_configs();
		/* nah */

		/* Bootstrap!  Yeah! */
		if ($newinstall) {
			$bootstrap_settings['freepbx_auth'] = false;
			$restrict_mods = true;
			if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
				include_once('/etc/asterisk/freepbx.conf');
			}
		}

		// install_modules()
		$included_modules = array();
		/* read modules list from MODULE_DIR */
		$dir = opendir(MODULE_DIR);
		while ($file = readdir($dir)) {
			if ($file[0] != "." && $file[0] != "_" && is_dir(MODULE_DIR."/".$file)) {
				$included_modules[] = $file;
			}
		}
		closedir($dir);
		$this->install_modules($included_modules);

		// module_admin install framework
		$this->install_modules(array('framework'));

		// GPG setup - trustFreePBX();
		\FreePBX::GPG()->trustFreePBX();

		// needreload();
		needreload();
	}

	private function ask_overwrite(InputInterface $input, OutputInterface $output, $file1, $file2) {
		$output->writeln($file2." has been changed from the original version.");

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

	/**
	 * Recursive Read Links
	 *
	 * This function is used to recursively read symlink until we reach a real directory
	 *
	 * @params string $source - The original file we are replacing
	 * @returns array of the original source we read in and the real directory for it
	 */
	private function recursive_readlink($source){
		$dir = dirname($source);
		$links = array();
		$ldir = null;

		while (!in_array($dir,array('.','..','','/')) && strpos('.git',$dir) == false) {
			if ($dir == $ldir) {
				break;
			}
			if (is_link($dir)) {
				$ldir = readlink($dir);
				$file = str_replace($dir, $ldir, $source);
				if (!is_link($ldir) && file_exists($file)) {
					$links[$source] = $file;
				}
			} else {
				if (file_exists($source) && !is_link(dirname($source))) {
					break;
				}
				$ldir = dirname($dir);
				$file = str_replace($dir, $ldir, $source);
				if (!is_link($ldir) && file_exists($file)) {
					$links[$source] = $file;
				}
			}
			$ldir = $dir;
			$dir = dirname($dir);
		}

		return $links;
	}

	/**
	 * Substitute Read Links
	 *
	 * This function is used to substitute symlinks, to real directories where information is stored
	 *
	 * @params string $source - The original file we are replacing
	 * @params array $links - A list of possible replacements
	 * @return string of the real file path to the given source
	 */
	private function substitute_readlinks($source,$links) {
		foreach ($links as $key => $value) {
			if (strpos($source, $key) !== false) {
				$source = str_replace($key, $value, $source);
				return $source;
			}
		}
	}

	private function recursive_copy(InputInterface $input, OutputInterface $output, $dirsourceparent, $dirsource = "", $newinstall = true, $make_links = false) {
		global $amp_conf;

		require_once('amp_conf/htdocs/admin/libraries/BMO/Installer.class.php');
		$bmoinst = new \Installer();

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
/*
			$destination =  $dirsource."/".$file;

			// configurable in amportal.conf
			$destination=preg_replace("/^\/htdocs/",trim($amp_conf["AMPWEBROOT"])."/",$destination);

			$destination=str_replace("/astetc",trim($amp_conf["ASTETCDIR"]),$destination);
			$destination=str_replace("/moh",trim($amp_conf["ASTVARLIBDIR"])."/$moh_subdir",$destination);
			$destination=str_replace("/astvarlib",trim($amp_conf["ASTVARLIBDIR"]),$destination);
			if(strpos($dirsource, 'modules') === false) {
				$destination=str_replace("/agi-bin",trim($amp_conf["ASTAGIDIR"]),$destination);
				$destination=str_replace("/sounds",trim($amp_conf["ASTVARLIBDIR"])."/sounds",$destination);
				$destination=str_replace("/bin",trim($amp_conf["AMPBIN"]),$destination);
			}
			$destination=str_replace("/sbin",trim($amp_conf["AMPSBIN"]),$destination);
*/

			if (!is_dir($source)) {
				$destination = $bmoinst->getDestination('framework', $source);

				// These are modified by apply_conf.sh, there may be others that fit in this category also. This keeps these from
				// being symlinked and then developers inadvertently checking in the changes when they should not have.
				//
				$never_symlink = array(
					"cdr_mysql.conf",
					"manager.conf",
					"vm_email.inc",
					"modules.conf"
				);

				$num_files++;
				if ($make_links && !in_array(basename($source),$never_symlink)) {
					// symlink, unlike copy, doesn't overwrite - have to delete first
					// ^^ lies! :(
					if (is_link($destination) || file_exists($destination)) {
						unlink($destination);
					}

					$links = $this->recursive_readlink($source);
					if (!empty($links)) {
						@symlink($this->substitute_readlinks($source,$links), $destination);
					} else {
						if(file_exists(dirname(__FILE__)."/".$source)) {
							@symlink(dirname(__FILE__)."/".$source, $destination);
						}
					}
				} else {
					$ow = false;
					if(file_exists($destination) && !is_link($destination)) {
						if ($this->check_diff($source, $destination) && !$make_links) {
							$ow = $this->ask_overwrite($input, $output, $source, $destination);
						}
					} else {
						$ow = true;
					}
					if($ow) {
						//Copy will not overwrite a symlink, phpnesssss
						if(file_exists($destination) && is_link($destination)) {
							unlink($destination);
						}
						copy($source, $destination);
					} else {
						continue;
					}
				}
				$num_copied++;
			} else {
				$destination = $bmoinst->getDestination('framework', $source . "/");

				// if this is a directory, ensure destination exists
				if (!file_exists($destination)) {
					if ($destination != "") {
						@mkdir($destination, "0750", true);
					}
				}

				list($tmp_num_files, $tmp_num_copied) = $this->recursive_copy($input, $output, $dirsourceparent, $dirsource."/".$file, $newinstall, $make_links);
				$num_files += $tmp_num_files;
				$num_copied += $tmp_num_copied;
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
				// since it is included. (TODO: should we not?)
				//
				if ($keep_checking) {
					exec($amp_conf['AMPBIN']."/module_admin --no-warnings checkdepends $up_module",$output,$retval);
					unset($output);
					if ($retval) {
						continue;
					}
				}
				// Framework modules cannot be enabled, only installed.
				//
				switch ($up_module) {
					case 'framework':
					case 'fw_ari':
						system($amp_conf['AMPBIN']."/module_admin --no-warnings -f install $up_module");
					break;
					default:
						system($amp_conf['AMPBIN']."/module_admin --no-warnings -f install $up_module");
						system($amp_conf['AMPBIN']."/module_admin --no-warnings -f enable $up_module");
				}
				unset($modules[$id]);
			}
		}
	}
}

