<?php

namespace theses\db;

use Doctrine\DBAL\Schema\Schema as BaseSchema;
use Doctrine\DBAL\Connection;

class Schema extends BaseSchema
{
    function __construct(array $options, Connection $connection)
    {
        $schemaConfig = null === $connection ? null : $connection->getSchemaManager()->createSchemaConfig();

        parent::__construct(array(), array(), $schemaConfig);

        $this->options = $options;

        $this->addUsersTable();
    }

    function addUsersTable()
    {
        $users = $this->createTable('users');
        $users->addColumn('id', 'integer', ['autoincrement' => true]);
        $users->addColumn('username', 'string', ['notnull' => true]);
        $users->addColumn('password', 'string', ['notnull' => true]);
        $users->addColumn('display_name', 'string');
        $users->addColumn('email', 'string');
        $users->addColumn('role', 'string', ['notnull' => true]);
        $users->addColumn('nickname', 'string');

        $users->setPrimaryKey(['id']);
        $users->addUniqueIndex(["username"]);
    }

    function addToSchema(BaseSchema $schema)
    {
        foreach ($this->getTables() as $table) {
            $schema->_addTable($table);
        }

        foreach ($this->getSequences() as $sequence) {
            $schema->_addSequence($sequence);
        }
    }
}
