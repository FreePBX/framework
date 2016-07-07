<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX\Database;
class Migration {
	private $conn;
	private $table;
	private $version;
	private $driver;

	public function __construct($conn, $table, $driver, $version) {
		$this->conn = $conn;
		$this->table = $table;
		$this->version = $version;
		$this->driver = $driver;
	}

	public function modify($columns=array(),$indexes=array(),$dryrun=false) {
		$synchronizer = new \Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer($this->conn);
		$schema = new \Doctrine\DBAL\Schema\Schema();
		$table = $schema->createTable($this->table);
		$primaryKey = '';
		foreach($columns as $name => $options) {
			$type = $options['type'];
			unset($options['type']);
			if(isset($options['primaryKey'])) {
				if($options['primaryKey']) {
					if(!empty($primaryKey)) {
						throw new \Exception(_("Multiple Primary Keys defined"));
					}
					$primaryKey = $name;
				}
				unset($options['primaryKey']);
			}
			$table->addColumn($name, $type, $options);
		}
		if(!empty($primaryKey)) {
			$table->setPrimaryKey(array($primaryKey));
		}
		foreach($indexes as $name => $data) {
			$type = $data['type'];
			$columns = $data['cols'];
			switch($type) {
				case "unique":
					$table->addUniqueIndex($columns,$name);
				break;
				case "index":
					$table->addIndex($columns,$name);
				break;
				case "fulltext":
					if($this->driver == "pdo_mysql" && version_compare($this->version, "5.6", "le")) {
						$table->addOption('engine' , 'MyISAM');
					}
					$table->addIndex($columns,$name,array("fulltext"));
				break;
			}
		}

		//with true to prevent drops
		if($dryrun) {
			return $synchronizer->getUpdateSchema($schema, true);
		} else {
			return $synchronizer->updateSchema($schema, true);
		}
	}

	public function drop($tables=array()) {

	}
}
