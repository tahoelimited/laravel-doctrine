<?php namespace Mitch\LaravelDoctrine\Console\Migrations;

use Illuminate\Console\Command;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;
use Doctrine\DBAL\Migrations\Configuration\XmlConfiguration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Symfony\Component\Console\Input\InputOption;

abstract class AbstractCommand extends Command
{
    /**
     * The configuration property only contains the configuration injected by the setter.
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * The migrationConfiguration property contains the configuration
     * created taking into account the command line options.
     *
     * @var Configuration
     */
    private $migrationConfiguration;

    /**
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    public function __construct()
    {
        parent::__construct($this->name);
    }

    protected function outputHeader(Configuration $configuration)
    {
        $name = $configuration->getName();
        $name = $name ? $name : 'Doctrine Database Migrations';
        $name = str_repeat(' ', 20) . $name . str_repeat(' ', 20);
        $this->question(str_repeat(' ', strlen($name)));
        $this->question($name);
        $this->question(str_repeat(' ', strlen($name)));
        $this->line('');
    }

    public function setMigrationConfiguration(Configuration $config)
    {
        $this->configuration = $config;
    }

    /**
     * When any (config) command line option is passed to the migration the migrationConfiguration
     * property is set with the new generated configuration.
     * If no (config) option is passed the migrationConfiguration property is set to the value
     * of the configuration one (if any).
     * Else a new configuration is created and assigned to the migrationConfiguration property.
     *
     * @return Configuration
     */
    protected function getMigrationConfiguration()
    {
        if ( ! $this->migrationConfiguration) {
            $config = config('doctrine.migrations');
            $directory = array_get($config, 'directory');
            if ( ! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            $configuration = new Configuration($this->getConnection(), $this->getOutputWriter());
            $configuration->setMigrationsDirectory(array_get($config, 'directory'));
            $configuration->setMigrationsNamespace(array_get($config, 'namespace'));
            $configuration->setMigrationsTableName(array_get($config, 'table', 'doctrine_migration_versions'));
            $configuration->registerMigrationsFromDirectory(array_get($config, 'directory'));
            $this->migrationConfiguration = $configuration;
        }

        return $this->migrationConfiguration;
    }

    /**
     * @return \Doctrine\DBAL\Migrations\OutputWriter
     */
    private function getOutputWriter()
    {
        if ( ! $this->outputWriter) {
            $this->outputWriter = new OutputWriter(function ($message) {
                return $this->output->writeln($message);
            });
        }

        return $this->outputWriter;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getConnection()
    {
        if ( ! $this->connection) {
            $entityManager = $this->laravel->make('Doctrine\ORM\EntityManagerInterface');
            $this->connection = $entityManager->getConnection();
        }

        return $this->connection;
    }
}
