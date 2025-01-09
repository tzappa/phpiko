<?php

declare(strict_types=1);

use Laminas\Diactoros\ServerRequestFactory;

// Load Composer's autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

$request = ServerRequestFactory::fromGlobals();

$uri = $request->getUri()->getPath();
if (str_starts_with($uri, '/adm')) {
    require __DIR__ . '/Admin/bootstrap.php';
} elseif (str_starts_with($uri, '/api')) {
    require __DIR__ . '/API/bootstrap.php';
} else {
    require __DIR__ . '/App/bootstrap.php';
}
