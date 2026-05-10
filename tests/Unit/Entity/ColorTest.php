<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Color;
use PHPUnit\Framework\TestCase;

class ColorTest extends TestCase
{
    private Color $color;

    protected function setUp(): void
    {
        $this->color = new Color();
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $this->assertNull($this->color->getId());
    }

    public function testSetAndGetName(): void
    {
        $this->color->setName('White');
        $this->assertSame('White', $this->color->getName());
    }

    public function testSetNameReturnsSelf(): void
    {
        $result = $this->color->setName('Blue');
        $this->assertSame($this->color, $result);
    }

    public function testToStringReturnsName(): void
    {
        $this->color->setName('Red');
        $this->assertSame('Red', (string) $this->color);
    }
}
