<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Ticket;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testConstructorInitializesCollectionsAndId()
    {
        $user = new User();
        $this->assertNotNull($user->getId());
        $this->assertCount(0, $user->getTickets());
    }

    public function testSettersAndGetters()
    {
        $user = new User();
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setEmail('john@example.com');
        $user->setPassword('secret');
        $user->setRoles(['ROLE_ADMIN']);
        $this->assertEquals('John', $user->getFirstname());
        $this->assertEquals('Doe', $user->getLastname());
        $this->assertEquals('john@example.com', $user->getEmail());
        $this->assertEquals('secret', $user->getPassword());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testGetUserIdentifierReturnsEmail()
    {
        $user = new User();
        $user->setEmail('john@example.com');
        $this->assertEquals('john@example.com', $user->getUserIdentifier());
    }

    public function testEraseCredentialsDoesNotThrow()
    {
        $user = new User();
        $user->eraseCredentials();
        $this->assertTrue(true); // Just ensure no exception
    }
}
