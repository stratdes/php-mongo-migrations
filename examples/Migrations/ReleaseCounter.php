<?php

namespace MyMigrations;

use Gruberro\MongoDbMigrations;

class ReleaseCounter implements MongoDbMigrations\MigrationInterface, MongoDbMigrations\RunAlwaysMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'release-counter';
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateDate()
    {
        return new \DateTime('2016-01-01 00:00:00');
    }

    /**
     * Create one record per release
     *
     * {@inheritdoc}
     */
    public function execute(\MongoDB $db)
    {
        $releaseCollection = $db->selectCollection('releases');
        $releaseCollection->insert(['created' => new \MongoDate()]);
    }
}
