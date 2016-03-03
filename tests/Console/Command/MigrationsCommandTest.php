<?php

namespace Gruberro\MongoDbMigrations\Tests\Console\Command;

use Gruberro\MongoDbMigrations;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationsCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $application->add(new MongoDbMigrations\Console\Command\MigrationsCommand());

        $command = $application->find('php-mongodb-migrations:migrate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                'database' => __FUNCTION__,
                'migration-directories' => [__DIR__ . '/../../../examples/Migrations/'],
            ]
        );

        $this->assertRegExp('/Successfully executed 3 migrations/', $commandTester->getDisplay());
    }
}
