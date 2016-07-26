<?php declare(strict_types=1);

namespace MyMigrations;

use Gruberro\MongoDbMigrations;
use MongoDB\Database;

class CreateUserCollection implements MongoDbMigrations\MigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return 'create-user-collection-and-its-indexes';
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateDate(): \DateTime
    {
        return new \DateTime('2016-02-25 16:30:00');
    }

    /**
     * Creates a user collection and it's indexes
     *
     * This migration creates the required user collection to establish an application login and it's required indexes.
     * An overall admin user is created as well.
     *
     * {@inheritdoc}
     */
    public function execute(Database $db)
    {
        $userCollection = $db->selectCollection('user');

        $userCollection->createIndex(['email_address' => 1], ['unique' => true]);

        $userCollection->insertOne(['username' => 'admin', 'password' => password_hash('topsecret', PASSWORD_DEFAULT), 'email_address' => 'admin@exmaple.com']);
    }
}
