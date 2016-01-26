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
			->setDefinition(array(
				new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		if(posix_geteuid() != 0) {
			$output->writeln("<error>You need to be root to run this command</error>");
			exit(1);
		}
		$args = $input->getArgument('args');
		if(!empty($args)) {
			$pre = $this->preAsteriskHooks($output,false);
			$post = $this->postAsteriskHooks($output,false);
			$aststat = $this->asteriskProcess();
			$asteriskrunning = ($aststat[0]);
			$bmo = \FreePBX::create();
			$found = false;
			foreach($pre as $pri => $data) {
				if(strtolower($data['module']) == $args[0]) {
					$found = true;
					if($asteriskrunning) {
						$output->writeln("<error>"._('This service must be started before Asterisk has started')."</error>");
						break;
					}
					$bmo->$data['module']->$data['method']($output);
					break;
				}
			}
			foreach($post as $pri => $data) {
				if(strtolower($data['module']) == $args[0]) {
					$found = true;
					if(!$asteriskrunning) {
						$output->writeln("<error>"._('This service must be started after Asterisk has started')."</error>");
						break;
					}
					$bmo->$data['module']->$data['method']($output);
					break;
				}
			}
			if(!$found) {
				$output->writeln("<error>"._('Unable to find service to start')."</error>");
			}
		} else {
			$brand = \FreePBX::Config()->get("DASHBOARD_FREEPBX_BRAND");
			$output->writeln(sprintf(_('Running %s startup...'),$brand));
			$chown = new Chown();
			$chown->execute($input, $output);
			$output->writeln('');
			$output->writeln(_('Checking Asterisk Status...'));
			$aststat = $this->asteriskProcess();
			if($aststat[0]){
				$output->writeln(sprintf(_('Asterisk Seems to be running on PID %s and has been running for %s'),$aststat[0], trim($aststat[1])));
				$output->writeln('<info>'._('Not running Pre-Asterisk Hooks.').'</info>');
			}else{
				$output->writeln(_('Run Pre-Asterisk Hooks'));
				$this->preAsteriskHooks($output);
				$output->writeln('');
				$this->startAsterisk($output);
				$progress = new ProgressBar($output, 100);
				$progress->start();
				$i = 0;
				while ($i++ < 3) {
					$progress->advance(33);
					sleep(1);
				}
				$aststat = $this->asteriskProcess();
				if($aststat[0]){
					$progress->finish();
					$output->writeln('');
					$output->writeln(sprintf(_('Asterisk Started on %s'),$aststat[0]));
					$output->writeln('');
					$output->writeln(_('Running Post-Asterisk Scripts'));
					$this->postAsteriskHooks($output);
				} else {
					$progress->finish();
					$output->writeln('<error>'._("Asterisk Failed to Start").'</error>');
				}
			}
		}
	}
	private function asteriskProcess(){
		$ps = '/usr/bin/env ps';
		$cmd = $ps . " -C asterisk --no-headers -o '%p|%t'";
		$stat = exec($cmd);
		return explode('|',$stat);
	}
	private function startAsterisk($output){
		$output->writeln(_('Starting Asterisk...'));
		$astbin = '/usr/bin/env safe_asterisk -U '.\FreePBX::Config()->get('AMPASTERISKUSER').' -G '.\FreePBX::Config()->get('AMPASTERISKGROUP').' > /dev/null 2>&1 &';
		exec($astbin);
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
}
