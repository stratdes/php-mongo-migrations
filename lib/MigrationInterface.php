<?php declare(strict_types=1);

namespace Gruberro\MongoDbMigrations;

use MongoDB\Database;

interface MigrationInterface
{
    /**
     * Returns a unique id for the current migration
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Returns the create date for the current migration
     *
     * @return \DateTime
     */
    public function getCreateDate(): \DateTime;

    /**
     * @param Database $db
     */
    public function execute(Database $db);
}
