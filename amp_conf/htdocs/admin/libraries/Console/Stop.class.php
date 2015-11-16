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

class Stop extends Command {
	protected function configure(){
		$this->setName('stop')
			->setDescription(_('Stop Asterisk and run other needed FreePBX commands'))
			->setDefinition(array(
				new InputOption('immediate', 'i', InputOption::VALUE_NONE, _('Shutdown NOW rather than convieniently')),
				new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		if(posix_geteuid() != 0) {
			$output->writeln("<error>"._("You need to be root to run this command")."</error>");
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
					if(!$asteriskrunning) {
						$output->writeln("<error>"._('This service must be stopped before Asterisk has stopped')."</error>");
						break;
					}
					$bmo->$data['module']->$data['method']($output);
					break;
				}
			}
			foreach($post as $pri => $data) {
				if(strtolower($data['module']) == $args[0]) {
					$found = true;
					if($asteriskrunning) {
						$output->writeln("<error>"._('This service must be stopped after Asterisk has stopped')."</error>");
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
			$output->writeln(sprintf(_('Running %s shutdown...'),$brand));
			$output->writeln('');
			$output->writeln(_('Checking Asterisk Status...'));
			$aststat = $this->asteriskProcess();
			if(!$aststat[0]){
				$output->writeln(_('Asterisk Seems to be NOT running'));
				$output->writeln('<info>'._('Not running Pre-Asterisk Shutdown Hooks.').'</info>');
			}else{
				$output->writeln(_('Run Pre-Asterisk Shutdown Hooks'));
				$this->preAsteriskHooks($output);
				if($input->getOption('immediate')){
					$output->writeln(_('Shutting down NOW...'));
					$this->stopAsterisk($output, now);
					$aststat = $this->asteriskProcess();
					sleep(2);
					while($i++ < 3){
						if($aststat[0]){
							$output->writeln(_('The immediate shutdown did not go as planned... Trying again...'));
							$this->stopAsterisk($output, now);
							sleep(2);
						}
					}
				}else{
					$output->writeln('');
					$output->writeln(_('Shutting down Asterisk Gracefully...'));
					$output->writeln(sprintf(_('Press %s to Cancel'),'C'));
					$output->writeln(sprintf(_('Press %s to shut down NOW'),'N'));
					$progress = new ProgressBar($output, 120);
					$this->stopAsterisk($output, 'gracefully');
					$progress->start();
					$i = 0;
					$stdin = fopen('php://stdin', 'r');
					stream_set_blocking ($stdin,0);
					exec('stty -g', $term);
					system("stty -icanon");
					while ($stdin) {
						$res = fgetc ($stdin);
						echo $res;
						$aststat = $this->asteriskProcess();
						if(!$aststat[0]){
							$progress->finish();
							$output->writeln('');
							$output->writeln(_('Asterisk Stopped Successfuly'));
							$userov = True;
							break;
						}

						if (strtolower($res) == 'c'){
							$progress->finish();
							$output->writeln('');
							$this->abortShutdown($output);
							fclose($stdin);
							$userov = True;
							break;
						}
						if (strtolower($res) == 'n'){
							$progress->finish();
							$output->writeln('');
							$this->stopAsterisk($output, 'now');
							fclose($stdin);
							$userov = True;
							break;
						}
						sleep(1);
						$progress->advance(1);
						$i++;
						if($i == 120){
							fclose($stdin);
							break;
						}
					}
					system("stty '" . $term[0] . "'");
					if(!$userov){
						$output->writeln(_('Grace Period timed out'));
						$this->stopAsterisk($output, 'now');
					}
				}
				$aststat = $this->asteriskProcess();
				sleep(1);
				if(!$aststat[0]){
					$output->writeln('');
					$output->writeln(_('Running Post-Asterisk Stop Scripts'));
					$this->postAsteriskHooks($output);
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
	private function stopAsterisk($output, $method){
		$output->writeln(_('Stopping Asterisk...'));
		$sastbin = '/usr/bin/env killall safe_asterisk > /dev/null 2>&1';
		$astbin = '/usr/bin/env asterisk -rx "core stop ' . $method .'"';
		exec($sastbin);
		exec($astbin);
	}
	private function abortShutdown($output){
		$freepbx = \FreePBX::Create();
		$astman = $freepbx->astman;
		if (is_object($astman) && $astman->Connected()) {
			$output->writeln(_('Aborting Shutdown'));
			$astman->send_request('Command',array('Command'=>'core abort shutdown'));
		}
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
