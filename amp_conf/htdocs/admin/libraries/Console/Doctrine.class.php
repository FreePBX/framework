<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer;
use Doctrine\DBAL\Schema\Schema;
class Doctrine extends Command {
	private $format = 'php';
	private $database = '';
	protected function configure(){
		$this->setName('doctrine')
		->setDescription('Run a doctrine commands')
		->setDefinition(array(
			new InputOption('database', '', InputOption::VALUE_REQUIRED, _("Database name")),
			new InputOption('format', '', InputOption::VALUE_REQUIRED, sprintf(_('Format can be: %s'),'xml,php')),
			new InputOption('force', '', InputOption::VALUE_NONE, _('Force XML/PHP output even if there is an alter string after generation checks')),
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$args = $input->getArgument('args');
		$dbh = \FreePBX::Database();
		if(empty($args[0])) {
			$output->writeln('<error>You MUST declare a table name!</error>');
			return;
		}
		$table = $args[0];

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

		$connection = $dbh->getDoctrineConnection();
		$synchronizer = new SingleDatabaseSynchronizer($connection);
		$sm = $connection->getSchemaManager();
		$fromSchema = $sm->createSchema();
		$schema = new Schema();

		$diff = Comparator::compareSchemas($schema,$fromSchema);
		if(!isset($diff->newTables[$table])) {
			throw new \Exception("Table does not exist");
		}
		$table = $diff->newTables[$table];

		$export = array();
		foreach($table->getColumns() as $column) {
			$data = $column->toArray();
			$col = array();
			//https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Types/Type.php#L36
			$type = $data['type']->getName();
			switch($type){
				case 'string':
					$col['type'] = 'string';
					$col['length'] = $data['length'];
				break;
				case 'blob':
				case 'integer':
				case 'bigint':
				case 'smallint':
				case 'datetime':
				case 'text':
				case 'boolean':
					$col['type'] = $type;
				break;
				default:
					throw new \Exception("Unknown Col Type: ".$type);
			}
			if(!is_null($data['default'])) {
				$col['default'] = $data['default'];
			}
			if(!$data['notnull']){
				$col['notnull'] = false;
			}
			if($data['unsigned']) {
				$col['unsigned'] = true;
			}
			if($data['autoincrement']) {
				$col['autoincrement'] = true;
			}
			$export[$data['name']] = $col;
		}
		if ($table->hasPrimaryKey()) {
			$pkCols = $table->getPrimaryKey()->getColumns();
			foreach($pkCols as $c) {
				$export[$c]['primarykey'] = true;
			}
		}

		$expindexes = array();
		foreach($table->getIndexes() as $index) {
			if($index->isPrimary()) {
				continue;
			}
			$name = $index->getName();
			$ind = array();

			if($index->isUnique()){
				$ind['type'] = 'unique';
			}else{
				$ind['type'] = 'index';
			}
			$ind['cols'] = array();
			$cols = $index->getColumns();
			foreach($cols as $c) {
				$ind['cols'][] = $c;
			}

			$expindexes[$name] = $ind;
		}

		if(!empty($table->getForeignKeys())) {
			throw new \Exception("Unable to handle foreign keys. Please help write the code!");
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

					if(isset($data['primarykey'])) {
						$c->addAttribute('primarykey', $data['primarykey'] ? 'true' : 'false');
					}

					if(isset($data['autoincrement'])) {
						$c->addAttribute('autoincrement', $data['autoincrement'] ? 'true' : 'false');
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

				$xml = simplexml_load_string($string);
				$test = \FreePBX::Database()->migrateXML($xml->table,true);
				if(!empty($test)) {
					if($input->getOption('force')) {
						$output->writeln("<error>Table did not accurately match generation.</error>");
						$output->writeln("<error>Modification string should be empty but returned: '".implode("; ",$test)."'.</error>");
						$output->writeln("<error>You **MAY** LOSE DATA!!!!</error>");
					} else {
						print_r("Cols");
						print_r($export);
						print_r("Indexes");
						print_r($expindexes);
						throw new \Exception("Error: Table did not accurately match generation. Aborting. Modification string should be empty but returned: '".implode("; ",$test)."'");
					}

				}
				$output->writeln($string);
			break;
			case "php":
				$table = \FreePBX::Database()->migrate($args[0]);
				$test = $table->modify($export, $expindexes, true);
				if(!empty($test)) {
					print_r("Cols");
					print_r($export);
					print_r("Indexes");
					print_r($expindexes);
					throw new \Exception("Error: Table did not accurately match generation. Aborting. Modification string should be empty but returned: '".implode("; ",$test)."'");
				}
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
