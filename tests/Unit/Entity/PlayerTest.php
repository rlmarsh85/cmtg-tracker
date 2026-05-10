<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Deck;
use App\Entity\Player;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class PlayerTest extends TestCase
{
    private Player $player;

    protected function setUp(): void
    {
        $this->player = new Player();
    }

    public function testConstructorSetsCreatedAtToNow(): void
    {
        $now = new \DateTimeImmutable();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->player->getCreatedAt());
        $this->assertLessThanOrEqual(2, abs($now->getTimestamp() - $this->player->getCreatedAt()->getTimestamp()));
    }

    public function testConstructorInitializesDecksCollection(): void
    {
        $this->assertInstanceOf(Collection::class, $this->player->getDecks());
        $this->assertCount(0, $this->player->getDecks());
    }

    public function testConstructorInitializesGamePlayersCollection(): void
    {
        $this->assertInstanceOf(Collection::class, $this->player->getGamePlayers());
        $this->assertCount(0, $this->player->getGamePlayers());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $this->assertNull($this->player->getId());
    }

    public function testSetAndGetUsername(): void
    {
        $this->player->setUsername('alice');
        $this->assertSame('alice', $this->player->getUsername());
    }

    public function testSetUsernameReturnsSelf(): void
    {
        $result = $this->player->setUsername('bob');
        $this->assertSame($this->player, $result);
    }

    public function testGetUsernameReturnsNullByDefault(): void
    {
        $this->assertNull($this->player->getUsername());
    }

    public function testSetAndGetEmail(): void
    {
        $this->player->setEmail('alice@example.com');
        $this->assertSame('alice@example.com', $this->player->getEmail());
    }

    public function testGetEmailReturnsNullByDefault(): void
    {
        $this->assertNull($this->player->getEmail());
    }

    public function testAddDeckIncreasesCollection(): void
    {
        $deck = new Deck();
        $this->player->addDeck($deck);
        $this->assertCount(1, $this->player->getDecks());
    }

    public function testAddDeckSetsPlayerOnDeck(): void
    {
        $deck = new Deck();
        $this->player->addDeck($deck);
        $this->assertSame($this->player, $deck->getPlayer());
    }

    public function testAddSameDeckTwiceCountsOnce(): void
    {
        $deck = new Deck();
        $this->player->addDeck($deck);
        $this->player->addDeck($deck);
        $this->assertCount(1, $this->player->getDecks());
    }

    public function testRemoveDeckDecreasesCollection(): void
    {
        $deck = new Deck();
        $this->player->addDeck($deck);
        $this->player->removeDeck($deck);
        $this->assertCount(0, $this->player->getDecks());
    }

    public function testRemoveDeckNullsPlayerReference(): void
    {
        $deck = new Deck();
        $this->player->addDeck($deck);
        $this->player->removeDeck($deck);
        $this->assertNull($deck->getPlayer());
    }

    public function testRemoveNonExistentDeckDoesNothing(): void
    {
        $deck = new Deck();
        $this->player->removeDeck($deck);
        $this->assertCount(0, $this->player->getDecks());
    }

    public function testGetGamePlayersIsCollection(): void
    {
        $this->assertInstanceOf(Collection::class, $this->player->getGamePlayers());
    }

    public function testToStringReturnsUsernameWhenSet(): void
    {
        $this->player->setUsername('charlie');
        $this->assertSame('charlie', (string) $this->player);
    }

    public function testToStringReturnsEmptyStringWhenUsernameIsNull(): void
    {
        $this->assertSame('', (string) $this->player);
    }
}
