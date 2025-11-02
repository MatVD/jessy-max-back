<?php

namespace App\Tests\Entity;

use App\Entity\Ticket;
use App\Enum\PaymentStatus;
use App\Entity\Event;
use App\Entity\Formation;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    public function testConstructorInitializesCollectionsAndStatus()
    {
        $ticket = new Ticket();
        $this->assertNotNull($ticket->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $ticket->getCreatedAt());
        $this->assertEquals(PaymentStatus::PENDING, $ticket->getPaymentStatus());
        $this->assertCount(0, $ticket->getRefundRequests());
    }

    public function testSettersAndGetters()
    {
        $ticket = new Ticket();
        $event = new Event();
        $formation = new Formation();
        $user = new User();
        $ticket->setEvent($event);
        $ticket->setFormation(null);
        $ticket->setUser($user);
        $ticket->setCustomerName('John Doe');
        $ticket->setCustomerEmail('john@example.com');
        $ticket->setPrice('50.00');
        $ticket->setPaymentStatus(PaymentStatus::PAID);
        $ticket->setStripeCheckoutSessionId('sess_123');
        $ticket->setStripePaymentIntentId('pi_123');
        $ticket->setQrCode('qr_123');
        $date = new \DateTimeImmutable('2025-10-31');
        $ticket->setUsedAt($date);
        $ticket->setPurchasedAt($date);
        $this->assertEquals($event, $ticket->getEvent());
        $this->assertNull($ticket->getFormation());
        $this->assertEquals($user, $ticket->getUser());
        $this->assertEquals('John Doe', $ticket->getCustomerName());
        $this->assertEquals('john@example.com', $ticket->getCustomerEmail());
        $this->assertEquals('50.00', $ticket->getPrice());
        $this->assertEquals(PaymentStatus::PAID, $ticket->getPaymentStatus());
        $this->assertEquals('sess_123', $ticket->getStripeCheckoutSessionId());
        $this->assertEquals('pi_123', $ticket->getStripePaymentIntentId());
        $this->assertEquals('qr_123', $ticket->getQrCode());
        $this->assertEquals($date, $ticket->getUsedAt());
        $this->assertEquals($date, $ticket->getPurchasedAt());
    }

    public function testMarkAsUsedSetsUsedAt()
    {
        $ticket = new Ticket();
        $ticket->markAsUsed();
        $this->assertInstanceOf(\DateTimeImmutable::class, $ticket->getUsedAt());
        $this->assertTrue($ticket->isUsed());
    }
}
