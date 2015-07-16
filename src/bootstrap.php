<?php

use \Silex\Provider;
use \Pomm\Silex\PommServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

if (!is_file(__DIR__ . '/config/current.php')) {
    throw new \RunTimeException('No current configuration file found in config.');
}

$app = new Silex\Application();

$app['config'] = require __DIR__ . '/config/current.php';

$app['debug'] = $app['config']['debug'];

$app->register(new Provider\TwigServiceProvider, [
    'twig.path' => __DIR__ . '/views',
]);

$app->register(new Provider\SessionServiceProvider);
$app->register(new Provider\SecurityServiceProvider);
$app->register(new Provider\ServiceControllerServiceProvider);

$app->register(new PommServiceProvider, [
    'pomm.class_path' => __DIR__ . '/vendor/pomm',
    'pomm.databases' => $app['config']['pomm'],
]);

$app['db'] = $app->share(function() use ($app) {
    return $app['pomm']->createConnection();
});

if (class_exists('\Silex\Provider\WebProfilerServiceProvider')) {

    $app->register(new Provider\HttpFragmentServiceProvider);
    $app->register(new Provider\UrlGeneratorServiceProvider);

    $profiler = new Provider\WebProfilerServiceProvider();
    $app->register($profiler, [
        'profiler.cache_dir' => __DIR__ . '/../cache/profiler',
    ]);
    $app->mount('/_profiler', $profiler);
}

return $app;
