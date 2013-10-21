<?php

namespace Controller;

use \Silex\Application;
use \Silex\ControllerProviderInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpKernel\HttpKernelInterface;

class Payment implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', [$this, 'getPayments']);
        $controllers->get('/add', [$this, 'addPayment']);
        $controllers->post('/add', [$this, 'createPayment']);
        $controllers->get('/{id}/edit', [$this, 'editPayment']);
        $controllers->post('/{id}/edit', [$this, 'savePayment']);
        $controllers->get('/{id}/delete', [$this, 'deletePayment']);

        return $controllers;
    }

    public function getPayments(Application $app, Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $pager = $app['db']->getMapFor('\Model\Payment')
            ->paginateFindWhere('1 = 1', compact('done'), 'ORDER BY created DESC', $limit, $page);

        return $app['twig']->render(
            'payment/list.html.twig',
            compact('pager')
        );
    }

    public function addPayment(Application $app)
    {
        return $app->handle(
            Request::create('/payments/-1/edit', 'GET'),
            HttpKernelInterface::SUB_REQUEST
        );
    }

    public function createPayment(Application $app, Request $request)
    {
        return $app->handle(
            Request::create('/payments/-1/edit', 'POST', $request->request->all()),
            HttpKernelInterface::SUB_REQUEST
        );
    }

    public function editPayment(Application $app, $id)
    {
        $expenses = $app['db']->getMapFor('\Model\Expense')
            ->findWhere('payment_id IS NULL OR payment_id = ?', [$id]);

        $map = $app['db']->getMapFor('\Model\Payment');
        if ($id > 0) {
            $payment = $map->findByPk(['id' => $id]);
            if (is_null($payment)) {
                $app->abort(404, "Remboursement #$id inconnu");
            }
        }
        else {
            $payment = $map->createObject([
                'id' => $id,
                'done' => false,
                'created' => 'now',
            ]);
        }

        $payment->expenses = $expenses;

        $personMap = $app['db']->getMapFor('\Model\Person');
        foreach ($expenses as $expense) {
            $expense->person = $personMap->findByPk(['id' => $expense->person_id]);
        }

        return $app['twig']->render(
            'payment/edit.html.twig',
            compact('payment', 'expenses')
        );
    }

    public function savePayment(Application $app, Request $request, $id)
    {
        $map = $app['db']->getMapFor('\Model\Payment');

        if ($id > 0) {
            $payment = $map->findByPk(['id' => $id]);
            if (is_null($payment)) {
                $app->abort(404, "Remboursement #$id inconnu");
            }
        }
        else {
            $payment = $map->createObject();
        }

        $payment->hydrate($request->request->get('payment'));
        $map->saveOne($payment);

        $map = $app['db']->getMapFor('\Model\Expense');
        foreach ($request->request->get('expenses') as $id => $include) {
            $expense = $map->findByPk(['id' => $id]);
            if ($include === 'on') {
                $expense->payment_id = $payment->id;
            }
            else {
                $expense->payment_id = null;
            }
            $map->saveOne($expense);
        }

        $app['session']->getFlashBag()
            ->add('success', 'Remboursement sauvegardé');
        return $app->redirect('/payments');
    }

    public function deletePayment(Application $app, $id)
    {
        $map = $app['db']->getMapFor('\Model\Payment');

        $payment = $map->findByPk(['id' => $id]);
        if ($payment !== null) {
            $this->unsetExpensePayement($app, $payment);

            $map->deleteOne($payment);

            $app['session']->getFlashBag()
                ->add('success', 'Remboursement supprimé');
        }
        else {
            $app->abort(404, "Remboursement $id inconnue");
        }

        return $app->redirect('/payments');
    }

    private function unsetExpensePayement(Application $app, $payment)
    {
        $map = $app['db']->getMapFor('\Model\Expense');

        $sql = sprintf(
            'UPDATE %s SET payment_id = null WHERE payment_id = %d',
            $map->getTableName(), $payment->id
        );
        $map->query($sql);
    }
}
