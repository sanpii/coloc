<?php
declare(strict_types = 1);

namespace App\Controller;

use \PommProject\Foundation\Pomm;
use \PommProject\Foundation\Where;
use \Symfony\Component\DependencyInjection\ContainerAwareInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\HttpKernelInterface;
use \Symfony\Component\Templating\EngineInterface;

class Payments implements ContainerAwareInterface
{
    use \Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    private $pomm;
    private $templating;

    public function __construct(EngineInterface $templating, Pomm $pomm)
    {
        $this->templating = $templating;
        $this->pomm = $pomm;
    }

    public function listPayments(Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $pager = $this->pomm['db']->getModel('\App\Model\PaymentModel')
            ->paginateFindWhere(new Where('1 = 1'), $limit, $page, 'ORDER BY created DESC');

        $pager->getIterator()
            ->registerFilter(function($values) {
                $values['amount'] = $this->getPaymentAmount($values);

                return $values;
            });

        return $this->render(
            'payment/list.html.twig',
            compact('pager')
        );
    }

    public function addPayment(): Response
    {
        return $this->forward(
            'app.controller.payments:editPayment',
            ['id' => -1]
        );
    }

    public function createPayment(Request $request): Response
    {
        return $this->formard(
            'app.controller.payements:save',
            $request->request->all() + ['id' => -1]
        );
    }

    public function editPayment(int $id): Response
    {
        $expenses = $this->pomm['db']->getModel('\App\Model\ExpenseModel')
            ->findWhere('payment_id IS NULL OR payment_id = $*', [$id], 'ORDER BY created');

        $map = $this->pomm['db']->getModel('\App\Model\PaymentModel');
        if ($id > 0) {
            $payment = $map->findByPk(['id' => $id]);
            if (is_null($payment)) {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Remboursement #$id inconnu");
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

        $personMap = $this->pomm['db']->getModel('\App\Model\PersonModel');
        $expenses->registerFilter(function($values) use($personMap) {
            $values['person'] = $personMap->findByPk(['id' => $values['person_id']]);

            return $values;
        });

        return $this->render(
            'payment/edit.html.twig',
            compact('payment', 'expenses')
        );
    }

    public function savePayment(Request $request, int $id): Response
    {
        $map = $this->pomm['db']->getModel('\App\Model\PaymentModel');
        $data = $request->request->get('payment');
        $data['done'] = ($data['done'] === 'on');

        if ($id > 0) {
            $pk = ['id' => $id];
            $payment = $map->findByPk($pk);
            if (is_null($payment)) {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Remboursement #$id inconnu");
            }
            $map->updateByPk($pk, $data);
        }
        else {
            $payment = $map->createAndSave($data);
        }

        $map = $this->pomm['db']->getModel('\App\Model\ExpenseModel');
        foreach ($request->request->get('expenses') as $id => $include) {
            $pk = ['id' => $id];
            $expense = $map->findByPk($pk);
            if ($include === 'on') {
                $expense->payment_id = $payment->id;
            }
            else {
                $expense->payment_id = null;
            }
            $map->updateOne($expense, ['payment_id']);
        }

        $this->addFlash('success', 'Remboursement sauvegardé');
        return $this->redirect('/payments');
    }

    public function deletePayment(int $id): Response
    {
        $map = $this->pomm['db']->getModel('\App\Model\PaymentModel');

        $payment = $map->findByPk(['id' => $id]);
        if ($payment !== null) {
            $map->deleteOne($payment);

            $this->addFlash('success', 'Remboursement supprimé');
        }
        else {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Remboursement #$id inconnu");
        }

        return $this->redirect('/payments');
    }

    private function getPaymentAmount($payment)
    {
        $user = $this->getUser();

        $amount = 0;
        $expenses = $this->pomm['db']->getModel('\App\Model\ExpenseModel')
            ->findWhere('payment_id = $*', [$payment['id']]);

        $trPersonId = (int)$this->pomm['db']->getModel('\App\Model\ConfigModel')
            ->get('tr_person_id');
        $trAmount = (double)$this->pomm['db']->getModel('\App\Model\ConfigModel')
            ->get('tr_amount');
        $trFreeAmount = (double)$this->pomm['db']->getModel('\App\Model\ConfigModel')
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
}
