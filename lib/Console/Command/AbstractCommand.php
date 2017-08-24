<?php declare(strict_types=1);

namespace Gruberro\MongoDbMigrations\Console\Command;

use Gruberro\MongoDbMigrations;
use MongoDB;
use MongoDB\Database;
use Symfony\Component\Console;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Console\Command\Command
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param string[] $directories
     * @return MongoDbMigrations\MigrationInterface[]
     */
    protected function getMigrations(array $directories): array
    {
        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                throw new Console\Exception\InvalidArgumentException("'{$directory}' is no valid directory");
            }

            $this->output->writeln("<comment>Iterating '{$directory}' for potential migrations classes</comment>", OutputInterface::VERBOSITY_DEBUG);
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::LEAVES_ONLY);

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getBasename('.php') === $file->getBasename()) {
                    continue;
                }

                require_once $file->getRealPath();

                $this->output->writeln("<comment>Loaded potential migration '{$file->getRealPath()}'</comment>", OutputInterface::VERBOSITY_DEBUG);
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
                    throw new RuntimeException("Found a non unique migration id '{$newInstance->getId()}' in '{$reflectionClass->getName()}', already defined by migration class '{$existingMigrationClass}'");
                }

                $migrations[$id] = $newInstance;

                $this->output->writeln("<comment>Found valid migration class '{$reflectionClass->getName()}'</comment>", OutputInterface::VERBOSITY_DEBUG);
            }
        }

        $migrationClassesCount = count($migrations);
        $this->output->writeln("<info>✓ Found {$migrationClassesCount} valid migration classes</info>", OutputInterface::VERBOSITY_VERBOSE);

        uasort($migrations, function (MongoDbMigrations\MigrationInterface $a, MongoDbMigrations\MigrationInterface $b) {
            return $a->getCreateDate() <=> $b->getCreateDate();
        });

        $this->output->writeln("<info>✓ Reordered all migration classes according to their create date</info>", OutputInterface::VERBOSITY_VERBOSE);

        return $migrations;
    }

    /**
     * @param string $server
     * @param string $database
     * @return Database
     */
    protected function connect(string $server, string $database): Database
    {
        $client = new MongoDB\Client($server);
        $db = $client->selectDatabase($database);
        $this->output->writeln("<info>✓ Successfully established database connection</info>", OutputInterface::VERBOSITY_VERBOSE);

        return $db;
    }

    /**
     * @param Database $db
     *
     * @throws RuntimeException If there is already a running command
     */
    protected function acquireLock(Database $db)
    {
        $databaseMigrationsLockCollection = $db->selectCollection('DATABASE_MIGRATIONS_LOCK');
        $databaseMigrationsLockCollection->createIndex(['locked' => 1]);

        $currentLock = $databaseMigrationsLockCollection->findOneAndReplace(['locked' => ['$exists' => true]], ['locked' => true, 'last_locked_date' => new MongoDB\BSON\UTCDatetime((new \DateTime())->getTimestamp() * 1000)], ['upsert' => true]);
        if ($currentLock !== null && $currentLock->locked === true) {
            throw new RuntimeException('Concurrent migrations are not allowed');
        }

        $this->output->writeln("<info>✓ Successfully acquired migration lock</info>", OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * @param Database $db
     */
    protected function releaseLock(Database $db)
    {
        $databaseMigrationsLockCollection = $db->selectCollection('DATABASE_MIGRATIONS_LOCK');
        $databaseMigrationsLockCollection->updateOne(['locked' => true], ['$set' => ['locked' => false]]);
        $this->output->writeln("<info>✓ Successfully released migration lock</info>", OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * @param Database $db
     * @return MongoDB\Collection
     */
    protected function getMigrationsCollection(Database $db): MongoDB\Collection
    {
        $databaseMigrationsCollection = $db->selectCollection('DATABASE_MIGRATIONS');
        $databaseMigrationsCollection->createIndex(['migration_id' => 1], ['unique' => true, 'background' => false]);

        return $databaseMigrationsCollection;
    }
}
