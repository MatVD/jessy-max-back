<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'name' => 'Jessy Max API',
            'version' => '1.0.0',
            'description' => 'API pour la plateforme de billetterie Jessy Max - Concerts et Formations musicales',
            'endpoints' => [
                'api' => '/api',
                'docs' => '/api/docs',
                'login' => '/api/login_check'
            ],
            'status' => 'online',
            'timestamp' => (new \DateTime())->format('c')
        ]);
    }
}
