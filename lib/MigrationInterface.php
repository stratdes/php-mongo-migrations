<?php

namespace Gruberro\MongoDbMigrations;

use MongoDB\Database;

interface MigrationInterface
{
    /**
     * Returns a unique id for the current migration
     *
     * @return string
     */
    public function getId();

    /**
     * Returns the create date for the current migration
     *
     * @return \DateTime
     */
    public function getCreateDate();

    /**
     * @param Database $db
     */
    public function execute(Database $db);
}
