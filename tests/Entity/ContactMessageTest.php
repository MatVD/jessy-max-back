<?php

namespace App\Tests\Entity;

use App\Entity\ContactMessage;
use PHPUnit\Framework\TestCase;

class ContactMessageTest extends TestCase
{
    public function testConstructorInitializesIdAndDate()
    {
        $msg = new ContactMessage();
        $this->assertNotNull($msg->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $msg->getCreatedAt());
    }

    public function testSettersAndGetters()
    {
        $msg = new ContactMessage();
        $msg->setName('John Doe');
        $msg->setEmail('john@example.com');
        $msg->setMessage('Hello!');
        $this->assertEquals('John Doe', $msg->getName());
        $this->assertEquals('john@example.com', $msg->getEmail());
        $this->assertEquals('Hello!', $msg->getMessage());
    }
}
