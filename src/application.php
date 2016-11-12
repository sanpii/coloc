<?php
declare(strict_types = 1);

$app = require __DIR__ . '/bootstrap.php';

$app['users'] = function() use ($app) {
    $users = [];

    $persons = $app['db']->getModel('\Model\PersonModel')
        ->findAll();
    foreach ($persons as $person) {
        $users[$person->email] = [
            'ROLE_ADMIN',
            $person->password,
        ];
    }
    return $users;
};

$app['security.firewalls'] = [
    'dev' => [
        'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
    ],
    'login' => [
        'pattern' => '^/login$',
        'anonymous' => true,
    ],
    'default' => [
        'pattern' => '^.*$',
        'form' => ['login_path' => '/login', 'check_path' => '/admin/login_check'],
        'logout' => ['logout_path' => '/logout'],
        'users' => $app['users'],
    ],
];


$app->mount('/', new Controller\Index);
$app->mount('/expenses', new Controller\Expenses);
$app->mount('/payments', new Controller\Payment);

return $app;
