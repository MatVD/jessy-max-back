<?php

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class RefundRequestTest extends AbstractE2ETest
{
    public function testRefundRequestFlow()
    {

        // Simuler une authentification utilisateur
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

        // Créer une demande de remboursement pour le ticket 1
        $this->client->request(
            'POST',
            '/api/refund_requests',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => sprintf('Bearer %s', $token)
            ],
            json_encode([
                'ticket' => '/api/tickets/' . $this->testTicket->getId(),
                'customerName' => 'Bob Durand',
                'customerEmail' => 'bob.durand@example.com',
                'reason' => 'Problème de santé',
                'refundAmount' => '100.00'
            ])
        );
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(201);
        $refundData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $refundData);
        $this->assertEquals('Bob Durand', $refundData['customerName']);
        $this->assertEquals('bob.durand@example.com', $refundData['customerEmail']);
        $this->assertEquals('Problème de santé', $refundData['reason']);
        // Compare as float since API Platform may normalize decimal values
        $this->assertEquals(100.00, (float)$refundData['refundAmount']);
    }
}
