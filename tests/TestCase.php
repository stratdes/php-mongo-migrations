<?php

namespace Gruberro\MongoDbMigrations\Tests;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->getTestDatabase()->drop();
    }

    /**
     * @return \MongoClient
     */
    protected function getTestClient()
    {
        return new \MongoClient();
    }

    /**
     * @return string
     */
    protected function getTestDatabaseName()
    {
        return 'php-mongo-migrations';
    }

    /**
     * @return \MongoDB
     */
    protected function getTestDatabase()
    {
        $client = $this->getTestClient();
        return $client->selectDB($this->getTestDatabaseName());
    }
}
