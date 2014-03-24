<?php

namespace theses;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\SchemaException;

class UpgradeManager
{
    function upgradeDatabaseSchema(Connection $connection)
    {
        $currentSchema = $connection->getSchemaManager()->createSchema();
        $upgradedSchema = clone $currentSchema;

        try {
            $thesesSchema = new db\Schema([], $connection);
            $thesesSchema->addToSchema($upgradedSchema);
        } catch (SchemaException $e) {}

        // Try adding the Jackalope Schema
        try {
            $jackalopeSchema = new \Jackalope\Transport\DoctrineDBAL\RepositorySchema([], $connection);
            $jackalopeSchema->addtoSchema($upgradedSchema);
        } catch (SchemaException $e) {}

        $migrations = $currentSchema->getMigrateToSql($upgradedSchema, $connection->getDatabasePlatform());

        foreach ($migrations as $query) {
            $connection->exec($query);
        }

        return $migrations;
    }
}
