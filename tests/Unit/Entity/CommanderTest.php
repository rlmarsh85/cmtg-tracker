<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ColorIdentity;
use App\Entity\Commander;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class CommanderTest extends TestCase
{
    private Commander $commander;

    protected function setUp(): void
    {
        $this->commander = new Commander();
    }

    public function testConstructorInitializesDecksCollections(): void
    {
        $this->assertInstanceOf(Collection::class, $this->commander->getDecksAsCommander());
        $this->assertInstanceOf(Collection::class, $this->commander->getDecksAsPartner());
        $this->assertCount(0, $this->commander->getDecksAsCommander());
        $this->assertCount(0, $this->commander->getDecksAsPartner());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $this->assertNull($this->commander->getId());
    }

    public function testSetAndGetName(): void
    {
        $this->commander->setName('Atraxa, Praetors\' Voice');
        $this->assertSame('Atraxa, Praetors\' Voice', $this->commander->getName());
    }

    public function testSetNameReturnsSelf(): void
    {
        $result = $this->commander->setName('Breya, Etherium Shaper');
        $this->assertSame($this->commander, $result);
    }

    public function testSetAndGetColorIdentityWithObject(): void
    {
        $ci = $this->createStub(ColorIdentity::class);
        $this->commander->setColorIdentity($ci);
        $this->assertSame($ci, $this->commander->getColorIdentity());
    }

    public function testSetAndGetColorIdentityWithNull(): void
    {
        $this->commander->setColorIdentity(null);
        $this->assertNull($this->commander->getColorIdentity());
    }

    public function testGetColorIdentityReturnsNullByDefault(): void
    {
        $this->assertNull($this->commander->getColorIdentity());
    }

    public function testSetAndGetPartnerType(): void
    {
        $this->commander->setPartnerType('partner');
        $this->assertSame('partner', $this->commander->getPartnerType());
    }

    public function testSetPartnerTypeToNull(): void
    {
        $this->commander->setPartnerType(null);
        $this->assertNull($this->commander->getPartnerType());
    }

    public function testSetAndGetPartnerWith(): void
    {
        $this->commander->setPartnerWith('Syr Gwyn, Hero of Ashvale');
        $this->assertSame('Syr Gwyn, Hero of Ashvale', $this->commander->getPartnerWith());
    }

    public function testSetPartnerWithToNull(): void
    {
        $this->commander->setPartnerWith(null);
        $this->assertNull($this->commander->getPartnerWith());
    }

    public function testSetAndGetImageUri(): void
    {
        $uri = 'https://cards.scryfall.io/normal/atraxa.jpg';
        $this->commander->setImageUri($uri);
        $this->assertSame($uri, $this->commander->getImageUri());
    }

    public function testSetImageUriToNull(): void
    {
        $this->commander->setImageUri(null);
        $this->assertNull($this->commander->getImageUri());
    }

    public function testGetDecksAsCommanderAndPartnerAreCollections(): void
    {
        $this->assertInstanceOf(Collection::class, $this->commander->getDecksAsCommander());
        $this->assertInstanceOf(Collection::class, $this->commander->getDecksAsPartner());
    }

    public function testToStringReturnsName(): void
    {
        $this->commander->setName('The Ur-Dragon');
        $this->assertSame('The Ur-Dragon', (string) $this->commander);
    }
}
