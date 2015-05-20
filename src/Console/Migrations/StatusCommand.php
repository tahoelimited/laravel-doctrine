<?php namespace Mitch\LaravelDoctrine\Console\Migrations;

use Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration;
use Symfony\Component\Console\Input\InputOption;

class StatusCommand extends AbstractCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'doctrine:migrations:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View the status of a set of migrations.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $configuration = $this->getMigrationConfiguration();

        $formattedVersions = array();
        foreach (array('prev', 'current', 'next', 'latest') as $alias) {
            $version = $configuration->resolveVersionAlias($alias);
            if ($version === null) {
                if ($alias == 'next') {
                    $formattedVersions[$alias] = 'Already at latest version';
                } elseif ($alias == 'prev') {
                    $formattedVersions[$alias] = 'Already at first version';
                }
            } elseif ($version === '0') {
                $formattedVersions[$alias] = '<comment>0</comment>';
            } else {
                $formattedVersions[$alias] = $configuration->formatVersion($version) . ' (<comment>' . $version . '</comment>)';
            }
        }

        $executedMigrations = $configuration->getMigratedVersions();
        $availableMigrations = $configuration->getAvailableVersions();
        $executedUnavailableMigrations = array_diff($executedMigrations, $availableMigrations);
        $numExecutedUnavailableMigrations = count($executedUnavailableMigrations);
        $newMigrations = count(array_diff($availableMigrations, $executedMigrations));

        $this->line("\n <info>==</info> Configuration\n");

        $info = array(
            'Name'                            => $configuration->getName() ? $configuration->getName() : 'Doctrine Database Migrations',
            'Database Driver'                 => $configuration->getConnection()->getDriver()->getName(),
            'Database Name'                   => $configuration->getConnection()->getDatabase(),
            'Configuration Source'            => $configuration instanceof AbstractFileConfiguration ? $configuration->getFile() : 'manually configured',
            'Version Table Name'              => $configuration->getMigrationsTableName(),
            'Migrations Namespace'            => $configuration->getMigrationsNamespace(),
            'Migrations Directory'            => $configuration->getMigrationsDirectory(),
            'Previous Version'                => $formattedVersions['prev'],
            'Current Version'                 => $formattedVersions['current'],
            'Next Version'                    => $formattedVersions['next'],
            'Latest Version'                  => $formattedVersions['latest'],
            'Executed Migrations'             => count($executedMigrations),
            'Executed Unavailable Migrations' => $numExecutedUnavailableMigrations > 0 ? '<error>' . $numExecutedUnavailableMigrations . '</error>' : 0,
            'Available Migrations'            => count($availableMigrations),
            'New Migrations'                  => $newMigrations > 0 ? '<question>' . $newMigrations . '</question>' : 0
        );
        foreach ($info as $name => $value) {
            $this->line('    <comment>>></comment> ' . $name . ': ' . str_repeat(' ',
                    50 - strlen($name)) . $value);
        }

        if ($this->option('show-versions')) {
            if ($migrations = $configuration->getMigrations()) {
                $this->line("\n <info>==</info> Available Migration Versions\n");
                $migratedVersions = $configuration->getMigratedVersions();
                foreach ($migrations as $version) {
                    $isMigrated = in_array($version->getVersion(), $migratedVersions);
                    $status = $isMigrated ? '<info>migrated</info>' : '<error>not migrated</error>';
                    $migrationName = $version->getMigration()->getDescription();
                    if ($migrationName) {
                        $migrationName = str_repeat(' ', 10) . $migrationName;
                    }
                    $this->line('    <comment>>></comment> ' . $configuration->formatVersion($version->getVersion()) .
                        ' (<comment>' . $version->getVersion() . '</comment>)' .
                        str_repeat(' ', 30 - strlen($name)) . $status . $migrationName);
                }
            }

            if ($executedUnavailableMigrations) {
                $this->line("\n <info>==</info> Previously Executed Unavailable Migration Versions\n");
                foreach ($executedUnavailableMigrations as $executedUnavailableMigration) {
                    $this->line('    <comment>>></comment> ' . $configuration->formatVersion($executedUnavailableMigration) .
                        ' (<comment>' . $executedUnavailableMigration . '</comment>)');
                }
            }
        }
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
                'show-versions',
                null,
                InputOption::VALUE_NONE,
                'This will display a list of all available migrations and their status'
            ]
        ];

        return array_merge(parent::getOptions(), $options);
    }

}
