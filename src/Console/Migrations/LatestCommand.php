<?php namespace Mitch\LaravelDoctrine\Console\Migrations;

class LatestCommand extends AbstractCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'doctrine:migrations:latest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Outputs the latest version number.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $configuration = $this->getMigrationConfiguration();
        $this->info($configuration->getLatestVersion());
    }

}
