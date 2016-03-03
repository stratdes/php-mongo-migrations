<?php

namespace Gruberro\MongoDbMigrations;

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
     * @param \MongoDB $db
     */
    public function execute(\MongoDB $db);
}
