<?php

namespace App\Tests\Entity;

use App\Entity\Location;
use PHPUnit\Framework\TestCase;

class LocationTest extends TestCase
{
    public function testConstructorInitializesCollectionsAndId()
    {
        $location = new Location();
        $this->assertNotNull($location->getId());
        $this->assertCount(0, $location->getEvents());
        $this->assertCount(0, $location->getFormations());
    }

    public function testSettersAndGetters()
    {
        $location = new Location();
        $location->setName('Salle A');
        $location->setAddress('123 rue de Paris');
        $location->setLatitude('48.8566');
        $location->setLongitude('2.3522');
        $this->assertEquals('Salle A', $location->getName());
        $this->assertEquals('123 rue de Paris', $location->getAddress());
        $this->assertEquals('48.8566', $location->getLatitude());
        $this->assertEquals('2.3522', $location->getLongitude());
    }
}
