<?php

if (file_exists('../.deployment.running'))
    require '.maintenance.php';

$container = require __DIR__ . '/../app/bootstrap.php';

if (php_sapi_name() != "cli") {
    /** @var Nette\DI\Container $container */
	$container->getByType(Nette\Application\Application::class)->run();
} else {
	// Get application from DI container.
	$application = $container->getByType(Contributte\Console\Application::class);

	// Run application.
	exit($application->run());
}
