<?php

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class TicketValidationTest extends AbstractE2ETest
{
    public function testTicketValidationFlow()
    {

        // Simuler la validation d'un ticket via QR code
        $qrCode = 'TICKET-QR-1'; // exemple, à adapter selon la génération réelle
        $this->client->request(
            'POST',
            '/api/tickets/validate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['qrCode' => $qrCode])
        );
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('valid', $data);
        $this->assertTrue($data['valid']);

        // Optionnel: vérifier le statut du ticket (utilisé)
        // $client->request('GET', '/api/tickets/1');
        // $ticket = json_decode($client->getResponse()->getContent(), true);
        // $this->assertTrue($ticket['used']);
    }
}
