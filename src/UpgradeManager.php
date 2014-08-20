<?php

namespace theses;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\SchemaException;

class UpgradeManager
{
    function upgradeDatabaseSchema(Connection $connection)
    {
        $currentSchema = $connection->getSchemaManager()->createSchema();
        $thesesSchema = new db\Schema([], $connection);

        // Try adding the Jackalope Schema
        try {
            $jackalopeSchema = new \Jackalope\Transport\DoctrineDBAL\RepositorySchema([], $connection);
            $jackalopeSchema->addtoSchema($thesesSchema);
        } catch (SchemaException $e) {}

        $migrations = $currentSchema->getMigrateToSql($thesesSchema, $connection->getDatabasePlatform());

        foreach ($migrations as $query) {
            $connection->exec($query);
        }

        return $migrations;
    }
}
