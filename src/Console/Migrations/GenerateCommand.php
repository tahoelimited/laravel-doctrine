<?php namespace Mitch\LaravelDoctrine\Console\Migrations;

use Symfony\Component\Console\Input\InputOption;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

class GenerateCommand extends AbstractCommand {

    private static $_template =
        '<?php

namespace <namespace>;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version<version> extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
<up>
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
<down>
    }
}
';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'doctrine:migrations:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a blank migration class.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $configuration = $this->getMigrationConfiguration();

        $version = date('YmdHis');
        $path = $this->generateMigration($configuration, $version);

        $this->info(sprintf('Generated new migration class to "<info>%s</info>"', $path));
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
                'editor-cmd',
                null,
                InputOption::VALUE_OPTIONAL,
                'Open file with this command upon creation.'
            ]
        ];
    }

    protected function generateMigration(Configuration $configuration, $version, $up = null, $down = null)
    {
        $placeHolders = array(
            '<namespace>',
            '<version>',
            '<up>',
            '<down>'
        );

        $replacements = array(
            $configuration->getMigrationsNamespace(),
            $version,
            $up ? "        " . implode("\n        ", explode("\n", $up)) : null,
            $down ? "        " . implode("\n        ", explode("\n", $down)) : null
        );

        $code = str_replace($placeHolders, $replacements, self::$_template);
        $code = preg_replace('/^ +$/m', '', $code);
        $dir = $configuration->getMigrationsDirectory();
        $dir = $dir ? $dir : getcwd();
        $dir = rtrim($dir, '/');
        $path = $dir . '/Version' . $version . '.php';

        if ( ! file_exists($dir)) {
            throw new \InvalidArgumentException(sprintf('Migrations directory "%s" does not exist.', $dir));
        }

        file_put_contents($path, $code);

        if ($editorCmd = $this->option('editor-cmd')) {
            proc_open($editorCmd . ' ' . escapeshellarg($path), array(), $pipes);
        }

        return $path;
    }

}
