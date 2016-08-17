<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Doctrine extends Command {
	protected function configure(){
		$this->setName('doctrine')
		->setDescription('Run a doctrine commands')
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$args = $input->getArgument('args');
		$dbh = \FreePBX::Database();
		$q = $dbh->prepare("DESCRIBE ".$args[0]);
		$q->execute();
		$table_fields = $q->fetchAll(\PDO::FETCH_ASSOC);
		$export = array();
		foreach($table_fields as $table){
		  $export[$table['Field']] = array();
		  preg_match('/(?P<type>\w+)($|\((?P<length>(\d+|(.*)))\))/', $table['Type'], $field);
		  switch($field['type']){
		    case 'varchar':
		    $export[$table['Field']]['type'] = 'string';
		    if(isset($field['length'])){
		      $export[$table['Field']]['length'] = $field['length'];
		    }
		    break;
		    case 'int':
		    $export[$table['Field']]['type'] = 'integer';
		    if(isset($field['length'])){
		      $export[$table['Field']]['length'] = $field['length'];
		    }
		    break;
		    case 'tinyint':
		    $export[$table['Field']]['type'] = 'boolean';
		    if(isset($field['length'])){
		      $export[$table['Field']]['length'] = $field['length'];
		    }
		    break;
		    case 'glob':
		    $export[$table['Field']]['type'] = 'text';
		    if(isset($field['length'])){
		      $export[$table['Field']]['length'] = $field['length'];
		    }
		    break;
		    case 'datetime':
		    $export[$table['Field']]['type'] = 'date';
		    if(isset($field['length'])){
		      $export[$table['Field']]['length'] = $field['length'];
		    }
		    break;
		    case 'datetime/timestamp':
		    $export[$table['Field']]['type'] = 'datetime';
		    if(isset($field['length'])){
		      $export[$table['Field']]['length'] = $field['length'];
		    }
		    break;
		    case 'smallint':
		    case 'bigint':
		    case 'decimal':
		    case 'float':
		    case 'blob':
		    case 'time':
		    $export[$table['Field']]['type'] = $field['type'];
		    if(isset($field['length'])){
		      $export[$table['Field']]['length'] = $field['length'];
		    }
		    break;
		  }
		  if($table['Type'] == "PRI"){
		    $export[$table['Field']]['primaryKey'] = true;
		  }
		  if($table['Null'] != "NO"){
		    $export[$table['Field']]['notnull'] = false;
		  }
		  if(!empty($table['Default'])){
		    $export[$table['Field']]['default'] = $table['Default'];
		  }
		  if($table['Extra'] == 'auto_increment'){
		    $export[$table['Field']]['autoincrement'] = true;
		  }
		}
		$output->writeln(var_export($export));
		$output->writeln(PHP_EOL);
		$q = $dbh->prepare("SHOW INDEX FROM ".$args[0]);
		$q->execute();
		$table_indexes = $q->fetchAll(\PDO::FETCH_ASSOC);
		$expindexes = array();
		foreach($table_indexes as $idx){
		  if($idx['Non_unique'] === 1){
		    $expindexes[$idx['Key_name']]['type'] = 'index';
		  }else{
		    $expindexes[$idx['Key_name']]['type'] = 'unique';
		  }
		  if(!isset($expindexes[$idx['Key_name']]['cols'])){
		    $expindexes[$idx['Key_name']]['cols'] = array();
		  }
		  $expindexes[$idx['Key_name']]['cols'][] = $idx['Column_name'];
		}
		$output->writeln(var_export($expindexes));
		$output->writeln(PHP_EOL);
	}
}
