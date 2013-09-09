<?php

use \Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

$app = require __DIR__ . '/bootstrap.php';

$app['users'] = $app->share(function() use ($app) {
    $users = [];

    $persons = $app['db']->getMapFor('\Model\Person')
        ->findAll();
    foreach ($persons as $person) {
        $users[$person->email] = [
            'ROLE_ADMIN',
            $person->password,
        ];
    }
    return $users;
});

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

$app->get('/login', function(Request $request) use($app) {
    return $app['twig']->render('login.html.twig', [
        'error' => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ]);
});

$app->get('/', function(Request $request) use($app) {
    $url = '/expenses?done=false&' . http_build_query($request->query->all());

    return $app->handle(
        Request::create($url, 'GET'),
        HttpKernelInterface::SUB_REQUEST
    );
});

$app->get('/expenses', function(Request $request) use($app) {
    $page = $request->get('page', 1);
    $done = $request->get('done', true);
    $limit = $request->get('limit', 20);

    if ($done === true) {
        $where = '1 = 1';
    }
    else {
        $where = 'payment_id IS NULL';
    }

    $pager = $app['db']->getMapFor('\Model\Expense')
        ->paginateFindWhere($where, [], 'ORDER BY created DESC', $limit, $page);

    $personMap = $app['db']->getMapFor('\Model\Person');
    foreach ($pager->getCollection() as $expense) {
        $expense->person = $personMap->findByPk(['id' => $expense->person_id]);
    }

    return $app['twig']->render(
        'index.html.twig',
        compact('pager', 'limit')
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

$app->get('/expenses/{id}/delete', function($id) use($app) {
    $map = $app['db']->getMapFor('\Model\Expense');

    $expense = $map->findByPk(['id' => $id]);
    if ($expense !== null) {
        $map->deleteOne($expense);

        $app['session']->getFlashBag()
            ->add('success', 'Payement supprimé');
    }
    else {
        $app->abort(404, "Dépense $id inconnée");
    }

    return $app->redirect('/');
});

return $app;
