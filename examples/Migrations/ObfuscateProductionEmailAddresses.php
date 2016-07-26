<?php

namespace MyMigrations;

use Gruberro\MongoDbMigrations;
use MongoDB\Database;

class ObfuscateProductionEmailAddresses implements MongoDbMigrations\MigrationInterface, MongoDbMigrations\ContextualMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'obfuscate-production-email-addresses';
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateDate()
    {
        return new \DateTime('2016-01-01 12:12:16');
    }

    /**
     * The obfuscation is relevant for both development and staging
     *
     * {@inheritdoc}
     */
    public function getContexts()
    {
        return ['development', 'staging'];
    }

    /**
     * Obfuscate all email addresses
     *
     * In case a production backup is restored, all email addresses are going to be obfuscated.
     *
     * {@inheritdoc}
     */
    public function execute(Database $db)
    {
        $userCollection = $db->selectCollection('user');
        $userCollection->updateMany(['email_address' => ['$ne' => 'deleted']], ['$set' => ['email_address' => 'deleted']]);
    }
}
