<?php

declare(strict_types=1);

namespace App\Commands;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use MySQLDump;
use mysqli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Příkaz pro zálohování databáze.
 */
class BackupDatabaseCommand extends Command
{
    private string $dir;

    private EntityManagerInterface $em;

    public function __construct(string $dir, EntityManagerInterface $em)
    {
        parent::__construct();

        $this->dir = $dir;
        $this->em  = $em;
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
        $host      = $this->em->getConnection()->getHost();
        $user      = $this->em->getConnection()->getUsername();
        $password  = $this->em->getConnection()->getPassword();
        $dbname    = $this->em->getConnection()->getDatabase();
        $timestamp = (new DateTimeImmutable())->format('YmdHi');

        try {
            $dump = new MySQLDump(new mysqli($host, $user, $password, $dbname));
            $dump->save($this->dir . '/' . $dbname . '-' . $timestamp . '.sql.gz');

            $output->writeln('Database dump created successfully.');

            return 0;
        } catch (Throwable $e) {
            $output->writeln('Database dump creation failed.');

            return 1;
        }
    }
}
