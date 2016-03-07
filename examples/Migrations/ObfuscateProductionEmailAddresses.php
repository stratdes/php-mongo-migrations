<?php

namespace MyMigrations;

use Gruberro\MongoDbMigrations;

class ObfuscateProductionEmailAddresses implements MongoDbMigrations\MigrationInterface, MongoDbMigrations\ContextualMigrationInterface
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
    public function execute(\MongoDB $db)
    {
        $userCollection = $db->selectCollection('user');
        $userCollection->update(['email_address' => ['$ne' => 'deleted']], ['email_address' => 'deleted'], ['multi' => true]);
    }
}
