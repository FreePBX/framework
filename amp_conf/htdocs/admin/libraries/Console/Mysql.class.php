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

use Symfony\Component\Process\Process;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Exception\ProcessFailedException;
class Mysql extends Command {
	protected function configure(){
		$this->setName('mysql')
		->setAliases(array('m'))
		->setDescription('Run a mysql Query:')
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		global $amp_conf; //an angel lost it's wings today for using this
		$dbuser = $amp_conf["AMPDBUSER"];
		$dbpass = $amp_conf["AMPDBPASS"];
		$dbhost = $amp_conf["AMPDBHOST"];
		$dbport = 3306;
		$dbname = $amp_conf["AMPDBNAME"];
		$dbtype = $amp_conf["AMPDBENGINE"];
		$dbsock = $amp_conf['AMPDBSOCK'];

		if($dbtype != "mysql") {
			$output->writeln("<error>"._("Only mysql is supported")."</error>");
			exit(1);
		}

		//read config and validate port number
		if (isset($amp_conf["AMPDBPORT"]) && !empty($amp_conf["AMPDBPORT"])) {
			if ((int) $amp_conf["AMPDBPORT"] > 1024) {
				$dbport = (int) $amp_conf["AMPDBPORT"];
			}
		}

		//conexion type socket or host:port
		if (empty($dbsock)) {
			$dbconnect = '-h'.$dbhost.' -P'.$dbport;
		} else {
			$dbconnect = '-S'.$dbsock;
		}

		if(posix_isatty(STDIN)) {
			$process = new Process('mysql -u'.$dbuser.' -p'.$dbpass.' '.$dbconnect.' '.$dbname);
			$process->setTty(true);
		} else {
			$process = new Process('mysql -u'.$dbuser.' -p'.$dbpass.' '.$dbconnect.' -e '.escapeshellarg(fgets(STDIN)).' '.$dbname);
		}

		$process->mustRun();
		if(!posix_isatty(STDIN)) {
			echo $process->getOutput();
		}
	}
}
