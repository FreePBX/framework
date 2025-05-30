<?php
/**
 * This is the FreePBX Big Module Object.
 *
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */

namespace FreePBX\Database;

use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Exception;
use FreePBX\Database\DBAL\SingleDatabaseSynchronizer;

class Migration
{
    private $conn;
    private $table;

    public function __construct($conn)
    {
        $this->conn = $conn;
        // http://wildlyinaccurate.com/doctrine-2-resolving-unknown-database-type-enum-requested/
        $this->conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function setTable($table)
    {
        $this->table = trim($table);
    }

    /**
     * Generate Update Array used to create or update tables
     *
     * @return array Array of table
     */
    public function generateUpdateArray()
    {
        if (empty($this->table)) {
            throw new Exception('Table not set!');
        }
        $sm = $this->conn->getSchemaManager();
        $fromSchema = $sm->createSchema();
        $schema = new Schema();

        $diff = Comparator::compareSchemas($schema,$fromSchema);
        if (!isset($diff->newTables[$this->table])) {
            throw new Exception('Table does not exist');
        }
        $columns = $sm->listTableColumns($this->table);
        $foreignKeys = $sm->listTableForeignKeys($this->table);
        $indexes = $sm->listTableIndexes($this->table);

        $export = [];
        $expindexes = [];
        foreach ($columns as $column) {
            $type = $column->getType()->getName();
            $name = $column->getName();
            switch ($type) {
                case 'string':
                    $export[$name]['type'] = $type;
                    $export[$name]['length'] = $column->getLength();
                    break;
                case 'blob':
                case 'integer':
                case 'bigint':
                case 'smallint':
                case 'date':
                case 'datetime':
                case 'text':
                case 'boolean':
                    $export[$name]['type'] = $type;
                    break;
                case 'float':
                case 'decimal':
                    $export[$name]['type'] = $type;
                    $export[$name]['precision'] = $column->getPrecision();
                    $export[$name]['scale'] = $column->getScale();
                    break;
                default:
                    throw new Exception('Unknown type: ' . $type);
            }
            if (!$column->getNotnull()) {
                $export[$name]['notnull'] = $column->getNotnull();
            }
            if ($column->getAutoincrement()) {
                $export[$name]['autoincrement'] = $column->getAutoincrement();
            }
            if ($column->getUnsigned()) {
                $export[$name]['unsigned'] = $column->getUnsigned();
            }
            $default = $column->getDefault();
            if (!in_array($type, ['datetime', 'datetime/timestamp']) && !is_null($default)) {
                $export[$name]['default'] = $default;
            }
        }
        foreach ($indexes as $index) {
            $name = $index->getName();
            if($index->isPrimary()) {
                foreach ($index->getColumns() as $col) {
                    $export[$col]['primarykey'] = true;
                }
                continue;
            } elseif ($index->isUnique()) {
                $expindexes[$name]['type'] = 'unique';
            } else {
                $expindexes[$name]['type'] = 'index';
            }
            $expindexes[$name]['cols'] = $index->getColumns();
        }

        if (!empty($foreignKeys)) {
            throw new Exception('There are foreign keys here. Cant accurately generate tables');
        }

        return ['columns' => $export, 'indexes' => $expindexes];
    }

    /**
     * Modify Multiple Tables
     *
     * @param  array  $tables  The tables to update
     * @param  bool   $dryrun  If set to true dont execute just return the sql modification string
     * @return mixed
     */
    public function modifyMultiple($tables = [], $dryrun = false)
    {
        $synchronizer = new SingleDatabaseSynchronizer($this->conn);
        $schemaConfig = new SchemaConfig();
        $schemaConfig->setDefaultTableOptions([
            'collate' => 'utf8mb4_unicode_ci',
            'charset' => 'utf8mb4',
        ]);
        $schema = new Schema([], [], $schemaConfig);
        foreach ($tables as $tname => $tdata) {
            $table = $schema->createTable($tname);
            $primaryKeys = [];
            foreach ($tdata['columns'] as $name => $options) {
                $type = $options['type'];
                unset($options['type']);
                if ($options['primaryKey'] ?? $options['primarykey'] ?? null) {
                    $primaryKeys[] = $name;
                    unset($options['primaryKey'], $options['primarykey']);
                }
                $table->addColumn($name, $type, $options);
            }
            if (!empty($primaryKeys)) {
                $table->setPrimaryKey($primaryKeys);
            }
            foreach ($tdata['indexes'] ?? [] as $name => $data) {
                $type = $data['type'];
                $columns = $data['cols'];
                switch ($type) {
                    case 'unique':
                        $table->addUniqueIndex($columns, $name);
                        break;
                    case 'index':
                        $table->addIndex($columns, $name);
                        break;
                    case 'fulltext':
                        $table->addIndex($columns, $name, ['fulltext']);
                        break;
                    case 'foreign':
                        $opts = [
                            'onUpdate' => $data['onUpdate'] ?? $data['onupdate'] ?? null,
                            'onDelete' => $data['onDelete'] ?? $data['ondelete'] ?? null,
                        ];
                        $opts = array_filter($opts);
                        $foreigncols = array_map('trim', explode(',', $data['foreigncols']));
                        $table->addForeignKeyConstraint($data['foreigntable'], $columns, $foreigncols, $opts, $name);
                        break;
                }
            }
        }
        // with true to prevent drops
        if ($dryrun) {
            return $synchronizer->getUpdateSchema($schema, true);
        } else {
            return $synchronizer->updateSchema($schema, true);
        }
    }

    /**
     * Modify Single Table
     *
     * @param  array  $columns Columns to update
     * @param  array  $indexes Indexes to update
     * @param  bool   $dryrun  If set to true dont execute just return the sql modification string
     * @return mixed
     */
    public function modify($columns = [], $indexes = [], $dryrun = false)
    {
        if (empty($this->table)) {
            throw new Exception('Table not set!');
        }
        $table = $this->table;
        return $this->modifyMultiple([
            $table => [
                'columns' => $columns,
                'indexes' => $indexes,
            ]
        ], $dryrun);
    }
}
