<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator();

$configurator->setDebugMode(getenv('DEVELOPMENT_MACHINE') === 'true');
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTimeZone('Europe/Prague');

if (file_exists(__DIR__ . '/config/config.local.neon')) {
    $configurator->setTempDirectory(__DIR__ . '/../temp');

    $configurator->addConfig(__DIR__ . '/config/config.local.neon');
    $configurator->addConfig(__DIR__ . '/config/config.neon');
    if (PHP_SAPI != 'cli') {
        $configurator->addConfig(__DIR__ . '/config/config.console.neon');
    }
    $configurator->createRobotLoader()
        ->addDirectory(__DIR__)
        ->register();
} else {
    $configurator->setTempDirectory(__DIR__ . '/../temp/installer');

    $configurator->addConfig(__DIR__ . '/../installer/config/config.neon');
    $configurator->createRobotLoader()
        ->addDirectory(__DIR__ . '/../installer')
        ->register();
}

$container = $configurator->createContainer();

return $container;
