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
 * Příkaz pro vymazání cache.
 */
class ClearCacheCommand extends Command
{
    public function __construct(private string $dir)
    {
        parent::__construct();
    }

    /**
     * Nastavuje příkaz.
     */
    protected function configure(): void
    {
        $this->setName('app:cache:clear');
        $this->setDescription('Clears cache and webtemp directories.');
    }

    /**
     * Spouští příkaz.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheDir   = $this->dir . '/temp/cache';
        $webtempDir = $this->dir . '/www/webtemp';

        try {
            $this->deleteDir($cacheDir);
            @mkdir($cacheDir);

            foreach (Finder::findFiles('*')->exclude('.gitignore')->in($webtempDir) as $key => $file) {
                unlink($key);
            }

            $output->writeln('Cache cleared successfully.');

            return 0;
        } catch (Throwable) {
            $output->writeln('Cache clearing failed.');

            return 1;
        }
    }

    /**
     * Maže složku.
     */
    private function deleteDir(string $path): bool
    {
        if (is_dir($path) === true) {
            $files = array_diff(scandir($path), ['.', '..']);

            foreach ($files as $file) {
                $this->deleteDir(realpath($path) . '/' . $file);
            }

            return @rmdir($path);
        } elseif (is_file($path) === true) {
            return @unlink($path);
        }

        return false;
    }
}
