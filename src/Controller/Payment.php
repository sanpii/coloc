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
        $expenses = $app['db']->getMapFor('\Model\Expense')
            ->findWhere('payment_id IS NULL');

        $personMap = $app['db']->getMapFor('\Model\Person');
        foreach ($expenses as $expense) {
            $expense->person = $personMap->findByPk(['id' => $expense->person_id]);
        }

        return $app['twig']->render(
            'payment/add.html.twig',
            compact('expenses')
        );
    }

    public function createPayment(Application $app, Request $request)
    {
        $map = $app['db']->getMapFor('\Model\Payment');

        $payment = $map->createObject([
            'done' => false,
            'created' => 'now',
        ]);
        $map->saveOne($payment);

        $map = $app['db']->getMapFor('\Model\Expense');
        foreach ($request->request->get('expenses') as $id => $include) {
            if ($include === 'on') {
                $expense = $map->findByPk(['id' => $id]);
                $expense->payment_id = $payment->id;
                $map->saveOne($expense);
            }
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
