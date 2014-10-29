<?php
namespace FreePBX\Install;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

require_once('amp_conf/htdocs/admin/libraries/BMO/Database.class.php');

class FreePBXInstallCommand extends Command {
	private $settings = array(
		'dbengine' => array(
			'default' => 'mysql',
	 		'description' => 'Database engine'
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

	protected $amp_conf;

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
		$style = new OutputFormatterStyle('white', 'red', array('bold'));
		$output->getFormatter()->setStyle('fire', $style);

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

		define("AMP_CONF", "/etc/amportal.conf");
		define("ASTERISK_CONF", "/etc/asterisk/asterisk.conf");

		// Fail if !root
		$euid = posix_getpwuid(posix_geteuid());
		if ($euid['name'] != 'root') {
			$output->writeln($this->getName() . " must be run as root.");
			exit(1);
		}

		// Copy default amportal.conf
		if (!file_exists(AMP_CONF)) {
			$newinstall = true;
			$this->amp_conf = $this->amportal_conf_read(dirname(__FILE__) . "/amportal.conf");
		} else {
			$this->amp_conf = $this->amportal_conf_read(AMP_CONF);
		}

		if (isset($answers['dbengine'])) {
			$this->amp_conf['AMPDBENGINE'] = $answers['dbengine'];
		}
		if (isset($answers['webroot'])) {
			$this->amp_conf['AMPWEBROOT'] = $answers['webroot'];
		}
		if (isset($answers['user'])) {
			$this->amp_conf['AMPASTERISKUSER'] = $answers['user'];
			$this->amp_conf['AMPASTERISKWEBUSER'] = $answers['user'];
			$this->amp_conf['AMPDEVUSER'] = $answers['user'];
		}
		if (isset($answers['group'])) {
			$this->amp_conf['AMPASTERISKGROUP'] = $answers['group'];
			$this->amp_conf['AMPASTERISKWEBGROUP'] = $answers['group'];
			$this->amp_conf['AMPDEVGROUP'] = $answers['group'];
		}
		if (!isset($this->amp_conf['AMPMANAGERHOST'])) {
			$this->amp_conf['AMPMANAGERHOST'] = 'localhost';
		}

		// ... and then write amportal.conf?
		// Read/parse amportal.conf

		// Copy asterisk.conf
		if (!file_exists(ASTERISK_CONF)) {
			$asterisk_conf = $this->asterisk_conf_read(dirname(__FILE__) . "/asterisk.conf");

			$this->asterisk_conf_write(dirname(__FILE__) . "/asterisk_new.conf", $asterisk_conf);
		} else {
			$asterisk_conf = $this->asterisk_conf_read(ASTERISK_CONF);

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
			$this->amp_conf['ASTETCDIR'] = $asterisk_conf['astetcdir'];
		}
		if (isset($asterisk_conf['astmoddir'])) {
			$this->amp_conf['ASTMODDIR'] = $asterisk_conf['astmoddir'];
		}
		if (isset($asterisk_conf['astvarlibdir'])) {
			$this->amp_conf['ASTVARLIBDIR'] = $asterisk_conf['astvarlibdir'];
		}
		if (isset($asterisk_conf['astagidir'])) {
			$this->amp_conf['ASTAGIDIR'] = $asterisk_conf['astagidir'];
		}
		if (isset($asterisk_conf['astspooldir'])) {
			$this->amp_conf['ASTSPOOLDIR'] = $asterisk_conf['astspooldir'];
		}
		if (isset($asterisk_conf['astrundir'])) {
			$this->amp_conf['ASTRUNDIR'] = $asterisk_conf['astrundir'];
		}
		if (isset($asterisk_conf['astlogdir'])) {
			$this->amp_conf['ASTLOGDIR'] = $asterisk_conf['astlogdir'];
		}

		// Read/parse asterisk.conf
		// ... and then write amportal.conf, again?!
		$this->amportal_conf_write(dirname(__FILE__) . "/amportal_new.conf");

		// Write /etc/asterisk/version ?
		exec("asterisk -V", $tmpout, $ret);
		if ($ret != 0) {
			$output->writeln("Error executing Asterisk.  Ensure that Asterisk is properly installed.");
			exit(1);
		}
		$astver = $tmpout[0];
		unset($tmpout);

		file_put_contents($this->amp_conf['ASTETCDIR'] . '/version', $astver);

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
		/* CDR first, so we can keep the correct database handler around. */
		$dsn = $this->amp_conf['AMPDBENGINE'] . ":host=" . $this->amp_conf['AMPDBHOST'] . ";dbname=asteriskcdrdb";
		$db = new \Database($this->amp_conf, $dsn);
		$sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'asteriskcdrdb';";
		if (!$db->getOne($sql)) {
			$this->installampimport_mysql_dump('cdr_mysql_table.sql', $db);
		}
		unset($db);

		$dsn = $this->amp_conf['AMPDBENGINE'] . ":host=" . $this->amp_conf['AMPDBHOST'] . ";dbname=" . $this->amp_conf['AMPDBNAME'];
		$db = new \Database($this->amp_conf, $dsn);
		$sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . $this->amp_conf['AMPDBNAME'] . "';";
		if (!$db->getOne($sql)) {
			$this->installampimport_mysql_dump('newinstall.sql', $db);
		}

		// Get version of FreePBX.
		$version = $this->get_version($db);

		// Copy amp_conf/
		$this->recursive_copy($input, $output, "amp_conf", "", "", $newinstall, false);

		// Create dirs
		// 	/var/www/html/admin/modules/framework/
		// 	/var/www/html/admin/modules/_cache/
		//	./amp_conf/htdocs/admin/modules/_cache/
		@mkdir($this->amp_conf['AMPWEBROOT'] . "/admin/modules/_cache", 0777, true);

		// Copy /var/www/html/admin/modules/framework/module.xml
		copy(dirname(__FILE__) . "/module.xml", $this->amp_conf['AMPWEBROOT'] . "/admin/modules/framework/module.xml");

		// Create dirs
		//	/var/spool/asterisk/voicemail/device/
		@mkdir($this->amp_conf['AMPSPOOLDIR'] . "/voicemail/device", 0755, true);
		// Copy /etc/asterisk/voicemail.conf.template
		// ... to /etc/asterisk/voicemail.conf

		//	/var/spool/asterisk/fax/
		@mkdir($this->amp_conf['AMPSPOOLDIR'] . "/fax", 0766, true);

		//	/var/spool/asterisk/monitor/
		@mkdir($this->amp_conf['AMPSPOOLDIR'] . "/monitor", 0766, true);

		//	/var/www/html/recordings/themes/js/
		@mkdir($this->amp_conf['AMPWEBROOT'] . "/recordings/themes/js", 0755, true);

		// Link /var/www/html/admin/common/libfreepbx.javascripts.js
		// ... to /var/www/html/recordings/themes/js/
		$js = $this->amp_conf['AMPWEBROOT'] . "/admin/common/libfreepbx.javascripts.js";
		$js_link = $this->amp_conf['AMPWEBROOT'] . "/recordings/themes/js/libfreepbx.javascripts.js";
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
		//	ASTMANAGERHOST - should this be localhost?  Yes.

		// Create missing #include files.
		exec("grep -h '^#include' " . $this->amp_conf['ASTETCDIR'] . "/*.conf | sed 's/\s*;.*//;s/#include\s*//'", $tmpout, $ret);
		if ($ret != 0) {
			$output->writeln("Error finding #include files.");
			exit(1);
		}

		foreach ($tmpout as $file) {
			if ($file[0] != "/") {
				$file = $this->amp_conf['ASTETCDIR'] . "/" . $file;
			}
			if (!file_exists($file)) {
				touch($file);
			}
		}
		unset($tmpout);

		// apply_conf.sh

		// Upgrade modules
		// freepbx_settings_init();
		// freepbx_conf set_conf_values()
		// generate_configs();
		// install_modules()
		// module_admin install framework

		// GPG setup - trustFreePBX();

		// needreload();
	}

	private function amportal_conf_read($filename) {
		$file = file($filename);

		foreach ($file as $line) {
			if (preg_match("/^\s*([a-zA-Z0-9_]+)\s*=\s*(.*)\s*([;#].*)?/", $line, $matches)) {
				$conf[$matches[1]] = $matches[2];
			}
		}

		return $conf;
	}

	private function amportal_conf_write($filename) {
		foreach ($this->amp_conf as $key => $value) {
			$file[] = $key . "=" . $value;
		}

		file_put_contents($filename, implode("\n", $file) . "\n");
	}

	private function asterisk_conf_read($filename) {
		$file = file($filename);

		foreach ($file as $line) {
			if (preg_match("/^\s*([a-zA-Z0-9_]+)\s*(?:=>|=)\s*(.*)\s*([;#].*)?/", $line, $matches)) {
				$conf[$matches[1]] = $matches[2];
			}
		}

		return $conf;
	}

	private function asterisk_conf_write($filename, $conf) {
		foreach ($conf as $key => $value) {
			$file[] = $key . "=>" . $value;
		}

		file_put_contents($filename, implode("\n", $file) . "\n");
	}

	private function get_version($db) {
		$version = $db->getOne("SELECT value FROM admin WHERE variable = 'version'");

		return $version;
	}
	private function set_version($db, $version) {
	}

	private function installampimport_mysql_dump($file, $db) {
		$dir = dirname(__FILE__);
		if (!file_exists($dir.'/SQL/'.$file)) {
			return false;
		}

		// Temporary variable, used to store current query
		$templine = '';
		// Read in entire file
		$lines = file($dir.'/SQL/'.$file);
		// Loop through each line
		foreach ($lines as $line) {
			// Skip it if it's a comment
			if (substr($line, 0, 2) == '--' || $line == '') {
				continue;
			}

			// Add this line to the current segment
			$templine .= $line;
			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';') {
				// Perform the query
				$sth = $db->query($templine);
				if($sth->errorCode() != 0) {
					fatal("Error performing query: ". $templine . " Message:".$sth->errorInfo());
				}
				// Reset temp variable to empty
				$templine = '';
			}
		}
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

	private function recursive_copy(InputInterface $input, OutputInterface $output, $dirsourceparent, $dirdest, $dirsource = "", $newinstall = true, $make_links = false) {
		$moh_subdir = isset($this->amp_conf['MOHDIR']) ? trim(trim($this->amp_conf['MOHDIR']),'/') : 'moh';

		// total # files, # actually copied
		$num_files = $num_copied = 0;

		if ($dirsource && ($dirsource[0] != "/")) $dirsource = "/".$dirsource;

		if (is_dir($dirsourceparent.$dirsource)) $dir_handle = opendir($dirsourceparent.$dirsource);

		while (isset($dir_handle) && ($file = readdir($dir_handle))) {
			if (($file==".") || ($file=="..") || ($file == "CVS") || ($file == ".svn") || ($file == ".git")) {
				continue;
			}

			$source = $dirsourceparent.$dirsource."/".$file;
			$destination =  $dirdest.$dirsource."/".$file;

			if ($dirsource == "" && $file == "moh" && !$newinstall) {
				// skip to the next dir
				continue;
			}

			// configurable in amportal.conf
			$destination=preg_replace("/^\/htdocs/",trim($this->amp_conf["AMPWEBROOT"])."/",$destination);

			$destination=str_replace("/astetc",trim($this->amp_conf["ASTETCDIR"]),$destination);
			$destination=str_replace("/moh",trim($this->amp_conf["ASTVARLIBDIR"])."/$moh_subdir",$destination);
			$destination=str_replace("/astvarlib",trim($this->amp_conf["ASTVARLIBDIR"]),$destination);
			if(strpos($dirsource, 'modules') === false) {
				$destination=str_replace("/agi-bin",trim($this->amp_conf["ASTAGIDIR"]),$destination);
				$destination=str_replace("/sounds",trim($this->amp_conf["ASTVARLIBDIR"])."/sounds",$destination);
				$destination=str_replace("/bin",trim($this->amp_conf["AMPBIN"]),$destination);
			}
			$destination=str_replace("/sbin",trim($this->amp_conf["AMPSBIN"]),$destination);

			if (!is_dir($source)) {
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
				// if this is a directory, ensure destination exists
				if (!file_exists($destination)) {
					if ($destination != "") {
						@mkdir($destination, "0750", true);
					}
				}

				list($tmp_num_files, $tmp_num_copied) = $this->recursive_copy($input, $output, $dirsourceparent, $dirdest, $dirsource."/".$file, $newinstall, $make_links);
				$num_files += $tmp_num_files;
				$num_copied += $tmp_num_copied;
			}
		}

		if (isset($dir_handle)) closedir($dir_handle);

		return array($num_files, $num_copied);
	}

	private function check_diff($file1, $file2) {
		// diff, ignore whitespace and be quiet
		exec("diff -wq ".escapeshellarg($file2)." ".escapeshellarg($file1), $output, $retVal);
		return ($retVal != 0);
	}
}

