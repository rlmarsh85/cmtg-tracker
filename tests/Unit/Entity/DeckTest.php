<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ColorIdentity;
use App\Entity\Commander;
use App\Entity\Deck;
use App\Entity\Player;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class DeckTest extends TestCase
{
    private Deck $deck;

    protected function setUp(): void
    {
        $this->deck = new Deck();
    }

    public function testConstructorSetsCreatedAtToNow(): void
    {
        $now = new \DateTimeImmutable();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->deck->getCreatedAt());
        $this->assertLessThanOrEqual(2, abs($now->getTimestamp() - $this->deck->getCreatedAt()->getTimestamp()));
    }

    public function testConstructorInitializesGamePlayersCollection(): void
    {
        $this->assertInstanceOf(Collection::class, $this->deck->getGamePlayers());
        $this->assertCount(0, $this->deck->getGamePlayers());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $this->assertNull($this->deck->getId());
    }

    public function testSetAndGetName(): void
    {
        $this->deck->setName('My Dragon Deck');
        $this->assertSame('My Dragon Deck', $this->deck->getName());
    }

    public function testSetNameReturnsSelf(): void
    {
        $result = $this->deck->setName('Elf Tribal');
        $this->assertSame($this->deck, $result);
    }

    public function testGetNameDefaultIsEmpty(): void
    {
        $this->assertSame('', $this->deck->getName());
    }

    public function testDefaultFormatIsCommander(): void
    {
        $this->assertSame('Commander', $this->deck->getFormat());
    }

    public function testSetAndGetFormat(): void
    {
        $this->deck->setFormat('Modern');
        $this->assertSame('Modern', $this->deck->getFormat());
    }

    public function testSetFormatReturnsSelf(): void
    {
        $result = $this->deck->setFormat('Legacy');
        $this->assertSame($this->deck, $result);
    }

    public function testFormatsConstantContainsTenValues(): void
    {
        $this->assertCount(10, Deck::FORMATS);
    }

    public function testFormatsConstantContainsExpectedValues(): void
    {
        $this->assertContains('Commander', Deck::FORMATS);
        $this->assertContains('Standard', Deck::FORMATS);
        $this->assertContains('Modern', Deck::FORMATS);
        $this->assertContains('Pioneer', Deck::FORMATS);
        $this->assertContains('Legacy', Deck::FORMATS);
        $this->assertContains('Vintage', Deck::FORMATS);
        $this->assertContains('Draft', Deck::FORMATS);
        $this->assertContains('Sealed', Deck::FORMATS);
        $this->assertContains('Pauper', Deck::FORMATS);
        $this->assertContains('Other', Deck::FORMATS);
    }

    public function testSetAndGetCommander(): void
    {
        $commander = $this->createStub(Commander::class);
        $this->deck->setCommander($commander);
        $this->assertSame($commander, $this->deck->getCommander());
    }

    public function testSetCommanderToNull(): void
    {
        $this->deck->setCommander(null);
        $this->assertNull($this->deck->getCommander());
    }

    public function testSetAndGetPartner(): void
    {
        $partner = $this->createStub(Commander::class);
        $this->deck->setPartner($partner);
        $this->assertSame($partner, $this->deck->getPartner());
    }

    public function testSetPartnerToNull(): void
    {
        $this->deck->setPartner(null);
        $this->assertNull($this->deck->getPartner());
    }

    public function testSetAndGetNotes(): void
    {
        $this->deck->setNotes('Aggro build');
        $this->assertSame('Aggro build', $this->deck->getNotes());
    }

    public function testSetNotesToNull(): void
    {
        $this->deck->setNotes(null);
        $this->assertNull($this->deck->getNotes());
    }

    public function testSetAndGetPlayer(): void
    {
        $player = $this->createStub(Player::class);
        $this->deck->setPlayer($player);
        $this->assertSame($player, $this->deck->getPlayer());
    }

    public function testSetAndGetColorIdentity(): void
    {
        $ci = $this->createStub(ColorIdentity::class);
        $this->deck->setColorIdentity($ci);
        $this->assertSame($ci, $this->deck->getColorIdentity());
    }

    public function testSetColorIdentityToNull(): void
    {
        $this->deck->setColorIdentity(null);
        $this->assertNull($this->deck->getColorIdentity());
    }

    public function testGetCreatedAtIsImmutable(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->deck->getCreatedAt());
    }

    public function testToStringReturnsNameWhenSet(): void
    {
        $this->deck->setName('Sliver Queen Combo');
        $this->assertSame('Sliver Queen Combo', (string) $this->deck);
    }

    public function testToStringReturnsEmptyStringByDefault(): void
    {
        $this->assertSame('', (string) $this->deck);
    }
}
