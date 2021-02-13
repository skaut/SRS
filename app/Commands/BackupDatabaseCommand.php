<?php

declare(strict_types=1);

namespace App\Commands;

use Nette\Utils\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function array_diff;
use function is_dir;
use function is_file;
use function mkdir;
use function realpath;
use function rmdir;
use function scandir;
use function unlink;

/**
 * Příkaz pro zálohování databáze.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BackupDatabaseCommand extends Command
{
    private string $dir;

    public function __construct(string $dir)
    {
        parent::__construct();

        $this->dir = $dir;
    }

    /**
     * Nastavuje příkaz.
     */
    protected function configure(): void
    {
        $this->setName('app:database:backup');
        $this->setDescription('Backups database.');
    }

    /**
     * Spouští příkaz.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host     = $this->em->getConnection()->getHost();
        $user     = $this->em->getConnection()->getUsername();
        $password = $this->em->getConnection()->getPassword();
        $dbname   = $this->em->getConnection()->getDatabase();

        $dump = new MySQLDump(new mysqli($host, $user, $password, $dbname));

        $timestamp = (new DateTimeImmutable())->format('YmdHi');

        $dump->save($this->dir . '/' . $dbname . '-' . $timestamp . '.sql.gz');
    }
}
