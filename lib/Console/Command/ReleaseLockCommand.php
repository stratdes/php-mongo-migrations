<?php

namespace Gruberro\MongoDbMigrations\Console\Command;

use Gruberro\MongoDbMigrations;
use Symfony\Component\Console;

class ReleaseLockCommand extends Console\Command\Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('php-mongodb-migrations:release-lock')
            ->setDescription('Release current migration lock')
            ->addOption(
                'server',
                's',
                Console\Input\InputOption::VALUE_REQUIRED,
                'The connection string (e.g. mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db)',
                'mongodb://localhost:27017'
            )
            ->addArgument(
                'database',
                Console\Input\InputArgument::REQUIRED,
                'The database to connect to'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $client = new \MongoClient($input->getOption('server'));
        $db = $client->selectDB($input->getArgument('database'));
        $output->writeln("<info>✓ Successfully established database connection</info>", $output::VERBOSITY_VERBOSE);

        $databaseMigrationsLockCollection = $db->selectCollection('DATABASE_MIGRATIONS_LOCK');
        $databaseMigrationsLockCollection->update(['locked' => ['$exists' => true]], ['$set' => ['locked' => false]], ['upsert' => true]);
        $output->writeln("<info>✓ Successfully released migration lock</info>");
    }
}
