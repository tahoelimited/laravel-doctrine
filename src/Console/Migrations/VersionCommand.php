<?php namespace Mitch\LaravelDoctrine\Console\Migrations;

use Doctrine\DBAL\Migrations\MigrationException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class VersionCommand extends AbstractCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'doctrine:migrations:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually add and delete migration versions from the version table.';

    /**
     * The Migrations Configuration instance
     *
     * @var \Doctrine\DBAL\Migrations\Configuration\Configuration
     */
    private $configuration;

    /**
     * Whether or not the versions have to be marked as migrated or not
     *
     * @var boolean
     */
    private $markMigrated;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->configuration = $this->getMigrationConfiguration();

        if ( ! $this->option('add') && ! $this->option('delete')) {
            throw new \InvalidArgumentException('You must specify whether you want to --add or --delete the specified version.');
        }

        $this->markMigrated = (boolean) $this->option('add');

        if ($this->input->isInteractive()) {
            $confirmation = $this->confirm('WARNING! You are about to add, delete or synchronize migration versions from the version table that could result in data lost. Are you sure you wish to continue? (y/n)',
                false);
            if ($confirmation) {
                $this->markVersions();
            } else {
                $this->error('Migration cancelled!');
            }
        } else {
            $this->markVersions();
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
            ['version', InputArgument::OPTIONAL, 'The version to add or delete.', null]
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
            ['add', null, InputOption::VALUE_NONE, 'Add the specified version.'],
            ['delete', null, InputOption::VALUE_NONE, 'Delete the specified version.'],
            ['all', null, InputOption::VALUE_NONE, 'Apply to all the versions.'],
            ['range-from', null, InputOption::VALUE_OPTIONAL, 'Apply from specified version.'],
            ['range-to', null, InputOption::VALUE_OPTIONAL, 'Apply to specified version.']
        ];

        return array_merge(parent::getOptions(), $options);
    }

    private function markVersions()
    {
        $affectedVersion = $this->argument('version');

        $allOption = $this->option('all');
        $rangeFromOption = $this->option('range-from');
        $rangeToOption = $this->option('range-to');

        if ($allOption && ($rangeFromOption !== null || $rangeToOption !== null)) {
            throw new \InvalidArgumentException('Options --all and --range-to/--range-from both used. You should use only one of them.');
        } elseif ($rangeFromOption !== null ^ $rangeToOption !== null) {
            throw new \InvalidArgumentException('Options --range-to and --range-from should be used together.');
        }

        if ($allOption === true) {
            $availableVersions = $this->configuration->getAvailableVersions();
            foreach ($availableVersions as $version) {
                $this->mark($version, true);
            }
        } elseif ($rangeFromOption !== null && $rangeToOption !== null) {
            $availableVersions = $this->configuration->getAvailableVersions();
            foreach ($availableVersions as $version) {
                if ($version >= $rangeFromOption && $version <= $rangeToOption) {
                    $this->mark($version, true);
                }
            }
        } else {
            $this->mark($affectedVersion);
        }
    }

    private function mark($version, $all = false)
    {
        if ( ! $this->configuration->hasVersion($version)) {
            throw MigrationException::unknownMigrationVersion($version);
        }

        $version = $this->configuration->getVersion($version);
        if ($this->markMigrated && $this->configuration->hasVersionMigrated($version)) {
            $marked = true;
            if ( ! $all) {
                throw new \InvalidArgumentException(sprintf('The version "%s" already exists in the version table.',
                    $version));
            }
        }

        if ( ! $this->markMigrated && ! $this->configuration->hasVersionMigrated($version)) {
            $marked = false;
            if ( ! $all) {
                throw new \InvalidArgumentException(sprintf('The version "%s" does not exists in the version table.',
                    $version));
            }
        }

        if ( ! isset($marked)) {
            if ($this->markMigrated) {
                $version->markMigrated();
            } else {
                $version->markNotMigrated();
            }
        }
    }

}
