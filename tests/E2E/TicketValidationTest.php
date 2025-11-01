<?php

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class TicketValidationTest extends AbstractE2ETest
{
    public function testTicketValidationFlow()
    {

        // Simuler la validation d'un ticket via QR code
        $qrCode = $this->validQrCode; // Use the actual JWT token from AbstractE2ETest
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
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);

        // Refresh the ticket from database to check it was marked as used
        $ticketRepo = $this->em->getRepository(\App\Entity\Ticket::class);
        $updatedTicket = $ticketRepo->find($this->testTicket->getId());
        $this->assertNotNull($updatedTicket);
        $this->assertTrue($updatedTicket->isUsed());
    }
}
