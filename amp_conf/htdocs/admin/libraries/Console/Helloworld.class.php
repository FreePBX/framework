<?php
//Namespace should be FreePBX\Console\Command
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//Start class. Class name should be same as file name. Classname.class.php
class Helloworld extends Command {
	//Declare component and your options.
	protected function configure(){
		$this->setName('helloworld')
		->setDescription('This says hello to the world')
		->setDefinition(array(
			new InputOption('flag', 'f', InputOption::VALUE_NONE, 'We are setting a flag'),
			new InputArgument('args', InputArgument::IS_ARRAY, '[flag|f] arrrrrrgs', null),)
			)
		->setHelp('This is a magical help section...');
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$arg = $input->getArgument('args');
		if ($input->getOption('flag')) {
			$text = "Flag Set.";
		} else {
			$text = "No Flag Set";
		}
		$output->writeln($text);
		if($arg){ print_r($arg);}
	}
}
