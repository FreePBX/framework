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
		$helper = $this->getHelper('question');

		$output->write(_("Connecting to the Database..."));
		try {
			$db = \FreePBX::Database();
		} catch(\Exception $e) {
			$output->writeln("<error>"._("Unable to connect to database!")."</error>");
			return;
		}
		$output->writeln(_("Connected"));
		$driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
		$bundles = array();
		while(true) {
			$question = new Question($driver.'>', '');
			$question->setAutocompleterValues($bundles);

			$answer = $helper->ask($input, $output, $question);
			if(preg_match("/^exit/i",$answer)) {
				exit();
			}
			$bundles[] = $answer;

			try {
				$time_start = microtime(true);
				$ob = $db->query($answer);
				$time_end = microtime(true);
			} catch(\Exception $e) {
				$output->writeln("<error>".$e->getMessage()."</error>");
				continue;
			}

			if(!$ob){
				$output->writeln("<error>".$db->errorInfo()."</error>");
				continue;
			}
			//if we get rows back from a query fetch them
			if($ob->rowCount()){
				$gotRows = $ob->fetchAll(\PDO::FETCH_ASSOC);
			} else {
				$gotRows = array();
			}

			if(!empty($gotRows)){
				$rows = array();
				foreach($gotRows as $row){
					$rows[] = array_values($row);
				}

				$table = new Table($output);
				$table
					->setHeaders(array_keys($gotRows[0]))
					->setRows($rows);
				$table->render();
				$output->writeln(sprintf(_("%s rows in set (%s sec)"),$ob->rowCount(), round($time_end - $time_start, 2)));
			} else {
				$output->writeln(_("Successfully executed"));
			}
		}
	}

	private function runSQL() {

	}
}
