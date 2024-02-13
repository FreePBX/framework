<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[\AllowDynamicProperties]
class Context extends Command {
	private $FreePBXConf = null;
	protected function configure(){
		$this->FreePBXConf = \FreePBX::Config();
		$this->setName('context')
		->setAliases(array('cx'))
		->setDescription(_('Shows the specified context from the dialplan'))
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, '', null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$args = $input->getArgument('args');
		$FreePBX = \FreePBX::Create();
		$astMan = $FreePBX->astman;
		$i = 0;
		if($astMan->connected()){
			$astMan->Command('dialplan reload');
			$res = $astMan->Command('dialplan show ' . $args[0]);
			$lines = explode("\n",$res['data']);
			$output->writeln('Context ' . $args[0] . ': ');
			foreach($lines as $line){
				if(strpos($line, '=>')){
					$i++;
					$output->writeln($line);
				}
			}
			if($i < 1){
				$output->writeln(_('May be invalid Check your spelling'));
			}
		}
	}
}
