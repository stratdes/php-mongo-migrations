<?php declare(strict_types=1);

namespace Gruberro\MongoDbMigrations\Console\Command;

use Gruberro\MongoDbMigrations;
use MongoDB;
use Symfony\Component\Console;

class MigrationsCommand extends Console\Command\Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('php-mongodb-migrations:migrate')
            ->setDescription('Execute all open migrations')
            ->addOption(
                'server',
                's',
                Console\Input\InputOption::VALUE_REQUIRED,
                'The connection string (e.g. mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db)',
                'mongodb://localhost:27017'
            )
            ->addOption(
                'contexts',
                'c',
                Console\Input\InputOption::VALUE_IS_ARRAY | Console\Input\InputOption::VALUE_REQUIRED,
                'A list of contexts evaluated with each migration of type ContextualMigrationInterface',
                []
            )
            ->addArgument(
                'database',
                Console\Input\InputArgument::REQUIRED,
                'The database to connect to'
            )
            ->addArgument(
                'migration-directories',
                Console\Input\InputArgument::IS_ARRAY,
                'List of directories containing migration classes',
                []
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $client = new MongoDB\Client($input->getOption('server'));
        $db = $client->selectDatabase($input->getArgument('database'));
        $output->writeln("<info>✓ Successfully established database connection</info>", $output::VERBOSITY_VERBOSE);

        $directories = $input->getArgument('migration-directories');
        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                throw new Console\Exception\InvalidArgumentException("'{$directory}' is no valid directory");
            }

            $output->writeln("<comment>Iterating '{$directory}' for potential migrations classes</comment>", $output::VERBOSITY_DEBUG);
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::LEAVES_ONLY);

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getBasename('.php') === $file->getBasename()) {
                    continue;
                }

                require_once $file->getRealPath();

                $output->writeln("<comment>Loaded potential migration '{$file->getRealPath()}'</comment>", $output::VERBOSITY_DEBUG);
            }
        }

        /** @var MongoDbMigrations\MigrationInterface[] $migrations */
        $migrations = [];
        foreach (get_declared_classes() as $className) {
            $reflectionClass = new \ReflectionClass($className);

            if ($reflectionClass->implementsInterface(MongoDbMigrations\MigrationInterface::class)) {
                /** @var MongoDbMigrations\MigrationInterface $newInstance */
                $newInstance = $reflectionClass->newInstance();
                $id = md5($newInstance->getId());

                if (isset($migrations[$id])) {
                    $existingMigrationClass = get_class($migrations[$id]);
                    throw new Console\Exception\RuntimeException("Found a non unique migration id '{$newInstance->getId()}' in '{$reflectionClass->getName()}', already defined by migration class '{$existingMigrationClass}'");
                }

                $migrations[$id] = $newInstance;

                $output->writeln("<comment>Found valid migration class '{$reflectionClass->getName()}'</comment>", $output::VERBOSITY_DEBUG);
            }
        }

        $migrationClassesCount = count($migrations);
        $output->writeln("<info>✓ Found {$migrationClassesCount} valid migration classes</info>", $output::VERBOSITY_VERBOSE);

        uasort($migrations, function (MongoDbMigrations\MigrationInterface $a, MongoDbMigrations\MigrationInterface $b) {
            if ($a->getCreateDate() === $b->getCreateDate()) {
                return 0;
            }

            return $a->getCreateDate() < $b->getCreateDate() ? -1 : 1;
        });

        $output->writeln("<info>✓ Reordered all migration classes according to their create date</info>", $output::VERBOSITY_VERBOSE);

        $databaseMigrationsLockCollection = $db->selectCollection('DATABASE_MIGRATIONS_LOCK');
        $databaseMigrationsLockCollection->createIndex(['locked' => 1]);

        $currentLock = $databaseMigrationsLockCollection->findOneAndReplace(['locked' => ['$exists' => true]], ['locked' => true, 'last_locked_date' => new MongoDB\BSON\UTCDatetime((new \DateTime())->getTimestamp() * 1000)], ['upsert' => true]);
        if ($currentLock !== null && $currentLock->locked === true) {
            throw new Console\Exception\RuntimeException('Concurrent migrations are not allowed');
        }

        try {
            $output->writeln("<info>✓ Successfully acquired migration lock</info>", $output::VERBOSITY_VERBOSE);

            $databaseMigrationsCollection = $db->selectCollection('DATABASE_MIGRATIONS');
            $databaseMigrationsCollection->createIndex(['migration_id' => 1], ['unique' => true, 'background' => false]);

            $progress = new Console\Helper\ProgressBar($output, count($migrations));

            switch ($output->getVerbosity()) {
                case $output::VERBOSITY_VERBOSE:
                    $format = 'verbose';
                    break;
                case $output::VERBOSITY_VERY_VERBOSE:
                    $format = 'very_verbose';
                    break;
                case $output::VERBOSITY_DEBUG:
                    $format = 'debug';
                    break;
                default:
                    $format = 'normal';
            }

            $progress->setFormat($format);
            $progress->start();
            $executedMigrations = 0;

            foreach ($migrations as $id => $migration) {
                $progress->advance();

                if ($migration instanceof MongoDbMigrations\ContextualMigrationInterface && $input->getOption('contexts') !== []) {
                    if ($migration->getContexts() === []) {
                        throw new \InvalidArgumentException('An empty context specification is not allowed');
                    }

                    if (array_intersect($migration->getContexts(), $input->getOption('contexts')) === []) {
                        continue;
                    }
                }

                if (!$migration instanceof MongoDbMigrations\RunAlwaysMigrationInterface) {
                    if ($databaseMigrationsCollection->count(['migration_id' => $id]) > 0) {
                        continue;
                    }
                }

                $migration->execute($db);
                $executedMigrations++;

                $migrationInfo = [
                    'migration_id' => $id,
                    'migration_class' => get_class($migration),
                    'last_execution_date' => new MongoDB\BSON\UTCDatetime((new \DateTime())->getTimestamp() * 1000),
                    'run_always' => $migration instanceof MongoDbMigrations\RunAlwaysMigrationInterface,
                ];

                if ($migration instanceof MongoDbMigrations\ContextualMigrationInterface) {
                    $migrationInfo['contexts'] = $migration->getContexts();
                }

                $databaseMigrationsCollection->updateOne(
                    ['migration_id' => $id],
                    ['$set' => $migrationInfo],
                    ['upsert' => true]
                );
            }

            $progress->finish();
            $output->writeln('');

            $output->writeln("<info>✓ Successfully executed {$executedMigrations} migrations</info>");
        } catch (\Exception $e) {
            throw new Console\Exception\RuntimeException('Error while executing migrations', $e->getCode(), $e);
        } finally {
            $databaseMigrationsLockCollection->updateOne(['locked' => true], ['$set' => ['locked' => false]]);
            $output->writeln("<info>✓ Successfully released migration lock</info>", $output::VERBOSITY_VERBOSE);
        }
    }
}
