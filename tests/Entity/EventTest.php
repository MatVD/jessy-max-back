<?php

namespace App\Tests\Entity;

use App\Entity\Event;
use App\Entity\Category;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testConstructorInitializesCollectionsAndDates()
    {
        $event = new Event();
        $this->assertNotNull($event->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getUpdatedAt());
        $this->assertCount(0, $event->getTickets());
        $this->assertCount(0, $event->getCategories());
    }

    public function testSettersAndGetters()
    {
        $event = new Event();
        $event->setTitle('Concert');
        $event->setDescription('Live music');
        $date = new \DateTimeImmutable('2025-10-31');
        $event->setDate($date);
        $event->setImageUrl('https://img.com/img.jpg');
        $event->setPrice('50.00');
        $event->setAvailableTickets(100);
        $event->setTotalTickets(200);
        $this->assertEquals('Concert', $event->getTitle());
        $this->assertEquals('Live music', $event->getDescription());
        $this->assertEquals($date, $event->getDate());
        $this->assertEquals('https://img.com/img.jpg', $event->getImageUrl());
        $this->assertEquals('50.00', $event->getPrice());
        $this->assertEquals(100, $event->getAvailableTickets());
        $this->assertEquals(200, $event->getTotalTickets());
    }

    public function testAddAndRemoveCategory()
    {
        $event = new Event();
        $cat = new Category();
        $event->addCategory($cat);
        $this->assertTrue($event->getCategories()->contains($cat));
        $event->removeCategory($cat);
        $this->assertFalse($event->getCategories()->contains($cat));
    }
}
