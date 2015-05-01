<?php

namespace Controller;

use \Silex\Application;
use \PommProject\Foundation\Where;
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

        $pager = $app['db']->getModel('\Model\PaymentModel')
            ->paginateFindWhere(
                new Where('1 = 1'),
                $limit,
                $page,
                'ORDER BY created DESC'
            );

        $pager->getIterator()->registerFilter(function ($values) use($app) {
            $values['amount'] = $this->getPaymentAmount($app, $values);

            return $values;
        });

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
        $expenses = $app['db']->getModel('\Model\ExpenseModel')
            ->findWhere('payment_id IS NULL OR payment_id = $*', [$id], 'ORDER BY created');

        $map = $app['db']->getModel('\Model\PaymentModel');
        if ($id > 0) {
            $payment = $map->findByPk(['id' => $id]);
            if (is_null($payment)) {
                $app->abort(404, "Remboursement #$id inconnu");
            }
        }
        else {
            $payment = $map->createEntity([
                'id' => $id,
                'done' => false,
                'created' => 'now',
            ]);
        }

        $payment->expenses = $expenses;

        $personMap = $app['db']->getModel('\Model\PersonModel');
        $expenses->registerFilter(function ($expense) use($personMap) {
            $expense->person = $personMap
                ->findByPk(['id' => $expense['person_id']]);

            return $expense;
        });

        return $app['twig']->render(
            'payment/edit.html.twig',
            compact('payment', 'expenses')
        );
    }

    public function savePayment(Application $app, Request $request, $id)
    {
        $map = $app['db']->getModel('\Model\PaymentModel');
        $data = $request->request->get('payment');
        $data['done'] = ($data['done'] === 'on');

        if ($id > 0) {
            $pk = compact('id');
            $payment = $map->findByPk($pk);
            if (is_null($payment)) {
                $app->abort(404, "Remboursement #$id inconnu");
            }
            $map->updateByPk($pk, $data);
        }
        else {
            $payment = $map->createAndSave($data);
        }

        $map = $app['db']->getModel('\Model\ExpenseModel');
        foreach ($request->request->get('expenses') as $id => $include) {
            $pk = compact('id');

            $expense = $map->findByPk($pk);
            if ($include === 'on') {
                $payment_id = $payment->id;
            }
            else {
                $payment_id = null;
            }
            $map->updateByPk($pk, compact('payment_id'));
        }

        $app['session']->getFlashBag()
            ->add('success', 'Remboursement sauvegardé');
        return $app->redirect('/payments');
    }

    public function deletePayment(Application $app, $id)
    {
        $map = $app['db']->getModel('\Model\PaymentModel');

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
        $map = $app['db']->getModel('\Model\ExpenseModel');

        $sql = sprintf(
            'UPDATE %s SET payment_id = null WHERE payment_id = %d',
            $map->getStructure()->getRelation(), $payment->id
        );

        $app['db']->getClientUsingPooler('prepared_query', $sql)
            ->execute();
    }

    private function getPaymentAmount(Application $app, $payment)
    {
        $user = $this->getCurrentUser($app);

        $amount = 0;
        $expenses = $app['db']->getModel('\Model\ExpenseModel')
            ->findWhere('payment_id = $*', [$payment['id']]);

        $trPersonId = (int)$app['db']->getModel('\Model\ConfigModel')
            ->get('tr_person_id');
        $trAmount = (double)$app['db']->getModel('\Model\ConfigModel')
            ->get('tr_amount');
        $trFreeAmount = (double)$app['db']->getModel('\Model\ConfigModel')
            ->get('tr_free_amount');

        foreach ($expenses as $expense) {
            $tr = $expense->getTr();

            $price = $expense->getPrice() - $tr * $trAmount;
            if ($expense->getPersonId() !== $user->getId()) {
                $price *= -1;
            }

            $trPrice = $tr * ($trAmount - $trFreeAmount);
            if ($trPersonId !== $user->getId()) {
                $trPrice *= -1;
            }

            $amount += $price + $trPrice;
        }

        return $amount / 2;
    }

    private function getCurrentUser(Application $app)
    {
        $token = $app['security']->getToken();
        $user = $token->getUser();
        return $app['db']->getModel('\Model\PersonModel')
            ->findWhere('email = $*', [$user->getUsername()])
            ->get(0);
    }
}
