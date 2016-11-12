<?php
declare(strict_types = 1);

use \Silex\Provider;
use \PommProject\Silex\ {
    ServiceProvider\PommServiceProvider,
    ProfilerServiceProvider\PommProfilerServiceProvider
};

require_once __DIR__ . '/../vendor/autoload.php';

if (!is_file(__DIR__ . '/config/current.php')) {
    throw new \RunTimeException('No current configuration file found in config.');
}

$app = new Silex\Application();

$app['config'] = function () use($app) {
    $config = require __DIR__ . '/config/current.php';
    $config['pomm']['spore']['class:session_builder'] = '\PommProject\ModelManager\SessionBuilder';

    return $config;
};

$app['debug'] = $app['config']['debug'];

$app->register(new Provider\TwigServiceProvider, [
    'twig.path' => __DIR__ . '/views',
]);

$app->register(new Provider\SessionServiceProvider);
$app->register(new Provider\SecurityServiceProvider);
$app->register(new Provider\ServiceControllerServiceProvider);

$app->register(new PommServiceProvider, [
    'pomm.configuration' => $app['config']['pomm'],
]);

$app['db'] = function ($app) {
    return $app['pomm']['spore'];
};

if (class_exists('\Silex\Provider\WebProfilerServiceProvider')) {
    $app->register(new Provider\HttpFragmentServiceProvider);
    $app->register(new Provider\UrlGeneratorServiceProvider);

    $profiler = new Provider\WebProfilerServiceProvider();
    $app->register($profiler, [
        'profiler.cache_dir' => __DIR__ . '/../cache/profiler',
    ]);
    $app->mount('/_profiler', $profiler);

    $app->register(new PommProfilerServiceProvider);
}

return $app;
