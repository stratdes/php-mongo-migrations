<?php declare(strict_types=1);

namespace Gruberro\MongoDbMigrations\Console\Command;

use Gruberro\MongoDbMigrations;
use MongoDB;
use Symfony\Component\Console;

class MigrationsCommand extends AbstractCommand
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
        $directories = $input->getArgument('migration-directories');
        $migrations = $this->getMigrations($directories);
        $db = $this->connect($input->getOption('server'), $input->getArgument('database'));

        $this->acquireLock($db);

        try {
            $databaseMigrationsCollection = $this->getMigrationsCollection($db);
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
            $this->releaseLock($db);
        }
    }
}
