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

class Index implements ContainerAwareInterface
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

    public function login(Request $request): Response
    {
        $authenticationUtils = $this->container->get('security.authentication_utils');

        return $this->render('login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }

    public function loginCheck(): Response
    {
        throw new \Exception('this should not be reached!');
    }

    public function logout(): Response
    {
        throw new \Exception('this should not be reached!');
    }

    public function index(Request $request): Response
    {
        return $this->forward(
            'app.controller.expenses:listExpenses',
            ['done' => false]
        );
    }
}
