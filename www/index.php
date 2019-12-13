<?php
declare(strict_types=1);

if (file_exists('../.deployment.running'))
    require '.maintenance.php';

$container = require __DIR__ . '/../app/bootstrap.php';

$container->getByType(Nette\Application\Application::class)
    ->run();
