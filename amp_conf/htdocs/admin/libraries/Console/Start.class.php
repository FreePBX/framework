<?php
//Namespace should be FreePBX\Console\Command
namespace FreePBX\Console\Command;

//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//progress bar
use Symfony\Component\Console\Helper\ProgressBar;
//Process
use Symfony\Component\Process\Process;

class Start extends Command {
	protected function configure(){
		$this->setName('start')
			->setDescription(_('Start Asterisk and run other needed FreePBX commands'))
			->addArgument('args', InputArgument::IS_ARRAY, _('Module names'))
			->addOption('pre', null, InputOption::VALUE_NONE, _('Only run pre-start hooks'))
			->addOption('post', null, InputOption::VALUE_NONE, _('Only run post-start hooks'))
			->setHelp($this->showHelp());
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		if(posix_geteuid() != 0) {
			$output->writeln("<error>You need to be root to run this command</error>");
			exit(1);
		}
		$options = $input->getOptions();
		$args = $input->getArgument('args');
		$pre = $this->preAsteriskHooks($output,false);
		$post = $this->postAsteriskHooks($output,false);
		$aststat = $this->asteriskProcess();
		$asteriskrunning = !empty($aststat);
		$bmo = \FreePBX::create();

		// We were asked to only run the pre-start hooks?
		if ($options['pre']) {
			// Note: Do not i18n.
			$output->writeln("Only running pre-hooks");
			$runpre = "force";
			$startasterisk = false;
			$runpost = false;
		} elseif ($options['post']) {
			// Note: Do not i18n.
			$output->writeln("Only running post-hooks");
			$runpre = false;
			$startasterisk = false;
			$runpost = "force";
		} else {
			$runpre = true;
			$startasterisk = true;
			$runpost = true;
		}

		// Do we have any params?
		if ($args) {
			// We do. Create a temporary array with our hooks, using the ones
			// we've been asked to do.
			$newpre = array();
			$newpost = array();
			$startasterisk = false;
			foreach ($args as $v) {
				if ($runpre) {
					foreach ($pre as $pri => $data) {
						if(strtolower($data['module']) == strtolower($v)) {
							$newpre[$pri] = $data;
						}
					}
				}
				if ($runpost) {
					foreach ($post as $pri => $data) {
						if(strtolower($data['module']) == strtolower($v)) {
							$newpost[$pri] = $data;
						}
					}
				}
			}

			// And overwrite our hooks to run later
			$pre = $newpre;
			$post = $newpost;
		}

		if ($startasterisk && $asteriskrunning) {
			$output->writeln("<error>Asterisk already running</error>");
		}

		// Now we're ready to go.  
		$brand = \FreePBX::Config()->get("DASHBOARD_FREEPBX_BRAND");
		$output->writeln(sprintf(_('Running %s startup...'),$brand));
		if ($runpre) {
			if ($runpre !== "force") {
				$chown = new Chown();
				$chown->execute($input, $output);
			}

			if ($aststat) {
				$output->writeln(sprintf(_('Unable to run Pre-Asterisk hooks, because Asterisk is already running on PID %s and has been running for %s'),$aststat, $this->asteriskUptime()));
				$startasterisk = false;
			} else {
				foreach($pre as $pri => $data) {
					$bmo->$data['module']->$data['method']($output);
				}
			}
		}

		if ($startasterisk) {
			if ($this->startAsterisk($output)) {
				$output->writeln('');
				$output->writeln(_("Asterisk Started"));
			} else {
				$output->writeln('');
				$output->writeln(_("Unable to start Asterisk!"));
			}
		}

		if ($runpost) {
			foreach($post as $pri => $data) {
				$bmo->$data['module']->$data['method']($output);
			}
		}
	}

	private function asteriskProcess() {
		$pid = `/usr/bin/env pidof asterisk`;
		return trim($pid);
	}

	private function asteriskUptime() {
		$uptime = `asterisk -rx 'core show uptime' | grep uptime`;
		if (!preg_match('/System uptime:(.+)/', $uptime, $out)) {
			return "ERROR";
		}
		return trim($out[1]);
	}

	private function startAsterisk($output){
		$output->writeln(_('Starting Asterisk...'));
		$astbin = '/usr/bin/env safe_asterisk -U '.\FreePBX::Config()->get('AMPASTERISKUSER').' -G '.\FreePBX::Config()->get('AMPASTERISKGROUP').' > /dev/null 2>&1 &';
		exec($astbin);
		// Wait for it to start. Give it up to 10 seconds.
		$progress = new ProgressBar($output, 100);
		$progress->start();
		$i = 100;
		while ($i--) {
			$x = (int) (cos($i/64)*100);
			$progress->setProgress($x);
			if ($this->asteriskIsReady()) {
				$progress->setProgress(100);
				return true;
			}
			usleep(100000);
		}

		// Hmm. Didn't start.
		return false;
	}

	private function preAsteriskHooks($output,$execute=true){
		if(!$execute) {
			return \FreePBX::Hooks()->returnHooks();
		}
		\FreePBX::Hooks()->processHooks($output);
		return;
	}
	private function postAsteriskHooks($output,$execute=true){
		if(!$execute) {
			return \FreePBX::Hooks()->returnHooks();
		}
		\FreePBX::Hooks()->processHooks($output);
		return;
	}

	private function asteriskIsReady() {
		$aststat = $this->asteriskProcess();
		if (empty($aststat)) {
			return false;
		}
		// There's an asterisk process. Is it ready?
		$out = `/usr/bin/env asterisk -rx 'core show sysinfo' 2>&1`;
		// This is the same as "return (strpos(...)===false);"
		if (strpos($out, 'System Statistics') === false) {
			return false;
		} else {
			return true;
		}
	}


	private function showHelp() {
		$help = "<info>"._('Usage').": fwconsole start [--pre|--post] [modulename] [modulename...]</info>".PHP_EOL;
		$options = array(
			"--pre" => _("Force run pre-start asterisk hooks"),
			"--post" => _("Force run post-start asterisk hooks"),
		);
		foreach ($options as $o => $t) {
			$help .= "<info>$o</info> : <comment>$t</comment>".PHP_EOL;
		}

		return $help;
	}
}
