<?php
declare(strict_types = 1);

use App\Kernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

// The check is to ensure we don't use .env in production
$env = getenv('APP_ENV') ?: 'dev';
$dotenv = __DIR__ . '/../.env';
if (is_file($dotenv) && $env === 'dev' && class_exists(Dotenv::class)) {
    (new Dotenv)
        ->load($dotenv);
}

$debug = getenv('APP_DEBUG') ?: false;
if ($debug && class_exists(Debug::class)) {
    umask(0000);

    Debug::enable();
}

// Request::setTrustedProxies(['0.0.0.0/0'], Request::HEADER_FORWARDED);

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', $_SERVER['APP_DEBUG'] ?? ('prod' !== ($_SERVER['APP_ENV'] ?? 'dev')));
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
