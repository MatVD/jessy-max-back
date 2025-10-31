<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Enum\CategoryType;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testConstructorInitializesCollectionsAndId()
    {
        $category = new Category();
        $this->assertNotNull($category->getId());
        $this->assertCount(0, $category->getEvents());
        $this->assertCount(0, $category->getFormations());
    }

    public function testSettersAndGetters()
    {
        $category = new Category();
        $category->setName('TestCat');
        $category->setType(CategoryType::EVENT);
        $this->assertEquals('TestCat', $category->getName());
        $this->assertEquals(CategoryType::EVENT, $category->getType());
    }
}
