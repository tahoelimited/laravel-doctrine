<?php namespace Mitch\LaravelDoctrine\Console\Migrations;

use Symfony\Component\Console\Input\InputOption;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\ORM\Tools\SchemaTool;

class DiffCommand extends GenerateCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'doctrine:migrations:diff';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a migration by comparing your current database to your mapping information.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $configuration = $this->getMigrationConfiguration();

        $entityManager = $this->laravel->make('Doctrine\ORM\EntityManagerInterface');
        $conn = $entityManager->getConnection();
        $platform = $conn->getDatabasePlatform();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if (empty($metadata)) {
            $this->info('No mapping information to process.', 'ERROR');

            return;
        }

        if ($filterExpr = $this->option('filter-expression')) {
            $conn->getConfiguration()
                ->setFilterSchemaAssetsExpression($filterExpr);
        }

        $tool = new SchemaTool($entityManager);

        $fromSchema = $conn->getSchemaManager()->createSchema();
        $toSchema = $tool->getSchemaFromMetadata($metadata);

        //Not using value from options, because filters can be set from config.yml
        if ($filterExpr = $conn->getConfiguration()->getFilterSchemaAssetsExpression()) {
            $tableNames = $toSchema->getTableNames();
            foreach ($tableNames as $tableName) {
                $tableName = substr($tableName, strpos($tableName, '.') + 1);
                if ( ! preg_match($filterExpr, $tableName)) {
                    $toSchema->dropTable($tableName);
                }
            }
        }

        $up = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateToSql($toSchema, $platform));
        $down = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateFromSql($toSchema, $platform));

        if ( ! $up && ! $down) {
            $this->info('No changes detected in your mapping information.', 'ERROR');

            return;
        }

        $version = date('YmdHis');
        $path = $this->generateMigration($configuration, $version, $up, $down);

        $this->info(sprintf('Generated new migration class to "<info>%s</info>" from schema differences.', $path));
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
                'filter-expression',
                null,
                InputOption::VALUE_OPTIONAL,
                'Tables which are filtered by Regular Expression.'
            ]
        ];
        return array_merge(parent::getOptions(), $options);
    }

    private function buildCodeFromSql(Configuration $configuration, array $sql)
    {
        $currentPlatform = $configuration->getConnection()->getDatabasePlatform()->getName();
        $code = array();
        foreach ($sql as $query) {
            if (stripos($query, $configuration->getMigrationsTableName()) !== false) {
                continue;
            }
            $code[] = sprintf("\$this->addSql(%s);", var_export($query, true));
        }

        if ($code) {
            array_unshift(
                $code,
                sprintf(
                    "\$this->abortIf(\$this->connection->getDatabasePlatform()->getName() != %s, %s);",
                    var_export($currentPlatform, true),
                    var_export(sprintf("Migration can only be executed safely on '%s'.", $currentPlatform), true)
                ),
                ""
            );
        }

        return implode("\n", $code);
    }

}
