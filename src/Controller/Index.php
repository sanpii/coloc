<?php

namespace Controller;

use \Silex\Application;
use \Silex\Api\ControllerProviderInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpKernel\HttpKernelInterface;

class Index implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/login', [$this, 'login']);
        $controllers->get('/', [$this, 'index']);

        return $controllers;
    }

    public function login(Application $app, Request $request)
    {
        return $app['twig']->render('login.html.twig', [
            'error' => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
        ]);
    }


    public function index(Application $app, Request $request)
    {
        $url = '/expenses?done=false&' . http_build_query($request->query->all());

        return $app->handle(
            Request::create($url, 'GET'),
            HttpKernelInterface::SUB_REQUEST
        );
    }
}
