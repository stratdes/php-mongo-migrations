<?php declare(strict_types=1);

namespace TestMigrations\FailingMigrations;

use Gruberro\MongoDbMigrations;
use MongoDB\Database;

class FailingMigration implements MongoDbMigrations\MigrationInterface
{
    public function getId(): string
    {
        return 'failing-migration';
    }

    public function getCreateDate(): \DateTime
    {
        return new \DateTime('2016-02-25 16:30:00');
    }

    public function execute(Database $db)
    {
        throw new \InvalidArgumentException('some error occured');
    }
}
