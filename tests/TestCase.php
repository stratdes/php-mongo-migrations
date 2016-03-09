<?php

namespace Gruberro\MongoDbMigrations\Tests;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var bool
     */
    protected $hasDependencies = false;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!$this->hasDependencies) {
            $this->getTestDatabase()->drop();
        }
    }

    /**
     * This method is overwritten to ensure there is a way to recognize @dependant tests and to avoid
     * database "refreshes" for those kind of tests.
     *
     * {@inheritdoc}
     */
    public function setDependencies(array $dependencies)
    {
        parent::setDependencies($dependencies);

        if (count($dependencies) > 0) {
            $this->hasDependencies = true;
        } else {
            $this->hasDependencies = false;
        }
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
