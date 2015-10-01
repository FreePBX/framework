<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Unlock extends Command {
	protected function configure(){
		$this->FreePBXConf = \FreePBX::Config();
		$this->setName('unlock')
		->setDescription(_('Unlock Session'))
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$FreePBX = \FreePBX::Create();
		$args = $input->getArgument('args');
		session_id($args[0]);
		session_start();
		$output->writeln(sprintf(_('Unlocking: %s'),$args[0]));
		if (!isset($_SESSION["AMP_user"])) {
			$_SESSION["AMP_user"] = new \ampuser('fwconsole');
			$_SESSION["AMP_user"]->setAdmin();
			$output->writeln(_('Session Should be unlocked now'));
		}
	}
}
