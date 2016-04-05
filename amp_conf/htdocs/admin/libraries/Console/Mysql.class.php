<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\Question;

class Mysql extends Command {
	protected function configure(){
		$this->setName('m')
		->setAliases(array('mysql'))
		->setDescription('Run a mysql Query:')
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		global $amp_conf; //an angel lost it's wings today for using this
		$dbuser = $amp_conf["AMPDBUSER"];
		$dbpass = $amp_conf["AMPDBPASS"];
		$dbhost = $amp_conf["AMPDBHOST"];
		$dbname = $amp_conf["AMPDBNAME"];
		$dbtype = $amp_conf["AMPDBENGINE"];
		if($dbtype != "mysql") {
			$output->writeln("<error>"._("Only mysql is supported")."</error>");
			exit(1);
		}
		$pipes = array();
		$descriptorspec = array(
			0 => STDIN,
			1 => STDOUT,
			2 => STDERR
		);
		$output->write(_("Connecting to the Database..."));
		$process = proc_open('mysql -u'.$dbuser.' -p'.$dbpass.' -h'.$dbhost.' '.$dbname, $descriptorspec, $pipes);
		return;
		$helper = $this->getHelper('question');
	}
}
