<?php

namespace App\Tests\E2E;

class JwtAuthTest extends AbstractE2ETest
{
    public function testJwtAuthenticationFlow(): void
    {
        // Le token est déjà créé dans setUp() de AbstractE2ETest
        $this->assertNotEmpty($this->jwtToken, 'JWT token should be returned');

        // Accès à une ressource sécurisée : lecture du ticket de l'utilisateur courant
        $ticketIri = sprintf('/api/tickets/%s', $this->testTicket->getId()->toRfc4122());

        $this->client->request(
            'GET',
            $ticketIri,
            [],
            [],
            ['HTTP_Authorization' => sprintf('Bearer %s', $this->jwtToken)]
        );

        $this->assertResponseIsSuccessful();
        $ticket = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($ticket);
        $this->assertSame($this->testTicket->getId()->toRfc4122(), $ticket['id'] ?? null);
    }
}
