<?php declare(strict_types=1);

namespace Gruberro\MongoDbMigrations\Tests;

use MongoDB\Client;
use MongoDB\Database;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @before
     */
    protected function setupDatabase()
    {
        if (! $this->requires()) {
            $this->getTestDatabase()->drop();
        }
    }

    /**
     * @return Client
     */
    protected function getTestClient(): Client
    {
        return new Client();
    }

    /**
     * @return string
     */
    protected function getTestDatabaseName(): string
    {
        return 'php-mongo-migrations';
    }

    /**
     * @return Database
     */
    protected function getTestDatabase(): Database
    {
        $client = $this->getTestClient();
        return $client->selectDatabase($this->getTestDatabaseName());
    }
}
