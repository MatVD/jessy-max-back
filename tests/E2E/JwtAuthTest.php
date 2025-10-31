<?php

namespace App\Tests\E2E;


use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class JwtAuthTest extends AbstractE2ETest
{
    public function testJwtAuthenticationFlow()
    {

        // Authentification JWT
        $this->client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'bob.durand@example.com',
                'password' => 'bobpass'
            ])
        );
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $token = $data['token'] ?? null;
        $this->assertNotEmpty($token, 'JWT token should be returned');

        // Accès à une ressource sécurisée (tickets)
        $this->client->request(
            'GET',
            '/api/tickets',
            [],
            [],
            [
                'HTTP_Authorization' => sprintf('Bearer %s', $token)
            ]
        );
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $tickets = json_decode($response->getContent(), true);
        $this->assertIsArray($tickets);
    }
}
