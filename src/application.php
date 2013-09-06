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

$app->get('/', function() use($app) {
    $expenses = $app['db']->getMapFor('\Model\Expense')
        ->findWhere('payment_id IS NULL');
    return $app['twig']->render(
        'index.html.twig',
        compact('expenses')
    );
});

$app->get('/expenses/add', function() use($app) {
    $persons = $app['db']->getMapFor('\Model\Person')
        ->findAll();

    return $app['twig']->render(
        'expense/add.html.twig',
        compact('persons')
    );
});

$app->post('/expenses/add', function(Request $request) use($app) {
    $map = $app['db']->getMapFor('\Model\Expense');

    $expense = $map->createObject();
    $expense->hydrate($request->request->get('expense'));
    $map->saveOne($expense);

    $app['session']->getFlashBag()
        ->add('success', 'Payement ajouté');
    return $app->redirect('/');
});

return $app;
