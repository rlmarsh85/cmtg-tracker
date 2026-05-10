<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ColorIdentity;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class ColorIdentityTest extends TestCase
{
    private ColorIdentity $colorIdentity;

    protected function setUp(): void
    {
        $this->colorIdentity = new ColorIdentity();
    }

    public function testConstructorInitializesColorsAsEmptyCollection(): void
    {
        $this->assertInstanceOf(Collection::class, $this->colorIdentity->getColors());
        $this->assertCount(0, $this->colorIdentity->getColors());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $this->assertNull($this->colorIdentity->getId());
    }

    public function testSetAndGetName(): void
    {
        $this->colorIdentity->setName('Izzet');
        $this->assertSame('Izzet', $this->colorIdentity->getName());
    }

    public function testSetNameReturnsSelf(): void
    {
        $result = $this->colorIdentity->setName('Temur');
        $this->assertSame($this->colorIdentity, $result);
    }

    public function testGetColorsReturnsCollection(): void
    {
        $this->assertInstanceOf(Collection::class, $this->colorIdentity->getColors());
    }

    public function testToStringReturnsName(): void
    {
        $this->colorIdentity->setName('Dimir');
        $this->assertSame('Dimir', (string) $this->colorIdentity);
    }
}
