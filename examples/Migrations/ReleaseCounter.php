<?php declare(strict_types=1);

namespace MyMigrations;

use Gruberro\MongoDbMigrations;
use MongoDB;

class ReleaseCounter implements MongoDbMigrations\MigrationInterface, MongoDbMigrations\RunAlwaysMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return 'release-counter';
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateDate(): \DateTime
    {
        return new \DateTime('2016-01-01 00:00:00');
    }

    /**
     * Create one record per release
     *
     * {@inheritdoc}
     */
    public function execute(MongoDB\Database $db)
    {
        $releaseCollection = $db->selectCollection('releases');
        $releaseCollection->insertOne(['created' => new MongoDB\BSON\UTCDatetime((new \DateTime())->getTimestamp() * 1000)]);
    }
}
