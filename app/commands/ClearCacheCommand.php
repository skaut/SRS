<?php
declare(strict_types=1);

namespace App\Commands;

use Nette\Utils\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Příkaz pro vymazání cache.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ClearCacheCommand extends Command
{
    /** @var string */
    private $dir;


    /**
     * ClearCacheCommand constructor.
     * @param string $dir
     */
    public function __construct($dir)
    {
        parent::__construct();

        $this->dir = $dir;
    }

    /**
     * Nastavuje příkaz.
     */
    protected function configure()
    {
        $this->setName('app:cache:clear');
        $this->setDescription('Clears cache, proxies and webtemp directories.');
    }

    /**
     * Spouští příkaz.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheDir = $this->dir . '/temp/cache';
        $proxiesDir = $this->dir . '/temp/proxies';
        $webtempDir = $this->dir . '/www/webtemp';

        try {
            $this->deleteDir($cacheDir);
            @mkdir($cacheDir);

            foreach (Finder::findFiles('*.php')->in($proxiesDir) as $key => $file) {
                unlink($key);
            }

            foreach (Finder::findFiles('*')->exclude('.gitignore')->in($webtempDir) as $key => $file) {
                unlink($key);
            }

            $output->writeln('Cache cleared.');
            return 0;
        } catch (\Exception $exc) {
            $output->write("error");
            return 1;
        }
    }

    /**
     * Maže složku.
     * @param $path
     * @return bool
     */
    private function deleteDir($path)
    {
        if (is_dir($path) === TRUE) {
            $files = array_diff(scandir($path), ['.', '..']);

            foreach ($files as $file) {
                $this->deleteDir(realpath($path) . '/' . $file);
            }

            return @rmdir($path);
        } else if (is_file($path) === TRUE) {
            return @unlink($path);
        }

        return FALSE;
    }
}
