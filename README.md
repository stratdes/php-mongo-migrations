# MongoDB Migrations

This command line application supports you in managing migrations for your MongoDB documents. These migrations should make part of your code changes and can be executed through your deployment.

## Installation

```
composer require stratdes/php-mongo-migrations=^0.1
```

## Writing migrations

You can write you own migrations by implementing the [`Gruberro\MongoDbMigrations\MigrationInterface`](lib/MigrationInterface.php) interface:

```php
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
```

You can optionally implement the [`Gruberro\MongoDbMigrations\RunAlwaysMigrationInterface`](lib/RunAlwaysMigrationInterface.php) interface to ensure a migration class is executed on every run.

Implementing the [`Gruberro\MongoDbMigrations\ContextualMigrationInterface`](lib/ContextualMigrationInterface.php) will allow you to specify a list of contexts to give additional control to your migrations.

Take a look at the [examples](examples/) to get some more insights.

## Using the command line interface

Now you can easily execute your migrations by starting the migration command:

```
./vendor/bin/migrations php-mongodb-migrations:migrate -c stage1 -c stage2 -s mongodb://localhost:27017 my_database path/to/my/migrations/ path/to/other/migrations
```

## Internal processing sequence

1. Gathering all migrations from the configured directories
2. Check for uniqueness (`getId()`) for all migrations
3. Ordering all migrations according their create date (`getCreateDate()`)
4. Locks other runs by creating a special document in `DATABASE_MIGRATIONS_LOCK` (take a look at the `php-mongodb-migrations:release-lock` command to release a lock manually)
5. Executes the migrations, if
   * at least one context is matching (no context is always matching) and
   * a migration has not been executed before or
   * the migration is marked as run always.
6. Stores a successfully executed migration in the collection `DATABASE_MIGRATIONS`.
7. Releases the run lock in `DATABASE_MIGRATIONS_LOCK` under any circumstances.
