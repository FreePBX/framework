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
	public function __construct($conn, $table) {
		$this->conn = $conn;
		$this->table = $table;
	}

	public function modify($columns=array(),$indexes=array()) {
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
			}
		}
		//with true to prevent drops
		//$sql = $synchronizer->getUpdateSchema($schema, true);
		//print_r($sql);
		$synchronizer->updateSchema($schema, true);
	}

	public function drop($tables=array()) {

	}
}
