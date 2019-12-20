<?php

declare(strict_types=1);

namespace App\Services;

use Contributte\Console\Application;
use DateTime;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Exception;
use MySQLDump;
use mysqli;
use Nette;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\DI\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

/**
 * Služba pro správu databáze.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class DatabaseService
{
    use Nette\SmartObject;

    /** @var Container */
    public $container;

    /** @var string */
    public $dir;

    /** @var Cache */
    protected $databaseCache;

    /** @var Application */
    private $consoleApplication;

    public function __construct(string $dir, Container $container, IStorage $storage, Application $consoleApplication)
    {
        $this->dir       = $dir;
        $this->container = $container;

        $this->databaseCache      = new Cache($storage, 'Database');
        $this->consoleApplication = $consoleApplication;
    }

    /**
     * Vytvoří zálohu databáze a spustí migrace. Spouští se pouze pokud není v cache záznam o provedeném update.
     *
     * @throws Throwable
     */
    public function update() : void
    {
        if ($this->databaseCache->load('updated') !== null) {
            return;
        }

        $this->databaseCache->save('lock', function () {
            if ($this->databaseCache->load('updated') === null) {
                $this->databaseCache->save('updated', new DateTime());

                $this->backup();

                $input  = new ArrayInput([
                    'command' => 'migrations:migrate',
                    '--no-interaction' => true,
                ]);
                $output = new BufferedOutput();

                $this->consoleApplication->add(new MigrateCommand());
                $this->consoleApplication->setAutoExit(false);
                $this->consoleApplication->run($input, $output);
            }

            return true;
        });
    }

    /**
     * Vytvoří zálohu databáze.
     *
     * @throws Exception
     */
    public function backup() : void
    {
        $database = $this->container->parameters['database'];

        $host     = $database['host'];
        $user     = $database['user'];
        $password = $database['password'];
        $dbname   = $database['dbname'];

        $dump = new MySQLDump(new mysqli($host, $user, $password, $dbname));

        $timestamp = (new DateTime())->format('YmdHi');

        $dump->save($this->dir . '/backup/' . $dbname . '-' . $timestamp . '.sql.gz');
    }
}
