<?php

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class StripeWebhookTest extends AbstractE2ETest
{
    public function testStripeWebhookFlow()
    {

        // Simuler le payload Stripe (checkout.session.completed)
        $payload = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'payment_status' => 'paid',
                    'customer_email' => 'bob.durand@example.com',
                    'amount_total' => 10000,
                    'payment_intent' => 'pi_test_123',
                    'metadata' => [
                        'ticket_id' => $this->testTicket->getId()->toRfc4122()
                    ]
                ]
            ]
        ];

        $this->client->request(
            'POST',
            '/stripe/webhook',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('success', $data['status']);

        // Optionnel: vérifier la mise à jour du ticket en base (si accessible via API)
        // $client->request('GET', '/api/tickets/1');
        // $ticket = json_decode($client->getResponse()->getContent(), true);
        // $this->assertEquals('paid', $ticket['paymentStatus']);
    }
}
