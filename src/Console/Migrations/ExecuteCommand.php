<?php namespace Mitch\LaravelDoctrine\Console\Migrations;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ExecuteCommand extends AbstractCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'doctrine:migrations:execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute a single migration version up or down manually.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $version = $this->argument('version');
        $direction = $this->option('down') ? 'down' : 'up';

        $configuration = $this->getMigrationConfiguration();
        $version = $configuration->getVersion($version);

        $timeAllqueries = $this->option('query-time');

        if ($path = $this->option('write-sql')) {
            $path = is_bool($path) ? getcwd() : $path;
            $version->writeSqlFile($path, $direction);
        } else {
            if ($this->input->isInteractive()) {
                $execute = $this->confirm('WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)',
                    false);
            } else {
                $execute = true;
            }

            if ($execute) {
                $version->execute($direction, (boolean) $this->option('dry-run'));
            } else {
                $this->error('Migration cancelled!');
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
            ['version', InputArgument::REQUIRED, 'The version to execute.', null]
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
            ['dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run.'],
            ['up', null, InputOption::VALUE_NONE, 'Execute the migration up.'],
            ['down', null, InputOption::VALUE_NONE, 'Execute the migration down.'],
            ['query-time', null, InputOption::VALUE_NONE, 'Time all the queries individually.']
        ];

        return array_merge(parent::getOptions(), $options);
    }

}
