<?php

namespace Gruberro\MongoDbMigrations\Tests;

use MongoDB\Client;
use MongoDB\Database;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (! $this->hasDependencies()) {
            $this->getTestDatabase()->drop();
        }
    }

    /**
     * @return Client
     */
    protected function getTestClient()
    {
        return new Client();
    }

    /**
     * @return string
     */
    protected function getTestDatabaseName()
    {
        return 'php-mongo-migrations';
    }

    /**
     * @return Database
     */
    protected function getTestDatabase()
    {
        $client = $this->getTestClient();
        return $client->selectDatabase($this->getTestDatabaseName());
    }
}
