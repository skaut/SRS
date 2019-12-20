<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator();

$configurator->setDebugMode(getenv('DEVELOPMENT_MACHINE') === 'true');
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTimeZone('Europe/Prague');
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__)
    ->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

return $configurator->createContainer();
