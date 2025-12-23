<?php

namespace App\Tests\Entity;

use App\Entity\Donation;
use App\Enum\PaymentStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class DonationTest extends TestCase
{
    public function testDefaultsAreSetOnConstruction(): void
    {
        $donation = new Donation();

        $this->assertInstanceOf(Uuid::class, $donation->getId());
        $this->assertSame(PaymentStatus::PENDING, $donation->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $donation->getCreatedAt());
        $this->assertNull($donation->getDonorName());
        $this->assertNull($donation->getDonorEmail());
        $this->assertNull($donation->getAmount());
        $this->assertNull($donation->getMessage());
        $this->assertNull($donation->getStripeSessionId());
        $this->assertNull($donation->getStripeCheckoutUrl());
    }

    public function testSettersAndGetters(): void
    {
        $donation = new Donation();
        $createdAt = new \DateTimeImmutable('-1 day');

        $donation
            ->setDonorName('John Doe')
            ->setDonorEmail('john@example.com')
            ->setAmount('25.50')
            ->setMessage('Keep up the good work!')
            ->setStripeSessionId('sess_123')
            ->setStripeCheckoutUrl('https://checkout.stripe.com/pay/sess_123')
            ->setStatus(PaymentStatus::PAID)
            ->setCreatedAt($createdAt);

        $this->assertSame('John Doe', $donation->getDonorName());
        $this->assertSame('john@example.com', $donation->getDonorEmail());
        $this->assertSame('25.50', $donation->getAmount());
        $this->assertSame('Keep up the good work!', $donation->getMessage());
        $this->assertSame('sess_123', $donation->getStripeSessionId());
        $this->assertSame('https://checkout.stripe.com/pay/sess_123', $donation->getStripeCheckoutUrl());
        $this->assertSame(PaymentStatus::PAID, $donation->getStatus());
        $this->assertSame($createdAt, $donation->getCreatedAt());
    }

    public function testIdRemainsStable(): void
    {
        $donation = new Donation();

        $firstId = $donation->getId();
        $secondId = $donation->getId();

        $this->assertSame($firstId->toRfc4122(), $secondId->toRfc4122());
    }
}