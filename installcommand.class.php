<?php
namespace FreePBX\Install;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class FreePBXInstallCommand extends Command {
	private $settings = array(
		'dbengine' => array(
			'option' => 'AMPDBENGINE',
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
		'astuser' => array(
			'default' => 'admin',
	 		'description' => 'Asterisk username'
		),
		'astgroup' => array(
			'default' => 'admin',
	 		'description' => 'Asterisk group'
		),
		'astpass' => array(
			'default' => '',
	 		'description' => 'Asterisk password'
		),
		'webroot' => array(
			'option' => 'AMPWEBROOT',
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
		$style = new OutputFormatterStyle('white', 'red', array('bold'));
		$output->getFormatter()->setStyle('fire', $style);

		if ($input->isInteractive()) {
			$helper = $this->getHelper('question');

			foreach ($this->settings as $key => $setting) {
				$question = new Question($setting['description'] . ($setting['default'] ? ' [' . $setting['default'] . ']' : '') . ': ', $setting['default']);
				$answers[$key] = $helper->ask($input, $output, $question);
			}
		} else {
			foreach ($this->settings as $key => $setting) {
				$answers[$key] = $input->getOption($key);
			}
		}

		define("AMP_CONF", "/etc/amportal.conf");

		// Fail if !root
		$euid = posix_getpwuid(posix_geteuid());
		if ($euid['name'] != 'root') {
			$output->writeln($this->getName() . " must be run as root.");
			die();
		}

		// Copy default amportal.conf
		if (!file_exists(AMP_CONF)) {
			$amp_conf = $this->amportal_conf_read(dirname(__FILE__) . "/amportal.conf");
		} else {
			$amp_conf = $this->amportal_conf_read(AMP_CONF);
		}
		foreach ($this->settings as $key => $setting) {
			if (isset($setting['option'])) {
				$amp_conf[$setting['option']] = $answers[$key];
			}
		}

		// ... and then write amportal.conf?
		// Read/parse amportal.conf

		// Copy asterisk.conf
		if (!file_exists(ASTERISK_CONF)) {
			$asterisk_conf = $this->asterisk_conf_read(dirname(__FILE__) . "/asterisk.conf");

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
			$this->asterisk_conf_write(dirname(__FILE__) . "/asterisk_new.conf", $asterisk_conf);
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
		$this->amportal_conf_write(dirname(__FILE__) . "/amportal_new.conf", $amp_conf);

		// Write /etc/asterisk/version ?
		exec("asterisk -V", $tmpout, $ret);
		if ($ret != 0) {
			$output->writeln("Error executing Asterisk.  Ensure that Asterisk is properly installed.");
			die();
		}
		$astver = $tmpout[0];

		file_put_contents($amp_conf['ASTETCDIR'] . '/version', $astver);

		// Parse Asterisk version.
		if (preg_match('/^Asterisk (?:SVN-)?(\d+(\.\d+)*)(-?(.*))$/', $astver, $matches)) {
			if ((version_compare($matches[1], "1.8") < 0) || 
			     version_compare($matches[1], "14", "ge")) {
				$output->writeln("Supported Asterisk versions: 1.8, 11, 12, 13");
				$output->writeln("Detected Asterisk version: " . $matches[1]);
				die();
			} else {
				$output->writeln("Could not determine Asterisk version (got: " . $astver . "). Please report this.");
				die();
			}
		}

		// Make sure SELinux is disabled
		exec("getenforce 2>/dev/null", $tmpout, $ret);
		if (isset($tmpout[0]) && $tmpout[0] === "Enabled") {
			$output->writeln("SELinux is enabled.  Please disable SELinux before installing FreePBX.");
			die();
		}

		// Datasource!  Getting somewhere now...
		// Create database(s).
		// Get version of FreePBX.

		// Create dirs
		// 	/var/www/html/admin/modules/framework/
		// 	/var/www/html/admin/modules/_cache/
		//	./amp_conf/htdocs/admin/modules/_cache/
		// Copy /var/www/html/admin/modules/framework/module.xml
		// Copy amp_conf/
		// Create missing #include files.

		// Create dirs
		//	/var/spool/asterisk/voicemail/device/
		//	/var/spool/asterisk/fax/
		//	/var/spool/asterisk/monitor/
		//	/var/www/html/recordings/themes/js/
		// Copy /etc/asterisk/voicemail.conf.template
		// ... to /etc/asterisk/voicemail.conf
		// Link /var/www/html/admin/common/libfreepbx.javascripts.js
		// ... to /var/www/html/recordings/themes/js/

		// Set User/Group settings
		//	AMPASTERISKWEBGROUP
		//	AMPASTERISKWEBUSER
		//	AMPASTERISKGROUP
		//	AMPASTERISKUSER
		//	AMPDEVGROUP
		//	AMPDEVUSER
		//	ASTMANAGERHOST

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

	function amportal_conf_read($filename) {
		$file = file($filename);

		foreach ($file as $line) {
			if (preg_match("/^\s*([a-zA-Z0-9_]+)\s*=\s*(.*)\s*([;#].*)?/", $line, $matches)) {
				$conf[$matches[1]] = $matches[2];
			}
		}

		return $conf;
	}

	function amportal_conf_write($filename, $conf) {
		foreach ($conf as $key => $value) {
			$file[] = $key . "=" . $value;
		}

		file_put_contents($filename, implode("\n", $file) . "\n");
	}

	function asterisk_conf_read($filename) {
		$file = file($filename);

		foreach ($file as $line) {
			if (preg_match("/^\s*([a-zA-Z0-9_]+)\s*(?:=>|=)\s*(.*)\s*([;#].*)?/", $line, $matches)) {
				$conf[$matches[1]] = $matches[2];
			}
		}

		return $conf;
	}

	function asterisk_conf_write($filename, $conf) {
		foreach ($conf as $key => $value) {
			$file[] = $key . "=>" . $value;
		}

		file_put_contents($filename, implode("\n", $file) . "\n");
	}
}

