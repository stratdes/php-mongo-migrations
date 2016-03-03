<?php

namespace Migrations;

use Gruberro\MongoDbMigrations;

class CreateNewCollection implements MongoDbMigrations\MigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'migration-1';
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateDate()
    {
        return new \DateTime('2015-01-01 12:12:12');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\MongoDB $db)
    {
        $testCollection = $db->selectCollection('test');
        $testCollection->insert(['a' => true, 'b' => false]);
    }
}
