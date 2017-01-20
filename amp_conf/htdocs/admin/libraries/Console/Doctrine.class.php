<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Doctrine extends Command {
	private $format = 'php';
	private $database = '';
	protected function configure(){
		$this->setName('doctrine')
		->setDescription('Run a doctrine commands')
		->setDefinition(array(
			new InputOption('database', '', InputOption::VALUE_REQUIRED, _("Database name")),
			new InputOption('format', '', InputOption::VALUE_REQUIRED, sprintf(_('Format can be: %s'),'xml,php')),
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$args = $input->getArgument('args');
		$dbh = \FreePBX::Database();
		if(empty($args[0])) {
			$output->writeln('<error>You MUST declare a table name!</error>');
			return;
		}
		$db = $input->getOption('database');
		if(!empty($db)) {
			$this->database = $db;
		}

		switch($input->getOption('format')) {
			case "xml":
				$this->format = 'xml';
			break;
			case "php":
			default:
			break;
		}

		$q = $dbh->prepare("DESCRIBE ".(!empty($this->database) ? $this->database.".":"").$args[0]);
		$q->execute();
		$table_fields = $q->fetchAll(\PDO::FETCH_ASSOC);
		$export = array();
		foreach($table_fields as $table){
			$export[$table['Field']] = array();
			preg_match('/(?P<type>\w+)($|\((?P<length>(\d+|(.*)))\))\s*(?P<extra>(\w*))/', $table['Type'], $field);
			switch($field['type']){
				case 'char':
				case 'varchar':
					$export[$table['Field']]['type'] = 'string';
					if(isset($field['length'])){
						$export[$table['Field']]['length'] = $field['length'];
					}
				break;
				case 'int':
					$export[$table['Field']]['type'] = 'integer';
				break;
				case 'tinyint':
					$export[$table['Field']]['type'] = 'boolean';
				break;
				case 'text':
				case 'glob':
					$export[$table['Field']]['type'] = 'text';
					if(isset($field['length'])){
						$export[$table['Field']]['length'] = $field['length'];
					}
				break;
				case 'date':
					$export[$table['Field']]['type'] = 'date';
				break;
				case 'datetime/timestamp':
				case 'datetime':
					$export[$table['Field']]['type'] = 'datetime';
				break;
				case 'smallint':
				case 'bigint':
				case 'float':
				case 'blob':
				case 'time':
					$export[$table['Field']]['type'] = $field['type'];
				break;
				case 'decimal':
					$parts = explode(",",$field['length']);
					$export[$table['Field']]['type'] = $field['type'];
					$export[$table['Field']]['precision'] = $parts[0];
					$export[$table['Field']]['scale'] = $parts[1];
				break;
				default:
					throw new \Exception("Unknown Col Type: ".$field['type']);
			}
			if($table['Key'] == "PRI"){
				$export[$table['Field']]['primaryKey'] = true;
			}
			if($table['Null'] != "NO"){
				$export[$table['Field']]['notnull'] = false;
			}

			if(!empty($field['extra'])) {
				if($field['extra'] == 'unsigned') {
					$export[$table['Field']]['unsigned'] = true;
				} else {
					throw new \Exception("Unknown field type");
				}
			}

			if(!in_array($field['type'],array("datetime","datetime/timestamp")) && isset($table['Default']) && !is_null($table['Default'])){
				$export[$table['Field']]['default'] = $table['Default'];
			}
			if($table['Extra'] == 'auto_increment'){
				$export[$table['Field']]['autoincrement'] = true;
			}
		}

		$q = $dbh->prepare("SHOW INDEX FROM ".(!empty($this->database) ? $this->database.".":"").$args[0]);
		$q->execute();
		$table_indexes = $q->fetchAll(\PDO::FETCH_ASSOC);
		$expindexes = array();
		foreach($table_indexes as $idx){
			if($idx['Key_name'] == 'PRIMARY') {
				continue;
			}
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

		$table = \FreePBX::Database()->migrate($args[0]);
		$test = $table->modify($export, $expindexes, true);
		if(!empty($test)) {
			print_r("Cols");
			print_r($export);
			print_r("Indexes");
			print_r($expindexes);
			throw new \Exception("Error: Table did not accurately match generation. Aborting. Modification string should be empty but returned: '".implode("; ",$test)."'");
		}

		switch($this->format) {
			case "xml":
				$xml = new \SimpleXMLElement('<database/>');
				if(!empty($this->database)) {
					$xml->addAttribute('name', $this->database);
				}
				$table = $xml->addChild("table");
				$table->addAttribute('name', $args[0]);
				foreach($export as $col => $data) {
					$c = $table->addChild("field");
					$c->addAttribute('name', $col);
					$c->addAttribute('type', $data['type']);

					if(isset($data['length'])) {
						$c->addAttribute('length', $data['length']);
					}

					if(isset($data['default'])) {
						$c->addAttribute('default', $data['default']);
					}

					if(isset($data['unsigned'])) {
						$c->addAttribute('unsigned', $data['unsigned'] ? 'true' : 'false');
					}

					if(isset($data['notnull'])) {
						$c->addAttribute('notnull', $data['notnull'] ? 'true' : 'false');
					}

					if(isset($data['primaryKey'])) {
						$c->addAttribute('primaryKey', $data['primaryKey'] ? 'true' : 'false');
					}

					if(isset($data['autoincrement'])) {
						$c->addAttribute('primaryKey', $data['autoincrement'] ? 'true' : 'false');
					}
				}
				foreach($expindexes as $index => $data) {
					$i = $table->addChild("key");
					$i->addAttribute('name', $index);
					$i->addAttribute('type', $data['type']);
					foreach($data['cols'] as $col) {
						$c = $i->addChild("column");
						$c->addAttribute('name', $col);
					}
				}
				$domxml = new \DOMDocument('1.0');
				$domxml->preserveWhiteSpace = false;
				$domxml->formatOutput = true;
				$domxml->loadXML($xml->asXML());

				$string = $domxml->saveXML();
				$string = str_replace('<?xml version="1.0"?>','',$string);
				$string = trim($string);
				$output->writeln($string);
			break;
			case "php":
				$output->writeln('$table = \FreePBX::Database()->migrate("'.$args[0].'");');
				$output->writeln('$cols = '.var_export($export,true).';');
				$output->writeln(PHP_EOL);

				$output->writeln('$indexes = '.var_export($expindexes,true).';');
				$output->writeln('$table->modify($cols, $indexes);');
				$output->writeln('unset($table);');
			break;
		}



	}
}
