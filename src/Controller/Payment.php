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
            $map->getTableName(), $payment->getId()
        );
        $map->query($sql);
    }
}
