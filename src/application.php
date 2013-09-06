<?php

use \Symfony\Component\HttpFoundation\Request;

$app = require __DIR__ . '/bootstrap.php';

$app['users'] = $app->share(function() use ($app) {
    $users = array();

    $persons = $app['db']->getMapFor('\Model\Person')
        ->findAll();
    foreach ($persons as $person) {
        $users[$person->email] = array(
            'ROLE_ADMIN',
            $person->password,
        );
    }
    return $users;
});

$app['security.firewalls'] = array(
    'dev' => array(
        'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
    ),
    'login' => array(
        'pattern' => '^/login$',
        'anonymous' => true,
    ),
    'default' => array(
        'pattern' => '^.*$',
        'form' => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
        'logout' => array('logout_path' => '/logout'),
        'users' => $app['users'],
    ),
);

$app->get('/login', function(Request $request) use($app) {
    return $app['twig']->render('login.html.twig', array(
        'error' => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->get('/', function(Request $request) use($app) {
    return $app['twig']->render('index.html.twig');
});

return $app;
