<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use OC\DB\SchemaWrapper;
use OCP\IConfig;

/**
 * Class MigratorTest
 *
 * @group DB
 *
 * @package Test\DB
 */
class MigratorTest extends \Test\TestCase {
	/**
	 * @var \Doctrine\DBAL\Connection $connection
	 */
	private $connection;

	/**
	 * @var \OC\DB\MDB2SchemaManager
	 */
	private $manager;

	/**
	 * @var IConfig
	 **/
	private $config;

	/** @var string */
	private $tableName;

	/** @var string */
	private $tableNameTmp;

	protected function setUp(): void {
		parent::setUp();

		$this->config = \OC::$server->getConfig();
		$this->connection = \OC::$server->get(\OC\DB\Connection::class);
		if ($this->connection->getDatabasePlatform() instanceof OraclePlatform) {
			$this->markTestSkipped('DB migration tests are not supported on OCI');
		}
		$this->manager = new \OC\DB\MDB2SchemaManager($this->connection);
		$this->tableName = $this->getUniqueTableName();
		$this->tableNameTmp = $this->getUniqueTableName();
	}

	private function getUniqueTableName() {
		return strtolower($this->getUniqueID($this->config->getSystemValue('dbtableprefix', 'oc_') . 'test_'));
	}

	protected function tearDown(): void {
		// Try to delete if exists (IF EXISTS NOT SUPPORTED IN ORACLE)
		try {
			$this->connection->exec('DROP TABLE ' . $this->connection->quoteIdentifier($this->tableNameTmp));
		} catch (Exception $e) {
		}

		try {
			$this->connection->exec('DROP TABLE ' . $this->connection->quoteIdentifier($this->tableName));
		} catch (Exception $e) {
		}
		parent::tearDown();
	}

	/**
	 * @return \Doctrine\DBAL\Schema\Schema[]
	 */
	private function getDuplicateKeySchemas() {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');
		$table->addIndex(['id'], $this->tableName . '_id');

		$endSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');
		$table->addUniqueIndex(['id'], $this->tableName . '_id');

		return [$startSchema, $endSchema];
	}

	/**
	 * @return \Doctrine\DBAL\Schema\Schema[]
	 */
	private function getChangedTypeSchema($from, $to) {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', $from);
		$table->addColumn('name', 'string');
		$table->addIndex(['id'], $this->tableName . '_id');

		$endSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', $to);
		$table->addColumn('name', 'string');
		$table->addIndex(['id'], $this->tableName . '_id');

		return [$startSchema, $endSchema];
	}


	private function getSchemaConfig() {
		$config = new SchemaConfig();
		$config->setName($this->connection->getDatabase());
		return $config;
	}

	private function isSQLite() {
		return $this->connection->getDatabasePlatform() instanceof SqlitePlatform;
	}

	private function isMySQL() {
		return $this->connection->getDatabasePlatform() instanceof MySQLPlatform;
	}

	public function testChangeToString() {
		list($startSchema, $endSchema) = $this->getChangedTypeSchema('integer', 'string');
		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);
		$schema = new SchemaWrapper($this->connection);
		$table = $schema->getTable(substr($this->tableName, 3));
		$this->assertEquals('integer', $table->getColumn('id')->getType()->getName());

		$this->connection->insert($this->tableName, ['id' => 1, 'name' => 'foo']);
		$this->connection->insert($this->tableName, ['id' => 2, 'name' => 'bar']);
		$this->connection->insert($this->tableName, ['id' => 3, 'name' => 'qwerty']);

		$migrator->checkMigrate($endSchema);
		$migrator->migrate($endSchema);
		$this->addToAssertionCount(1);

		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('*')->from(substr($this->tableName, 3))->execute();
		$this->assertEquals([
			['id' => 1, 'name' => 'foo'],
			['id' => 2, 'name' => 'bar'],
			['id' => 3, 'name' => 'qwerty']
		], $result->fetchAll());
		$schema = new SchemaWrapper($this->connection);
		$table = $schema->getTable(substr($this->tableName, 3));
		$this->assertEquals('string', $table->getColumn('id')->getType()->getName());
	}

	public function testUpgrade() {
		[$startSchema, $endSchema] = $this->getDuplicateKeySchemas();
		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$this->connection->insert($this->tableName, ['id' => 1, 'name' => 'foo']);
		$this->connection->insert($this->tableName, ['id' => 2, 'name' => 'bar']);
		$this->connection->insert($this->tableName, ['id' => 3, 'name' => 'qwerty']);

		$migrator->migrate($endSchema);
		$this->addToAssertionCount(1);
	}

	public function testUpgradeDifferentPrefix() {
		$oldTablePrefix = $this->config->getSystemValue('dbtableprefix', 'oc_');

		$this->config->setSystemValue('dbtableprefix', 'ownc_');
		$this->tableName = strtolower($this->getUniqueID($this->config->getSystemValue('dbtableprefix') . 'test_'));

		[$startSchema, $endSchema] = $this->getDuplicateKeySchemas();
		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$this->connection->insert($this->tableName, ['id' => 1, 'name' => 'foo']);
		$this->connection->insert($this->tableName, ['id' => 2, 'name' => 'bar']);
		$this->connection->insert($this->tableName, ['id' => 3, 'name' => 'qwerty']);

		$migrator->migrate($endSchema);
		$this->addToAssertionCount(1);

		$this->config->setSystemValue('dbtableprefix', $oldTablePrefix);
	}

	public function testInsertAfterUpgrade() {
		[$startSchema, $endSchema] = $this->getDuplicateKeySchemas();
		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->migrate($endSchema);

		$this->connection->insert($this->tableName, ['id' => 1, 'name' => 'foo']);
		$this->connection->insert($this->tableName, ['id' => 2, 'name' => 'bar']);
		try {
			$this->connection->insert($this->tableName, ['id' => 2, 'name' => 'qwerty']);
			$this->fail('Expected duplicate key insert to fail');
		} catch (Exception $e) {
			$this->addToAssertionCount(1);
		}
	}

	public function testAddingPrimaryKeyWithAutoIncrement() {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer');
		$table->addColumn('name', 'string');

		$endSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true]);
		$table->addColumn('name', 'string');
		$table->setPrimaryKey(['id']);

		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->migrate($endSchema);

		$this->addToAssertionCount(1);
	}

	public function testReservedKeywords() {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true]);
		$table->addColumn('user', 'string', ['length' => 255]);
		$table->setPrimaryKey(['id']);

		$endSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $endSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true]);
		$table->addColumn('user', 'string', ['length' => 64]);
		$table->setPrimaryKey(['id']);

		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);

		$migrator->migrate($endSchema);

		$this->addToAssertionCount(1);
	}

	public function testAddingForeignKey() {
		$startSchema = new Schema([], [], $this->getSchemaConfig());
		$table = $startSchema->createTable($this->tableName);
		$table->addColumn('id', 'integer', ['autoincrement' => true]);
		$table->addColumn('name', 'string');
		$table->setPrimaryKey(['id']);

		$fkName = "fkc";
		$tableFk = $startSchema->createTable($this->tableNameTmp);
		$tableFk->addColumn('fk_id', 'integer');
		$tableFk->addColumn('name', 'string');
		$tableFk->addForeignKeyConstraint($this->tableName, ['fk_id'], ['id'], [], $fkName);

		$migrator = $this->manager->getMigrator();
		$migrator->migrate($startSchema);


		$this->assertTrue($startSchema->getTable($this->tableNameTmp)->hasForeignKey($fkName));
	}
}
