<?php

declare(strict_types=1);

if (file_exists('../.deployment.running'))
    require '.maintenance.php';

require __DIR__ . '/../vendor/autoload.php';

App\Bootstrap::boot()
    ->createContainer()
    ->getByType(Nette\Application\Application::class)
    ->run();