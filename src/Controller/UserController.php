<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/user/me', name: 'app_user_me')]
    public function __invoke(): Response
    {
        $user = $this->getUser();

        return $this->json([
            'user' => $user,
        ]);
    }
}