<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class Mysql extends Command {
	protected function configure(){
		$this->setName('m')
		->setAliases(array('mysql'))
		->setDescription('Run a mysql Query:')
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$db = \FreePBX::Database();
		$arg = $input->getArgument('args');
		if ($arg) {
			$query = explode(' ',trim($arg[0]));
			$verb  = strtoupper($query[0]);
			switch($verb){
				case 'INSERT':
				case 'DROP':
				case 'UPDATE':
					$sql = $arg[0];
					$ob = $db->query($sql,\PDO::FETCH_ASSOC);
					if(!$ob){
						$output->writeln($db->errorInfo());
					}
					if($ob->rowCount()){
						$output.writeln('<info>' . $ob->rowCount() . '</info> Rows affected');
					}
					break;
				case 'SELECT':
					$sql = $arg[0];
					$ob = $db->query($sql,\PDO::FETCH_ASSOC);
					if(!$ob){
						$output->writeln($db->errorInfo());
					}
					//if we get rows back from a query fetch them
					if($ob->rowCount()){
						$gotRows = $ob->fetchAll();
					}

					//handle results if we got rows
					if($gotRows){
						$rows = array();
						foreach($gotRows as $row){
							array_push($rows, array_values($row));
						}
						$table = new Table($output);
						$table
							->setHeaders(array_keys($res[0]))
							->setRows($rows);
						$table->render();
					}
					break;
				case 'SHOW':
					$rows = array();
					$sql = $arg[0];
					$result = $db->query($sql);
					$table = new Table($output);
					while ($row = $result->fetch(\PDO::FETCH_NUM)) {
						$rows[] = array($row[0]);
					}
					$table
						->setHeaders(array($query[1]))
						->setRows($rows);
					$table->render();
					break;
				case 'BUILTIN':
					return true;
					break;
				default:
					$output->writeln("I didn't understand the verb provided");
					break;
			}
		} else {
			$output->writeln("I didn't understand the verb provided");
		}
	}
}
