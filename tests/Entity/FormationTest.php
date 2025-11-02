<?php

namespace App\Tests\Entity;

use App\Entity\Formation;
use App\Entity\Category;
use App\Entity\Ticket;
use App\Enum\PaymentStatus;
use PHPUnit\Framework\TestCase;

class FormationTest extends TestCase
{
    public function testConstructorInitializesCollectionsAndDates()
    {
        $formation = new Formation();
        $this->assertNotNull($formation->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $formation->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $formation->getUpdatedAt());
        $this->assertCount(0, $formation->getTickets());
        $this->assertCount(0, $formation->getCategories());
    }

    public function testSettersAndGetters()
    {
        $formation = new Formation();
        $formation->setTitle('PHP Formation');
        $formation->setDescription('Learn PHP');
        $formation->setImageUrl('https://img.com/img.jpg');
        $date = new \DateTimeImmutable('2025-10-31');
        $formation->setStartDate($date);
        $formation->setDuration('2 days');
        $formation->setPrice('100.00');
        $formation->setMaxParticipants(10);
        $formation->setInstructor('Jane Doe');
        $this->assertEquals('PHP Formation', $formation->getTitle());
        $this->assertEquals('Learn PHP', $formation->getDescription());
        $this->assertEquals('https://img.com/img.jpg', $formation->getImageUrl());
        $this->assertEquals($date, $formation->getStartDate());
        $this->assertEquals('2 days', $formation->getDuration());
        $this->assertEquals('100.00', $formation->getPrice());
        $this->assertEquals(10, $formation->getMaxParticipants());
        $this->assertEquals('Jane Doe', $formation->getInstructor());
    }

    public function testAddAndRemoveCategory()
    {
        $formation = new Formation();
        $cat = new Category();
        $formation->addCategory($cat);
        $this->assertTrue($formation->getCategories()->contains($cat));
        $formation->removeCategory($cat);
        $this->assertFalse($formation->getCategories()->contains($cat));
    }

    public function testGetAvailableTickets()
    {
        $formation = new Formation();
        $formation->setMaxParticipants(5);
        $ticket1 = $this->createMock(Ticket::class);
        $ticket1->method('getPaymentStatus')->willReturn(PaymentStatus::PAID);
        $ticket2 = $this->createMock(Ticket::class);
        $ticket2->method('getPaymentStatus')->willReturn(PaymentStatus::PENDING);
        $formation->getTickets()->add($ticket1);
        $formation->getTickets()->add($ticket2);
        $this->assertEquals(4, $formation->getAvailableTickets());
    }
}
