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

class Expenses implements ContainerAwareInterface
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

    public function listExpenses(Request $request): Response
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

        $pager = $this->pomm['db']->getModel('\App\Model\ExpenseModel')
            ->paginateFindWhere(new Where($where), $limit, $page, 'ORDER BY created DESC');

        $personMap = $this->pomm['db']->getModel('\App\Model\PersonModel');
        $pager->getIterator()->registerFilter(function($values) use($personMap) {
            $values['person'] = $personMap
                ->findByPk(['id' => $values['person_id']]);
            return $values;
        });

        return $this->render(
            'expense/list.html.twig',
            compact('pager')
        );
    }

    public function addExpense(): Response
    {
        return $this->forward(
            'app.controller.expenses:editExpense',
            ['id' => -1]
        );
    }

    public function createExpense(Request $request): Response
    {
        return $this->forward(
            'app.controller.expenses:saveExpense',
            ['id' => -1]
        );
    }

    public function editExpense(int $id): Response
    {
        $map = $this->pomm['db']->getModel('\App\Model\ExpenseModel');

        if ($id > 0) {
            $expense = $map->findByPk(['id' => $id]);
            if (is_null($expense)) {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Dépense #$id inconnue");
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

        $persons = $this->pomm['db']->getModel('\App\Model\PersonModel')
            ->findAll();

        return $this->render(
            'expense/edit.html.twig',
            compact('expense', 'persons')
        );
    }

    public function saveExpense(Request $request, int $id): Response
    {
        $map = $this->pomm['db']->getModel('\App\Model\ExpenseModel');
        $data = $request->request->get('expense');

        if ($id > 0) {
            $pk = ['id' => $id];
            $expense = $map->findByPk($pk);
            if (is_null($expense)) {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Dépense #$id inconnue");
            }
            $map->updateByPk($pk, $data);

        }
        else {
            $expense = $map->createAndSave($data);
        }

        $this->addFlash('success', 'Dépense sauvegardée');
        return $this->redirect('/');
    }

    public function deleteExpense(int $id): Response
    {
        $map = $this->pomm['db']->getModel('\App\Model\ExpenseModel');
        $pk = ['id' => $id];

        $expense = $map->findByPk($pk);
        if ($expense !== null) {
            $map->deleteByPk($pk);

            $this->addFlash('success', 'Dépenese supprimée');
        }
        else {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Dépense #$id inconnue");
        }

        return $this->redirect('/');
    }
}
