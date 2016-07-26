<?php

namespace TestMigrations\FailingMigrations;

use Gruberro\MongoDbMigrations;
use MongoDB\Database;

class FailingMigration implements MongoDbMigrations\MigrationInterface
{
    public function getId()
    {
        return 'failing-migration';
    }

    public function getCreateDate()
    {
        return new \DateTime('2016-02-25 16:30:00');
    }

    public function execute(Database $db)
    {
        throw new \InvalidArgumentException('some error occured');
    }
}
