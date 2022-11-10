<?php

namespace App\Controller;

use App\Entity\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class GoogleController extends AbstractController
{


    private $JWTManager;
    private $session;

    public function __construct(JWTTokenManagerInterface $JWTManager)
    {
        // 3. Update the value of the private entityManager variable through injection

        $this->JWTManager = $JWTManager;
        $this->session = new Session();
    }

    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/connect/google", name="connect_google")
     */
    public function connectAction(ClientRegistry $clientRegistry)
    {
        // on Symfony 3.3 or lower, $clientRegistry = $this->get('knpu.oauth2.registry');

        // will redirect to Facebook!

        $this->session->set('idE',  $_GET["idE"]);

        return $clientRegistry
            ->getClient('google') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect();
    }

    /**
     * Facebook redirects to back here afterwards
     *
     * @Route("/connect/google/check", name="connect_google_check")
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function connectCheckAction(Request $request)
    {
        if (!$this->getUser()) {
            return new RedirectResponse(
                'http://localhost:3000/login/' . $this->session->get('idE'), // might be the site, where users choose their oauth provider
                Response::HTTP_TEMPORARY_REDIRECT
            );
        } else {
            return new RedirectResponse(
                'http://localhost:3000/' . $this->session->get('idE') . '/cart' . '?t=' . $this->JWTManager->create($this->getUser()), // might be the site, where users choose their oauth provider
                Response::HTTP_TEMPORARY_REDIRECT
            );

            //return new JsonResponse(['token' => $this->JWTManager->create($this->getUser())]);
        }
    }
}
