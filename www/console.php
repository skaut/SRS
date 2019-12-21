<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

exit(App\Bootstrap::boot()
    ->createContainer()
    ->getByType(Contributte\Console\Application::class)
    ->run());