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
    return $app->handle(
        Request::create('/expenses/-1/edit', 'GET'),
        HttpKernelInterface::SUB_REQUEST
    );
});

$app->post('/expenses/add', function(Request $request) use($app) {
    return $app->handle(
        Request::create('/expenses/-1/edit', 'POST', $request->request->all()),
        HttpKernelInterface::SUB_REQUEST
    );
});

$app->get('/expenses/{id}/edit', function($id) use($app) {
    $map = $app['db']->getMapFor('\Model\Expense');

    if ($id > 0) {
        $expense = $map->findByPk(['id' => $id]);
        if (is_null($expense)) {
            $app->abort(404, "Dépense #$id inconnue");
        }
    }
    else {
        $expense = $map->createObject([
            'id' => $id,
            'price' => 0,
            'shop' => '',
            'description' => '',
            'created' => date('d-m-Y'),
        ]);
    }

    $persons = $app['db']->getMapFor('\Model\Person')
        ->findAll();

    return $app['twig']->render(
        'expense/edit.html.twig',
        compact('expense', 'persons')
    );
});

$app->post('/expenses/{id}/edit', function(Request $request, $id) use($app) {
    $map = $app['db']->getMapFor('\Model\Expense');

    if ($id > 0) {
        $expense = $map->findByPk(['id' => $id]);
        if (is_null($expense)) {
            $app->abort(404, "Dépense #$id inconnue");
        }
    }
    else {
        $expense = $map->createObject();
    }

    $expense->hydrate($request->request->get('expense'));
    $map->saveOne($expense);

    $app['session']->getFlashBag()
        ->add('success', 'Paiement sauvegardé');
    return $app->redirect('/');
});

$app->get('/expenses/{id}/delete', function($id) use($app) {
    $map = $app['db']->getMapFor('\Model\Expense');

    $expense = $map->findByPk(['id' => $id]);
    if ($expense !== null) {
        $map->deleteOne($expense);

        $app['session']->getFlashBag()
            ->add('success', 'Paiement supprimé');
    }
    else {
        $app->abort(404, "Dépense $id inconnue");
    }

    return $app->redirect('/');
});

return $app;
