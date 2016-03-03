<?php

namespace Migrations;

use Gruberro\MongoDbMigrations;

class ProductionContextOnly implements MongoDbMigrations\MigrationInterface, MongoDbMigrations\ContextualMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'migration-3';
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateDate()
    {
        return new \DateTime('2016-01-01 12:12:16');
    }

    /**
     * {@inheritdoc}
     */
    public function getContexts()
    {
        return ['production'];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\MongoDB $db)
    {
        $testCollection = $db->selectCollection('production');
        $testCollection->update(['key' => '1234'], ['a' => true, 'b' => false, 'key' => '1234'], ['upsert' => true]);
    }
}
