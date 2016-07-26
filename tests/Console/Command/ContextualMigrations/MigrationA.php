<?php

namespace TestMigrations\ContextualMigrations;

use Gruberro\MongoDbMigrations;
use MongoDB\Database;

class MigrationA implements MongoDbMigrations\MigrationInterface, MongoDbMigrations\ContextualMigrationInterface
{
    public function getId()
    {
        return 'some-context-migration';
    }

    public function getCreateDate()
    {
        return new \DateTime('2016-02-25 16:30:00');
    }

    public function execute(Database $db)
    {
    }

    public function getContexts()
    {
        return ['some-context'];
    }
}
