<?php

namespace Controller;

use \Silex\Application;
use \PommProject\Foundation\Where;
use \Silex\ControllerProviderInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpKernel\HttpKernelInterface;

class Expenses implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', [$this, 'getExpenses']);
        $controllers->get('/add', [$this, 'addExpense']);
        $controllers->post('/add', [$this, 'createExpense']);
        $controllers->get('/{id}/edit', [$this, 'editExpense']);
        $controllers->post('/{id}/edit', [$this, 'saveExpense']);
        $controllers->get('/{id}/delete', [$this, 'deleteExpense']);

        return $controllers;
    }

    public function getExpenses(Application $app, Request $request)
    {
        $page = $request->get('page', 1);
        $done = $request->get('done', true);
        $limit = $request->get('limit', 20);

        if ($done === true) {
            $where = '1 = 1';
        }
        else {
            $where = 'payment_id IS NULL';
        }

        $pager = $app['db']->getModel('\Model\ExpenseModel')
            ->paginateFindWhere(
                new Where($where),
                $limit,
                $page,
                'ORDER BY created DESC'
            );

        $personMap = $app['db']->getModel('\Model\PersonModel');
        $pager->getIterator()->registerFilter(function ($values) use($personMap) {
            $values['person'] = $personMap
                ->findByPk(['id' => $values['person_id']])
                ->extract();

            return $values;
        });

        return $app['twig']->render(
            'expense/list.html.twig',
            compact('pager')
        );
    }

    public function addExpense(Application $app)
    {
        return $app->handle(
            Request::create('/expenses/-1/edit', 'GET'),
            HttpKernelInterface::SUB_REQUEST
        );
    }

    public function createExpense(Application $app, Request $request)
    {
        return $app->handle(
            Request::create('/expenses/-1/edit', 'POST', $request->request->all()),
            HttpKernelInterface::SUB_REQUEST
        );
    }

    public function editExpense(Application $app, $id)
    {
        $map = $app['db']->getModel('\Model\ExpenseModel');

        if ($id > 0) {
            $expense = $map->findByPk(['id' => $id]);
            if (is_null($expense)) {
                $app->abort(404, "Dépense #$id inconnue");
            }
        }
        else {
            $expense = $map->createEntity([
                'id' => $id,
                'price' => 0,
                'shop' => '',
                'person_id' => -1,
                'description' => '',
                'created' => 'now',
                'tr' => 0,
            ]);
        }

        $persons = $app['db']->getModel('\Model\PersonModel')
            ->findAll();

        return $app['twig']->render(
            'expense/edit.html.twig',
            compact('expense', 'persons')
        );
    }

    public function saveExpense(Application $app, Request $request, $id)
    {
        $map = $app['db']->getModel('\Model\ExpenseModel');
        $data = $request->request->get('expense');

        if ($id > 0) {
            $pk = compact('id');
            $expense = $map->findByPk($pk);
            if (is_null($expense)) {
                $app->abort(404, "Dépense #$id inconnue");
            }
            $map->updateByPk($pk, $data);
        }
        else {
            $expense = $map->createAndSave($data);
        }


        $app['session']->getFlashBag()
            ->add('success', 'Dépense sauvegardée');
        return $app->redirect('/');
    }

    public function deleteExpense(Application $app, $id)
    {
        $map = $app['db']->getModel('\Model\ExpenseModel');

        $expense = $map->findByPk(['id' => $id]);
        if ($expense !== null) {
            $map->deleteOne($expense);

            $app['session']->getFlashBag()
                ->add('success', 'Dépenese supprimée');
        }
        else {
            $app->abort(404, "Dépense $id inconnue");
        }

        return $app->redirect('/');
    }
}

