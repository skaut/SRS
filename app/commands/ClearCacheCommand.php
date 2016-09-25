<?php
/**
 * Date: 15.11.12
 * Time: 14:06
 * Author: Michal Májský
 */

namespace SRS\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Utils\Finder;

/**
 * Prikaz pro smazani cachce
 */
class ClearCacheCommand extends Command
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('srs:cc');
        $this->setDescription('clear cache - smaže cache');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheDir = WWW_DIR . "/../temp/cache";
        $proxiesDir = WWW_DIR . '/../temp/proxies';
        $webtempDir = WWW_DIR . '/webtemp';

        //nette cache
        //if (file_exists($cacheDir.'/../btfj.dat'))
        //unlink($cacheDir.'/../btfj.dat');
        ClearCacheCommand::recursiveRemoveDirectory($cacheDir);
        mkdir($cacheDir); //jinak by nette spadlo

        //doctrine proxies
        foreach (Finder::findFiles('*.php')->in($proxiesDir) as $key => $file) {
            unlink($key);
        }

        //webloader webtemp
        foreach (Finder::findFiles('*')->exclude('.gitignore')->in($webtempDir) as $key => $file) {
            unlink($key);
        }


        $output->writeln('Cache promazána');

    }


    public static function recursiveRemoveDirectory($directory, $empty = FALSE)
    {
        // if the path has a slash at the end we remove it here
        if (substr($directory, -1) == '/') {
            $directory = substr($directory, 0, -1);
        }

        // if the path is not valid or is not a directory ...
        if (!file_exists($directory) || !is_dir($directory)) {
            // ... we return false and exit the function
            return FALSE;

            // ... if the path is not readable
        } elseif (!is_readable($directory)) {
            // ... we return false and exit the function
            return FALSE;

            // ... else if the path is readable
        } else {

            // we open the directory
            $handle = opendir($directory);

            // and scan through the items inside
            while (FALSE !== ($item = readdir($handle))) {
                // if the filepointer is not the current directory
                // or the parent directory
                if ($item != '.' && $item != '..') {
                    // we build the new path to delete
                    $path = $directory . '/' . $item;

                    // if the new path is a directory
                    if (is_dir($path)) {
                        // we call this function with the new path
                        ClearCacheCommand::recursiveRemoveDirectory($path);

                        // if the new path is a file
                    } else {
                        // we remove the file
                        unlink($path);
                    }
                }
            }
            // close the directory
            closedir($handle);

            // if the option to empty is not set to true
            if ($empty == FALSE) {
                // try to delete the now empty directory
                if (!rmdir($directory)) {
                    // return false if not possible
                    return FALSE;
                }
            }
            // return success
            return TRUE;
        }
    }
}