<?php declare(strict_types=1);

namespace Gruberro\MongoDbMigrations\Console\Command;

use Gruberro\MongoDbMigrations;
use MongoDB;
use MongoDB\Database;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class VersionsCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('php-mongodb-migrations:version')
            ->setDescription('Manually add and delete migrations from the version collection.')
            ->addOption(
                'server',
                's',
                InputOption::VALUE_REQUIRED,
                'The connection string (e.g. mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db)',
                'mongodb://localhost:27017'
            )
            ->addArgument(
                'database',
                InputArgument::REQUIRED,
                'The database to connect to'
            )
            ->addArgument(
                'migration-directories',
                InputArgument::IS_ARRAY,
                'List of directories containing migration classes',
                []
            )
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED,
                'The migration id to add or delete'
            )
            ->addOption(
                'add',
                null,
                InputOption::VALUE_NONE,
                'Add the specified migration id'
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'Delete the specified migration id'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Apply to all the migrations'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command allows you to manually add, delete or synchronize migrations from the version collection:

    <info>%command.full_name% DATABASE_NAME MIGRATION_ID /path/to/migrations --add</info>

If you want to delete a version you can use the <comment>--delete</comment> option:

    <info>%command.full_name% DATABASE_NAME MIGRATION_ID /path/to/migrations --delete</info>

If you want to synchronize by adding or deleting all migrations available in the version collection you can use the <comment>--all</comment> option:

    <info>%command.full_name% DATABASE_NAME /path/to/migrations --add --all</info>
    <info>%command.full_name% DATABASE_NAME /path/to/migrations --delete --all</info>
EOT
            );
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $id = $input->getOption('id');
        $directories = $input->getArgument('migration-directories');
        $migrations = $this->getMigrations($directories);
        $db = $this->connect($input->getOption('server'), $input->getArgument('database'));

        $this->acquireLock($db);

        $add = $input->getOption('add');
        $delete = $input->getOption('delete');
        $all = $input->getOption('all');

        // Assume add is the default behavior
        if ($add === false && $delete === false) {
            $add = true;
        }

        if ($add === true && $delete === true) {
            throw new Console\Exception\RuntimeException("--add and --delete is not allowed to be set both");
        }

        if (!$all && $id === null) {
            throw new Console\Exception\RuntimeException("Specify --all or a single migration id");
        }

        try {
            if ($all) {
                $id = null;
            }

            if ($id !== null) {
                $migrations = array_filter($migrations, function (MongoDbMigrations\MigrationInterface $migration) use ($id): bool {
                    return $migration->getId() === $id;
                });

                if (count($migrations) === 0) {
                    throw new Console\Exception\RuntimeException("No migration for id '{$id}' found");
                }
            }

            if ($add) {
                $this->addMigrations($migrations, $db);
            } elseif ($delete) {
                $this->deleteMigrations($migrations, $db);
            }
        } catch (\Exception $e) {
            throw new Console\Exception\RuntimeException('Error while executing migrations', $e->getCode(), $e);
        } finally {
            $this->releaseLock($db);
        }
    }

    /**
     * @param array $migrations
     * @param Database $db
     */
    private function addMigrations(array $migrations, Database $db)
    {
        $databaseMigrationsCollection = $this->getMigrationsCollection($db);

        foreach ($migrations as $id => $migration) {
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

        $addedMigrations = count($migrations);
        $this->output->writeln("<info>✓ Successfully added {$addedMigrations} migrations to version collection</info>");
    }

    /**
     * @param array $migrations
     * @param Database $db
     */
    private function deleteMigrations(array $migrations, Database $db)
    {
        $databaseMigrationsCollection = $this->getMigrationsCollection($db);

        foreach ($migrations as $id => $migration) {
            $databaseMigrationsCollection->deleteOne(['migration_id' => $id]);
        }

        $deletedMigrations = count($migrations);
        $this->output->writeln("<info>✓ Successfully deleted {$deletedMigrations} migrations from version collection</info>");
    }
}
