<?php

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class TicketCreationTest extends AbstractE2ETest
{
    public function testTicketCreationFlow()
    {
        // Skip this test as it requires actual Stripe API calls
        // In a real-world scenario, you would mock the Stripe API or use Stripe test mode with valid credentials
        $this->markTestSkipped('This test requires valid Stripe API credentials and should be run in integration mode.');

        // Simuler une authentification utilisateur (JWT ou session, selon config)
        // Ici, on suppose un endpoint /api/login_check pour obtenir un token
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

        // Créer un ticket (exemple: pour un event existant)
        $this->client->request(
            'POST',
            '/api/tickets',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => sprintf('Bearer %s', $token)
            ],
            json_encode([
                'event' => '/api/events/' . $this->testEvent->getId(),
                'customerName' => 'Bob Durand',
                'customerEmail' => 'bob.durand@example.com',
                'totalPrice' => '100.00'
            ])
        );
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(201);
        $ticketData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $ticketData);
        $this->assertEquals('Bob Durand', $ticketData['customerName']);
        $this->assertEquals('bob.durand@example.com', $ticketData['customerEmail']);
        $this->assertEquals('100.00', $ticketData['totalPrice']);
    }
}
