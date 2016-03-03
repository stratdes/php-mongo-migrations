<?php

namespace Migrations;

use Gruberro\MongoDbMigrations;

class RunAlways implements MongoDbMigrations\MigrationInterface, MongoDbMigrations\RunAlwaysMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'migration-2';
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateDate()
    {
        return new \DateTime('2016-01-01 12:12:12');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\MongoDB $db)
    {
        $testCollection = $db->selectCollection('counter');
        $testCollection->insert(['another_record' => true]);
    }
}
