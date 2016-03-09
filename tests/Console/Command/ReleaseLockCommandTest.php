<?php

namespace Gruberro\MongoDbMigrations\Tests\Console\Command;

use Gruberro\MongoDbMigrations;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ReleaseLockCommandTest extends MongoDbMigrations\Tests\TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $application->add(new MongoDbMigrations\Console\Command\ReleaseLockCommand());

        $command = $application->find('php-mongodb-migrations:release-lock');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                'database' => $this->getTestDatabaseName(),
            ]
        );

        $this->assertRegExp('/Successfully released migration lock/', $commandTester->getDisplay());

        $databaseMigrationsLockCollection = $this->getTestDatabase()->selectCollection('DATABASE_MIGRATIONS_LOCK');
        $this->assertFalse(
            $databaseMigrationsLockCollection->findOne(['locked' => ['$exists' => true]])['locked'],
            'The database lock must not be acquired!'
        );
    }

    public function testExecuteWithPreviousLock()
    {
        $databaseMigrationsLockCollection = $this->getTestDatabase()->selectCollection('DATABASE_MIGRATIONS_LOCK');
        $databaseMigrationsLockCollection->insert(['locked' => true, 'last_locked_date' => new \MongoDate()]);

        $application = new Application();
        $application->add(new MongoDbMigrations\Console\Command\ReleaseLockCommand());

        $command = $application->find('php-mongodb-migrations:release-lock');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                'database' => $this->getTestDatabaseName(),
            ]
        );

        $this->assertRegExp('/Successfully released migration lock/', $commandTester->getDisplay());

        $this->assertFalse(
            $databaseMigrationsLockCollection->findOne(['locked' => ['$exists' => true]])['locked'],
            'The database lock must not be acquired!'
        );
    }
}
