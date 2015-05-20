<?php namespace Mitch\LaravelDoctrine\Console\Migrations;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\DBAL\Migrations\Migration;

class MigrateCommand extends AbstractCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'doctrine:migrations:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute a migration to a specified version or the latest available version.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $configuration = $this->getMigrationConfiguration();
        $migration = new Migration($configuration);

        $this->outputHeader($configuration);
        $noInteraction = !$this->input->isInteractive();

        $timeAllqueries = $this->option('query-time');
        $executedMigrations = $configuration->getMigratedVersions();
        $availableMigrations = $configuration->getAvailableVersions();
        $executedUnavailableMigrations = array_diff($executedMigrations, $availableMigrations);

        $versionAlias = $this->argument('version');
        $version = $configuration->resolveVersionAlias($versionAlias);
        if ($version === null) {
            switch ($versionAlias) {
                case 'prev':
                    $this->error('Already at first version.');
                    break;
                case 'next':
                    $this->error('Already at latest version.');
                    break;
                default:
                    $this->error('Unknown version: ' . $this->output->getFormatter()->escape($versionAlias) . '');
            }

            return 1;
        }

        if ($executedUnavailableMigrations) {
            $this->error(sprintf('WARNING! You have %s previously executed migrations in the database that are not registered migrations.',
                count($executedUnavailableMigrations)));

            foreach ($executedUnavailableMigrations as $executedUnavailableMigration) {
                $this->line('    <comment>>></comment> ' . $configuration->formatVersion($executedUnavailableMigration) . ' (<comment>' . $executedUnavailableMigration . '</comment>)');
            }
            if ( ! $noInteraction) {
                $confirmation = $this->confirm('Are you sure you wish to continue? (y/n)', false);
                if ( ! $confirmation) {
                    $this->error('Migration cancelled!');

                    return 1;
                }
            }
        }

        if ($path = $this->option('write-sql')) {
            $path = is_bool($path) ? getcwd() : $path;
            $migration->writeSqlFile($path, $version);
        } else {
            $dryRun = (boolean) $this->option('dry-run');
            // warn the user if no dry run and interaction is on
            if ( ! $dryRun && ! $noInteraction) {
                $confirmation = $this->confirm('WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)', false);
                if ( ! $confirmation) {
                    $this->error('Migration cancelled!');

                    return 1;
                }
            }
            $sql = $migration->migrate($version, $dryRun, $timeAllqueries);
            if ( ! $sql) {
                $this->comment('No migrations to execute.');
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            [
                'version',
                InputArgument::OPTIONAL,
                'The version number (YYYYMMDDHHMMSS) or alias (first, prev, next, latest) to migrate to.',
                'latest'
            ],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = [
            [
                'write-sql',
                null,
                InputOption::VALUE_NONE,
                'The path to output the migration SQL file instead of executing it.'
            ],
            [
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration as a dry run.'
            ],
            [
                'query-time',
                null,
                InputOption::VALUE_NONE,
                'Time all the queries individually.'
            ]
        ];

        return array_merge(parent::getOptions(), $options);
    }

}
