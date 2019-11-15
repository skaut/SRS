<?php
$container = require __DIR__ . '/../../../app/bootstrap.php';

$em = $container->getByType(\App\Model\EntityManagerDecorator::class);

return $em;
