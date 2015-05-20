<?php namespace Mitch\LaravelDoctrine\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Doctrine\ORM\Tools\EntityGenerator;

class ConvertMapping extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'doctrine:mapping:convert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert mapping information between supported formats.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $entityManager = $this->laravel->make('Doctrine\ORM\EntityManagerInterface');
        if ($this->option('from-database') === true) {
            $databaseDriver = new DatabaseDriver(
                $entityManager->getConnection()->getSchemaManager()
            );
            $entityManager->getConfiguration()->setMetadataDriverImpl(
                $databaseDriver
            );
            if (($namespace = $this->option('namespace')) !== null) {
                $databaseDriver->setNamespace($namespace);
            }
        }

        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($entityManager);
        $metadata = $cmf->getAllMetadata();
        $metadata = MetadataFilter::filter($metadata, $this->option('filter'));

        // Process destination directory
        if ( ! is_dir($destPath = $this->argument('dest-path'))) {
            mkdir($destPath, 0777, true);
        }

        $destPath = realpath($destPath);
        if ( ! file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Mapping destination directory '<info>%s</info>' does not exist.",
                    $this->argument('dest-path'))
            );
        }

        if ( ! is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Mapping destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        $toType = strtolower($this->argument('to-type'));
        $exporter = $this->getExporter($toType, $destPath);
        $exporter->setOverwriteExistingFiles($this->option('force'));

        if ($toType == 'annotation') {
            $entityGenerator = new EntityGenerator();
            $exporter->setEntityGenerator($entityGenerator);
            $entityGenerator->setNumSpaces($this->option('num-spaces'));
            if (($extend = $this->option('extend')) !== null) {
                $entityGenerator->setClassToExtend($extend);
            }
        }

        if (count($metadata)) {
            foreach ($metadata as $class) {
                $this->info(sprintf('Processing entity "<info>%s</info>"', $class->name));
            }
            $exporter->setMetadata($metadata);
            $exporter->export();
            $this->info(PHP_EOL . sprintf(
                    'Exporting "<info>%s</info>" mapping information to "<info>%s</info>"', $toType, $destPath
                ));
        } else {
            $this->info('No Metadata Classes to process.');
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
            ['to-type', InputArgument::REQUIRED, 'The mapping type to be converted.'],
            ['dest-path', InputArgument::REQUIRED, 'The path to generate your entities classes.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'filter',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'A string pattern used to match entities that should be processed.'
            ],
            [
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force to overwrite existing mapping files.'
            ],
            [
                'from-database',
                null,
                null,
                'Whether or not to convert mapping information from existing database.'
            ],
            [
                'extend',
                null,
                InputOption::VALUE_OPTIONAL,
                'Defines a base class to be extended by generated entity classes.'
            ],
            [
                'num-spaces',
                null,
                InputOption::VALUE_OPTIONAL,
                'Defines the number of indentation spaces.',
                4
            ],
            [
                'namespace',
                null,
                InputOption::VALUE_OPTIONAL,
                'Defines a namespace for the generated entity classes, if converted from database.'
            ],
        ];
    }

    /**
     * @param string $toType
     * @param string $destPath
     *
     * @return \Doctrine\ORM\Tools\Export\Driver\AbstractExporter
     */
    protected function getExporter($toType, $destPath)
    {
        $cme = new ClassMetadataExporter();

        return $cme->getExporter($toType, $destPath);
    }

}
