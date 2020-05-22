<?php

declare(strict_types=1);

namespace App;

use Nette\Configurator;
use function getenv;

class Bootstrap
{
    public static function boot() : Configurator
    {
        umask(0002);

        $configurator = new Configurator();

        //$configurator->setDebugMode('23.75.345.200'); // enable for your remote IP
        $configurator->setDebugMode(getenv('DEVELOPMENT_MACHINE') === 'true');
        $configurator->enableTracy(__DIR__ . '/../log');

        $configurator->setTimeZone('Europe/Prague');
        $configurator->setTempDirectory(__DIR__ . '/../temp');

        $configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();

        $configurator->addConfig(__DIR__ . '/config/common.neon');
        $configurator->addConfig(__DIR__ . '/config/local.neon');

        return $configurator;
    }
}
