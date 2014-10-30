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
			->setDescription('Start Asterisk and run other needed FreePBX commands')
			->setDefinition(array(
				new InputOption('immediate', 'i', InputOption::VALUE_NONE, 'Shutdown NOW rather than convieniently'),
				new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		$output->writeln('Running FreePBX shutdown...');
		$output->writeln('');
		$output->writeln('Checking Asterisk Status...');
		$aststat = $this->asteriskProcess();
		if(!$aststat[0]){
			$output->writeln('Asterisk Seems to be NOT running</info>');
			$output->writeln('<info>Not running Pre-Asterisk Shutdown Hooks.</info>');
		}else{
			$output->writeln('Run Pre-Asterisk Shutdown Hooks');
			$this->preAsteriskHooks($output);
			if($input->getOption('immediate')){
				$output->writeln('Shutting down NOW...');
				$this->stopAsterisk($output, now);
				$aststat = $this->asteriskProcess();
				sleep(2);
				while($i++ < 3){
					if($aststat[0]){
						$output->writeln('The immediate shutdown did not go as planned... Trying again...');
						$this->stopAsterisk($output, now);
						sleep(2);
					}
				}
			}else{
				$output->writeln('');
				$output->writeln('Shutting down Asterisk Gracefully...');
				$output->writeln('Press C to Cancel');
				$output->writeln('Press N to shut down NOW');
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
						$output->writeln('Asterisk Stopped Successfuly');
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
					$output->writeln('Grace timed out');
					$this->stopAsterisk($output, 'now');
				}
			}
			$aststat = $this->asteriskProcess();
			sleep(1);
			if(!$aststat[0]){
				$output->writeln('');
				$output->writeln('Running Post-Asterisk Stop Scripts');
				$this->postAsteriskHooks($output);
			}
		}
	}
	private function asteriskProcess(){
		$ps = '/bin/env ps';
		$cmd = $ps . " -C asterisk --no-headers -o '%p|%t'";
		$stat = exec($cmd);
		return explode('|',$stat);
	}
	private function stopAsterisk($output, $method){
		$output->writeln('Stopping Asterisk...');
		$sastbin = '/bin/env killall safe_asterisk > /dev/null 2>&1';
		$astbin = '/bin/env asterisk -rx "core stop ' . $method .'"';
		exec($sastbin);
		exec($astbin);
	}
	private function abortShutdown($output){
		$freepbx = \FreePBX::Create();
		$astman = $freepbx->astman;
		if (is_object($astman) && $astman->Connected()) {
			$output->writeln('Aborting Shutdown');
			$astman->send_request('Command',array('Command'=>'core abort shutdown'));
		}
	}
	private function preAsteriskHooks($output){
		\FreePBX::Hooks()->processHooks($output);
		return;
	}
	private function postAsteriskHooks($output){
		\FreePBX::Hooks()->processHooks($output);
		return;
	}
}
