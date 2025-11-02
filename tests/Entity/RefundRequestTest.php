<?php

namespace App\Tests\Entity;

use App\Entity\RefundRequest;
use App\Enum\RefundStatus;
use App\Entity\Ticket;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class RefundRequestTest extends TestCase
{
    public function testConstructorInitializesIdDateStatus()
    {
        $refund = new RefundRequest();
        $this->assertNotNull($refund->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $refund->getCreatedAt());
        $this->assertEquals(RefundStatus::PENDING, $refund->getStatus());
    }

    public function testSettersAndGetters()
    {
        $refund = new RefundRequest();
        $ticket = new Ticket();
        $user = new User();
        $refund->setTicket($ticket);
        $refund->setUser($user);
        $refund->setCustomerName('John Doe');
        $refund->setCustomerEmail('john@example.com');
        $refund->setReason('Demande de remboursement');
        $refund->setStatus(RefundStatus::PROCESSED);
        $refund->setRefundAmount('20.00');
        $refund->setStripeRefundId('stripe_123');
        $date = new \DateTimeImmutable('2025-10-31');
        $refund->setProcessedAt($date);
        $this->assertEquals($ticket, $refund->getTicket());
        $this->assertEquals($user, $refund->getUser());
        $this->assertEquals('John Doe', $refund->getCustomerName());
        $this->assertEquals('john@example.com', $refund->getCustomerEmail());
        $this->assertEquals('Demande de remboursement', $refund->getReason());
        $this->assertEquals(RefundStatus::PROCESSED, $refund->getStatus());
        $this->assertEquals('20.00', $refund->getRefundAmount());
        $this->assertEquals('stripe_123', $refund->getStripeRefundId());
        $this->assertEquals($date, $refund->getProcessedAt());
    }

    public function testMarkAsProcessedSetsDateAndStatus()
    {
        $refund = new RefundRequest();
        $refund->markAsProcessed();
        $this->assertInstanceOf(\DateTimeImmutable::class, $refund->getProcessedAt());
        $this->assertEquals(RefundStatus::PROCESSED, $refund->getStatus());
    }
}
