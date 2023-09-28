<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;

use function dirname;
use function getenv;
use function putenv;
use function umask;

class Bootstrap
{
    public static function boot(): Configurator
    {
        umask(0002);

        $logDir  = __DIR__ . '/../log';
        $tempDir = dirname(__DIR__) . '/temp';

        putenv('TMPDIR=' . $tempDir);

        $configurator = new Configurator();

        // $configurator->setDebugMode('23.75.345.200'); // enable for your remote IP
        $configurator->setDebugMode(getenv('DEVELOPMENT_MACHINE') === 'true');
        $configurator->enableTracy($logDir);

        $configurator->setTimeZone('Europe/Prague');
        $configurator->setTempDirectory($tempDir);

        $configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();

        $configurator->addConfig(__DIR__ . '/config/common.neon');
        $configurator->addConfig(__DIR__ . '/config/local.neon');

        return $configurator;
    }
}
