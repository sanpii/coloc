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
}
