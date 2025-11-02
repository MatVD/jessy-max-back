<?php

namespace App\Tests\E2E;

class JwtAuthTest extends AbstractE2ETest
{
    public function testJwtAuthenticationFlow(): void
    {
        // Le token est déjà créé dans setUp() de AbstractE2ETest
        $this->assertNotEmpty($this->jwtToken, 'JWT token should be returned');

        // Accès à une ressource sécurisée (tickets)
        $this->client->request(
            'GET',
            '/api/tickets',
            [],
            [],
            ['HTTP_Authorization' => sprintf('Bearer %s', $this->jwtToken)]
        );
        
        $this->assertResponseIsSuccessful();
        $tickets = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($tickets);
    }
}