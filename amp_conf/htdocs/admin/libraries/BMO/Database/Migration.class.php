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
use Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
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
		$synchronizer = new SingleDatabaseSynchronizer($this->conn);
		$schemaConfig = new SchemaConfig();
		if($this->driver == "pdo_mysql" && version_compare($this->version, "5.5.3", "ge")) {
			$schemaConfig->setDefaultTableOptions(array(
				"collate"=>"utf8mb4_unicode_ci",
				"charset"=>"utf8mb4"
			));
		}
		$schema = new Schema(array(),array(),$schemaConfig);
		$table = $schema->createTable($this->table);
		$primaryKeys = array();
		foreach($columns as $name => $options) {
			$type = $options['type'];
			unset($options['type']);
			$pk = isset($options['primaryKey']) ? $options['primaryKey'] : (isset($options['primarykey']) ? $options['primarykey'] : null);
			if(!is_null($pk)) {
				if($pk) {
					$primaryKeys[] = $name;
				}
			}
			$table->addColumn($name, $type, $options);
		}
		if(!empty($primaryKeys)) {
			$table->setPrimaryKey($primaryKeys);
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
				case "foreign":
					$table->addForeignKeyConstraint($data['foreigntable'], $columns, $data['foreigncols'], $data['options'], $name);
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
